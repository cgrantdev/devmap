<?php

namespace App\Console\Commands;

use App\Http\Controllers\Vendor\ImportController;
use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Daily re-fetch for every vendor whose feed URL has been saved on
 * vendor_settings.feed_url. Reuses the same parseFeed + ingestRows
 * pipeline as the /vendor/import UI so behaviour matches exactly
 * (upsert on (brand_id, external_id), sale_price → discount_price,
 * unique slugs, etc).
 *
 * Colin/Rudy Sep 22 — the vendor Import page was one-shot: hit a
 * URL, products land, URL clears. This closes the loop so pricing
 * + stock stay fresh without vendors babysitting the button.
 * Scheduled from routes/console.php at 05:00 UTC (01:00 ET) —
 * off-peak, before the daily digest window.
 */
class SyncVendorFeeds extends Command
{
    protected $signature = 'feeds:sync-vendors
                            {--brand= : Sync only one brand slug}
                            {--dry-run : Fetch + count without writing}';

    protected $description = 'Re-pull every vendor XML feed and upsert products.';

    public function handle(ImportController $importer): int
    {
        $query = Brand::query()
            ->where('is_active', true)
            ->whereHas('vendorSetting', fn ($q) => $q
                ->whereNotNull('feed_url')
                ->where('feed_url', '!=', '')
            );

        if ($slug = $this->option('brand')) {
            $query->where('slug', $slug);
        }

        $brands = $query->with('vendorSetting')->get();
        $this->info("Syncing {$brands->count()} vendor feed(s)…");

        $ok = 0;
        $failed = 0;
        $totalNew = 0;
        $totalUpdated = 0;

        foreach ($brands as $brand) {
            $url = $brand->vendorSetting->feed_url;
            $this->line("  → {$brand->name}: {$url}");

            try {
                $response = Http::timeout(45)->get($url);
                if (!$response->successful()) {
                    $this->warn("     ✗ HTTP {$response->status()}");
                    $failed++;
                    continue;
                }

                // Reuse the ImportController's parser via reflection —
                // the method is private so it's not exposed as a
                // service, but the algorithm is the single source of
                // truth for feed shape detection.
                $ref = new \ReflectionClass($importer);
                $parse = $ref->getMethod('parseFeed'); $parse->setAccessible(true);
                [$rows, $diag] = $parse->invoke($importer, $response->body());

                if (empty($rows)) {
                    $this->warn("     ✗ {$diag}");
                    $failed++;
                    continue;
                }

                $rowStats = ['new' => 0, 'updated' => 0];
                if (!$this->option('dry-run')) {
                    $rowStats = $this->ingestRowsReflectively($importer, $brand->id, $rows);
                    $brand->vendorSetting->forceFill([
                        'feed_last_synced_at' => now(),
                    ])->save();
                }

                $this->line("     ✓ {$rowStats['new']} new, {$rowStats['updated']} updated ({$diag})");
                $totalNew += $rowStats['new'];
                $totalUpdated += $rowStats['updated'];
                $ok++;
            } catch (\Throwable $e) {
                $this->warn("     ✗ {$e->getMessage()}");
                Log::warning('feeds:sync-vendors error', [
                    'brand_id' => $brand->id,
                    'err' => $e->getMessage(),
                ]);
                $failed++;
            }

            usleep(500_000); // polite pacing between vendors
        }

        $this->newLine();
        $this->info("Done. {$ok} synced ({$totalNew} new, {$totalUpdated} updated), {$failed} failed.");
        return self::SUCCESS;
    }

    /**
     * ingestRows lives on ImportController as a protected/private
     * helper that redirects on completion — not what a scheduled
     * job wants. Re-implement the row loop here calling the same
     * upsert logic without the HTTP response.
     */
    private function ingestRowsReflectively(ImportController $importer, int $brandId, array $rows): array
    {
        $ref = new \ReflectionClass($importer);
        $extractPrice = $ref->getMethod('extractPrice'); $extractPrice->setAccessible(true);
        $uniqueSlug = $ref->getMethod('uniqueProductSlug'); $uniqueSlug->setAccessible(true);

        $new = 0;
        $updated = 0;
        foreach ($rows as $r) {
            $productUrl = $r['product_url'] ?? '';
            $extId = $r['sku'] ?? '';

            $existing = null;
            if ($extId !== '') {
                $existing = \App\Models\Product::where('brand_id', $brandId)
                    ->where('external_id', $extId)
                    ->first();
            }
            if (!$existing && $productUrl) {
                $existing = \App\Models\Product::where('brand_id', $brandId)
                    ->where('product_url', $productUrl)
                    ->first();
            }

            $retail = $extractPrice->invoke($importer, $r['price']);
            $saleRaw = trim((string) ($r['sale_price'] ?? ''));
            $sale = $saleRaw !== '' ? $extractPrice->invoke($importer, $saleRaw) : null;
            $discountPrice = ($sale !== null && (float) $sale > 0 && (float) $sale < (float) $retail)
                ? $sale
                : null;

            $attrs = [
                'name' => $r['name'],
                'price' => $retail,
                'discount_price' => $discountPrice,
                'image_url' => $r['image_url'] ?? null,
                'product_url' => $productUrl ?: null,
                'size_mg' => $r['size'] ?? null,
                'stock_status' => $r['stock_status'] ?? null,
                'description' => $r['description'] ?? null,
                'external_id' => $extId ?: null,
            ];

            if ($existing) {
                $existing->fill($attrs)->save();
                $updated++;
            } else {
                $attrs['brand_id'] = $brandId;
                $attrs['slug'] = $uniqueSlug->invoke($importer, $brandId, $r['name']);
                \App\Models\Product::create($attrs);
                $new++;
            }
        }

        return ['new' => $new, 'updated' => $updated];
    }
}
