<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->string('website')->nullable()->after('shop_url');
            $table->integer('founded_year')->nullable()->after('website');
            $table->string('coupon_code')->nullable()->after('founded_year');
            $table->text('shipping_info')->nullable()->after('coupon_code');
            $table->text('return_policy')->nullable()->after('shipping_info');
            $table->string('business_hours')->nullable()->after('return_policy');
            $table->string('banner_image_url')->nullable()->after('banner'); // URL alternative to file upload
            $table->boolean('top_vendor')->default(false)->after('status');
            $table->boolean('featured')->default(false)->after('top_vendor');
            $table->boolean('is_partner')->default(false)->after('featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn([
                'website',
                'founded_year',
                'coupon_code',
                'shipping_info',
                'return_policy',
                'business_hours',
                'banner_image_url',
                'top_vendor',
                'featured',
                'is_partner'
            ]);
        });
    }
};
