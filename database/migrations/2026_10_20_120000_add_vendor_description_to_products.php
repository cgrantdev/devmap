<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds products.vendor_description — the vendor's own product copy as
 * scraped from the retail page. Used in place of our boilerplate
 * description where available, to fix the near-duplicate dedup problem
 * Google was collapsing most product pages under (Oct 2026 core update).
 *
 * The existing `description` column stays as a fallback — we don't
 * clobber what's already there, we add a preferred source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->longText('vendor_description')->nullable()->after('description');
            $table->timestamp('vendor_description_fetched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['vendor_description', 'vendor_description_fetched_at']);
        });
    }
};
