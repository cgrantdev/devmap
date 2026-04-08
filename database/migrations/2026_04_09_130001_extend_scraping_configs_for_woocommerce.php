<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('scraping_configs', function (Blueprint $table) {
            // 'css_scrape' (default) | 'woo_api' | 'wp_rest' | 'xml_feed'
            if (!Schema::hasColumn('scraping_configs', 'type')) {
                $table->string('type', 32)->default('css_scrape')->after('vendor_name');
            }
            // Base store URL for API-based ingestion (e.g. https://example.com)
            if (!Schema::hasColumn('scraping_configs', 'store_url')) {
                $table->string('store_url', 512)->nullable()->after('products_url');
            }
            // Encrypted auth credentials (consumer_key + consumer_secret for Woo, etc.)
            if (!Schema::hasColumn('scraping_configs', 'auth_credentials')) {
                $table->text('auth_credentials')->nullable()->after('store_url');
            }
            // Trusted vendors skip the review queue
            if (!Schema::hasColumn('scraping_configs', 'auto_promote')) {
                $table->boolean('auto_promote')->default(false)->after('auth_credentials');
            }

            $table->index(['type', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('scraping_configs', function (Blueprint $table) {
            $table->dropIndex(['scraping_configs_type_enabled_index']);
            $table->dropColumn(['type', 'store_url', 'auth_credentials', 'auto_promote']);
        });
    }
};
