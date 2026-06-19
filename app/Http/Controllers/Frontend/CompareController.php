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
                $q->where('status', 'published')
                  ->select('id', 'product_category_id', 'slug', 'description', 'status');
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
                // Hide $0 / unpriced rows — they distort the price comparison
                // and usually indicate a stale import or a parent variable
                // product that didn't get its price resolved.
                ->where(function ($q) {
                    $q->where('discount_price', '>', 0)
                      ->orWhere(function ($qq) {
                          $qq->whereNull('discount_price')->where('price', '>', 0);
                      });
                })
                ->with('brand.vendorSetting')
                ->get()
                ->map(function ($product) {
                    // Cast to float UP FRONT. Eloquent returns decimal columns
                    // as strings, and sortBy() comparing string "100.00" vs
                    // string "70.00" sorts lexically ('1' < '7'), putting the
                    // $100 rows in the middle of the list. Forcing float
                    // makes every comparison numeric.
                    $retail = (float) (
                        $product->discount_price && $product->discount_price < $product->price
                            ? $product->discount_price
                            : $product->price
                    );

                    // PeptideMap-applied price using the brand's discount %.
                    $pct = $product->brand?->vendorSetting?->coupon_discount_percent;
                    $pmapPrice = ($pct !== null && $pct > 0 && $pct < 100 && $retail > 0)
                        ? round($retail * (1 - ((float) $pct / 100)), 2)
                        : null;

                    // The figure we sort on per category — what the visitor
                    // actually pays. Falls back to retail when there's no
                    // PeptideMap discount configured for this vendor.
                    $finalPrice = $pmapPrice ?? $retail;

                    return [
                        'id' => $product->id,
                        'name' => $product->display_name,
                        'product_type' => $product->product_type,
                        'slug' => $product->slug,
                        // Cast prices to float so the Vue template can do
                        // numeric comparisons. Eloquent ships decimal columns
                        // as strings; '69.99' < '100.00' is FALSE in JS
                        // lexical comparison (because '6' > '1'), which broke
                        // the 'show discount + retail strikethrough' branch
                        // for vendors whose discount has more digits left of
                        // the decimal than retail.
                        'price' => $product->price !== null ? (float) $product->price : null,
                        'discount_price' => $product->discount_price !== null ? (float) $product->discount_price : null,
                        'effective_price' => $retail,
                        'final_price' => $finalPrice,
                        'pmap_price' => $pmapPrice,
                        'image_url' => $product->image_url,
                        'product_url' => $product->product_url,
                        'go_url' => "/go/{$product->id}",
                        'brand_name' => $product->brand?->name,
                        'brand_slug' => $product->brand?->slug,
                        'brand_logo' => $product->brand?->vendorSetting?->logo
                            ? asset('storage/' . $product->brand->vendorSetting->logo)
                            : null,
                        'brand_coupon_code' => $product->brand?->vendorSetting?->coupon_code,
                        'brand_discount_percent' => $pct !== null ? (float) $pct : null,
                        'size_mg' => $product->size_mg,
                    ];
                })
                // Sort cheapest-first within this compound based on what the
                // visitor will actually pay (PMAP price when applicable,
                // retail otherwise). Numeric sort enforced even though
                // every final_price is now a float, in case a future change
                // reintroduces mixed types.
                ->sortBy(fn ($p) => (float) $p['final_price'])
                ->values();

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
                'encyclopedia_url' => ($educationPost && $educationPost->status === 'published')
                    ? "/encyclopedia/{$category->slug}"
                    : null,
                'product_count' => $products->count(),
                // Use final_price (post-coupon) so the 'from $X' badge
                // shown on the compound header matches what visitors actually
                // pay — same metric as the row sort below.
                'cheapest_price' => $products->first()['final_price'] ?? null,
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
