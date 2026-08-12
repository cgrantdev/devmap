<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Vendor-side identifier (their SKU / internal product id).
            // Combined with brand_id, this is the idempotency key for Push
            // API upserts — a vendor can PUT the same external_id repeatedly
            // and we update in place instead of creating duplicates.
            if (!Schema::hasColumn('products', 'external_id')) {
                $table->string('external_id', 191)->nullable()->after('slug');
                $table->unique(['brand_id', 'external_id'], 'products_brand_external_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'external_id')) {
                $table->dropUnique('products_brand_external_unique');
                $table->dropColumn('external_id');
            }
        });
    }
};
