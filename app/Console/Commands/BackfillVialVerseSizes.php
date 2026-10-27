<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * VialVerse's feed emits products with the size baked into the URL
 * path (/peptides/bpc-157/10mg) and the display name ("BPC-157 10mg"),
 * but doesn't fill a size field — so size_mg lands empty for every
 * product. Julia 10/27: 75 of ~88 VialVerse products keep showing up
 * on the "no size set" admin filter.
 *
 * This command parses the size out of the product_url's final path
 * segment and writes it to size_mg. Idempotent — rows that already
 * have a size are untouched.
 */
class BackfillVialVerseSizes extends Command
{
    protected $signature = 'vialverse:backfill-sizes {--dry-run : Report without writing}';
    protected $description = 'Parse size from product_url and populate size_mg for VialVerse rows.';

    public function handle(): int
    {
        $brand = Brand::where('slug', 'vialverse')->first();
        if (!$brand) {
            $this->error('VialVerse brand not found.');
            return self::FAILURE;
        }

        $products = Product::where('brand_id', $brand->id)->get();
        $set = 0; $skipped = 0; $unparsed = 0;

        foreach ($products as $p) {
            if (trim((string) $p->size_mg) !== '') {
                $skipped++;
                continue;
            }
            $size = $this->sizeFromUrl($p->product_url);
            if (!$size) {
                $unparsed++;
                $this->line("  ? no size from URL: {$p->name} ({$p->product_url})");
                continue;
            }
            $this->line("  → {$p->name}  size={$size}");
            if (!$this->option('dry-run')) {
                $p->size_mg = $size;
                $p->save();
            }
            $set++;
        }

        $this->newLine();
        $this->info("Done. {$set} sizes set, {$skipped} already had a size, {$unparsed} could not be parsed.");
        return self::SUCCESS;
    }

    /**
     * VialVerse URL shapes:
     *   /peptides/bpc-157/10mg               → 10mg
     *   /peptides/5-amino-1mq/50mg           → 50mg
     *   /lab-essentials/bacteriostatic-water/3ml  → 3ml
     *   /peptides/bpc-157-tb-500/10mg-5mg-5mg → 10mg/5mg/5mg (blend)
     *   /peptides/bpc-157                    → (no size segment)
     *   /lab-essentials/3ml-vial-storage-box-10-vials → (not a sized product)
     */
    private function sizeFromUrl(?string $url): ?string
    {
        if (!$url) return null;
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $segments = array_values(array_filter(explode('/', $path), fn ($s) => $s !== ''));
        if (count($segments) < 2) return null;
        $last = strtolower(end($segments));

        // Single-size pattern: "10mg", "50mcg", "3ml", "100iu"
        if (preg_match('~^(\d+(?:\.\d+)?)(mg|mcg|ml|iu)$~', $last, $m)) {
            return $m[1] . $m[2];
        }
        // Blend pattern: "10mg-5mg-5mg" → "10mg/5mg/5mg"
        if (preg_match('~^(\d+(?:\.\d+)?(?:mg|mcg)(?:-\d+(?:\.\d+)?(?:mg|mcg)){1,3})$~', $last)) {
            return str_replace('-', '/', $last);
        }
        return null;
    }
}
