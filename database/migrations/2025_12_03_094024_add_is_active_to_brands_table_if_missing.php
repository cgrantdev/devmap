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
        // Add slug column if it doesn't exist
        if (!Schema::hasColumn('brands', 'slug')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });

            // Generate slugs for existing brands
            $brands = \DB::table('brands')->get();
            foreach ($brands as $brand) {
                $slug = \Illuminate\Support\Str::slug($brand->name);
                $existingSlug = $slug;
                $counter = 1;
                
                // Ensure unique slug
                while (\DB::table('brands')->where('slug', $existingSlug)->where('id', '!=', $brand->id)->exists()) {
                    $existingSlug = $slug . '-' . $counter;
                    $counter++;
                }
                
                \DB::table('brands')
                    ->where('id', $brand->id)
                    ->update(['slug' => $existingSlug]);
            }

            // Make slug unique and not nullable
            Schema::table('brands', function (Blueprint $table) {
                $table->string('slug')->unique()->nullable(false)->change();
            });
        }

        // Add is_active column if it doesn't exist
        if (!Schema::hasColumn('brands', 'is_active')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('slug');
            });
        }

        // Remove vendor_setting_id if it exists (redundant - VendorSetting has brand_id)
        if (Schema::hasColumn('brands', 'vendor_setting_id')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropForeign(['vendor_setting_id']);
                $table->dropColumn('vendor_setting_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('brands', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
};
