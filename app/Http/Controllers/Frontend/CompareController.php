<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CompareController extends Controller
{
    /**
     * The ordered list of featured compound category IDs for the compare page.
     * Maintained manually to match the product team's priority list.
     */
    private const FEATURED_COMPOUND_NAMES = [
        'GLP3-R (Retatrutide)',
        'BPC-157 / TB-500',
        'GLP1-S (Semaglutide)',
        'GLP2-T (Tirzepatide)',
        'GLOW',
        'KLOW',
        'BPC-157',
        'Tesamorelin',
        'Sermorelin',
        'CJC-1295 / Ipamorelin',   // closest match for "Tesamorelin / Ipamorelin / CJC 1295"
        'NAD+',
        'AOD-9604',
        'Ipamorelin',               // placeholder for "Tesamorelin / Ipamorelin / BPC-157 Blend"
        'CJC-1295',
        'GHK-Cu',
        'PT-141',
        'TB-500',
        'MOTS-c',
    ];

    /**
     * Display name overrides (so the page shows the user-facing name
     * even if the DB category uses a coded name like "GLP3-R (Retatrutide)").
     */
    private const DISPLAY_NAMES = [
        'GLP3-R (Retatrutide)' => 'Retatrutide',
        'GLP1-S (Semaglutide)' => 'Semaglutide',
        'GLP2-T (Tirzepatide)' => 'Tirzepatide',
        'BPC-157 / TB-500' => 'BPC-157 / TB-500 Blend',
        'CJC-1295 / Ipamorelin' => 'Ipamorelin / CJC-1295 Blend',
        'GLOW' => 'GLOW — GHK-Cu/BPC-157/TB-500',
        'KLOW' => 'KLOW — GHK-Cu/BPC-157/TB-500/KPV',
    ];

    public function index(Request $request)
    {
        // Fetch all featured categories in one query
        $categories = ProductCategory::where('is_active', true)
            ->whereIn('name', self::FEATURED_COMPOUND_NAMES)
            ->with(['educationPost' => function ($q) {
                $q->select('id', 'product_category_id', 'slug', 'description');
            }])
            ->get()
            ->keyBy('name');

        // For each compound, get all visible products with brand info, sorted by effective price
        $compounds = collect();

        foreach (self::FEATURED_COMPOUND_NAMES as $catName) {
            $category = $categories->get($catName);
            if (!$category) {
                continue; // skip if category doesn't exist in DB
            }

            $products = Product::visible()
                ->where('status', 'active')
                ->where('product_category_id', $category->id)
                ->with('brand')
                ->orderByRaw('COALESCE(discount_price, price) ASC')
                ->get()
                ->map(function ($product) {
                    $effectivePrice = $product->discount_price && $product->discount_price < $product->price
                        ? $product->discount_price
                        : $product->price;

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => $product->price,
                        'discount_price' => $product->discount_price,
                        'effective_price' => $effectivePrice,
                        'image_url' => $product->image_url,
                        'product_url' => $product->product_url,
                        'go_url' => "/go/{$product->id}",
                        'brand_name' => $product->brand?->name,
                        'brand_slug' => $product->brand?->slug,
                        'brand_logo' => $product->brand?->vendorSetting?->logo
                            ? asset('storage/' . $product->brand->vendorSetting->logo)
                            : null,
                        'size_mg' => $product->size_mg,
                    ];
                });

            $displayName = self::DISPLAY_NAMES[$catName] ?? $category->name;
            $educationPost = $category->educationPost;

            $compounds->push([
                'id' => $category->id,
                'name' => $displayName,
                'slug' => $category->slug,
                'anchor' => Str::slug($displayName),
                'description' => $educationPost?->description
                    ? Str::limit(strip_tags($educationPost->description), 200)
                    : null,
                'encyclopedia_url' => $educationPost
                    ? "/encyclopedia/{$category->slug}"
                    : null,
                'product_count' => $products->count(),
                'cheapest_price' => $products->first()['effective_price'] ?? null,
                'vendor_count' => $products->pluck('brand_name')->unique()->count(),
                'products' => $products->values(),
            ]);
        }

        return Inertia::render('Frontend/Compare', [
            'compounds' => $compounds,
            'seo' => [
                'title' => 'Compare Peptide Prices — PeptideMap',
                'description' => 'Compare research peptide prices across verified vendors. Side-by-side vendor pricing for BPC-157, Semaglutide, Tirzepatide, and more.',
            ],
        ]);
    }
}
