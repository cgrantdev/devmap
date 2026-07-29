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
 * Ingest products from a Medusa.js Store API.
 *
 * Endpoint: {store_url}/store/products?limit=100&offset=0[&region_id=X]
 * Auth: none (Store API is public by design).
 *
 * Medusa products carry an array of `variants`, each with its own `prices`
 * array keyed by region/currency. Each variant becomes its own staged
 * product so size + price variations land as independent listings — same
 * pattern as our WooCommerce variable-product handling.
 *
 * Prices are returned in lowest-denomination integers (4999 = $49.99 USD),
 * so we divide by 100 when emitting to staging.
 *
 * Config fields used:
 *   - store_url             Medusa backend host (e.g. https://api.shop.com)
 *   - auth_credentials.region_id  (optional) Region ID for USD prices
 *   - auth_credentials.currency_code (optional, default 'usd')
 */
class RunMedusaIngestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        $storeUrl = rtrim((string) $this->config->store_url, '/');
        if (!$storeUrl) {
            $this->recordFailure('Missing Medusa store URL');
            return;
        }

        $creds = $this->config->auth_credentials ?? [];
        $regionId = $creds['region_id'] ?? null;
        $currency = strtolower($creds['currency_code'] ?? 'usd');

        $endpoint = $storeUrl . '/store/products';
        $offset = 0;
        $limit = 100;
        $stagedCount = 0;
        $maxIters = 50; // safety cap = 5000 products

        try {
            while ($maxIters-- > 0) {
                $query = ['limit' => $limit, 'offset' => $offset];
                if ($regionId) $query['region_id'] = $regionId;

                // Medusa v2 requires a publishable API key on every Store API
                // request. It's tied to a sales channel and scopes which
                // products/prices the endpoint exposes. Send it as the
                // documented x-publishable-api-key header when configured.
                $headers = ['User-Agent' => 'PeptideMap/1.0 ingest'];
                if (!empty($creds['publishable_api_key'])) {
                    $headers['x-publishable-api-key'] = $creds['publishable_api_key'];
                }

                $response = Http::timeout(45)
                    ->acceptJson()
                    ->withHeaders($headers)
                    ->get($endpoint, $query);

                if (!$response->successful()) {
                    $this->recordFailure('HTTP ' . $response->status() . ' from ' . $endpoint);
                    return;
                }

                $payload = $response->json();
                $products = $payload['products'] ?? [];
                if (!is_array($products) || empty($products)) break;

                foreach ($products as $p) {
                    if (($p['status'] ?? 'published') !== 'published') continue;

                    $imageUrl = $p['thumbnail']
                        ?? (is_array($p['images'] ?? null) && isset($p['images'][0]['url']) ? $p['images'][0]['url'] : null);

                    $description = is_string($p['description'] ?? null)
                        ? strip_tags($p['description']) : null;

                    foreach (($p['variants'] ?? []) as $v) {
                        $priceCents = $this->extractPrice($v['prices'] ?? [], $currency);
                        if ($priceCents === null) continue;
                        $price = round($priceCents / 100, 2);

                        $variantTitle = $v['title'] ?? '';
                        $combinedName = $variantTitle
                            ? trim(($p['title'] ?? '') . ' — ' . $variantTitle)
                            : ($p['title'] ?? null);

                        $staged = $ingestion->upsertStaged($this->config, [
                            'source_type' => ScrapedProduct::SOURCE_MEDUSA_STORE,
                            'external_id' => (string) ($v['id'] ?? ($p['id'] . '-' . ($v['sku'] ?? ''))),
                            'source_url' => $storeUrl . '/products/' . ($p['handle'] ?? $p['id']),
                            'name' => $combinedName,
                            'description' => $description,
                            'price' => $price,
                            'discount_price' => null, // Medusa expresses sales via discount engine, not a separate field
                            'image_url' => $imageUrl,
                            'stock_status' => $this->stockStatus($v),
                            'raw_data' => [
                                'product_id' => $p['id'] ?? null,
                                'variant_id' => $v['id'] ?? null,
                                'sku' => $v['sku'] ?? null,
                                'inventory' => $v['inventory_quantity'] ?? null,
                            ],
                        ]);

                        if ($staged) $stagedCount++;
                    }
                }

                $offset += $limit;
                if (count($products) < $limit) break;
            }
        } catch (RequestException $e) {
            $this->recordFailure('Medusa request exception: ' . $e->getMessage());
            return;
        } catch (\Throwable $e) {
            $this->recordFailure('Unexpected error: ' . $e->getMessage());
            return;
        }

        Log::info('Medusa ingest complete', [
            'config_id' => $this->config->id,
            'store_url' => $storeUrl,
            'staged_count' => $stagedCount,
        ]);

        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->last_error = null;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }

    /** Pull the price matching our preferred currency. Medusa returns
     *  prices[] like [{ amount: 4999, currency_code: 'usd' }, ...]. */
    protected function extractPrice(array $prices, string $currency): ?int
    {
        foreach ($prices as $row) {
            if (strtolower($row['currency_code'] ?? '') === $currency) {
                return isset($row['amount']) ? (int) $row['amount'] : null;
            }
        }
        // Fallback to the first price entry if no currency match — better
        // than dropping the product silently.
        $first = $prices[0] ?? null;
        return $first && isset($first['amount']) ? (int) $first['amount'] : null;
    }

    protected function stockStatus(array $variant): ?string
    {
        if (!empty($variant['allow_backorder'])) return 'in_stock';
        $qty = $variant['inventory_quantity'] ?? null;
        if ($qty === null) return null;
        return ((int) $qty) > 0 ? 'in_stock' : 'out_of_stock';
    }

    protected function recordFailure(string $message): void
    {
        Log::error('Medusa ingest failed: ' . $message, ['config_id' => $this->config->id]);
        $this->config->error_count++;
        $this->config->last_error = $message;
        $this->config->last_run_at = now();
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
