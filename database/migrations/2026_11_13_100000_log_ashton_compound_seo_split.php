<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('seo_changes')->insert([
            [
                'happened_at' => '2026-11-13',
                'title' => 'Compound SEO split: per-page Title/Meta/H1/Intro/FAQs',
                'summary' => 'Added products_custom_* and compare_custom_* fields on product_categories. /product/{slug} (Peptides) and /compare/{slug} (Compare) now author independent Title, Meta description, H1, Intro HTML and FAQ set via /admin/seo/categories. Old shared custom_* columns kept as fallback.',
                'category' => 'content',
                'actor' => 'colin',
                'commit_sha' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'happened_at' => '2026-11-13',
                'title' => '/product/{slug} schema parity with /compare',
                'summary' => 'Added BreadcrumbList, ItemList of vendor listings, and Product with AggregateOffer (lowPrice/highPrice/offerCount) on /product/{slug}. Previously only /compare/{slug} carried these.',
                'category' => 'schema',
                'actor' => 'colin',
                'commit_sha' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'happened_at' => '2026-11-13',
                'title' => 'Strip " - Peptidemap" tail on all non-home pages',
                'summary' => 'Server-side strip was already in app.blade.php; added matching client-side stripSiteSuffix() utility to ProductListing.vue so Vue hydration does not re-apply the suffix.',
                'category' => 'meta',
                'actor' => 'colin',
                'commit_sha' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('seo_changes')
            ->whereIn('title', [
                'Compound SEO split: per-page Title/Meta/H1/Intro/FAQs',
                '/product/{slug} schema parity with /compare',
                'Strip " - Peptidemap" tail on all non-home pages',
            ])
            ->delete();
    }
};
