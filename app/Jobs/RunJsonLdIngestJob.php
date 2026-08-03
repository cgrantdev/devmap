<?php

namespace App\Jobs;

use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use App\Services\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ingest products from any storefront that emits Product JSON-LD (schema.org)
 * on each product page. Works for Peptiva (Medusa+Next.js), most Shopify
 * themes, most WooCommerce themes, and any modern SEO-conscious storefront.
 *
 * Flow:
 *   1. GET the listing page (products_url), extract every /products/{slug}
 *      link (or configured link pattern) from the HTML.
 *   2. For each unique product URL, GET the page and pull the first
 *      <script type="application/ld+json"> block whose @type is Product.
 *   3. Extract name, image, offers.price, sku, availability → stage.
 *
 * Config fields used:
 *   - products_url                     Listing page (e.g. https://peptiva.eu/store)
 *   - selectors.link_pattern           (optional) Regex for product URLs.
 *                                      Default: #href="(/products/[^"]+)"#
 *   - selectors.base_url               (optional) Prepended to relative links.
 *                                      Default: parsed from products_url origin.
 *   - selectors.max_products           (optional, default 200) hard cap
 *   - auth_credentials.currency_code   (optional) filters offers[] by currency
 */
class RunJsonLdIngestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;
    public int $timeout = 900; // 15 min — listing + N product pages

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        $listing = (string) $this->config->products_url;
        if (!$listing) {
            $this->recordFailure('Missing products_url (listing page)');
            return;
        }

        $selectors = $this->config->selectors ?? [];
        $linkPattern = $selectors['link_pattern'] ?? '#href="(/products/[^"?#]+)"#';
        $maxProducts = (int) ($selectors['max_products'] ?? 200);
        $baseUrl = $selectors['base_url'] ?? $this->originOf($listing);

        $creds = $this->config->auth_credentials ?? [];
        $currency = strtolower($creds['currency_code'] ?? 'usd');

        try {
            $listingHtml = $this->fetchHtml($listing);
            if (!$listingHtml) {
                $this->recordFailure('Empty response from listing page');
                return;
            }

            preg_match_all($linkPattern, $listingHtml, $matches);
            $urls = array_unique($matches[1] ?? []);
            if (empty($urls)) {
                $this->recordFailure('No product links matched pattern ' . $linkPattern);
                return;
            }

            $urls = array_slice($urls, 0, $maxProducts);
            $stagedCount = 0;
            $skippedCount = 0;

            foreach ($urls as $path) {
                $absUrl = str_starts_with($path, 'http') ? $path : $baseUrl . $path;
                $html = $this->fetchHtml($absUrl);
                if (!$html) { $skippedCount++; continue; }

                $product = $this->extractProductJsonLd($html);
                if (!$product) { $skippedCount++; continue; }

                $offer = $this->pickOffer($product['offers'] ?? null, $currency);
                if (!$offer || !isset($offer['price'])) { $skippedCount++; continue; }

                $staged = $ingestion->upsertStaged($this->config, [
                    'source_type' => ScrapedProduct::SOURCE_JSON_LD,
                    'external_id' => (string) ($product['sku'] ?? $product['@id'] ?? $absUrl),
                    'source_url' => $absUrl,
                    'name' => is_string($product['name'] ?? null) ? trim($product['name']) : null,
                    'description' => is_string($product['description'] ?? null)
                        ? trim(strip_tags($product['description'])) : null,
                    'price' => (float) $offer['price'],
                    'discount_price' => null,
                    'image_url' => $this->firstString($product['image'] ?? null),
                    'stock_status' => $this->stockStatus($offer['availability'] ?? null),
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

    private function fetchHtml(string $url): ?string
    {
        try {
            $resp = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; PeptideMap/1.0; +https://peptidemap.com/bot)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);
            return $resp->successful() ? $resp->body() : null;
        } catch (\Throwable) {
            return null;
        }
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
