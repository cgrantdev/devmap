<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Services\GoAffProClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Pulls fresh affiliate-side stats from each vendor's affiliate program
 * (clicks / sales / commission earned / pending) and caches the snapshot
 * on vendor_settings.affiliate_stats_json. The /admin/affiliates page
 * renders directly from these cached values so page loads stay fast.
 *
 * Scheduled daily in routes/console.php. Idempotent — safe to run
 * manually with php artisan affiliates:sync.
 */
class SyncAffiliateStats extends Command
{
    protected $signature = 'affiliates:sync
                            {--brand= : Only sync one brand slug (skips the rest)}';

    protected $description = 'Pull fresh clicks / sales / commission per vendor from their affiliate program.';

    public function handle(GoAffProClient $goaffpro): int
    {
        $query = Brand::query()
            ->where('is_active', true)
            ->whereHas('vendorSetting', fn ($q) => $q
                ->whereNotNull('affiliate_platform')
                ->whereIn('affiliate_platform', ['goaffpro'])
                ->whereNotNull('affiliate_credentials')
            );

        if ($slug = $this->option('brand')) {
            $query->where('slug', $slug);
        }

        $brands = $query->with('vendorSetting')->get();
        $this->info("Syncing {$brands->count()} vendor(s)…");

        $ok = 0;
        $failed = 0;

        foreach ($brands as $brand) {
            $vs = $brand->vendorSetting;
            $creds = $vs->affiliate_credentials ?? [];
            $token = is_array($creds) ? ($creds['token'] ?? null) : null;

            if (!$token) {
                $this->warn("  skip {$brand->name} — no token");
                continue;
            }

            $stats = null;
            if ($vs->affiliate_platform === 'goaffpro') {
                $stats = $goaffpro->fetchStats($token);
            }

            if ($stats) {
                $vs->affiliate_stats_json = $stats;
                $vs->affiliate_stats_updated_at = now();
                $vs->save();
                $ok++;
                $this->line("  ✓ {$brand->name}: {$stats['orders_total']} orders / \${$stats['commission_earned']} earned");
            } else {
                $failed++;
                $this->warn("  ✗ {$brand->name}: API returned nothing (check token)");
                Log::info('affiliates:sync failed for brand', ['brand_id' => $brand->id, 'platform' => $vs->affiliate_platform]);
            }

            // Polite pacing between calls.
            usleep(500_000);
        }

        $this->newLine();
        $this->info("Done. {$ok} synced, {$failed} failed.");
        return self::SUCCESS;
    }
}
