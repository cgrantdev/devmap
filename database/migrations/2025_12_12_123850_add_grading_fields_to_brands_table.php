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
        Schema::table('brands', function (Blueprint $table) {
            $table->decimal('shipping_time', 3, 1)->nullable()->default(0)->after('rating_count');
            $table->decimal('customer_service', 3, 1)->nullable()->default(0)->after('shipping_time');
            $table->decimal('quality', 3, 1)->nullable()->default(0)->after('customer_service');
            $table->decimal('cost', 3, 1)->nullable()->default(0)->after('quality');
            $table->decimal('packaging', 3, 1)->nullable()->default(0)->after('cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['shipping_time', 'customer_service', 'quality', 'cost', 'packaging']);
        });
    }
};
