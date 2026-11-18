<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO changelog rows for b13929c (Jake's Nov 17 request: blog body, FAQs
 * and featured image; vendor, calculator and coupon page copy). Visible
 * at /admin/seo/changelog.
 */
return new class extends Migration
{
    private const SHA = 'b13929c';

    private const ROWS = [
        [
            'title' => 'Agency editors: blog body and FAQs; vendor, calculator and coupon page copy',
            'summary' => 'Blog editor: an Article body field (visual or HTML; posts with tables open in HTML mode), a FAQ list shown on the post and used for its FAQPage (it starts from the post\'s current FAQs), and the featured image on the main tab. Brand SEO: H1, main page copy and FAQs. Calculator SEO: H1, intro, page copy and FAQs for each preset page and /calculator. New Coupon SEO: title, meta, H1, intro, copy and FAQs per /coupon/{vendor}. Each field replaces its part of the page; blank fields keep the default.',
            'category' => 'agency',
        ],
        [
            'title' => '/brand/{slug}: FAQs now shown on the page',
            'summary' => 'Vendor pages sent FAQPage JSON-LD (coupon code, where they ship, product count, rating) with no visible FAQ. The same FAQs now show at the bottom of the page, so the visible and schema counts match. FAQs added in Brand SEO replace them.',
            'category' => 'schema',
        ],
        [
            'title' => '/brand/{slug}: Why Choose bullets show again',
            'summary' => 'The Why Choose bullets edited in Brand SEO had not shown on vendor pages since Sep 22. They now show in the Why Choose panel when filled in; vendors without bullets show no default list.',
            'category' => 'content',
        ],
    ];

    public function up(): void
    {
        foreach (self::ROWS as $row) {
            $exists = DB::table('seo_changes')
                ->where('title', $row['title'])
                ->where('happened_at', '2026-11-19')
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('seo_changes')->insert($row + [
                'happened_at' => '2026-11-19',
                'actor' => 'colin',
                'commit_sha' => self::SHA,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('seo_changes')
            ->where('happened_at', '2026-11-19')
            ->whereIn('title', array_column(self::ROWS, 'title'))
            ->delete();
    }
};
