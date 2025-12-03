<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add brand_id column (nullable first for migration)
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('id')->constrained('brands')->onDelete('cascade');
        });

        // Migrate existing data: for each vendor_setting with user_id, find/create brand and link it
        $vendorSettings = DB::table('vendor_settings')->whereNotNull('user_id')->get();
        
        foreach ($vendorSettings as $setting) {
            // Find or create brand for this user
            $brand = DB::table('brands')
                ->where('user_id', $setting->user_id)
                ->first();
            
            if ($brand) {
                // Link vendor_setting to brand
                DB::table('vendor_settings')
                    ->where('id', $setting->id)
                    ->update(['brand_id' => $brand->id]);
            } else {
                // Create brand for this user if it doesn't exist
                $user = DB::table('users')->where('id', $setting->user_id)->first();
                if ($user) {
                    $brandId = DB::table('brands')->insertGetId([
                        'name' => $user->name,
                        'user_id' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Link vendor_setting to brand
                    DB::table('vendor_settings')
                        ->where('id', $setting->id)
                        ->update(['brand_id' => $brandId]);
                    
                    // Update brand with vendor_setting_id
                    DB::table('brands')
                        ->where('id', $brandId)
                        ->update(['vendor_setting_id' => $setting->id]);
                }
            }
        }

        // Make brand_id required and remove user_id
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable(false)->change();
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
        });

        // Migrate data back: get user_id from brand
        $vendorSettings = DB::table('vendor_settings')->whereNotNull('brand_id')->get();
        
        foreach ($vendorSettings as $setting) {
            $brand = DB::table('brands')->where('id', $setting->brand_id)->first();
            if ($brand && $brand->user_id) {
                DB::table('vendor_settings')
                    ->where('id', $setting->id)
                    ->update(['user_id' => $brand->user_id]);
            }
        }

        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });
    }
};
