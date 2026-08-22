<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Landing pages for search verticals competitors rank on but we don't.
 * Audit (Aug 2026) showed The Peptide Catalog had /blends, /skincare,
 * /bulk, /testing-labs top-level pages capturing long-tail intent we
 * were sending straight to the generic /products page.
 */
class LandingPageController extends Controller
{
    public function blends(Request $request)
    {
        return $this->renderCatalogPage([
            'slug' => 'blends',
            'title' => 'Peptide Blends — Compare Multi-Compound Vials',
            'h1' => 'Peptide Blends',
            'subtitle' => 'Every multi-compound blend across our tracked vendors — sorted cheapest first per blend ratio.',
            'meta_description' => 'Compare peptide blend prices across verified vendors — BPC-157/TB-500, CJC-1295/Ipamorelin, KLOW blends, and more. Live prices, per-mL sorted, coupon codes.',
            'intro' => 'Blends combine two or more peptides in a single vial — often at a lower per-mg cost than buying each compound separately. This page indexes every blend across our tracked vendors so you can compare like-for-like ratios (e.g. 5mg BPC-157 / 5mg TB-500) instead of hunting through individual product pages.',
            'query' => fn ($q) => $q->where('size_mg', 'like', '%/%'),
            'empty_message' => 'No blend products currently indexed.',
        ], $request);
    }

    public function skincare(Request $request)
    {
        // Categories with cosmetic-peptide use cases — matched by name.
        $skincareCategoryIds = ProductCategory::where(function ($q) {
            foreach (['GHK-Cu', 'Argireline', 'Matrixyl', 'Palmitoyl', 'Copper', 'Snap-8', 'Melanotan', 'Epithalon'] as $term) {
                $q->orWhere('name', 'like', "%{$term}%");
            }
        })->pluck('id')->all();

        return $this->renderCatalogPage([
            'slug' => 'skincare',
            'title' => 'Skincare Peptides — Cosmetic Peptide Vendor Comparison',
            'h1' => 'Skincare Peptides',
            'subtitle' => 'Cosmetic peptides for topical and injectable use — GHK-Cu, Argireline, Matrixyl, and more.',
            'meta_description' => 'Compare cosmetic peptide prices across verified vendors. GHK-Cu, Argireline, Matrixyl, Melanotan, and every skincare-adjacent compound our vendors carry.',
            'intro' => 'Cosmetic peptides — GHK-Cu for wound healing and skin firmness, Argireline and Matrixyl for expression lines, copper-peptide complexes for hair — trade on very different vendor pricing than injectable research peptides. This page filters to the compounds and topical formulations used in that space.',
            'query' => fn ($q) => $q->where(function ($qq) use ($skincareCategoryIds) {
                $qq->where('product_type', 'Topical');
                if (!empty($skincareCategoryIds)) {
                    $qq->orWhereIn('product_category_id', $skincareCategoryIds);
                }
            }),
            'empty_message' => 'No skincare products currently indexed.',
        ], $request);
    }

    public function bulk(Request $request)
    {
        return $this->renderCatalogPage([
            'slug' => 'bulk',
            'title' => 'Bulk Peptides — Compare Gram + High-mg Vendor Prices',
            'h1' => 'Bulk Peptides',
            'subtitle' => 'Gram-scale and hundred-mg-plus vials across our tracked vendors — for labs, high-dose protocols, and long-cycle research.',
            'meta_description' => 'Compare bulk peptide prices. Gram-scale and 200mg+ vials across verified vendors — for research labs, long-cycle protocols, and cost-per-mg optimization.',
            'intro' => 'Larger vials nearly always beat small-vial pricing on a per-mg basis. This page filters to gram-scale (1g and up) and larger single-vial sizes (200mg+) so you can compare bulk-tier vendor pricing directly instead of digging through the main catalog.',
            'query' => fn ($q) => $q->where(function ($qq) {
                $qq->where('size_mg', 'regexp', '^[0-9]+g$')          // 1g, 2g, 5g, 10g, 50g, 100g
                   ->orWhere('size_mg', 'regexp', '^[0-9]{3,}mg$');   // 100mg, 200mg, 1000mg, 1500mg, 5000mg
            }),
            'empty_message' => 'No bulk products currently indexed.',
        ], $request);
    }

    /**
     * Testing-labs — trust-signal page listing which vendors publish COAs from
     * which third-party labs. Static-then-dynamic: for MVP we detect lab names
     * mentioned in vendor descriptions; once we add a proper testing_labs
     * column on vendor_settings, swap the source.
     */
    public function testingLabs(Request $request)
    {
        $labs = [
            'Janoshik'   => ['name' => 'Janoshik Analytical', 'country' => 'Czechia',       'note' => 'Full HPLC + mass spec panel. Widely used across the space.'],
            'Certara'    => ['name' => 'Certara',              'country' => 'United States', 'note' => 'Regulatory-grade analytical services.'],
            'KryoLabs'   => ['name' => 'KryoLabs',             'country' => 'United States', 'note' => 'Independent COA lab.'],
            'Simec'      => ['name' => 'Simec Analytical',     'country' => 'United States', 'note' => 'HPLC + purity testing.'],
            'BioTech'    => ['name' => 'BioTech Analytical',   'country' => 'United States', 'note' => 'Peptide-specific COA lab.'],
        ];

        // Detect lab mentions in vendor description text. Not perfect but
        // catches the majority of vendors who explicitly cite their lab.
        $vendors = Brand::where('is_active', true)
            ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
            ->with(['vendorSetting.location'])
            ->get();

        $byLab = [];
        foreach (array_keys($labs) as $labKey) {
            $byLab[$labKey] = [];
        }
        $byLab['Unspecified'] = [];

        foreach ($vendors as $brand) {
            $desc = strtolower((string) ($brand->vendorSetting->description ?? ''));
            $matched = false;
            foreach ($labs as $key => $lab) {
                if (str_contains($desc, strtolower($key))) {
                    $byLab[$key][] = [
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'location' => $brand->vendorSetting?->location?->name,
                    ];
                    $matched = true;
                }
            }
            if (!$matched) {
                $byLab['Unspecified'][] = [
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'location' => $brand->vendorSetting?->location?->name,
                ];
            }
        }

        // Only render lab sections with at least one vendor.
        $labSections = [];
        foreach ($labs as $key => $lab) {
            if (!empty($byLab[$key])) {
                $labSections[] = array_merge($lab, [
                    'key' => $key,
                    'vendors' => $byLab[$key],
                    'vendor_count' => count($byLab[$key]),
                ]);
            }
        }

        $seo = [
            'title' => 'Third-Party Testing Labs — Which Peptide Vendors Use Which Lab',
            'description' => 'Independently-tested peptide vendors grouped by which third-party analytical lab (Janoshik, Certara, KryoLabs) verifies their COAs. Verify before you buy.',
            'canonical' => url('/testing-labs'),
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/LandingTestingLabs', [
            'lab_sections' => $labSections,
            'unspecified' => $byLab['Unspecified'],
            'seo' => $seo,
        ]);
    }

    /**
     * Shared render pipeline for /blends, /skincare, /bulk. Consolidates the
     * product query + serialization + SEO stamping — each vertical just
     * supplies its filter closure and hero copy via $config.
     */
    private function renderCatalogPage(array $config, Request $request)
    {
        // Global location filter (header CountrySelector).
        $locationFilter = trim((string) $request->get('location', ''));

        $products = Product::visible()
            ->where('status', 'active')
            ->where(function ($q) { $q->where('price', '>', 0)->orWhere('discount_price', '>', 0); })
            ->when($locationFilter, fn ($q) => $q->whereHas(
                'brand.vendorSetting.location',
                fn ($l) => $l->where('name', $locationFilter)
            ))
            ->with(['brand.vendorSetting.location', 'category'])
            ->tap($config['query'])
            ->get()
            ->map(function ($p) {
                $retail = (float) ($p->discount_price && $p->discount_price < $p->price
                    ? $p->discount_price : $p->price);
                $pct = $p->brand?->vendorSetting?->coupon_discount_percent;
                $pmapPrice = ($pct !== null && $pct > 0 && $pct < 100 && $retail > 0)
                    ? round($retail * (1 - ((float) $pct / 100)), 2) : null;
                $finalPrice = $pmapPrice ?? $retail;

                $countryName = $p->brand?->vendorSetting?->location?->name;
                [$currencyCode, $currencySymbol] = Currency::forCountry($countryName);

                return [
                    'id' => $p->id,
                    'name' => $p->display_name,
                    'category_name' => $p->category?->name,
                    'size_mg' => $p->size_mg,
                    'product_type' => $p->product_type,
                    'price' => $p->price !== null ? (float) $p->price : null,
                    'discount_price' => $p->discount_price !== null ? (float) $p->discount_price : null,
                    'final_price' => $finalPrice,
                    'pmap_price' => $pmapPrice,
                    'currency_symbol' => $currencySymbol,
                    'currency_code' => $currencyCode,
                    'go_url' => "/go/{$p->id}",
                    'product_url' => $p->brand?->slug
                        ? "/product/{$p->brand->slug}/" . ($p->slug ?: $p->id) . "/{$p->id}"
                        : "/product/" . ($p->slug ?: $p->id) . "/{$p->id}",
                    'brand_name' => $p->brand?->name,
                    'brand_slug' => $p->brand?->slug,
                    'brand_location' => $countryName,
                    'brand_coupon_code' => $p->brand?->vendorSetting?->coupon_code,
                    'brand_discount_percent' => $pct !== null ? (float) $pct : null,
                ];
            })
            ->sortBy(fn ($r) => $r['final_price'])
            ->values();

        $vendorCount = $products->pluck('brand_slug')->unique()->count();

        $seo = [
            'title' => $config['title'],
            'description' => $config['meta_description'],
            'canonical' => url('/' . $config['slug']),
            'h1' => $config['h1'],
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/LandingCatalog', [
            'slug' => $config['slug'],
            'h1' => $config['h1'],
            'subtitle' => $config['subtitle'],
            'intro' => $config['intro'],
            'empty_message' => $config['empty_message'],
            'products' => $products,
            'vendor_count' => $vendorCount,
            'product_count' => $products->count(),
            'seo' => $seo,
        ]);
    }
}
