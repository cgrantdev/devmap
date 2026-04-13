<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ScrapingConfig;
use App\Services\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Discover products from a vendor's store page by crawling their site.
 * Works for any platform — extracts product URLs, names, prices, images
 * from the store/shop page HTML.
 */
class DiscoverProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 15;

    public function __construct(
        public Brand $brand,
        public ?string $storeUrl = null,
    ) {}

    public function handle(): void
    {
        $url = $this->storeUrl ?? $this->brand->vendorSetting?->shop_url;
        if (!$url) {
            Log::warning('DiscoverProducts: no store URL', ['brand_id' => $this->brand->id]);
            return;
        }

        Log::info('DiscoverProducts: starting', ['brand' => $this->brand->name, 'url' => $url]);

        $products = $this->crawlForProducts($url);

        if (empty($products)) {
            // Try common store page paths
            $paths = ['/shop', '/store', '/products', '/collections', '/us/store', '/peptides'];
            foreach ($paths as $path) {
                $products = $this->crawlForProducts(rtrim($url, '/') . $path);
                if (!empty($products)) break;
                usleep(300_000);
            }
        }

        if (empty($products)) {
            Log::info('DiscoverProducts: no products found', ['brand' => $this->brand->name]);
            return;
        }

        $created = 0;
        $defaultCategory = ProductCategory::firstOrCreate(
            ['slug' => 'uncategorized'],
            ['name' => 'Uncategorized', 'is_active' => true]
        );

        foreach ($products as $p) {
            // Skip if product already exists for this brand with same URL
            if (Product::where('brand_id', $this->brand->id)->where('product_url', $p['url'])->exists()) {
                continue;
            }

            // Try to match to an existing category by name
            $category = null;
            if (!empty($p['name'])) {
                $category = ProductCategory::where('is_active', true)
                    ->where(function ($q) use ($p) {
                        $q->where('name', 'LIKE', '%' . $p['name'] . '%')
                          ->orWhere('slug', 'LIKE', '%' . Str::slug($p['name']) . '%');
                    })
                    ->first();
            }

            Product::create([
                'name' => $p['name'] ?? 'Unknown Product',
                'slug' => Str::slug(($p['name'] ?? 'product') . '-' . Str::slug($this->brand->name)),
                'brand_id' => $this->brand->id,
                'product_category_id' => $category?->id ?? $defaultCategory->id,
                'price' => $p['price'] ?? 0,
                'product_url' => $p['url'],
                'image_url' => $p['image'] ?? null,
                'description' => $p['description'] ?? null,
                'status' => 'active',
                'availability' => 'in_stock',
                'purity' => 99.0,
                'lab_tested' => true,
                'hidden' => false,
                'auto_scraped' => true,
            ]);
            $created++;
        }

        Log::info('DiscoverProducts: complete', [
            'brand' => $this->brand->name,
            'found' => count($products),
            'created' => $created,
        ]);
    }

    private function crawlForProducts(string $url): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'PeptideMapBot/1.0 (+https://peptidemap.com)'])
                ->get($url);

            if (!$response->successful()) return [];

            $html = $response->body();
            $products = [];

            // Strategy 1: JSON-LD Product data
            $products = array_merge($products, $this->extractJsonLdProducts($html));

            // Strategy 2: WooCommerce product links
            $products = array_merge($products, $this->extractWooProducts($html, $url));

            // Strategy 3: Shopify product links
            $products = array_merge($products, $this->extractShopifyProducts($html, $url));

            // Strategy 4: Generic product links (href containing /product/ or /products/)
            $products = array_merge($products, $this->extractGenericProducts($html, $url));

            // Deduplicate by URL
            $seen = [];
            $unique = [];
            foreach ($products as $p) {
                $key = $p['url'] ?? '';
                if ($key && !isset($seen[$key])) {
                    $seen[$key] = true;
                    $unique[] = $p;
                }
            }

            return array_slice($unique, 0, 100); // Cap at 100 products
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function extractJsonLdProducts(string $html): array
    {
        $products = [];
        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $json) {
            $data = json_decode(trim($json), true);
            if (!$data) continue;

            $items = [];
            if (($data['@type'] ?? '') === 'Product') $items[] = $data;
            if (isset($data['@graph'])) {
                foreach ($data['@graph'] as $node) {
                    if (($node['@type'] ?? '') === 'Product') $items[] = $node;
                }
            }
            if (($data['@type'] ?? '') === 'ItemList') {
                foreach ($data['itemListElement'] ?? [] as $el) {
                    if (isset($el['item']) && ($el['item']['@type'] ?? '') === 'Product') {
                        $items[] = $el['item'];
                    }
                }
            }

            foreach ($items as $item) {
                $price = null;
                $offers = $item['offers'] ?? $item['offer'] ?? null;
                if (is_array($offers)) {
                    $offer = isset($offers['price']) ? $offers : ($offers[0] ?? []);
                    $price = $offer['price'] ?? $offer['lowPrice'] ?? null;
                }

                $products[] = [
                    'name' => $item['name'] ?? null,
                    'url' => $item['url'] ?? null,
                    'price' => $price ? (float) $price : null,
                    'image' => is_array($item['image'] ?? null) ? ($item['image'][0] ?? null) : ($item['image'] ?? null),
                    'description' => Str::limit($item['description'] ?? '', 500),
                ];
            }
        }
        return $products;
    }

    private function extractWooProducts(string $html, string $baseUrl): array
    {
        $products = [];
        // WooCommerce uses .product class and data-product_id
        if (preg_match_all('/<a[^>]+href=["\']([^"\']+\/product\/[^"\']+)["\'][^>]*>/i', $html, $matches)) {
            foreach (array_unique($matches[1]) as $href) {
                $fullUrl = $this->resolveUrl($href, $baseUrl);
                $name = $this->extractNameFromUrl($href);
                $products[] = ['name' => $name, 'url' => $fullUrl, 'price' => null, 'image' => null];
            }
        }
        return $products;
    }

    private function extractShopifyProducts(string $html, string $baseUrl): array
    {
        $products = [];
        if (preg_match_all('/<a[^>]+href=["\']([^"\']*\/products\/[^"\'#?]+)["\'][^>]*>/i', $html, $matches)) {
            foreach (array_unique($matches[1]) as $href) {
                if (str_contains($href, '/products/')) {
                    $fullUrl = $this->resolveUrl($href, $baseUrl);
                    $name = $this->extractNameFromUrl($href);
                    $products[] = ['name' => $name, 'url' => $fullUrl, 'price' => null, 'image' => null];
                }
            }
        }
        return $products;
    }

    private function extractGenericProducts(string $html, string $baseUrl): array
    {
        $products = [];
        // Look for links with product-like URLs
        if (preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]{3,80})<\/a>/i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $href = $m[1];
                $text = trim(strip_tags($m[2]));
                $lower = strtolower($href);

                // Skip non-product links
                if (str_contains($lower, 'cart') || str_contains($lower, 'account') ||
                    str_contains($lower, 'login') || str_contains($lower, 'blog') ||
                    str_contains($lower, 'faq') || str_contains($lower, 'contact') ||
                    str_contains($lower, 'about') || str_contains($lower, 'privacy') ||
                    str_contains($lower, 'terms') || str_contains($lower, '.css') ||
                    str_contains($lower, '.js') || str_contains($lower, '#')) {
                    continue;
                }

                // Look for peptide-related keywords in link text or URL
                $isPeptide = preg_match('/bpc|tb-?500|sema|tirze|reta|ghk|ipamorelin|cjc|peptide|semax|selank|dsip|nad|aod|mots|pt-?141|sermorelin|tesamorelin|melanotan/i', $text . ' ' . $href);
                if ($isPeptide && strlen($text) > 3) {
                    $fullUrl = $this->resolveUrl($href, $baseUrl);
                    $products[] = ['name' => $text, 'url' => $fullUrl, 'price' => null, 'image' => null];
                }
            }
        }
        return $products;
    }

    private function extractNameFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $slug = basename($path);
        $slug = preg_replace('/\.(html?|php|aspx?)$/', '', $slug);
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    private function resolveUrl(string $url, string $base): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
        if (str_starts_with($url, '//')) return 'https:' . $url;
        $parsed = parse_url($base);
        $origin = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        return str_starts_with($url, '/') ? $origin . $url : $origin . '/' . $url;
    }
}
