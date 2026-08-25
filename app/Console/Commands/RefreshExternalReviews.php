<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Services\ExternalReviewFetcher;
use Illuminate\Console\Command;

/**
 * Refresh external review aggregates for one vendor or all of them.
 * Scheduled weekly by default; can be run ad-hoc for testing.
 *
 * Usage:
 *   php artisan reviews:refresh                    # all vendors with URLs
 *   php artisan reviews:refresh certified-pep      # single brand
 */
class RefreshExternalReviews extends Command
{
    protected $signature = 'reviews:refresh {brand? : Brand slug or id (optional — omit to refresh all)}';
    protected $description = 'Fetch + cache external review aggregates onto vendor_settings.';

    public function handle(ExternalReviewFetcher $fetcher): int
    {
        $ref = $this->argument('brand');

        $brands = $ref
            ? Brand::where('slug', $ref)->orWhere('id', is_numeric($ref) ? (int) $ref : 0)->get()
            : Brand::where('is_active', true)
                ->whereHas('vendorSetting', function ($q) {
                    $q->where(function ($qq) {
                        $qq->whereNotNull('reviews_io_url')
                           ->orWhereNotNull('trustpilot_url')
                           ->orWhereNotNull('google_reviews_url')
                           ->orWhereNotNull('pepreviewpro_url');
                    });
                })->get();

        if ($brands->isEmpty()) {
            $this->warn('No matching brands with review URLs configured.');
            return self::SUCCESS;
        }

        $ok = 0; $skipped = 0;
        foreach ($brands as $brand) {
            $vs = $brand->vendorSetting;
            if (!$vs) { $skipped++; continue; }

            $this->line("Refreshing: {$brand->name}");
            $sources = $fetcher->refresh($vs);
            $agg = $vs->fresh();
            $this->info("  → " . count($sources) . " source(s) — aggregate: ★ " . ($agg->external_rating_avg ?? '—') . " across " . ($agg->external_rating_count ?? 0) . " reviews");
            $ok++;

            // Polite pacing between fetches — we're hitting third-party sites.
            usleep(1_500_000);
        }

        $this->info("Refreshed {$ok} brand(s); skipped {$skipped}.");
        return self::SUCCESS;
    }
}
