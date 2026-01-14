<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Product;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add slug column if it doesn't exist
        if (!Schema::hasColumn('products', 'slug')) {
        Schema::table('products', function (Blueprint $table) {
                $table->string('slug', 191)->nullable()->after('name');
            });
        }

        // Generate slugs for existing products that don't have one
        Product::whereNull('slug')->orWhere('slug', '')->chunk(100, function ($products) {
            foreach ($products as $product) {
                if (empty($product->name)) {
                    // Use product ID if name is empty
                    $slug = 'product-' . $product->id;
                } else {
                    $slug = Str::slug($product->name);
                    
                    // If slug is empty after processing, use product ID
                    if (empty($slug)) {
                        $slug = 'product-' . $product->id;
                    }
                }
                
                // Truncate slug if too long (max 191 chars for MySQL)
                if (strlen($slug) > 191) {
                    $slug = substr($slug, 0, 191);
                }
                
                $existingSlug = $slug;
                $counter = 1;
                
                // Ensure unique slug
                while (Product::where('slug', $existingSlug)->where('id', '!=', $product->id)->exists()) {
                    $suffix = '-' . $counter;
                    $maxLength = 191 - strlen($suffix);
                    $baseSlug = substr($slug, 0, $maxLength);
                    $existingSlug = $baseSlug . $suffix;
                    $counter++;
                    
                    // Safety check to prevent infinite loop
                    if ($counter > 1000) {
                        $existingSlug = 'product-' . $product->id . '-' . time();
                        break;
                    }
                }
                
                $product->update(['slug' => $existingSlug]);
            }
        });

        // Make slug unique and not nullable (only if column exists)
        if (Schema::hasColumn('products', 'slug')) {
            Schema::table('products', function (Blueprint $table) {
                // First ensure all products have slugs
                DB::statement('UPDATE products SET slug = CONCAT("product-", id) WHERE slug IS NULL OR slug = ""');
                
                $table->string('slug', 191)->unique()->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
