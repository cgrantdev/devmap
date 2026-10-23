<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Console\Command;

/**
 * Fix a product's product_category_id by lookup. Takes product id +
 * category name OR slug. Addresses miscategorized rows (e.g.
 * Recherche's SLU-PP-332 landed under NAD+ via the generic
 * DiscoverProductsJob matcher).
 */
class ProductRecategorize extends Command
{
    protected $signature = 'product:recategorize {product_id} {category}';
    protected $description = 'Set a product\'s category by id + category name/slug.';

    public function handle(): int
    {
        $pid = (int) $this->argument('product_id');
        $catArg = $this->argument('category');

        $product = Product::find($pid);
        if (!$product) {
            $this->error("Product {$pid} not found.");
            return self::FAILURE;
        }

        $cat = ProductCategory::where('slug', $catArg)
            ->orWhere('name', $catArg)
            ->first();
        if (!$cat) {
            $this->error("Category '{$catArg}' not found (tried slug + name).");
            return self::FAILURE;
        }

        $oldCat = ProductCategory::find($product->product_category_id);
        $product->product_category_id = $cat->id;
        $product->save();

        $this->info(sprintf(
            "Product %d (%s): category %s → %s",
            $pid,
            $product->name,
            $oldCat?->name ?? '(none)',
            $cat->name
        ));
        return self::SUCCESS;
    }
}
