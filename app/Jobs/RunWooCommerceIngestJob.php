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
                    // Resolve price.
                    // Variable products have empty regular_price/price on the parent
                    // — the prices live on each variation. For those, fetch the
                    // variations and use the cheapest one (effectively "from $X").
                    $regularPrice = $p['regular_price'] ?? null;
                    $salePrice = !empty($p['sale_price']) ? $p['sale_price'] : null;

                    if (($p['type'] ?? null) === 'variable' && empty($regularPrice) && !empty($p['variations'])) {
                        $variantPrices = $this->fetchVariationPrices(
                            $storeUrl, $consumerKey, $consumerSecret, (int) $p['id']
                        );
                        if (!empty($variantPrices['regular'])) {
                            $regularPrice = (string) min($variantPrices['regular']);
                        }
                        if (empty($salePrice) && !empty($variantPrices['sale'])) {
                            $salePrice = (string) min($variantPrices['sale']);
                        }
                    }

                    $staged = $ingestion->upsertStaged($this->config, [
                        'source_type' => ScrapedProduct::SOURCE_WOO_API,
                        'external_id' => (string) ($p['id'] ?? ''),
                        'source_url' => $p['permalink'] ?? null,
                        'name' => $p['name'] ?? null,
                        'description' => strip_tags((string) ($p['short_description'] ?? $p['description'] ?? '')) ?: null,
                        'price' => $regularPrice ?: ($p['price'] ?? null),
                        'discount_price' => $salePrice,
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
     * Fetch all variation prices for a variable product. Returns
     *   ['regular' => [12.50, 25.00, ...], 'sale' => [10.00, ...]]
     * The caller picks min() to use the cheapest variant as the listed price.
     */
    protected function fetchVariationPrices(string $storeUrl, string $key, string $secret, int $productId): array
    {
        $endpoint = $storeUrl . '/wp-json/wc/v3/products/' . $productId . '/variations';
        $regular = [];
        $sale = [];

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
                return ['regular' => [], 'sale' => []];
            }

            foreach ($response->json() ?? [] as $variant) {
                if (!empty($variant['regular_price']) && is_numeric($variant['regular_price'])) {
                    $regular[] = (float) $variant['regular_price'];
                }
                if (!empty($variant['sale_price']) && is_numeric($variant['sale_price'])) {
                    $sale[] = (float) $variant['sale_price'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Variation fetch threw', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
        }

        return ['regular' => $regular, 'sale' => $sale];
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
