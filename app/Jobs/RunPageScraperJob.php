<?php

namespace App\Jobs;

use App\Models\Product;
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
 * Generic page scraper — fetches each product's page URL and extracts
 * price, image, and stock data from the HTML.  Works for any vendor
 * platform (Medusa, Shopify, custom, etc.) by reading og: meta tags,
 * JSON-LD structured data, and optional CSS selectors.
 */
class RunPageScraperJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 15;

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        $brandId = $this->config->vendor_id;
        if (!$brandId) {
            $this->recordFailure('No vendor_id on scraping config');
            return;
        }

        // Get all products for this brand that have a product_url
        $products = Product::where('brand_id', $brandId)
            ->whereNotNull('product_url')
            ->where('product_url', '!=', '')
            ->get();

        if ($products->isEmpty()) {
            $this->recordFailure('No products with URLs found for brand #' . $brandId);
            return;
        }

        $selectors = $this->config->selectors ?? [];
        $stagedCount = 0;
        $errorCount = 0;

        foreach ($products as $product) {
            try {
                $data = $this->scrapeProductPage($product->product_url, $selectors);

                if (!$data) {
                    $errorCount++;
                    continue;
                }

                $staged = $ingestion->upsertStaged($this->config, [
                    'source_type' => 'page_scrape',
                    'external_id' => (string) $product->id,
                    'source_url' => $product->product_url,
                    'name' => $data['name'] ?? $product->name,
                    'description' => $data['description'] ?? $product->description,
                    'price' => $data['price'] ?? null,
                    'discount_price' => $data['discount_price'] ?? null,
                    'image_url' => $data['image_url'] ?? null,
                    'stock_status' => $data['stock_status'] ?? null,
                    'raw_data' => ['scraped_at' => now()->toIso8601String(), 'url' => $product->product_url],
                ]);

                if ($staged) {
                    $stagedCount++;
                }

                // Be polite — don't hammer the vendor's server
                usleep(500_000); // 500ms between requests
            } catch (\Throwable $e) {
                Log::warning('Page scrape failed for product', [
                    'product_id' => $product->id,
                    'url' => $product->product_url,
                    'error' => $e->getMessage(),
                ]);
                $errorCount++;
            }
        }

        Log::info('Page scraper complete', [
            'config_id' => $this->config->id,
            'brand_id' => $brandId,
            'products_scraped' => $stagedCount,
            'errors' => $errorCount,
        ]);

        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->last_error = $errorCount > 0
            ? "{$errorCount} product(s) failed to scrape"
            : null;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }

    /**
     * Fetch a product page and extract structured data.
     * Priority: JSON-LD > og: meta tags > CSS selectors.
     */
    protected function scrapeProductPage(string $url, array $selectors): ?array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'User-Agent' => 'PeptideMapBot/1.0 (+https://peptidemap.com)',
                'Accept' => 'text/html',
            ])
            ->get($url);

        if (!$response->successful()) {
            return null;
        }

        $html = $response->body();
        $data = [];

        // 1. Try JSON-LD structured data (most reliable)
        $jsonLd = $this->extractJsonLd($html);
        if ($jsonLd) {
            $data['name'] = $jsonLd['name'] ?? null;
            $data['description'] = $jsonLd['description'] ?? null;
            $data['image_url'] = is_array($jsonLd['image'] ?? null)
                ? ($jsonLd['image'][0] ?? null)
                : ($jsonLd['image'] ?? null);

            // Price from offers
            $offers = $jsonLd['offers'] ?? $jsonLd['offer'] ?? null;
            if (is_array($offers)) {
                // Could be single offer or array of offers
                $offer = isset($offers['price']) ? $offers : ($offers[0] ?? []);
                $data['price'] = $offer['price'] ?? $offer['lowPrice'] ?? null;
                $availability = $offer['availability'] ?? '';
                $data['stock_status'] = str_contains(strtolower($availability), 'instock')
                    ? 'in_stock'
                    : (str_contains(strtolower($availability), 'outofstock') ? 'out_of_stock' : null);
            }
        }

        // 2. Fill gaps from og: meta tags
        $data['name'] = $data['name'] ?? $this->extractMeta($html, 'og:title');
        $data['description'] = $data['description'] ?? $this->extractMeta($html, 'og:description');
        $data['image_url'] = $data['image_url'] ?? $this->extractMeta($html, 'og:image');

        // Price from og: tags (some sites use product: namespace)
        if (empty($data['price'])) {
            $data['price'] = $this->extractMeta($html, 'product:price:amount')
                ?? $this->extractMeta($html, 'og:price:amount');
        }

        // 3. If image still not found, find the first large product image from the page
        if (empty($data['image_url'])) {
            $data['image_url'] = $this->extractFirstProductImage($html, $url);
        }

        // 4. CSS selectors as final override (if configured)
        if (!empty($selectors['price'])) {
            $selectorPrice = $this->extractBySelector($html, $selectors['price']);
            if ($selectorPrice) {
                $data['price'] = preg_replace('/[^0-9.]/', '', $selectorPrice);
            }
        }
        if (!empty($selectors['image'])) {
            $selectorImage = $this->extractImgBySelector($html, $selectors['image']);
            if ($selectorImage) {
                $data['image_url'] = $this->resolveUrl($selectorImage, $url);
            }
        }

        // Clean up price — extract numeric value
        if (!empty($data['price'])) {
            $cleaned = preg_replace('/[^0-9.]/', '', (string) $data['price']);
            $data['price'] = $cleaned ?: null;
        }

        return $data;
    }

    /**
     * Extract JSON-LD Product schema from script tags.
     */
    protected function extractJsonLd(string $html): ?array
    {
        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if (!$decoded) continue;

            // Could be an array of schemas
            $schemas = isset($decoded['@type']) ? [$decoded] : ($decoded['@graph'] ?? $decoded);
            if (!is_array($schemas)) continue;

            foreach ($schemas as $schema) {
                if (is_array($schema) && in_array($schema['@type'] ?? '', ['Product', 'IndividualProduct'])) {
                    return $schema;
                }
            }
        }

        return null;
    }

    /**
     * Extract content from a meta tag by property or name.
     */
    protected function extractMeta(string $html, string $property): ?string
    {
        $pattern = '/<meta[^>]*(?:property|name)=["\']' . preg_quote($property, '/') . '["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i';
        if (preg_match($pattern, $html, $m)) {
            return html_entity_decode(trim($m[1]));
        }
        // Try reversed attribute order: content before property
        $pattern2 = '/<meta[^>]*content=["\']([^"\']*)["\'][^>]*(?:property|name)=["\']' . preg_quote($property, '/') . '["\'][^>]*>/i';
        if (preg_match($pattern2, $html, $m)) {
            return html_entity_decode(trim($m[1]));
        }
        return null;
    }

    /**
     * Find the first "real" product image — skip tiny icons, logos, svgs.
     * Looks for img tags with src pointing to common product image patterns.
     */
    protected function extractFirstProductImage(string $html, string $baseUrl): ?string
    {
        // Match all img tags with src
        if (!preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $src) {
            $lower = strtolower($src);
            // Skip tiny/non-product images
            if (str_contains($lower, 'logo') || str_contains($lower, 'icon') ||
                str_contains($lower, 'avatar') || str_contains($lower, 'favicon') ||
                str_contains($lower, '.svg') || str_contains($lower, 'placeholder') ||
                str_contains($lower, 'spinner') || str_contains($lower, 'pixel') ||
                str_contains($lower, 'data:image') || str_contains($lower, '1x1')) {
                continue;
            }
            // Look for common product image indicators
            if (str_contains($lower, 'product') || str_contains($lower, 'cdn') ||
                str_contains($lower, 's3.') || str_contains($lower, 'shopify') ||
                str_contains($lower, 'medusa') || str_contains($lower, 'woo') ||
                str_contains($lower, '.png') || str_contains($lower, '.jpg') ||
                str_contains($lower, '.jpeg') || str_contains($lower, '.webp')) {
                return $this->resolveUrl($src, $baseUrl);
            }
        }

        return null;
    }

    /**
     * Very basic CSS selector text extraction (supports simple selectors only).
     */
    protected function extractBySelector(string $html, string $selector): ?string
    {
        // Handle meta tag selectors
        if (str_starts_with($selector, 'meta[')) {
            if (preg_match('/meta\[(?:property|name)=["\']([^"\']+)["\']\]/', $selector, $sm)) {
                return $this->extractMeta($html, $sm[1]);
            }
        }

        // Handle class selectors like .price, .product-price
        if (str_starts_with($selector, '.')) {
            $class = ltrim($selector, '.');
            $pattern = '/<[^>]+class=["\'][^"\']*\b' . preg_quote($class, '/') . '\b[^"\']*["\'][^>]*>(.*?)<\/[^>]+>/si';
            if (preg_match($pattern, $html, $m)) {
                return strip_tags(trim($m[1]));
            }
        }

        return null;
    }

    /**
     * Extract img src from a CSS selector match.
     */
    protected function extractImgBySelector(string $html, string $selector): ?string
    {
        if (str_starts_with($selector, 'meta[')) {
            if (preg_match('/meta\[(?:property|name)=["\']([^"\']+)["\']\]/', $selector, $sm)) {
                return $this->extractMeta($html, $sm[1]);
            }
        }
        return null;
    }

    /**
     * Resolve a potentially relative URL against a base URL.
     */
    protected function resolveUrl(string $url, string $base): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        $parsed = parse_url($base);
        $origin = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        if (str_starts_with($url, '/')) {
            return $origin . $url;
        }
        return $origin . '/' . $url;
    }

    protected function recordFailure(string $message): void
    {
        Log::error('Page scraper failed: ' . $message, ['config_id' => $this->config->id]);
        $this->config->error_count++;
        $this->config->last_error = $message;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
