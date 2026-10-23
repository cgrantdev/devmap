<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Console\Command;

/**
 * Flag products whose raw name mentions a compound different from the
 * one they're categorized under. Catches miscategorizations like
 * Recherche's "SLU PP 332 5 mg Canada" being filed under NAD+.
 */
class VendorCategoryAudit extends Command
{
    protected $signature = 'vendor:category-audit {brand}';
    protected $description = 'Flag products where name mentions a different compound than the assigned category.';

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

        // Known compound keywords → actual category slug mappings.
        $compoundHints = [
            'slu pp 332' => 'slu-pp-332', 'slu-pp-332' => 'slu-pp-332',
            'kpv' => 'kpv',
            'kisspeptin' => 'kisspeptin',
            'tirzepatide' => 'tirzepatide', 'glp-2' => 'tirzepatide', 'glp 2' => 'tirzepatide',
            'semaglutide' => 'semaglutide', 'glp-1' => 'semaglutide', 'glp 1' => 'semaglutide',
            'retatrutide' => 'retatrutide', 'glp-3' => 'retatrutide', 'glp 3' => 'retatrutide',
            'bpc-157' => 'bpc-157', 'bpc 157' => 'bpc-157',
            'tb-500' => 'tb-500', 'tb 500' => 'tb-500',
            'ghk-cu' => 'ghk-cu', 'ghk cu' => 'ghk-cu',
            'mots-c' => 'mots-c', 'mots c' => 'mots-c',
            'nad+' => 'nad', 'nad plus' => 'nad',
            'glutathione' => 'glutathione',
            'cjc-1295' => 'cjc-1295', 'cjc 1295' => 'cjc-1295',
            'ipamorelin' => 'ipamorelin',
            'tesamorelin' => 'tesamorelin',
            'sermorelin' => 'sermorelin',
            'selank' => 'selank',
            'semax' => 'semax',
            'dsip' => 'dsip',
            'pt-141' => 'pt-141', 'pt 141' => 'pt-141',
            'melanotan' => 'melanotan',
            'epitalon' => 'epitalon', 'epithalon' => 'epitalon',
            'ss-31' => 'ss-31', 'ss 31' => 'ss-31',
            'pinealon' => 'pinealon',
            'aod-9604' => 'aod-9604', 'aod 9604' => 'aod-9604',
            '5-amino-1mq' => '5-amino-1mq',
        ];

        $slugToCat = ProductCategory::whereIn('slug', array_values(array_unique($compoundHints)))
            ->get()->keyBy('slug');

        $products = Product::where('brand_id', $brand->id)->with('category')->get();
        $misfits = [];
        foreach ($products as $p) {
            $lower = strtolower((string) $p->name);
            foreach ($compoundHints as $needle => $expectedSlug) {
                if (str_contains($lower, $needle)) {
                    $actualSlug = $p->category?->slug;
                    if ($actualSlug !== $expectedSlug && isset($slugToCat[$expectedSlug])) {
                        $misfits[] = [
                            'id' => $p->id,
                            'name' => $p->name,
                            'current' => $p->category?->name ?? '(none)',
                            'should_be' => $slugToCat[$expectedSlug]->name,
                            'expected_slug' => $expectedSlug,
                        ];
                        break;
                    }
                }
            }
        }

        if (empty($misfits)) {
            $this->info("All {$products->count()} products for {$brand->name} look correctly categorized.");
            return self::SUCCESS;
        }

        $this->warn("Found " . count($misfits) . " miscategorized product(s):");
        foreach ($misfits as $m) {
            $this->line(sprintf(
                "  [%d] %s — currently %s, should be %s",
                $m['id'], mb_substr($m['name'], 0, 50), $m['current'], $m['should_be']
            ));
            $this->line(sprintf("    Fix: php artisan product:recategorize %d %s", $m['id'], $m['expected_slug']));
        }
        return self::SUCCESS;
    }
}
