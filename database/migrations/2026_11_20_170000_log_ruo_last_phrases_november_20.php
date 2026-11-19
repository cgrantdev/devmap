<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO changelog row for a40b215 (strict RUO pass 2, last phrases from the
 * live re-scan). Visible at /admin/seo/changelog.
 */
return new class extends Migration
{
    private const SHA = 'a40b215';

    private const ROWS = [
        [
            'title' => '/products OG description and two encyclopedia sentences: last dosing/protocol wording',
            'summary' => '/products OG description: "Filter by compound, purity, price, and dose across every vendor" now says "vial size". CJC-1295 conclusion: "invent clinical protocols" is now "invent clinical guidance". Triptorelin background: "as part of assisted reproductive technology protocols" is now "in assisted reproductive technology".',
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
