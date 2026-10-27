<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Julia 10/27: VialVerse (brand 160) has ~75 duplicate rows for products
 * with "no size set". Report-only — groups by LOWER(TRIM(name)) and shows
 * every row in each duplicate cluster with its external_id, product_url,
 * size_mg, and price, so we can see which key the daily feed sync is
 * failing to dedup on.
 */
class VialVerseDedupDump extends Command
{
    protected $signature = 'vialverse:dedup-dump';
    protected $description = 'Report-only: show duplicate VialVerse product clusters.';

    public function handle(): int
    {
        $brand = Brand::where('slug', 'vialverse')->first();
        if (!$brand) {
            $this->error('VialVerse brand not found.');
            return self::FAILURE;
        }

        $products = Product::where('brand_id', $brand->id)
            ->orderBy('name')->orderBy('id')
            ->get(['id', 'name', 'size_mg', 'external_id', 'product_url', 'price']);

        // Count products with no size_mg — this is what Julia likely means
        // by "no size set" repeating.
        $noSize = $products->filter(fn ($p) => trim((string) $p->size_mg) === '')->count();
        $withSize = $products->count() - $noSize;
        $this->info("{$products->count()} total products: {$withSize} have size_mg set, {$noSize} do not.");
        $this->newLine();

        // Duplicate detection by COMPOUND (name minus trailing size).
        $groups = $products->groupBy(function ($p) {
            $n = strtolower(trim((string) $p->name));
            // Strip trailing size patterns like "10mg", "100mcg", "10mg/5mg/5mg".
            $n = preg_replace('~\s+\d+(\.\d+)?\s*(mg|mcg|ml|iu)(\s*/\s*\d+(\.\d+)?\s*(mg|mcg|ml|iu))*(\s*\(.*\))?$~i', '', $n);
            return trim($n);
        });
        $dupGroups = $groups->filter(fn ($g) => $g->count() > 1);

        $this->info("Grouping by compound (size stripped): {$dupGroups->count()} compound(s) with more than one row.");
        $this->newLine();

        foreach ($dupGroups as $name => $g) {
            $this->line("[{$name}]");
            foreach ($g as $p) {
                $sku = $p->external_id ?: '(no sku)';
                $size = $p->size_mg ?: '(no size)';
                $this->line(sprintf('   id=%d  size=%-12s  sku=%-30s  price=%-7s  url=%s',
                    $p->id, $size, $sku, $p->price, $p->product_url ?: '(none)'));
            }
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
