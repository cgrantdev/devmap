<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO changelog row for 08a140d: the coupon boost that stuck on Certified
 * Pep and changed its /coupon page title. Visible at /admin/seo/changelog.
 */
return new class extends Migration
{
    private const TITLE = 'Certified Pep: stuck WEEKEND35 promotion removed';

    public function up(): void
    {
        $exists = DB::table('seo_changes')
            ->where('title', self::TITLE)
            ->where('happened_at', '2026-11-14')
            ->exists();
        if ($exists) {
            return;
        }

        DB::table('seo_changes')->insert([
            'title' => self::TITLE,
            'summary' => 'Limited-time promotions never ended on vendors with no discount % of their own, so WEEKEND35 (35% off) stayed as Certified Pep\'s code on every page, including the /coupon/certified-pep title ("Certified Pep Coupon Code WEEKEND35 — 35% Off"). Fixed. The vendor is back to the code it had before the promotion, PMAP, with no discount %, so the page now reads "Certified Pep Coupon Code PMAP". No other vendor changed. Promotions also now end at the ET time entered; they were ending four to five hours early.',
            'category' => 'technical',
            'happened_at' => '2026-11-14',
            'actor' => 'colin',
            'commit_sha' => '08a140d',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('seo_changes')
            ->where('happened_at', '2026-11-14')
            ->where('title', self::TITLE)
            ->delete();
    }
};
