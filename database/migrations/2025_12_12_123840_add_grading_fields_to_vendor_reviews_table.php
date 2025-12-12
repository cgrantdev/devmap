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
        Schema::table('vendor_reviews', function (Blueprint $table) {
            $table->tinyInteger('shipping_time')->nullable()->after('rating');
            $table->tinyInteger('customer_service')->nullable()->after('shipping_time');
            $table->tinyInteger('quality')->nullable()->after('customer_service');
            $table->tinyInteger('cost')->nullable()->after('quality');
            $table->tinyInteger('packaging')->nullable()->after('cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_reviews', function (Blueprint $table) {
            $table->dropColumn(['shipping_time', 'customer_service', 'quality', 'cost', 'packaging']);
        });
    }
};
