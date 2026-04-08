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
