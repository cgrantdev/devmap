<?php

namespace App\Jobs;

use App\Jobs\Concerns\OnePerScrapingConfig;
use App\Models\Product;
use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use App\Services\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

/**
 * Ingest products from any storefront that emits Product JSON-LD (schema.org)
 * on each product page. Works for Peptiva (Medusa+Next.js), most Shopify
 * themes, most WooCommerce themes, and any modern SEO-conscious storefront.
 *
 * Flow:
 *   1. Find the product URLs. A sitemap lists the whole catalog, so
 *      products_url ending in .xml is read as a sitemap, and a bare store
 *      root reads {root}/sitemap.xml first. Otherwise (or when the root
 *      has no usable sitemap) take every /products/{slug} link (or
 *      configured link pattern) on the listing page.
 *   2. For each unique product URL, GET the page and pull the first
 *      <script type="application/ld+json"> block whose @type is Product.
 *   3. Extract name, image, offers.price, sku, availability, and the size
 *      at the end of the name → stage.
 *
 * Pages are fetched half a second apart. A 401, 403 or 429 stops the run
 * and pushes the next one out (a day, or six hours for a rate limit).
 *
 * Config fields used:
 *   - products_url                     Listing page, sitemap, or store root
 *                                      (e.g. https://peptiva.eu/store)
 *   - selectors.link_pattern           (optional) Regex for product links on
 *                                      a listing page.
 *                                      Default: #href="(/products/[^"]+)"#
 *   - selectors.url_pattern            (optional) Regex a sitemap URL's path
 *                                      must match. Default: /product/{slug}
 *                                      or /products/{slug}.
 *   - selectors.base_url               (optional) Prepended to relative links.
 *                                      Default: parsed from products_url origin.
 *   - selectors.max_products           (optional, default 200) hard cap
 *   - auth_credentials.currency_code   (optional) filters offers[] by currency
 */
class RunJsonLdIngestJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, OnePerScrapingConfig, Queueable, SerializesModels;

    // One attempt per scheduled run: the schedule is the retry, so a failed
    // or killed run does not hit the vendor again minutes later.
    public int $tries = 1;
    public int $backoff = 60;
    public int $timeout = 900; // 15 min — listing + N product pages

    private const USER_AGENT = 'Mozilla/5.0 (compatible; PeptideMap/1.0; +https://peptidemap.com/bot)';

    /** Sitemap URLs kept by default: /product/{slug} or /products/{slug}. */
    private const PRODUCT_PATH_PATTERN = '~/products?/[^/]+/?$~i';

    /** Child sitemaps read from a sitemap index. */
    private const MAX_CHILD_SITEMAPS = 10;

    /** Pause between requests, so a run is a trickle rather than a burst. */
    private const PAUSE_MICROSECONDS = 500_000;

    /** Set when the store refused a request (401, 403 or 429). */
    private bool $wasRefused = false;

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        $listing = (string) $this->config->products_url;
        if (!$listing) {
            $this->recordFailure('Missing products_url (listing page)');
            return;
        }

        $selectors = $this->config->selectors ?? [];
        $maxProducts = (int) ($selectors['max_products'] ?? 200);

        $creds = $this->config->auth_credentials ?? [];
        $currency = strtolower($creds['currency_code'] ?? 'usd');

        try {
            $urls = $this->discoverProductUrls($listing, $selectors);
            if ($this->wasRefused) {
                return;
            }
            if ($urls === null) {
                $this->recordFailure('Empty response from listing page');
                return;
            }
            if (empty($urls)) {
                $this->recordFailure('No product URLs found at ' . $listing);
                return;
            }

            $urls = array_slice($urls, 0, $maxProducts);
            $stagedCount = 0;
            $skippedCount = 0;

            foreach ($urls as $absUrl) {
                Sleep::usleep(self::PAUSE_MICROSECONDS);

                $html = $this->fetch($absUrl, 'text/html,application/xhtml+xml');
                if ($this->wasRefused) {
                    return;
                }
                if (!$html) { $skippedCount++; continue; }

                // Every Product on the page (Vancouver Island Peptides puts
                // one per variant on a page), each as one or more listings.
                $listedUrls = [];
                foreach ($this->extractProducts($html) as $product) {
                    foreach ($this->listingsFor($product, $absUrl, $currency) as $listing) {
                        $listedUrls[] = $listing['source_url'];
                        if ($ingestion->upsertStaged($this->config, $listing)) {
                            $stagedCount++;
                        }
                    }
                }
                if ($listedUrls === []) { $skippedCount++; }
                if (count($listedUrls) > 1 && !in_array($absUrl, $listedUrls, true)) {
                    $this->retirePageListing($absUrl);
                }
            }

            Log::info('JSON-LD ingest complete', [
                'config_id' => $this->config->id,
                'listing' => $listing,
                'discovered_urls' => count($urls),
                'staged_count' => $stagedCount,
                'skipped' => $skippedCount,
            ]);

            $this->config->last_run_at = now();
            $this->config->success_count++;
            $this->config->last_error = null;
            $this->config->calculateNextRunAt();
            $this->config->save();
        } catch (\Throwable $e) {
            $this->recordFailure('Unexpected error: ' . $e->getMessage());
        }
    }

    /**
     * The size at the end of a product name: "BPC-157 10mg" → "10mg",
     * "IGF-1 LR3 0.1mg" → "0.1mg", "BPC-157 + TB-500 Blend 5mg + 5mg" →
     * "5mg/5mg". Null when the name does not end in a size ("GHK-Cu Face
     * Serum 1.5%", "BPC-157 10mg x 10").
     */
    public static function sizeFromName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }
        // "10 mg/vial", "10 mg per vial", "1 mg vial": the size is per vial.
        $name = (string) preg_replace('~\s*(?:/\s*vial|per\s+vial|vials?)\s*$~i', '', trim($name));
        $amount = '\d+(?:\.\d+)?\s*(?:mcg|mg|iu|ml|g)';
        if (!preg_match('~(?<![\w.])(' . $amount . '(?:\s*[+/]\s*' . $amount . ')*)\s*$~i', $name, $m)) {
            return null;
        }

        return str_replace('+', '/', strtolower((string) preg_replace('/\s+/', '', $m[1])));
    }

    /**
     * The size at the end of a URL slug: "/product/adamax-5mg" → "5mg",
     * "/product/NAD-1000-mg" → "1000mg". For stores whose product names
     * leave the size out (Southern Labs).
     */
    public static function sizeFromUrl(?string $url): ?string
    {
        $slug = basename((string) parse_url((string) $url, PHP_URL_PATH));
        $slug = (string) preg_replace('/\.(html?|php)$/i', '', $slug);
        if (!preg_match('~(?:^|[-_])(\d+(?:\.\d+)?)[-_]?(mcg|mg|ml|iu)$~i', $slug, $m)) {
            return null;
        }

        return $m[1] . strtolower($m[2]);
    }

    /**
     * The staged rows for one Product node: one row per offer when the
     * offers name sizes (V8 Peptides lists each size of a product as an
     * offer named "10 mg"), otherwise one row at the first price in the
     * wanted currency.
     *
     * @return list<array<string, mixed>>
     */
    private function listingsFor(array $product, string $pageUrl, string $currency): array
    {
        $name = is_string($product['name'] ?? null)
            ? trim(html_entity_decode($product['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            : '';
        if ($name === '') {
            return [];
        }

        $description = is_string($product['description'] ?? null) ? trim(strip_tags($product['description'])) : null;
        $url = $this->productUrl($product, $pageUrl);
        $pack = $this->packNote($description);
        $common = [
            'source_type' => ScrapedProduct::SOURCE_JSON_LD,
            'description' => $description,
            'discount_price' => null,
            'image_url' => $this->firstString($product['image'] ?? null),
        ];

        $nameSize = self::sizeFromName($name);
        $sized = array_values(array_filter(
            $this->offersIn($product['offers'] ?? null, $currency),
            fn (array $offer) => self::sizeFromName(is_string($offer['name'] ?? null) ? $offer['name'] : null) !== null
        ));

        if ($sized !== [] && ($nameSize === null || count($sized) > 1)) {
            $rows = [];
            foreach ($sized as $offer) {
                $label = trim((string) $offer['name']);
                $offerUrl = $this->offerUrl($offer, $url, $label, count($sized) > 1);
                $rows[] = $common + [
                    'external_id' => (string) ($offer['sku'] ?? $offerUrl),
                    'source_url' => $offerUrl,
                    'name' => (stripos($name, $label) === false ? $name . ' ' . $label : $name) . $pack,
                    'price' => (float) $offer['price'],
                    'stock_status' => $this->stockStatus($offer['availability'] ?? null),
                    'dosage' => self::sizeFromName($label),
                    'raw_data' => ['sku' => $offer['sku'] ?? null, 'currency' => $offer['priceCurrency'] ?? null],
                ];
            }

            return $rows;
        }

        $offer = $this->pickOffer($product['offers'] ?? null, $currency);
        if (!$offer || !isset($offer['price'])) {
            return [];
        }
        $size = $nameSize ?? self::sizeFromUrl($url);
        // "Adamax" from /product/adamax-5mg is listed as "Adamax 5mg";
        // "Water 30 mL bottle" already names its size.
        $namesSize = $size !== null && str_contains(strtolower((string) preg_replace('/\s+/', '', $name)), $size);

        return [$common + [
            'external_id' => (string) ($product['sku'] ?? $product['@id'] ?? $pageUrl),
            'source_url' => $url,
            'name' => ($nameSize === null && $size !== null && !$namesSize ? $name . ' ' . $size : $name) . $pack,
            'price' => (float) $offer['price'],
            'stock_status' => $this->stockStatus($offer['availability'] ?? null),
            'dosage' => $size,
            'raw_data' => ['sku' => $product['sku'] ?? null, 'currency' => $offer['priceCurrency'] ?? null],
        ]];
    }

    /**
     * A page now listed size by size (URL#10-mg, URL#variant-5) supersedes
     * the single listing an earlier import made for the page itself, which
     * carried one size's price and no size. Hide that row so the page is
     * not listed twice (V8 Peptides, 25 Nov 2026). Hidden, not deleted.
     */
    private function retirePageListing(string $pageUrl): void
    {
        $retired = Product::where('brand_id', $this->config->vendor_id)
            ->where('product_url', $pageUrl)
            ->where('hidden', false)
            ->update(['hidden' => true]);
        if ($retired > 0) {
            Log::info('JSON-LD ingest: hid page-level listing superseded by per-size listings', [
                'config_id' => $this->config->id,
                'url' => $pageUrl,
                'rows' => $retired,
            ]);
        }
    }

    /**
     * The product's own URL when it is this page (a variant fragment or
     * query allowed: "...bpc-157-canada.html#variant-5"), else the page.
     */
    private function productUrl(array $product, string $pageUrl): string
    {
        $url = is_string($product['url'] ?? null) ? trim($product['url']) : '';
        $strip = fn (string $u) => (string) preg_replace('~[?#].*$~', '', $u);

        return $url !== '' && str_starts_with($url, 'http') && $strip($url) === $strip($pageUrl) ? $url : $pageUrl;
    }

    /**
     * An offer's own URL. Offers that share the product URL get a fragment
     * per size, or every size would land on the same product.
     */
    private function offerUrl(array $offer, string $productUrl, string $label, bool $several): string
    {
        $url = is_string($offer['url'] ?? null) && str_starts_with($offer['url'], 'http') ? $offer['url'] : $productUrl;
        if ($several && $url === $productUrl && !str_contains($url, '#')) {
            $url .= '#' . trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($label)), '-');
        }

        return $url;
    }

    /**
     * " — 10 vials" when the description sells a multi-vial pack
     * ("Pack: 10 vials."), so the listing is not read as a single vial.
     */
    private function packNote(?string $description): string
    {
        return $description !== null
            && preg_match('~\bPack:\s*(\d+)\s*vials?\b~i', $description, $m) === 1
            && (int) $m[1] > 1
            ? ' — ' . $m[1] . ' vials'
            : '';
    }

    /**
     * Absolute product page URLs in the order found. Null when the listing
     * page could not be fetched.
     *
     * @return list<string>|null
     */
    private function discoverProductUrls(string $listing, array $selectors): ?array
    {
        $path = (string) parse_url($listing, PHP_URL_PATH);
        $isSitemap = str_ends_with(strtolower($path), '.xml');
        $isStoreRoot = ($path === '' || $path === '/') && parse_url($listing, PHP_URL_QUERY) === null;

        if ($isSitemap || $isStoreRoot) {
            $sitemap = $isSitemap ? $listing : $this->originOf($listing) . '/sitemap.xml';
            $urls = $this->urlsFromSitemap($sitemap, $selectors['url_pattern'] ?? self::PRODUCT_PATH_PATTERN, 0);
            if ($this->wasRefused || $urls !== [] || $isSitemap) {
                return $urls;
            }
            // A store root without a usable sitemap: read the page's links.
        }

        $html = $this->fetch($listing, 'text/html,application/xhtml+xml');
        if ($html === null) {
            return $this->wasRefused ? [] : null;
        }

        $linkPattern = $selectors['link_pattern'] ?? '~href="(/products/[^"?#]+)"~';
        $baseUrl = $selectors['base_url'] ?? $this->originOf($listing);
        preg_match_all($linkPattern, $html, $matches);

        $urls = [];
        foreach ($matches[1] ?? [] as $link) {
            $urls[] = str_starts_with($link, 'http') ? $link : $baseUrl . $link;
        }

        return array_values(array_unique($urls));
    }

    /**
     * Page URLs in a sitemap whose path matches $pattern. A sitemap index is
     * followed one level down, product sitemaps first (Shopify's
     * sitemap_products_1.xml, Yoast's product-sitemap.xml).
     *
     * @return list<string>
     */
    private function urlsFromSitemap(string $url, string $pattern, int $depth): array
    {
        $xml = $this->fetch($url, 'application/xml,text/xml;q=0.9,*/*;q=0.8');
        if ($xml === null) {
            return [];
        }

        preg_match_all('~<loc>\s*(?:<!\[CDATA\[)?\s*(.*?)\s*(?:\]\]>)?\s*</loc>~si', $xml, $matches);
        $locs = array_map(
            fn (string $loc) => html_entity_decode(trim($loc), ENT_QUOTES | ENT_XML1, 'UTF-8'),
            $matches[1] ?? []
        );

        if (stripos($xml, '<sitemapindex') !== false) {
            if ($depth > 0) {
                return [];
            }
            $productSitemaps = array_values(array_filter($locs, fn (string $loc) => stripos($loc, 'product') !== false));
            $urls = [];
            foreach (array_slice($productSitemaps ?: $locs, 0, self::MAX_CHILD_SITEMAPS) as $child) {
                Sleep::usleep(self::PAUSE_MICROSECONDS);
                $urls = array_merge($urls, $this->urlsFromSitemap($child, $pattern, $depth + 1));
                if ($this->wasRefused) {
                    return [];
                }
            }

            return array_values(array_unique($urls));
        }

        $urls = array_filter(
            $locs,
            fn (string $loc) => preg_match($pattern, (string) parse_url($loc, PHP_URL_PATH)) === 1
        );

        return array_values(array_unique($urls));
    }

    /**
     * GET a page. Null when it failed; a 401, 403 or 429 also stops the run
     * (see refused()).
     */
    private function fetch(string $url, string $accept): ?string
    {
        try {
            $resp = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => $accept,
                ])
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (in_array($resp->status(), [401, 403, 429], true)) {
            $this->refused($resp->status(), $url);

            return null;
        }

        return $resp->successful() ? $resp->body() : null;
    }

    /**
     * The store refused us: record it and wait before trying again. A block
     * waits a day; a rate limit waits six hours.
     */
    private function refused(int $status, string $url): void
    {
        $this->wasRefused = true;
        $this->recordFailure(($status === 429 ? 'Rate limited (429)' : 'Refused (' . $status . ')') . ' at ' . $url);
        $this->config->deferNextRun($status === 429 ? now()->addHours(6) : now()->addDay());
    }

    /**
     * Every Product node in the page's JSON-LD blocks: top-level objects,
     * arrays of blocks, @graph members, and a Product one level inside
     * another node (WebPage.mainEntity).
     *
     * @return list<array<string, mixed>>
     */
    private function extractProducts(string $html): array
    {
        preg_match_all('#<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>#si', $html, $matches);
        $found = [];
        foreach ($matches[1] ?? [] as $raw) {
            $decoded = json_decode(trim($raw), true);
            if (!is_array($decoded)) continue;
            foreach (array_is_list($decoded) ? $decoded : [$decoded] as $node) {
                if (!is_array($node)) continue;
                if ($this->isProductType($node['@type'] ?? null)) {
                    $found[] = $node;
                    continue;
                }
                foreach ($node as $key => $value) {
                    if (!is_array($value)) continue;
                    $children = $key === '@graph' && array_is_list($value) ? $value : (array_is_list($value) ? [] : [$value]);
                    foreach ($children as $child) {
                        if (is_array($child) && $this->isProductType($child['@type'] ?? null)) {
                            $found[] = $child;
                        }
                    }
                }
            }
        }

        return $found;
    }

    private function isProductType(mixed $type): bool
    {
        if (is_string($type)) return $type === 'Product';
        if (is_array($type)) return in_array('Product', $type, true);
        return false;
    }

    /**
     * Offers can be a single object, an array of Offer, or an AggregateOffer.
     * Prefer matching currency, fall back to lowPrice / first entry.
     */
    private function pickOffer(mixed $offers, string $currency): ?array
    {
        // AggregateOffer — collapse to a synthetic offer keyed on lowPrice.
        if (is_array($offers) && ($offers['@type'] ?? null) === 'AggregateOffer') {
            $price = $offers['lowPrice'] ?? $offers['price'] ?? null;
            return $price !== null ? ['price' => $price, 'priceCurrency' => $offers['priceCurrency'] ?? null] : null;
        }

        return $this->offersIn($offers, $currency)[0] ?? null;
    }

    /**
     * The priced offers, in page order, in the wanted currency when any
     * offer uses it. A price may sit in priceSpecification (WooCommerce).
     *
     * @return list<array<string, mixed>>
     */
    private function offersIn(mixed $offers, string $currency): array
    {
        if (!is_array($offers)) {
            return [];
        }

        $priced = [];
        foreach (array_is_list($offers) ? $offers : [$offers] as $offer) {
            if (!is_array($offer) || ($offer['@type'] ?? null) === 'AggregateOffer') {
                continue;
            }
            $price = $offer['price'] ?? null;
            $spec = $offer['priceSpecification'] ?? null;
            if (($price === null || $price === '') && is_array($spec)) {
                $first = array_is_list($spec) ? ($spec[0] ?? null) : $spec;
                if (is_array($first)) {
                    $price = $first['price'] ?? null;
                    $offer['priceCurrency'] ??= $first['priceCurrency'] ?? null;
                }
            }
            if (!is_numeric($price) || (float) $price <= 0) {
                continue;
            }
            $offer['price'] = $price;
            $priced[] = $offer;
        }

        $wanted = array_values(array_filter(
            $priced,
            fn (array $offer) => strtolower((string) ($offer['priceCurrency'] ?? '')) === $currency
        ));

        return $wanted !== [] ? $wanted : $priced;
    }

    private function firstString(mixed $v): ?string
    {
        if (is_string($v)) return $v;
        if (is_array($v)) {
            foreach ($v as $item) {
                if (is_string($item)) return $item;
                if (is_array($item) && isset($item['url']) && is_string($item['url'])) return $item['url'];
            }
        }
        return null;
    }

    private function stockStatus(?string $availability): ?string
    {
        if (!$availability) return null;
        $a = strtolower($availability);
        // schema.org URLs like https://schema.org/InStock
        if (str_contains($a, 'instock')) return 'in_stock';
        if (str_contains($a, 'outofstock') || str_contains($a, 'soldout')) return 'out_of_stock';
        return null;
    }

    private function originOf(string $url): string
    {
        $parts = parse_url($url);
        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
    }

    protected function recordFailure(string $message): void
    {
        Log::error('JSON-LD ingest failed: ' . $message, ['config_id' => $this->config->id]);
        $this->config->error_count++;
        $this->config->last_error = $message;
        $this->config->last_run_at = now();
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
