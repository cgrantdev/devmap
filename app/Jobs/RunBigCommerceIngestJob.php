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

/**
 * Ingest products from BigCommerce V3 REST API.
 *
 * Endpoint: https://api.bigcommerce.com/stores/{store_hash}/v3/catalog/products
 * Auth: X-Auth-Token header with a Store-level access token (read scope).
 *
 * Variants are nested under each product. We include ?include=variants,images
 * so we can read them in a single pagination loop. Each variant with its
 * own price becomes its own staged product (mirroring the WooCommerce
 * variable-product handling).
 *
 * Config fields used:
 *   - auth_credentials.store_hash       e.g. "abc123def"
 *   - auth_credentials.access_token     X-Auth-Token value
 *   - store_url                         (optional) public storefront URL for source_url
 */
class RunBigCommerceIngestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        $creds = $this->config->auth_credentials ?? [];
        $storeHash = $creds['store_hash'] ?? null;
        $accessToken = $creds['access_token'] ?? null;
        $storefront = rtrim((string) ($this->config->store_url ?? ''), '/');

        if (!$storeHash || !$accessToken) {
            $this->recordFailure('Missing BigCommerce store_hash or access_token');
            return;
        }

        $endpoint = "https://api.bigcommerce.com/stores/{$storeHash}/v3/catalog/products";
        $page = 1;
        $limit = 100;
        $stagedCount = 0;
        $maxPages = 50; // safety cap = 5000 products

        try {
            while ($page <= $maxPages) {
                $response = Http::timeout(45)
                    ->acceptJson()
                    ->withHeaders([
                        'X-Auth-Token' => $accessToken,
                        'User-Agent' => 'PeptideMap/1.0 ingest',
                    ])
                    ->get($endpoint, [
                        'limit' => $limit,
                        'page' => $page,
                        'include' => 'variants,images',
                        'is_visible' => true,
                    ]);

                if ($response->status() === 401 || $response->status() === 403) {
                    $this->recordFailure('Authentication failed: ' . $response->status());
                    return;
                }
                if (!$response->successful()) {
                    $this->recordFailure('HTTP ' . $response->status() . ' from BigCommerce');
                    return;
                }

                $payload = $response->json();
                $products = $payload['data'] ?? [];
                if (!is_array($products) || empty($products)) break;

                foreach ($products as $p) {
                    $name = $p['name'] ?? null;
                    if (!$name) continue;

                    $description = strip_tags((string) ($p['description'] ?? '')) ?: null;
                    $imageUrl = $this->primaryImage($p);
                    $sourceUrl = $storefront
                        ? ($storefront . '/' . ($p['custom_url']['url'] ?? '') )
                        : null;

                    $variants = $p['variants'] ?? [];

                    // Single-variant products (the "default" SKU): emit one row.
                    if (count($variants) <= 1) {
                        $v = $variants[0] ?? null;
                        $price = $v['price'] ?? $p['price'] ?? null;
                        $salePrice = $v['sale_price'] ?? $p['sale_price'] ?? null;
                        $salePrice = ($salePrice && $price && $salePrice < $price) ? $salePrice : null;

                        if (!$price) continue;

                        $staged = $ingestion->upsertStaged($this->config, [
                            'source_type' => ScrapedProduct::SOURCE_BIGCOMMERCE,
                            'external_id' => (string) ($p['id'] ?? ''),
                            'source_url' => $sourceUrl,
                            'name' => $name,
                            'description' => $description,
                            'price' => $price,
                            'discount_price' => $salePrice,
                            'image_url' => $imageUrl,
                            'stock_status' => $this->stockStatus($p, $v),
                            'raw_data' => ['product_id' => $p['id'] ?? null, 'sku' => $p['sku'] ?? null],
                        ]);
                        if ($staged) $stagedCount++;
                        continue;
                    }

                    // Multi-variant: one staged row per variant so size/pack
                    // distinctions become separate listings.
                    foreach ($variants as $v) {
                        $price = $v['price'] ?? $p['price'] ?? null;
                        $salePrice = $v['sale_price'] ?? null;
                        $salePrice = ($salePrice && $price && $salePrice < $price) ? $salePrice : null;
                        if (!$price) continue;

                        $variantLabel = $this->variantLabel($v);
                        $combinedName = $variantLabel ? trim($name . ' — ' . $variantLabel) : $name;

                        $staged = $ingestion->upsertStaged($this->config, [
                            'source_type' => ScrapedProduct::SOURCE_BIGCOMMERCE,
                            'external_id' => (string) (($p['id'] ?? '') . '-v' . ($v['id'] ?? '')),
                            'source_url' => $sourceUrl,
                            'name' => $combinedName,
                            'description' => $description,
                            'price' => $price,
                            'discount_price' => $salePrice,
                            'image_url' => $v['image_url'] ?? $imageUrl,
                            'stock_status' => $this->stockStatus($p, $v),
                            'raw_data' => [
                                'product_id' => $p['id'] ?? null,
                                'variant_id' => $v['id'] ?? null,
                                'sku' => $v['sku'] ?? null,
                                'options' => $v['option_values'] ?? null,
                            ],
                        ]);
                        if ($staged) $stagedCount++;
                    }
                }

                $totalPages = (int) ($payload['meta']['pagination']['total_pages'] ?? 0);
                if ($totalPages > 0 && $page >= $totalPages) break;
                if (count($products) < $limit) break;
                $page++;
            }
        } catch (RequestException $e) {
            $this->recordFailure('BigCommerce request exception: ' . $e->getMessage());
            return;
        } catch (\Throwable $e) {
            $this->recordFailure('Unexpected error: ' . $e->getMessage());
            return;
        }

        Log::info('BigCommerce ingest complete', [
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

    protected function primaryImage(array $product): ?string
    {
        $images = $product['images'] ?? [];
        if (empty($images)) return null;
        // BC marks the primary image with is_thumbnail=true; fall back to first.
        foreach ($images as $img) {
            if (!empty($img['is_thumbnail'])) {
                return $img['url_standard'] ?? $img['url_zoom'] ?? $img['url_thumbnail'] ?? null;
            }
        }
        return $images[0]['url_standard'] ?? $images[0]['url_zoom'] ?? null;
    }

    /** Flatten BC's option_values into a human label like "5mg / 60 capsules". */
    protected function variantLabel(array $variant): ?string
    {
        $opts = $variant['option_values'] ?? [];
        if (empty($opts)) return null;
        $labels = array_map(fn ($o) => $o['label'] ?? null, $opts);
        $labels = array_filter($labels);
        return $labels ? implode(' / ', $labels) : null;
    }

    protected function stockStatus(array $product, ?array $variant): ?string
    {
        $level = $variant['inventory_level'] ?? $product['inventory_level'] ?? null;
        $tracking = $variant['inventory_tracking'] ?? $product['inventory_tracking'] ?? null;
        // BC returns availability='available'/'disabled'/'preorder'
        $availability = $product['availability'] ?? null;
        if ($availability === 'disabled') return 'out_of_stock';
        if ($tracking && $tracking !== 'none' && $level !== null) {
            return ((int) $level) > 0 ? 'in_stock' : 'out_of_stock';
        }
        return $availability === 'available' ? 'in_stock' : null;
    }

    protected function recordFailure(string $message): void
    {
        Log::error('BigCommerce ingest failed: ' . $message, ['config_id' => $this->config->id]);
        $this->config->error_count++;
        $this->config->last_error = $message;
        $this->config->last_run_at = now();
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
