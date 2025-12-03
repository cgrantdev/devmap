<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create product categories from existing product names
        $productNames = DB::table('products')
            ->select('name')
            ->distinct()
            ->whereNotNull('name')
            ->get();

        foreach ($productNames as $productName) {
            $slug = Str::slug($productName->name);
            $existingSlug = $slug;
            $counter = 1;
            
            // Ensure unique slug
            while (DB::table('product_categories')->where('slug', $existingSlug)->exists()) {
                $existingSlug = $slug . '-' . $counter;
                $counter++;
            }

            DB::table('product_categories')->insert([
                'name' => $productName->name,
                'slug' => $existingSlug,
                'description' => null,
                'image_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add new columns to products table
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_category_id')->nullable()->after('user_id')->constrained('product_categories')->nullOnDelete();
            $table->decimal('size_mg', 10, 2)->nullable()->after('second_price');
            $table->enum('availability', ['in_stock', 'out_of_stock', 'pre_order'])->default('in_stock')->after('size_mg');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('availability');
        });

        // Migrate existing data: link products to categories by name
        $categories = DB::table('product_categories')->get();
        foreach ($categories as $category) {
            DB::table('products')
                ->where('name', $category->name)
                ->update(['product_category_id' => $category->id]);
        }

        // Remove the name column after migration (we'll do this in a separate migration to be safe)
        // Schema::table('products', function (Blueprint $table) {
        //     $table->dropColumn('name');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
            $table->dropColumn(['product_category_id', 'size_mg', 'availability', 'status']);
        });

        // Note: We don't drop product_categories table here as it might have been populated
        // This should be handled by dropping the create_product_categories_table migration
    }
};
