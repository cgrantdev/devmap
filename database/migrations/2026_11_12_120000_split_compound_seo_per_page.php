<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Split agency-editable SEO fields on product_categories so /product/{slug}
 * (Peptides page) and /compare/{slug} (Compare page) can own distinct copy.
 *
 * Ashton (Advantage Collective, 2026-11-11): the two pages shared one H1,
 * intro, FAQ set — Google saw duplicate content and the pages competed
 * instead of carrying distinct intents. Split into:
 *
 *   products_custom_title              (<title> for /product/{slug})
 *   products_custom_meta_description   (meta description)
 *   products_custom_h1                 (visible + bot H1)
 *   products_custom_intro_html         (intro HTML under H1)
 *   products_custom_faqs               (FAQPage schema + visible accordion)
 *
 *   compare_custom_title               (same, for /compare/{slug})
 *   compare_custom_meta_description
 *   compare_custom_h1
 *   compare_custom_intro_html
 *   compare_custom_faqs
 *
 * Existing custom_h1 / custom_intro_html / custom_faqs values (if any)
 * are copied into BOTH buckets so content already live stays live on
 * both pages — the agency can then diverge them. Old columns are kept
 * as backward-compat until the frontend fully migrates in a later PR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $t) {
            $t->string('products_custom_title', 200)->nullable()->after('custom_faqs');
            $t->text('products_custom_meta_description')->nullable()->after('products_custom_title');
            $t->string('products_custom_h1', 180)->nullable()->after('products_custom_meta_description');
            $t->text('products_custom_intro_html')->nullable()->after('products_custom_h1');
            $t->json('products_custom_faqs')->nullable()->after('products_custom_intro_html');

            $t->string('compare_custom_title', 200)->nullable()->after('products_custom_faqs');
            $t->text('compare_custom_meta_description')->nullable()->after('compare_custom_title');
            $t->string('compare_custom_h1', 180)->nullable()->after('compare_custom_meta_description');
            $t->text('compare_custom_intro_html')->nullable()->after('compare_custom_h1');
            $t->json('compare_custom_faqs')->nullable()->after('compare_custom_intro_html');
        });

        // Copy existing shared values into both new buckets so anything
        // already authored keeps working on both pages. NULL stays NULL.
        DB::table('product_categories')
            ->whereNotNull('custom_h1')
            ->update([
                'products_custom_h1' => DB::raw('custom_h1'),
                'compare_custom_h1' => DB::raw('custom_h1'),
            ]);
        DB::table('product_categories')
            ->whereNotNull('custom_intro_html')
            ->update([
                'products_custom_intro_html' => DB::raw('custom_intro_html'),
                'compare_custom_intro_html' => DB::raw('custom_intro_html'),
            ]);
        DB::table('product_categories')
            ->whereNotNull('custom_faqs')
            ->update([
                'products_custom_faqs' => DB::raw('custom_faqs'),
                'compare_custom_faqs' => DB::raw('custom_faqs'),
            ]);
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $t) {
            $t->dropColumn([
                'products_custom_title',
                'products_custom_meta_description',
                'products_custom_h1',
                'products_custom_intro_html',
                'products_custom_faqs',
                'compare_custom_title',
                'compare_custom_meta_description',
                'compare_custom_h1',
                'compare_custom_intro_html',
                'compare_custom_faqs',
            ]);
        });
    }
};
