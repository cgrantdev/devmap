<?php

namespace App\Jobs;

use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use App\Services\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RunWooCommerceIngestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        $creds = $this->config->auth_credentials ?? [];
        $consumerKey = $creds['consumer_key'] ?? null;
        $consumerSecret = $creds['consumer_secret'] ?? null;
        $storeUrl = rtrim((string) $this->config->store_url, '/');

        if (!$consumerKey || !$consumerSecret || !$storeUrl) {
            $this->recordFailure('Missing WooCommerce credentials or store URL');
            return;
        }

        $endpoint = $storeUrl . '/wp-json/wc/v3/products';
        $page = 1;
        $perPage = 100;
        $stagedCount = 0;
        $maxPages = 50; // hard cap = 5,000 products per run, just a safety valve

        try {
            while ($page <= $maxPages) {
                $response = Http::withBasicAuth($consumerKey, $consumerSecret)
                    ->timeout(30)
                    ->acceptJson()
                    ->get($endpoint, [
                        'per_page' => $perPage,
                        'page' => $page,
                        'status' => 'publish',
                    ]);

                if ($response->status() === 401 || $response->status() === 403) {
                    $this->recordFailure('Authentication failed: ' . $response->status());
                    return;
                }

                if (!$response->successful()) {
                    $this->recordFailure('HTTP ' . $response->status() . ' from ' . $endpoint);
                    return;
                }

                $products = $response->json();
                if (!is_array($products) || empty($products)) {
                    break;
                }

                foreach ($products as $p) {
                    // Variable products: skip the parent and create one staged
                    // product per variation, so each size/quantity ends up as
                    // an independent listing on the compare/directory pages.
                    if (($p['type'] ?? null) === 'variable' && !empty($p['variations'])) {
                        $variants = $this->fetchVariations(
                            $storeUrl, $consumerKey, $consumerSecret, (int) $p['id']
                        );

                        foreach ($variants as $variant) {
                            $variantName = $this->buildVariantName($p, $variant);
                            $regularPrice = !empty($variant['regular_price']) ? $variant['regular_price'] : null;
                            $salePrice = !empty($variant['sale_price']) ? $variant['sale_price'] : null;

                            // Skip variants with no price at all (likely "out of stock"
                            // placeholders or admin-deleted variations).
                            if (empty($regularPrice) && empty($variant['price'])) {
                                continue;
                            }

                            $staged = $ingestion->upsertStaged($this->config, [
                                'source_type' => ScrapedProduct::SOURCE_WOO_API,
                                'external_id' => (string) ($p['id'] . '-v' . $variant['id']),
                                'source_url' => $variant['permalink'] ?? $p['permalink'] ?? null,
                                'name' => $variantName,
                                'description' => strip_tags((string) ($p['short_description'] ?? $p['description'] ?? '')) ?: null,
                                'price' => $regularPrice ?: ($variant['price'] ?? null),
                                'discount_price' => $salePrice,
                                'image_url' => $variant['image']['src'] ?? ($p['images'][0]['src'] ?? null),
                                'stock_status' => $variant['stock_status'] ?? $p['stock_status'] ?? null,
                                'raw_data' => $this->compactVariantRawData($p, $variant),
                            ]);

                            if ($staged) {
                                $stagedCount++;
                            }
                        }
                        continue; // skip the parent itself
                    }

                    // Simple product — create one staged record from the parent payload.
                    $staged = $ingestion->upsertStaged($this->config, [
                        'source_type' => ScrapedProduct::SOURCE_WOO_API,
                        'external_id' => (string) ($p['id'] ?? ''),
                        'source_url' => $p['permalink'] ?? null,
                        'name' => $p['name'] ?? null,
                        'description' => strip_tags((string) ($p['short_description'] ?? $p['description'] ?? '')) ?: null,
                        'price' => $p['regular_price'] ?? $p['price'] ?? null,
                        'discount_price' => !empty($p['sale_price']) ? $p['sale_price'] : null,
                        'image_url' => $p['images'][0]['src'] ?? null,
                        'stock_status' => $p['stock_status'] ?? null,
                        'raw_data' => $this->compactRawData($p),
                    ]);

                    if ($staged) {
                        $stagedCount++;
                    }
                }

                // Total pages header lets us exit early
                $totalPages = (int) ($response->header('X-WP-TotalPages') ?? $response->header('x-wp-totalpages') ?? 0);
                if ($totalPages > 0 && $page >= $totalPages) {
                    break;
                }
                if (count($products) < $perPage) {
                    break;
                }

                $page++;
            }
        } catch (RequestException $e) {
            $this->recordFailure('WooCommerce request exception: ' . $e->getMessage());
            return;
        } catch (\Throwable $e) {
            $this->recordFailure('Unexpected error: ' . $e->getMessage());
            return;
        }

        Log::info('WooCommerce ingest complete', [
            'config_id' => $this->config->id,
            'pages_fetched' => $page,
            'staged_count' => $stagedCount,
        ]);

        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->last_error = null;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }

    /**
     * Fetch every variation for a variable product. Returns the raw array
     * of variation objects from /wp-json/wc/v3/products/{id}/variations,
     * or [] on any failure (logged).
     */
    protected function fetchVariations(string $storeUrl, string $key, string $secret, int $productId): array
    {
        $endpoint = $storeUrl . '/wp-json/wc/v3/products/' . $productId . '/variations';

        try {
            $response = Http::withBasicAuth($key, $secret)
                ->timeout(20)
                ->acceptJson()
                ->get($endpoint, ['per_page' => 100]);

            if (!$response->successful()) {
                Log::warning('Variation fetch failed', [
                    'product_id' => $productId,
                    'status' => $response->status(),
                ]);
                return [];
            }

            return is_array($response->json()) ? $response->json() : [];
        } catch (\Throwable $e) {
            Log::warning('Variation fetch threw', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Build a display name like "BPC-157 — 5mg" or "BPC-157/TB-500 — 5mg/5mg"
     * by appending the variation's selected attribute options to the parent's name.
     *
     * Bare numeric tokens get "mg" appended automatically — vendors often
     * encode blend ratios like "5/5" (meaning 5mg + 5mg) which become
     * "5mg/5mg" so the compare page can read them correctly.
     */
    protected function buildVariantName(array $parent, array $variant): string
    {
        $base = trim((string) ($parent['name'] ?? ''));
        $attrs = collect($variant['attributes'] ?? [])
            ->pluck('option')
            ->filter()
            ->map(fn ($v) => $this->normalizeUnits(trim((string) $v)))
            ->filter()
            ->values()
            ->all();

        if (empty($attrs)) {
            return $base;
        }

        return $base . ' — ' . implode(' / ', $attrs);
    }

    /**
     * Append "mg" to bare numeric tokens that don't already carry a unit.
     * Examples:
     *   "5/5"           → "5mg/5mg"
     *   "10/10/10"      → "10mg/10mg/10mg"
     *   "50mg"          → "50mg" (unchanged)
     *   "100mcg"        → "100mcg" (unchanged)
     *   "Standard"      → "Standard" (unchanged)
     *   "1000 IU"       → "1000 IU" (unchanged)
     */
    protected function normalizeUnits(string $value): string
    {
        // Match a number not already followed by a letter (i.e. no unit attached
        // and no unit immediately after with optional whitespace).
        return preg_replace_callback(
            '/\b(\d+(?:\.\d+)?)\b(?!\s*[a-zA-Z])/',
            fn ($m) => $m[1] . 'mg',
            $value
        );
    }

    /**
     * Variant payload kept for debugging — small subset of the parent + variation data.
     */
    protected function compactVariantRawData(array $parent, array $variant): array
    {
        return [
            'parent_id' => $parent['id'] ?? null,
            'parent_name' => $parent['name'] ?? null,
            'parent_categories' => $parent['categories'] ?? null,
            'variant_id' => $variant['id'] ?? null,
            'variant_sku' => $variant['sku'] ?? null,
            'variant_attributes' => $variant['attributes'] ?? null,
            'regular_price' => $variant['regular_price'] ?? null,
            'sale_price' => $variant['sale_price'] ?? null,
            'stock_status' => $variant['stock_status'] ?? null,
            'stock_quantity' => $variant['stock_quantity'] ?? null,
        ];
    }

    /**
     * Trim Woo's huge product payload down to fields worth keeping for debugging.
     */
    protected function compactRawData(array $p): array
    {
        $keep = ['id', 'sku', 'type', 'status', 'featured', 'date_created', 'date_modified',
            'regular_price', 'sale_price', 'on_sale', 'total_sales', 'stock_status',
            'stock_quantity', 'categories', 'tags', 'attributes', 'variations'];
        return array_intersect_key($p, array_flip($keep));
    }

    protected function recordFailure(string $message): void
    {
        Log::error('WooCommerce ingest failed: ' . $message, ['config_id' => $this->config->id]);
        $this->config->error_count++;
        $this->config->last_error = $message;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
