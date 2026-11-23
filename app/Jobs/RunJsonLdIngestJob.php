<?php

namespace App\Jobs;

use App\Jobs\Concerns\OnePerScrapingConfig;
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

                $product = $this->extractProductJsonLd($html);
                if (!$product) { $skippedCount++; continue; }

                $offer = $this->pickOffer($product['offers'] ?? null, $currency);
                if (!$offer || !isset($offer['price'])) { $skippedCount++; continue; }

                $name = is_string($product['name'] ?? null) ? trim($product['name']) : null;

                $staged = $ingestion->upsertStaged($this->config, [
                    'source_type' => ScrapedProduct::SOURCE_JSON_LD,
                    'external_id' => (string) ($product['sku'] ?? $product['@id'] ?? $absUrl),
                    'source_url' => $absUrl,
                    'name' => $name,
                    'description' => is_string($product['description'] ?? null)
                        ? trim(strip_tags($product['description'])) : null,
                    'price' => (float) $offer['price'],
                    'discount_price' => null,
                    'image_url' => $this->firstString($product['image'] ?? null),
                    'stock_status' => $this->stockStatus($offer['availability'] ?? null),
                    'dosage' => self::sizeFromName($name),
                    'raw_data' => [
                        'sku' => $product['sku'] ?? null,
                        'currency' => $offer['priceCurrency'] ?? null,
                    ],
                ]);
                if ($staged) $stagedCount++;
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
        $amount = '\d+(?:\.\d+)?\s*(?:mcg|mg|iu|ml|g)';
        if (!$name || !preg_match('~(?<![\w.])(' . $amount . '(?:\s*[+/]\s*' . $amount . ')*)\s*$~i', trim($name), $m)) {
            return null;
        }

        return str_replace('+', '/', strtolower((string) preg_replace('/\s+/', '', $m[1])));
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
     * Pull the first JSON-LD block whose (nested) @type includes 'Product'.
     * Handles arrays of blocks, @graph containers, and single objects.
     */
    private function extractProductJsonLd(string $html): ?array
    {
        preg_match_all('#<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>#si', $html, $matches);
        foreach ($matches[1] ?? [] as $raw) {
            $decoded = json_decode(trim($raw), true);
            if (!is_array($decoded)) continue;
            $found = $this->findProductNode($decoded);
            if ($found) return $found;
        }
        return null;
    }

    private function findProductNode(array $node): ?array
    {
        if ($this->isProductType($node['@type'] ?? null)) return $node;
        if (isset($node['@graph']) && is_array($node['@graph'])) {
            foreach ($node['@graph'] as $g) {
                if (is_array($g) && $this->isProductType($g['@type'] ?? null)) return $g;
            }
        }
        // Array-of-blocks at the top level (some sites emit `[ {...Product}, {...Breadcrumb} ]`)
        foreach ($node as $v) {
            if (is_array($v) && $this->isProductType($v['@type'] ?? null)) return $v;
        }
        return null;
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
        if (!is_array($offers)) return null;

        // AggregateOffer — collapse to a synthetic offer keyed on lowPrice.
        if (($offers['@type'] ?? null) === 'AggregateOffer') {
            $price = $offers['lowPrice'] ?? $offers['price'] ?? null;
            return $price !== null ? ['price' => $price, 'priceCurrency' => $offers['priceCurrency'] ?? null] : null;
        }

        // Single Offer object.
        if (isset($offers['price']) || isset($offers['@type'])) {
            return $offers;
        }

        // Array of Offer.
        $currencyMatch = null;
        foreach ($offers as $o) {
            if (!is_array($o) || !isset($o['price'])) continue;
            if (strtolower($o['priceCurrency'] ?? '') === $currency) {
                return $o;
            }
            $currencyMatch ??= $o;
        }
        return $currencyMatch;
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
