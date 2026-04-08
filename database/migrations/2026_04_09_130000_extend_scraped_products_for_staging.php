<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scraped_products', function (Blueprint $table) {
            // Direct FKs so we can query the staging table without always joining scraping_configs
            if (!Schema::hasColumn('scraped_products', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('scraping_config_id')->constrained('brands')->nullOnDelete();
            }
            // Nullable link to the live product this staging row has been promoted to.
            if (!Schema::hasColumn('scraped_products', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('brand_id')->constrained('products')->nullOnDelete();
            }
            // Ingestion source
            if (!Schema::hasColumn('scraped_products', 'source_type')) {
                $table->string('source_type', 32)->default('css_scrape')->after('product_id');
            }
            // External vendor-side ID (Woo product ID, Shopify handle, etc) — used for upserts
            if (!Schema::hasColumn('scraped_products', 'external_id')) {
                $table->string('external_id', 191)->nullable()->after('source_type');
            }
            // Review workflow
            if (!Schema::hasColumn('scraped_products', 'status')) {
                $table->string('status', 16)->default('pending')->after('external_id');
                // values: pending, approved (= promoted to products), rejected
            }
            // Richer Woo fields
            if (!Schema::hasColumn('scraped_products', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('scraped_products', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            }
            // Price change tracking
            if (!Schema::hasColumn('scraped_products', 'previous_price')) {
                $table->decimal('previous_price', 10, 2)->nullable()->after('discount_price');
            }
            if (!Schema::hasColumn('scraped_products', 'price_changed_at')) {
                $table->timestamp('price_changed_at')->nullable()->after('previous_price');
            }

            $table->index(['brand_id', 'status']);
            $table->index(['source_type', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('scraped_products', function (Blueprint $table) {
            $table->dropIndex(['scraped_products_brand_id_status_index']);
            $table->dropIndex(['scraped_products_source_type_external_id_index']);
            $table->dropConstrainedForeignId('brand_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn([
                'source_type',
                'external_id',
                'status',
                'description',
                'discount_price',
                'previous_price',
                'price_changed_at',
            ]);
        });
    }
};
