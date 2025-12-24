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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('auto_scraped')->default(false)->after('updated_at');
            $table->boolean('manual_override')->default(false)->after('auto_scraped');
            $table->timestamp('last_scraped_at')->nullable()->after('manual_override');
            $table->string('source_url')->nullable()->after('last_scraped_at'); // Original product URL from vendor site
            $table->enum('stock_status', ['in-stock', 'low-stock', 'out-of-stock'])->nullable()->after('source_url');
            
            $table->index('auto_scraped');
            $table->index('manual_override');
            $table->index('last_scraped_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['auto_scraped']);
            $table->dropIndex(['manual_override']);
            $table->dropIndex(['last_scraped_at']);
            $table->dropColumn(['auto_scraped', 'manual_override', 'last_scraped_at', 'source_url', 'stock_status']);
        });
    }
};
