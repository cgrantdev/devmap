<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites the nineteen category slugs that held spaces, "/" or "+".
 *
 * Slash slugs ("Selank/Semax") 404ed on /product/{slug}. Space slugs
 * ("HGH 191AA") loaded but were noindexed and left out of the sitemap.
 * Each row moves to a hyphen slug. The 595 slug also fixes its spelling
 * ("Tesamorlin" to tesamorelin-ipamorelin).
 *
 * A row is only touched while it still holds the exact old slug, and
 * only when no other row holds the new one. The old values are kept in
 * settings.category_slug_cleanup_2026_11_13 and down() puts them back.
 * App\Support\LegacyCategorySlug 301s every old URL to the new slug.
 */
return new class extends Migration
{
    private const BACKUP_KEY = 'category_slug_cleanup_2026_11_13';

    /** id => [old slug, new slug]. Same list as LegacyCategorySlug::RENAMED. */
    private const RENAMED = [
        595 => ['Tesamorlin / Ipamorelin', 'tesamorelin-ipamorelin'],
        564 => ['HGH 191AA', 'hgh-191aa'],
        596 => ['Selank/Semax', 'selank-semax'],
        591 => ['Retatrutide / Cagrilintide Blend', 'retatrutide-cagrilintide-blend'],
        547 => ['Vitamin B12', 'vitamin-b12'],
        546 => ['MIC + B12', 'mic-b12'],
        581 => ['Phosphate Buffered Saline', 'phosphate-buffered-saline'],
        577 => ['BPC-157 / TB500 / Cartalax', 'bpc-157-tb500-cartalax'],
        593 => ['Retatrutide / Tirzepatide Blend', 'retatrutide-tirzepatide-blend'],
        592 => ['Semaglutide / Cagrilintide Blend', 'semaglutide-cagrilintide-blend'],
        607 => ['AM Shred', 'am-shred'],
        605 => ['PM Matrix', 'pm-matrix'],
        594 => ['Adalank /  Adamax', 'adalank-adamax'],
        475 => ['Palmitoyl Pentapeptide-4', 'palmitoyl-pentapeptide-4'],
        580 => ['Sterile Water', 'sterile-water'],
        602 => ['Alpha-Klotho LR', 'alpha-klotho-lr'],
        601 => ['GHK-CU/BPC 157/KPV', 'ghk-cu-bpc-157-kpv'],
        588 => ['Thymosin Beta-4 Fragment 1-4', 'thymosin-beta-4-fragment-1-4'],
        587 => ['N-Acetyl Larazotide', 'n-acetyl-larazotide'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('product_categories')) {
            return;
        }

        $done = [];
        $skipped = [];

        foreach (self::RENAMED as $id => [$old, $new]) {
            $row = DB::table('product_categories')->where('id', $id)->first(['id', 'slug']);
            if (! $row || (string) $row->slug !== $old) {
                $skipped[$id] = $row ? 'slug is now '.$row->slug : 'row missing';
                continue;
            }

            $taken = DB::table('product_categories')
                ->where('id', '!=', $id)
                ->whereRaw('LOWER(slug) = ?', [strtolower($new)])
                ->exists();
            if ($taken) {
                $skipped[$id] = $new.' already used by another row';
                continue;
            }

            DB::table('product_categories')->where('id', $id)->update([
                'slug' => $new,
                'updated_at' => now(),
            ]);
            $done[$id] = ['old' => $old, 'new' => $new];
        }

        if (Schema::hasTable('settings')) {
            $previous = DB::table('settings')->where('key', self::BACKUP_KEY)->value('value');
            $previous = is_string($previous) ? (json_decode($previous, true) ?: []) : [];

            DB::table('settings')->updateOrInsert(
                ['key' => self::BACKUP_KEY],
                [
                    'value' => json_encode([
                        'renamed' => $done + ($previous['renamed'] ?? []),
                        'skipped' => $skipped,
                        'ran_at' => now()->toIso8601String(),
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->bustCaches();
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_categories')) {
            return;
        }

        foreach (self::RENAMED as $id => [$old, $new]) {
            DB::table('product_categories')
                ->where('id', $id)
                ->where('slug', $new)
                ->update(['slug' => $old, 'updated_at' => now()]);
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', self::BACKUP_KEY)->delete();
        }

        $this->bustCaches();
    }

    /**
     * Listing payloads, the sitemap and compare hub hold slugs. Version
     * bump for FrontendCache entries; direct keys for the rest.
     */
    private function bustCaches(): void
    {
        try {
            \App\Support\FrontendCache::bust();
            Cache::forget(\App\Http\Controllers\Frontend\SitemapController::CACHE_KEY);
        } catch (\Throwable $e) {
            // A cache outage must not fail the deploy; entries expire on TTL.
        }
    }
};
