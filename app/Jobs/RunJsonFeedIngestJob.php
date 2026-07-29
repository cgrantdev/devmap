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
 * Ingest products from a generic JSON feed.
 *
 * Two shapes are accepted out of the box so vendors don't have to refactor
 * their endpoint to match a single canonical schema before we wire them up:
 *
 *   Canonical:
 *     { "products": [ { name, url, image_url, price, sale_price, ... } ] }
 *
 *   Variant-keyed (Instant Peptides style):
 *     { "products": [ { name, url, variants: [ { size, unit, price,
 *                                                  pack_qty, in_stock, form } ] } ] }
 *     Each variant becomes its own staged product, with name suffixed by
 *     the pack quantity ("BPC-157 500mcg Capsules — 3 pack").
 *
 * The feed URL is read from $config->products_url. No auth is sent — these
 * are public endpoints by design. If a vendor needs auth, we'll add header
 * support via $config->auth_credentials later.
 */
class RunJsonFeedIngestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        $feedUrl = (string) ($this->config->products_url ?? '');
        if (!$feedUrl) {
            $this->recordFailure('Missing feed URL on scraping config');
            return;
        }

        try {
            $response = Http::timeout(45)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'PeptideMap/1.0 ingest'])
                ->get($feedUrl);

            if (!$response->successful()) {
                $this->recordFailure('HTTP ' . $response->status() . ' from ' . $feedUrl);
                return;
            }

            $payload = $response->json();
        } catch (RequestException $e) {
            $this->recordFailure('Feed request exception: ' . $e->getMessage());
            return;
        } catch (\Throwable $e) {
            $this->recordFailure('Unexpected error: ' . $e->getMessage());
            return;
        }

        $products = is_array($payload['products'] ?? null) ? $payload['products'] : null;
        if (!is_array($products)) {
            // Some feeds put products at the top level rather than under a key.
            $products = is_array($payload) ? $payload : null;
        }
        if (!is_array($products)) {
            $this->recordFailure('Feed response did not contain a products array');
            return;
        }

        $stagedCount = 0;
        foreach ($products as $p) {
            if (!is_array($p)) continue;

            // Variant-keyed feeds: emit one staged row per variant.
            if (!empty($p['variants']) && is_array($p['variants'])) {
                foreach ($p['variants'] as $v) {
                    $variantName = $this->buildVariantName($p, $v);
                    $size = $this->buildSizeString($v);
                    $price = $v['price'] ?? null;
                    $salePrice = $v['sale_price'] ?? null;

                    if (!$price && !$salePrice) continue;

                    $staged = $ingestion->upsertStaged($this->config, [
                        'source_type' => ScrapedProduct::SOURCE_JSON_FEED,
                        'external_id' => $this->variantExternalId($p, $v),
                        'source_url' => $p['url'] ?? null,
                        'name' => $variantName,
                        'description' => $p['description'] ?? null,
                        'price' => $price,
                        'discount_price' => $salePrice,
                        'image_url' => $p['image_url'] ?? null,
                        'stock_status' => $this->stockStatus($v['in_stock'] ?? null),
                        'raw_data' => ['parent' => $p['name'] ?? null, 'variant' => $v, 'size' => $size],
                    ]);

                    if ($staged) $stagedCount++;
                }
                continue;
            }

            // Canonical: one row per product. Accepts several common field
            // aliases so vendors don't have to reshape their existing feeds:
            //   image      → image_url  (Certa Peptides, misc.)
            //   permalink  → url        (WooCommerce Store API style)
            //   regular_price / sale_price + on_sale
            //   product_id + variation_id → external_id (Certa's flat variants)
            $imageUrl = $p['image_url'] ?? $p['image'] ?? $p['thumbnail'] ?? null;
            $sourceUrl = $p['url'] ?? $p['permalink'] ?? null;
            $onSale = !empty($p['on_sale']);
            $regularPrice = $p['regular_price'] ?? null;
            $price = $p['price'] ?? $regularPrice;
            // Sale price = discount_price, sale_price, OR (when on_sale=true)
            // the current price if it's below regular_price.
            $salePrice = $p['sale_price'] ?? $p['discount_price'] ?? null;
            if (!$salePrice && $onSale && $regularPrice && $price && $price < $regularPrice) {
                $salePrice = $price;
                $price = $regularPrice; // present retail as the higher figure
            }
            if (!$price && !$salePrice) continue;

            // Prefer variation_id when present (a WooCommerce variant flattened
            // into the feed) so re-runs stably match the same staged row.
            $externalId = (string) (
                ($p['variation_id'] ?? null)
                    ? ($p['product_id'] ?? '') . '-v' . $p['variation_id']
                    : ($p['id'] ?? $p['product_id'] ?? $p['sku'] ?? $sourceUrl ?? $p['name'] ?? '')
            );

            $staged = $ingestion->upsertStaged($this->config, [
                'source_type' => ScrapedProduct::SOURCE_JSON_FEED,
                'external_id' => $externalId,
                'source_url' => $sourceUrl,
                'name' => $this->addDoseToName($p),
                'description' => is_string($p['description'] ?? null) ? strip_tags($p['description']) : null,
                'price' => $price,
                'discount_price' => $salePrice,
                'image_url' => $imageUrl,
                'stock_status' => $this->stockStatus($p['in_stock'] ?? $p['stock_status'] ?? null),
                'raw_data' => $p,
            ]);

            if ($staged) $stagedCount++;
        }

        Log::info('JSON feed ingest complete', [
            'config_id' => $this->config->id,
            'feed_url' => $feedUrl,
            'staged_count' => $stagedCount,
        ]);

        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->last_error = null;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }

    /** Format size + unit into a single string like "500mcg" or "5mg/5mg". */
    protected function buildSizeString(array $variant): ?string
    {
        $size = $variant['size'] ?? null;
        $unit = $variant['unit'] ?? null;
        if (!$size) return null;
        return $unit ? trim($size . $unit) : (string) $size;
    }

    /** "Parent — 5mg Capsule × 3 pack" for variant rows so the public name
     *  carries enough info to disambiguate between vendor SKUs. */
    protected function buildVariantName(array $product, array $variant): ?string
    {
        $base = $product['name'] ?? '';
        $size = $this->buildSizeString($variant);
        $form = $variant['form'] ?? null;
        $packQty = (int) ($variant['pack_qty'] ?? 1);

        $parts = array_filter([
            $size,
            $form && strtolower($form) !== 'peptide' ? ucfirst($form) : null,
            $packQty > 1 ? "× {$packQty} pack" : null,
        ]);

        if (empty($parts)) return $base;
        return trim($base . ' — ' . implode(' · ', $parts));
    }

    protected function variantExternalId(array $product, array $variant): string
    {
        $parts = [
            $product['id'] ?? $product['url'] ?? $product['name'] ?? 'p',
            $variant['size'] ?? '',
            $variant['unit'] ?? '',
            $variant['form'] ?? '',
            $variant['pack_qty'] ?? 1,
        ];
        return (string) implode('|', $parts);
    }

    /** Map various truthy/falsy in_stock representations to a canonical string. */
    protected function stockStatus($raw): ?string
    {
        if ($raw === null) return null;
        if (is_bool($raw)) return $raw ? 'in_stock' : 'out_of_stock';
        if (is_string($raw)) {
            $l = strtolower($raw);
            if (in_array($l, ['true', 'yes', 'in_stock', 'instock', 'available'], true)) return 'in_stock';
            if (in_array($l, ['false', 'no', 'out_of_stock', 'outofstock', 'unavailable'], true)) return 'out_of_stock';
        }
        return null;
    }

    /** Certa Peptides publishes one row per dose with the dose in a separate
     *  `dose` field rather than baked into `name`. Fold it in so downstream
     *  display / dedup logic sees a distinct name per variant. */
    protected function addDoseToName(array $product): ?string
    {
        $name = $product['name'] ?? null;
        if (!$name) return null;
        $dose = $product['dose'] ?? $product['size'] ?? null;
        if (!$dose) return $name;
        // Skip if the dose is already suffixed in the name (guards against
        // vendors who put it in both places).
        if (stripos($name, (string) $dose) !== false) return $name;
        return trim($name) . ' — ' . trim((string) $dose);
    }

    protected function recordFailure(string $message): void
    {
        Log::error('JSON feed ingest failed: ' . $message, ['config_id' => $this->config->id]);
        $this->config->error_count++;
        $this->config->last_error = $message;
        $this->config->last_run_at = now();
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
