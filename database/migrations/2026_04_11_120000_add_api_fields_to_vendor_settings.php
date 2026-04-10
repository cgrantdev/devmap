<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->string('api_platform', 32)->nullable()->after('shop_url'); // medusa, woocommerce, shopify, custom
            $table->text('api_key')->nullable()->after('api_platform'); // encrypted at application level
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn(['api_platform', 'api_key']);
        });
    }
};
