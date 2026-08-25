<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;

/**
 * Manually set rating + count for a review platform on one vendor.
 * Useful for Trustpilot, Google, PepReviewPro — sources our scraper
 * can't hit from a datacenter IP. Julia looks up the numbers on the
 * source page and runs this once per vendor.
 *
 * Usage:
 *   php artisan reviews:set-manual certified-pep trustpilot 4.7 240
 *   php artisan reviews:set-manual certified-pep google 4.9 384
 *   php artisan reviews:set-manual certified-pep pepreviewpro 4.5 12
 *
 * Values re-computed automatically:
 *   - vendor_settings.external_ratings_json[{platform}] gets rating + count
 *   - vendor_settings.external_rating_avg + external_rating_count get
 *     count-weighted-mean recalculated across all populated platforms
 */
class SetManualReviewData extends Command
{
    protected $signature = 'reviews:set-manual
                            {brand : Brand slug or id}
                            {platform : reviews_io | trustpilot | google | pepreviewpro}
                            {rating : Star rating (0.0 - 5.0)}
                            {count : Review count (integer)}';

    protected $description = 'Manually set rating + count for a review platform on one vendor.';

    private const PLATFORMS = [
        'reviews_io'   => ['label' => 'Reviews.io',    'url_field' => 'reviews_io_url'],
        'trustpilot'   => ['label' => 'Trustpilot',    'url_field' => 'trustpilot_url'],
        'google'       => ['label' => 'Google Reviews', 'url_field' => 'google_reviews_url'],
        'pepreviewpro' => ['label' => 'PepReviewPro',  'url_field' => 'pepreviewpro_url'],
    ];

    public function handle(): int
    {
        $ref = $this->argument('brand');
        $brand = is_numeric($ref) ? Brand::find((int) $ref) : Brand::where('slug', $ref)->first();
        if (!$brand) {
            $this->error("Brand not found: {$ref}");
            return self::FAILURE;
        }

        $platform = strtolower($this->argument('platform'));
        if (!isset(self::PLATFORMS[$platform])) {
            $this->error("Unknown platform '{$platform}'. Use one of: " . implode(', ', array_keys(self::PLATFORMS)));
            return self::FAILURE;
        }

        $rating = (float) $this->argument('rating');
        $count = (int) $this->argument('count');
        if ($rating < 0 || $rating > 5) {
            $this->error("Rating must be 0.0 - 5.0");
            return self::FAILURE;
        }

        $vs = $brand->vendorSetting;
        if (!$vs) {
            $this->error('Vendor has no vendorSetting row.');
            return self::FAILURE;
        }

        $meta = self::PLATFORMS[$platform];
        $urlField = $meta['url_field'];
        $url = $vs->$urlField;
        if (!$url) {
            $this->warn("No {$meta['label']} URL set on this vendor — the tile will still render on the storefront but users can't click through until you set {$urlField}.");
        }

        // Upsert into the JSON blob the storefront reads from.
        $existing = is_array($vs->external_ratings_json) ? $vs->external_ratings_json : [];
        $existing[$platform] = [
            'url' => $url,
            'platform' => $meta['label'],
            'rating' => $rating,
            'count' => $count,
            'manual' => true,
        ];

        // Recompute aggregate across every populated platform.
        $sum = 0; $totalCount = 0;
        foreach ($existing as $entry) {
            if (!empty($entry['rating']) && !empty($entry['count'])) {
                $sum += $entry['rating'] * $entry['count'];
                $totalCount += $entry['count'];
            }
        }
        $agg = $totalCount > 0 ? round($sum / $totalCount, 2) : null;

        $vs->forceFill([
            'external_ratings_json' => $existing,
            'external_rating_avg' => $agg,
            'external_rating_count' => $totalCount,
        ])->save();

        $this->info("Saved {$meta['label']} manually: {$rating} / {$count} reviews for {$brand->name}.");
        $this->line("New aggregate: " . ($agg ?? '—') . " across {$totalCount} reviews.");

        return self::SUCCESS;
    }
}
