<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Split agency-editable SEO fields on product_categories so the Peptides
 * page (/product/{slug}) and the Compare page (/compare/{slug}) can own
 * distinct copy for distinct search intents.
 *
 * Adds 10 columns: products_custom_* and compare_custom_* for
 * title, meta description, h1, intro html, and faqs (json).
 *
 * Seeds the new columns from the legacy custom_h1 / custom_intro_html /
 * custom_faqs so any content already live keeps rendering on both pages,
 * until the agency diverges them.
 *
 * Re-runnable (Nov 20 2026): an earlier run in production added the
 * columns and stopped before it was recorded, so every later "migrate"
 * failed on "Duplicate column name 'products_custom_title'" and the
 * migrations behind it never ran. Only missing columns are added, and the
 * legacy copy runs only when the columns are new: the agency has been
 * editing them since Nov 12 and the copy would overwrite their work.
 * Pages fall back to the legacy fields wherever a new one is empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        $alreadyThere = Schema::hasColumn('product_categories', 'products_custom_title');

        $columns = [
            'products_custom_title' => fn (Blueprint $t) => $t->string('products_custom_title', 200)->nullable()->after('custom_faqs'),
            'products_custom_meta_description' => fn (Blueprint $t) => $t->text('products_custom_meta_description')->nullable()->after('products_custom_title'),
            'products_custom_h1' => fn (Blueprint $t) => $t->string('products_custom_h1', 180)->nullable()->after('products_custom_meta_description'),
            'products_custom_intro_html' => fn (Blueprint $t) => $t->text('products_custom_intro_html')->nullable()->after('products_custom_h1'),
            'products_custom_faqs' => fn (Blueprint $t) => $t->json('products_custom_faqs')->nullable()->after('products_custom_intro_html'),
            'compare_custom_title' => fn (Blueprint $t) => $t->string('compare_custom_title', 200)->nullable()->after('products_custom_faqs'),
            'compare_custom_meta_description' => fn (Blueprint $t) => $t->text('compare_custom_meta_description')->nullable()->after('compare_custom_title'),
            'compare_custom_h1' => fn (Blueprint $t) => $t->string('compare_custom_h1', 180)->nullable()->after('compare_custom_meta_description'),
            'compare_custom_intro_html' => fn (Blueprint $t) => $t->text('compare_custom_intro_html')->nullable()->after('compare_custom_h1'),
            'compare_custom_faqs' => fn (Blueprint $t) => $t->json('compare_custom_faqs')->nullable()->after('compare_custom_intro_html'),
        ];
        foreach ($columns as $name => $add) {
            if (! Schema::hasColumn('product_categories', $name)) {
                Schema::table('product_categories', function (Blueprint $t) use ($add) {
                    $add($t);
                });
            }
        }

        if ($alreadyThere) {
            return;
        }

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
