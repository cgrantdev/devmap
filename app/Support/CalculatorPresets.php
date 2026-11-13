<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for the peptide calculator's preset library.
 *
 * Consumed by:
 *  - routes/web.php /calculator and /calculator/{slug} - passes presets
 *    to the Vue component + resolves a slug into prefilled state and
 *    compound-specific SEO.
 *  - resources/js/Pages/Frontend/Calculator.vue - renders the grouped
 *    dropdown + applies a prefilled preset on mount.
 *
 * Each entry carries:
 *  - slug: lowercase-dashed, used in /calculator/{slug}
 *  - name: display label in the dropdown + hero title
 *  - group: category for the grouped dropdown
 *  - mg / water / dose: the preset values (mg of peptide, mL of BAC
 *    water, mcg per dose). Defaults keep one draw between 5 and 100
 *    units on a 100-unit syringe so the result is measurable.
 *  - compound: lowercase product_categories slug used for live pricing
 *    and the /compare/{compound} link (null when we list no products)
 *  - variant: optional listing-name filter for compounds sold in
 *    distinct forms under one category (CJC-1295 with / without DAC)
 *
 * Adding a compound here automatically makes /calculator/{slug} live
 * and appears in the dropdown next deploy.
 */
class CalculatorPresets
{
    /** Product types that can be a lyophilized vial for reconstitution. */
    private const VIAL_TYPES = ['peptide', 'bioregulator', 'other'];

    /**
     * Flat list of every preset. Grouping is derived from the `group` key.
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            // GLP-1 / Weight management
            ['slug' => 'semaglutide-3mg', 'name' => 'Semaglutide (3mg)', 'group' => 'GLP-1 / Weight', 'mg' => 3, 'water' => 1.5, 'dose' => 250, 'compound' => 'semaglutide'],
            ['slug' => 'semaglutide-5mg', 'name' => 'Semaglutide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 500, 'compound' => 'semaglutide'],
            ['slug' => 'semaglutide-10mg', 'name' => 'Semaglutide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 250, 'compound' => 'semaglutide'],
            ['slug' => 'tirzepatide-5mg', 'name' => 'Tirzepatide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 2500, 'compound' => 'tirzepatide'],
            ['slug' => 'tirzepatide-10mg', 'name' => 'Tirzepatide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 2500, 'compound' => 'tirzepatide'],
            ['slug' => 'tirzepatide-15mg', 'name' => 'Tirzepatide (15mg)', 'group' => 'GLP-1 / Weight', 'mg' => 15, 'water' => 3, 'dose' => 2500, 'compound' => 'tirzepatide'],
            ['slug' => 'tirzepatide-30mg', 'name' => 'Tirzepatide (30mg)', 'group' => 'GLP-1 / Weight', 'mg' => 30, 'water' => 3, 'dose' => 2500, 'compound' => 'tirzepatide'],
            ['slug' => 'retatrutide-5mg', 'name' => 'Retatrutide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 1000, 'compound' => 'retatrutide'],
            ['slug' => 'retatrutide-10mg', 'name' => 'Retatrutide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 1000, 'compound' => 'retatrutide'],
            ['slug' => 'liraglutide-5mg', 'name' => 'Liraglutide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 600, 'compound' => null],
            ['slug' => 'cagrilintide-5mg', 'name' => 'Cagrilintide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 600, 'compound' => 'cagrilintide'],
            ['slug' => 'survodutide-10mg', 'name' => 'Survodutide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 800, 'compound' => 'survodutide'],
            ['slug' => 'pemvidutide-10mg', 'name' => 'Pemvidutide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 1000, 'compound' => 'pemvidutide'],

            // Growth hormone / GH secretagogues
            ['slug' => 'cjc-1295-no-dac-2mg', 'name' => 'CJC-1295 no DAC (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 100, 'compound' => 'cjc-1295', 'variant' => 'no-dac'],
            ['slug' => 'cjc-1295-no-dac-5mg', 'name' => 'CJC-1295 no DAC (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2.5, 'dose' => 100, 'compound' => 'cjc-1295', 'variant' => 'no-dac'],
            ['slug' => 'cjc-1295-dac-2mg', 'name' => 'CJC-1295 w/ DAC (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 1000, 'compound' => 'cjc-1295', 'variant' => 'dac'],
            ['slug' => 'ipamorelin-2mg', 'name' => 'Ipamorelin (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 200, 'compound' => 'ipamorelin'],
            ['slug' => 'ipamorelin-5mg', 'name' => 'Ipamorelin (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2.5, 'dose' => 200, 'compound' => 'ipamorelin'],
            ['slug' => 'cjc-ipamorelin-blend-10mg', 'name' => 'CJC/Ipamorelin Blend (10mg)', 'group' => 'Growth / GH', 'mg' => 10, 'water' => 3, 'dose' => 300, 'compound' => 'cjc-1295-ipamorelin'],
            ['slug' => 'tesamorelin-2mg', 'name' => 'Tesamorelin (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 1, 'dose' => 1000, 'compound' => 'tesamorelin'],
            ['slug' => 'tesamorelin-5mg', 'name' => 'Tesamorelin (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2, 'dose' => 2000, 'compound' => 'tesamorelin'],
            ['slug' => 'sermorelin-2mg', 'name' => 'Sermorelin (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 200, 'compound' => 'sermorelin'],
            ['slug' => 'sermorelin-5mg', 'name' => 'Sermorelin (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2.5, 'dose' => 300, 'compound' => 'sermorelin'],
            ['slug' => 'hexarelin-2mg', 'name' => 'Hexarelin (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 100, 'compound' => null],
            ['slug' => 'hexarelin-5mg', 'name' => 'Hexarelin (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2.5, 'dose' => 200, 'compound' => null],
            ['slug' => 'ghrp-2-5mg', 'name' => 'GHRP-2 (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2, 'dose' => 150, 'compound' => 'ghrp-2'],
            ['slug' => 'ghrp-6-5mg', 'name' => 'GHRP-6 (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2, 'dose' => 150, 'compound' => 'ghrp-6'],

            // Healing / repair
            ['slug' => 'bpc-157-5mg', 'name' => 'BPC-157 (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 250, 'compound' => 'bpc-157'],
            ['slug' => 'bpc-157-10mg', 'name' => 'BPC-157 (10mg)', 'group' => 'Healing / Repair', 'mg' => 10, 'water' => 3, 'dose' => 500, 'compound' => 'bpc-157'],
            ['slug' => 'tb-500-5mg', 'name' => 'TB-500 (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 2500, 'compound' => 'tb-500'],
            ['slug' => 'tb-500-10mg', 'name' => 'TB-500 (10mg)', 'group' => 'Healing / Repair', 'mg' => 10, 'water' => 3, 'dose' => 2500, 'compound' => 'tb-500'],
            ['slug' => 'bpc-tb500-blend-10mg', 'name' => 'BPC-157 / TB-500 Blend (10mg)', 'group' => 'Healing / Repair', 'mg' => 10, 'water' => 3, 'dose' => 500, 'compound' => 'bpc-157-tb-500'],
            ['slug' => 'kpv-5mg', 'name' => 'KPV (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 500, 'compound' => 'kpv'],
            ['slug' => 'll-37-5mg', 'name' => 'LL-37 (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 500, 'compound' => 'll-37'],
            ['slug' => 'thymosin-alpha-1-5mg', 'name' => 'Thymosin Alpha-1 (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 1600, 'compound' => 'thymosin-alpha-1'],
            ['slug' => 'ghk-cu-50mg', 'name' => 'GHK-Cu (50mg)', 'group' => 'Healing / Repair', 'mg' => 50, 'water' => 3, 'dose' => 2000, 'compound' => 'ghk-cu'],
            ['slug' => 'ghk-cu-100mg', 'name' => 'GHK-Cu (100mg)', 'group' => 'Healing / Repair', 'mg' => 100, 'water' => 5, 'dose' => 2000, 'compound' => 'ghk-cu'],

            // Longevity / mitochondrial
            ['slug' => 'mots-c-5mg', 'name' => 'MOTS-c (5mg)', 'group' => 'Longevity', 'mg' => 5, 'water' => 1.5, 'dose' => 2500, 'compound' => 'mots-c'],
            ['slug' => 'mots-c-10mg', 'name' => 'MOTS-c (10mg)', 'group' => 'Longevity', 'mg' => 10, 'water' => 2, 'dose' => 5000, 'compound' => 'mots-c'],
            ['slug' => 'humanin-5mg', 'name' => 'Humanin (5mg)', 'group' => 'Longevity', 'mg' => 5, 'water' => 2, 'dose' => 1500, 'compound' => 'humanin'],
            ['slug' => 'ss-31-elamipretide-10mg', 'name' => 'SS-31 / Elamipretide (10mg)', 'group' => 'Longevity', 'mg' => 10, 'water' => 2, 'dose' => 1000, 'compound' => 'elamipretide'],
            ['slug' => 'epitalon-10mg', 'name' => 'Epitalon (10mg)', 'group' => 'Longevity', 'mg' => 10, 'water' => 2, 'dose' => 1000, 'compound' => 'epitalon'],
            ['slug' => 'epitalon-50mg', 'name' => 'Epitalon (50mg)', 'group' => 'Longevity', 'mg' => 50, 'water' => 2, 'dose' => 5000, 'compound' => 'epitalon'],
            ['slug' => 'nad-100mg', 'name' => 'NAD+ (100mg)', 'group' => 'Longevity', 'mg' => 100, 'water' => 2, 'dose' => 5000, 'compound' => 'nad'],
            ['slug' => 'nad-500mg', 'name' => 'NAD+ (500mg)', 'group' => 'Longevity', 'mg' => 500, 'water' => 3, 'dose' => 10000, 'compound' => 'nad'],
            ['slug' => 'foxo4-dri-5mg', 'name' => 'FOXO4-DRI (5mg)', 'group' => 'Longevity', 'mg' => 5, 'water' => 2, 'dose' => 1000, 'compound' => 'foxo4-dri'],

            // Cognitive / nootropic
            ['slug' => 'semax-5mg', 'name' => 'Semax (5mg)', 'group' => 'Cognitive', 'mg' => 5, 'water' => 2, 'dose' => 300, 'compound' => 'semax'],
            ['slug' => 'selank-5mg', 'name' => 'Selank (5mg)', 'group' => 'Cognitive', 'mg' => 5, 'water' => 2, 'dose' => 300, 'compound' => 'selank'],
            ['slug' => 'dihexa-10mg', 'name' => 'Dihexa (10mg)', 'group' => 'Cognitive', 'mg' => 10, 'water' => 2, 'dose' => 2000, 'compound' => 'dihexa'],
            ['slug' => 'dsip-5mg', 'name' => 'DSIP (5mg)', 'group' => 'Cognitive', 'mg' => 5, 'water' => 2, 'dose' => 300, 'compound' => 'dsip'],

            // Sexual function / cosmetic
            ['slug' => 'pt-141-bremelanotide-10mg', 'name' => 'PT-141 / Bremelanotide (10mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 10, 'water' => 2, 'dose' => 1750, 'compound' => 'bremelanotide'],
            ['slug' => 'melanotan-ii-10mg', 'name' => 'Melanotan II (10mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 10, 'water' => 2, 'dose' => 250, 'compound' => 'melanotan-ii'],
            ['slug' => 'melanotan-i-10mg', 'name' => 'Melanotan I (10mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 10, 'water' => 2, 'dose' => 500, 'compound' => 'melanotan-i'],
            ['slug' => 'kisspeptin-10-5mg', 'name' => 'Kisspeptin-10 (5mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 5, 'water' => 3, 'dose' => 100, 'compound' => 'kisspeptin'],
            ['slug' => 'kisspeptin-10-10mg', 'name' => 'Kisspeptin-10 (10mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 10, 'water' => 3, 'dose' => 200, 'compound' => 'kisspeptin'],

            // Metabolic / lipolytic
            ['slug' => 'aod-9604-5mg', 'name' => 'AOD-9604 (5mg)', 'group' => 'Metabolic', 'mg' => 5, 'water' => 2, 'dose' => 300, 'compound' => 'aod-9604'],
            ['slug' => '5-amino-1mq-50mg', 'name' => '5-Amino-1MQ (50mg)', 'group' => 'Metabolic', 'mg' => 50, 'water' => 2, 'dose' => 10000, 'compound' => '5-amino-1mq'],
            ['slug' => 'slu-pp-332-10mg', 'name' => 'SLU-PP-332 (10mg)', 'group' => 'Metabolic', 'mg' => 10, 'water' => 2, 'dose' => 500, 'compound' => 'slu-pp-332'],
        ];
    }

    /**
     * O(1) lookup of a preset by its slug.
     */
    public static function findBySlug(string $slug): ?array
    {
        foreach (self::all() as $p) {
            if ($p['slug'] === $slug) return $p;
        }
        return null;
    }

    /**
     * Compound-level root label (e.g. "Tirzepatide" from "Tirzepatide (10mg)")
     * used for SEO titles.
     */
    public static function compoundName(array $preset): string
    {
        $name = $preset['name'];
        // Strip the "(5mg)" / "(10mg)" tail for the search-engine title.
        return trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $name));
    }

    /**
     * Cheapest live vial listing for a preset's compound at the preset's
     * vial size, for the calculator's "Price this vial" card. Cached for
     * an hour per preset. Returns null when the compound has no category
     * or no listing matches; never throws, so the calculator page can't
     * fail because of the price lookup.
     *
     * @return array<string, mixed>|null
     */
    public static function pricing(array $preset): ?array
    {
        $compound = $preset['compound'] ?? null;
        if (!$compound) {
            return null;
        }

        try {
            $data = Cache::remember(
                'calculator-pricing:v1:' . $preset['slug'],
                3600,
                function () use ($preset, $compound) {
                    // Cache a miss too, so presets without listings don't
                    // re-query on every page view.
                    return self::buildPricing($preset, $compound) ?? ['found' => false];
                }
            );
        } catch (\Throwable $e) {
            Log::warning('Calculator pricing lookup failed', [
                'preset' => $preset['slug'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        return (is_array($data) && !empty($data['found'])) ? $data : null;
    }

    private static function buildPricing(array $preset, string $compound): ?array
    {
        $category = ProductCategory::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(slug) = ?', [strtolower($compound)])
            ->orderBy('id')
            ->first(['id', 'name', 'slug']);
        if (!$category) {
            return null;
        }

        $rows = Product::query()
            ->visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id)
            ->with([
                'brand' => fn ($q) => $q->forCard(),
                'brand.vendorSetting' => fn ($q) => $q->forCard(),
                'brand.vendorSetting.location:id,name',
            ])
            ->get(['id', 'brand_id', 'name', 'slug', 'size_mg', 'price', 'discount_price', 'product_type', 'availability']);

        $targetMg = (float) $preset['mg'];
        $tolerance = max(0.01, $targetMg * 0.01);
        $variant = $preset['variant'] ?? null;
        $matches = [];

        foreach ($rows as $product) {
            $brand = $product->brand;
            if (!$brand || empty($brand->slug) || empty($product->slug)) {
                continue;
            }
            $lowerName = strtolower((string) $product->name);
            if (!self::isVialListing($product->product_type, $lowerName)) {
                continue;
            }
            if ($variant !== null && !self::matchesVariant($variant, $lowerName)) {
                continue;
            }
            if (str_contains(strtolower((string) $product->availability), 'out')) {
                continue;
            }
            $total = self::sizeTotalMg((string) $product->size_mg) ?? self::sizeTotalMg($lowerName);
            if ($total === null || abs($total - $targetMg) > $tolerance) {
                continue;
            }
            $price = self::effectivePrice($product->price, $product->discount_price);
            if ($price <= 0) {
                continue;
            }

            [$code, $symbol] = ProductRowCurrency::resolve($product);
            $matches[] = [
                'price' => $price,
                'currency_code' => $code,
                'currency_symbol' => $symbol,
                'vendor_name' => (string) $brand->name,
                'listing_url' => '/product/' . rawurlencode((string) $brand->slug)
                    . '/' . rawurlencode((string) $product->slug)
                    . '/' . $product->id,
            ];
        }

        if ($matches === []) {
            return null;
        }

        // Prices are stored in each vendor's own currency with no FX rate,
        // so the headline compares USD listings when any exist.
        $usd = array_values(array_filter($matches, fn ($m) => $m['currency_code'] === 'USD'));
        $pool = $usd !== [] ? $usd : $matches;
        usort($pool, fn ($a, $b) => $a['price'] <=> $b['price']);
        $best = $pool[0];

        return [
            'found' => true,
            'preset_slug' => $preset['slug'],
            'size_mg' => $targetMg,
            'category_name' => (string) $category->name,
            'cheapest_price' => round($best['price'], 2),
            'currency_code' => $best['currency_code'],
            'currency_symbol' => $best['currency_symbol'],
            'vendor_name' => $best['vendor_name'],
            'listing_url' => $best['listing_url'],
            'compare_url' => '/compare/' . rawurlencode(strtolower((string) $category->slug)),
            'product_listing_url' => '/product/' . rawurlencode((string) $category->slug),
            'listing_count' => count($matches),
        ];
    }

    /**
     * Total milligrams described by a size string such as "10mg",
     * "5mg/5mg", "500 mcg" or a bare "5". Null when no amount is found.
     */
    public static function sizeTotalMg(?string $text): ?float
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }
        if (preg_match('/^\d+(?:\.\d+)?$/', $text)) {
            return (float) $text;
        }
        if (!preg_match_all('/(\d+(?:\.\d+)?)\s*(mcg|ug|mg|g)\b/i', $text, $hits, PREG_SET_ORDER)) {
            return null;
        }
        $total = 0.0;
        foreach ($hits as $hit) {
            $amount = (float) $hit[1];
            $unit = strtolower($hit[2]);
            if ($unit === 'mcg' || $unit === 'ug') {
                $amount /= 1000;
            } elseif ($unit === 'g') {
                $amount *= 1000;
            }
            $total += $amount;
        }
        return $total > 0 ? $total : null;
    }

    /**
     * Single lyophilized vials only: excludes sprays, capsules, pens,
     * topicals, kits and multi-vial packs, which the reconstitution math
     * doesn't apply to and whose price isn't per vial.
     */
    private static function isVialListing(?string $type, string $lowerName): bool
    {
        $type = strtolower(trim((string) $type));
        if ($type !== '' && !in_array($type, self::VIAL_TYPES, true)) {
            return false;
        }
        return !preg_match(
            '/\b(kit|kits|capsules?|caps|tablets?|spray|nasal|cream|serum|oral|pens?|gel|patch)\b|\b\d+\s*x\b|\bx\s*\d+\b|\b\d+\s*vials\b|\b\d+\s*pack\b/',
            $lowerName
        );
    }

    /**
     * CJC-1295 is sold with and without DAC under one category. Listings
     * that don't say which are skipped rather than guessed.
     */
    private static function matchesVariant(string $variant, string $lowerName): bool
    {
        $withoutDac = (bool) preg_match('/\b(no|without|w\/o|non)[\s-]*dac\b|\bmod[\s-]*grf\b/', $lowerName);
        $withDac = !$withoutDac && (bool) preg_match('/\bdac\b/', $lowerName);
        if ($variant === 'dac') {
            return $withDac;
        }
        if ($variant === 'no-dac') {
            return $withoutDac;
        }
        return true;
    }

    /** Sale price when it's a real discount, otherwise the list price. */
    private static function effectivePrice($price, $discount): float
    {
        $price = (float) $price;
        $discount = (float) $discount;
        if ($discount > 0 && ($price <= 0 || $discount < $price)) {
            return $discount;
        }
        return $price;
    }
}
