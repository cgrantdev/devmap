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

        $groups = $products->groupBy(fn ($p) => strtolower(trim((string) $p->name)));
        $dupGroups = $groups->filter(fn ($g) => $g->count() > 1);

        $this->info("{$products->count()} total products. {$dupGroups->count()} duplicate name-groups covering " . $dupGroups->sum(fn ($g) => $g->count()) . ' rows.');
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
