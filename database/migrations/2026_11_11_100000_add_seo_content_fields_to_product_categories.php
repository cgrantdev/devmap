<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Editable content fields the SEO agency authors per compound, shared
 * by /product/{slug} (Peptides page) and /compare/{slug} (Compare
 * page). Both routes resolve through the same ProductCategory row,
 * so one editor covers both page types — 236 pages across 10 priority
 * compounds in the first batch (Advantage Collective, 2026-11-11).
 *
 * custom_h1               : override the visible H1 on both pages.
 * custom_intro_html       : safe HTML block rendered under the H1.
 * custom_faqs             : JSON array of { q, a } entries. Rendered
 *                           as an accordion AND emitted into the page's
 *                           FAQPage JSON-LD so the Q&A can surface in
 *                           Google rich results.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('custom_h1', 180)->nullable()->after('description');
            $table->text('custom_intro_html')->nullable()->after('custom_h1');
            $table->json('custom_faqs')->nullable()->after('custom_intro_html');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn(['custom_h1', 'custom_intro_html', 'custom_faqs']);
        });
    }
};
