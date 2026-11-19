<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO changelog row for 6919ebe (strict RUO pass 2 follow-up: metas and
 * category descriptions). Visible at /admin/seo/changelog.
 */
return new class extends Migration
{
    private const SHA = '6919ebe';

    private const ROWS = [
        [
            'title' => 'Encyclopedia, /products and /bulk metas: dosing wording removed',
            'summary' => 'Fifteen encyclopedia meta and OG descriptions (e.g. Ibutamoren, Oxytocin, Gonadorelin, Vitamin B12, BAM15, Setmelanotide, Teriparatide) lost a trailing "No dosing is given here." sentence; Orexin-A lost "No dosing."; ghrp-2 OG text says "Not an FDA-approved U.S. drug" instead of "therapeutic". /products meta: "Filter by compound, purity, price, and dose" now ends "vial size". /bulk default meta no longer says "long-cycle protocols". Product category descriptions (shown on product pages) lost the same dosing sentences.',
            'category' => 'meta',
        ],
    ];

    public function up(): void
    {
        foreach (self::ROWS as $row) {
            $exists = DB::table('seo_changes')
                ->where('title', $row['title'])
                ->where('happened_at', '2026-11-20')
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('seo_changes')->insert($row + [
                'happened_at' => '2026-11-20',
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
            ->where('happened_at', '2026-11-20')
            ->whereIn('title', array_column(self::ROWS, 'title'))
            ->delete();
    }
};
