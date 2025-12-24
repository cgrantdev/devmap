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
        if (!Schema::hasTable('banners')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            // Drop old foreign key and column
            $table->dropForeign(['vendor_id']);
            $table->dropIndex(['vendor_id']);
            $table->dropColumn('vendor_id');
            
            // Add new brand_id column
            $table->foreignId('brand_id')->nullable()->after('active')->constrained('brands')->onDelete('set null');
            $table->index('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('banners')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            // Drop new foreign key and column
            $table->dropForeign(['brand_id']);
            $table->dropIndex(['brand_id']);
            $table->dropColumn('brand_id');
            
            // Restore old vendor_id column
            $table->foreignId('vendor_id')->nullable()->after('active')->constrained('users')->nullOnDelete();
            $table->index('vendor_id');
        });
    }
};
