<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Dump every product we have for a brand with name, size, price,
 * stock status, and the vendor-site URL. Used when a vendor contact
 * asks us to compare our catalog against theirs (Mike at Recherche
 * 10/23 — he wants our 37 scraped URLs vs his WooCommerce export).
 *
 * Output is CSV-ready so Julia can paste into a sheet.
 */
class VendorProductsExport extends Command
{
    protected $signature = 'vendor:products-export {brand}';
    protected $description = 'CSV dump of a brand\'s products (id,name,size,price,stock,url).';

    public function handle(): int
    {
        $arg = $this->argument('brand');
        $brand = ctype_digit((string) $arg)
            ? Brand::find((int) $arg)
            : Brand::where('slug', $arg)->first();
        if (!$brand) {
            $this->error("Brand '{$arg}' not found.");
            return self::FAILURE;
        }

        $products = Product::where('brand_id', $brand->id)
            ->orderBy('name')
            ->get(['id', 'name', 'size_mg', 'price', 'discount_price', 'stock_status', 'product_url']);

        $this->line("id,name,size,price,discount,stock,url");
        foreach ($products as $p) {
            $this->line(implode(',', [
                $p->id,
                '"' . str_replace('"', '""', (string) $p->name) . '"',
                '"' . (string) $p->size_mg . '"',
                $p->price,
                $p->discount_price,
                $p->stock_status,
                '"' . (string) $p->product_url . '"',
            ]));
        }
        $this->newLine();
        $this->info("Total: {$products->count()} products for {$brand->name}.");

        // Flag duplicates — same name + size pair appearing more than once.
        $dupes = $products->groupBy(fn ($p) => strtolower(trim($p->name)) . '|' . strtolower(trim((string) $p->size_mg)))
            ->filter(fn ($g) => $g->count() > 1);
        if ($dupes->isNotEmpty()) {
            $this->newLine();
            $this->warn("Potential duplicates (same name + size):");
            foreach ($dupes as $key => $rows) {
                $ids = $rows->pluck('id')->implode(', ');
                $this->warn("  [{$ids}]  {$rows->first()->name}  size={$rows->first()->size_mg}");
            }
        }
        return self::SUCCESS;
    }
}
