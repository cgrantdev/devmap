<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\SeoPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CompareController extends Controller
{
    /**
     * The ordered list of featured compound category IDs for the compare page.
     * Maintained manually to match the product team's priority list.
     */
    /**
     * Compounds shown on /compare, in priority order. First 13 are the
     * user-curated 'top' list — the obesity GLP-1s, the popular blends,
     * and the highest-traffic singles. The rest are filled out by remaining
     * categories with healthy product counts so the page displays ~25 compounds
     * total. Categories that don't exist in the DB are silently skipped, so
     * additions here are safe even if the underlying category doesn't exist
     * yet.
     */
    private const FEATURED_COMPOUND_NAMES = [
        // Top priority (in the order the user requested)
        'Retatrutide',
        'Tirzepatide',
        'Semaglutide',
        'Tesamorelin',
        'BPC-157 / TB-500',
        'GHK-Cu',
        'GLOW',
        'KLOW',
        'CJC-1295 / Ipamorelin',
        'MOTS-c',
        'BPC-157',
        'NAD+',
        'Glutathione',
        // Then other popular compounds, ranked by current product count
        'Ipamorelin',
        'AOD-9604',
        'CJC-1295',
        'TB-500',
        'Sermorelin',
        'Cagrilintide',
        '5-AMINO-1MQ',
        'PT-141',
        'IGF-1 LR3',
        'Kisspeptin',
        'SS-31',
        'Tesofensine',
    ];

    /**
     * Display name overrides (so the page shows a friendlier label than the
     * raw category name when it helps — blends especially benefit from
     * spelling out their constituents).
     */
    private const DISPLAY_NAMES = [
        'BPC-157 / TB-500' => 'BPC-157 / TB-500 Blend',
        'CJC-1295 / Ipamorelin' => 'CJC-1295 / Ipamorelin Blend',
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

        // Generate SEO data (editable via Admin -> Settings -> SEO Pages, key: "compare")
        $defaultTitle = 'Compare Peptide Vendors Side-by-Side — Peptidemap';
        $defaultDescription = 'Every vendor, every price, sorted cheapest-first. Compare peptide suppliers on GLP-1s, BPC-157, GHK-Cu, TB-500 and 100+ more compounds.';

        $seoPage = SeoPage::where('key', 'compare')->first();
        $seo = [
            'key' => 'compare',
            'title' => $seoPage?->title ?: $defaultTitle,
            'description' => $seoPage?->description ?: $defaultDescription,
            'og_title' => $seoPage?->og_title ?: ($seoPage?->title ?: $defaultTitle),
            'og_description' => $seoPage?->og_description ?: ($seoPage?->description ?: $defaultDescription),
            'og_image' => $seoPage?->og_image ?: null,
            'image' => $seoPage?->og_image ?: null,
            'url' => url('/compare'),
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/Compare', [
            'compounds' => $compounds,
            'seo' => $seo,
        ]);
    }

    /**
     * Per-compound compare page: /compare/{slug}
     *
     * Focused destination for "cheapest {compound}" / "{compound} vendor
     * comparison" queries. Shows every visible priced vendor for one
     * compound, cheapest first, with the compound's encyclopedia summary
     * and cross-links to related compare pages.
     *
     * Emits ItemList + Offer schema so Google can treat this as a
     * commercial-intent page (matches strategist rec #10 for encyclopedia).
     */
    public function show(Request $request, string $slug)
    {
        $category = ProductCategory::where('slug', $slug)
            ->where('is_active', true)
            ->with(['educationPost' => function ($q) {
                $q->where('status', 'published')
                  ->select('id', 'product_category_id', 'slug', 'overview', 'description');
            }])
            ->firstOrFail();

        $products = $this->productsForCategory($category);

        // Related compounds — up to 8 other featured compounds by product count.
        // Gives the reader natural next-clicks; also builds the internal-link
        // graph the strategist recommended (rec #16).
        $related = ProductCategory::where('is_active', true)
            ->where('id', '!=', $category->id)
            ->whereIn('name', self::FEATURED_COMPOUND_NAMES)
            ->withCount(['products as active_products' => function ($q) {
                $q->visible()->where('status', 'active')
                  ->where(function ($qq) {
                      $qq->where('discount_price', '>', 0)
                         ->orWhere(function ($qqq) { $qqq->whereNull('discount_price')->where('price', '>', 0); });
                  });
            }])
            ->orderByDesc('active_products')
            ->limit(8)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => self::DISPLAY_NAMES[$c->name] ?? $c->name,
                'slug' => $c->slug,
                'url' => "/compare/{$c->slug}",
                'product_count' => $c->active_products,
            ])
            ->values();

        $displayName = self::DISPLAY_NAMES[$category->name] ?? $category->name;
        $educationPost = $category->educationPost;
        $summary = $educationPost?->overview ?: $educationPost?->description;
        $summary = $summary ? strip_tags($summary) : null;

        $vendorCount = $products->pluck('brand_name')->unique()->count();
        $productCount = $products->count();
        $cheapest = $products->first()['final_price'] ?? null;
        $priciest = $products->last()['final_price'] ?? null;

        // SEO — title leads with buying intent, description names the numbers.
        $cheapestFmt = $cheapest ? '$' . number_format($cheapest, 2) : null;
        $priciestFmt = $priciest ? '$' . number_format($priciest, 2) : null;
        $seoTitle = "Cheapest {$displayName} — {$vendorCount} Vendors Compared";
        $seoDescription = $vendorCount > 0
            ? "Compare {$productCount} {$displayName} product" . ($productCount === 1 ? '' : 's')
              . " across {$vendorCount} verified vendor" . ($vendorCount === 1 ? '' : 's')
              . ($cheapestFmt ? ". Prices from {$cheapestFmt}" . ($priciestFmt && $priciestFmt !== $cheapestFmt ? " to {$priciestFmt}" : '') : '')
              . ". Coupon codes and lab-testing status on every listing."
            : "Vendor comparison for {$displayName} — currently no in-stock listings on Peptidemap.";

        // ItemList + Offer schema — turns this from an informational page into
        // a commercial-intent page for search engines.
        $itemList = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => "{$displayName} vendor comparison",
            'numberOfItems' => $productCount,
            'itemListElement' => $products->take(20)->values()->map(fn ($p, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@type' => 'Product',
                    'name' => $p['name'] ?? $displayName,
                    'brand' => ['@type' => 'Brand', 'name' => $p['brand_name']],
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => number_format($p['final_price'] ?? 0, 2, '.', ''),
                        'priceCurrency' => 'USD',
                        'availability' => 'https://schema.org/InStock',
                        'url' => url("/product/{$p['brand_slug']}/{$p['slug']}/{$p['id']}"),
                        'seller' => ['@type' => 'Organization', 'name' => $p['brand_name']],
                    ],
                ],
            ])->all(),
        ];

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',    'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Compare', 'item' => url('/compare')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $displayName, 'item' => url("/compare/{$slug}")],
            ],
        ];

        $seo = [
            'key' => 'compare-compound',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoTitle,
            'og_description' => $seoDescription,
            'og_image' => route('og.compound', ['slug' => $slug]) . '?v=' . ($category->updated_at?->timestamp ?? 0),
            'image' => route('og.compound', ['slug' => $slug]) . '?v=' . ($category->updated_at?->timestamp ?? 0),
            'url' => url("/compare/{$slug}"),
            'h1' => "Cheapest {$displayName}",
            'schema' => [$itemList, $breadcrumb],
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/CompareCompound', [
            'compound' => [
                'id' => $category->id,
                'name' => $displayName,
                'raw_name' => $category->name,
                'slug' => $category->slug,
                'summary' => $summary,
                'encyclopedia_url' => $educationPost ? "/encyclopedia/{$category->slug}" : null,
                'product_count' => $productCount,
                'vendor_count' => $vendorCount,
                'cheapest_price' => $cheapest,
                'priciest_price' => $priciest,
                'products' => $products,
            ],
            'related' => $related,
            'seo' => $seo,
        ]);
    }

    /**
     * Shared product-mapping used by /compare (index) and /compare/{slug} (show).
     * Returns the vendor rows for one category, cheapest final_price first,
     * priced only ($0 excluded), with brand + coupon info attached.
     */
    private function productsForCategory(ProductCategory $category)
    {
        return Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id)
            ->where(function ($q) {
                $q->where('discount_price', '>', 0)
                  ->orWhere(function ($qq) {
                      $qq->whereNull('discount_price')->where('price', '>', 0);
                  });
            })
            ->with('brand.vendorSetting')
            ->get()
            ->map(function ($product) {
                $retail = (float) (
                    $product->discount_price && $product->discount_price < $product->price
                        ? $product->discount_price
                        : $product->price
                );
                $pct = $product->brand?->vendorSetting?->coupon_discount_percent;
                $pmapPrice = ($pct !== null && $pct > 0 && $pct < 100 && $retail > 0)
                    ? round($retail * (1 - ((float) $pct / 100)), 2)
                    : null;
                $finalPrice = $pmapPrice ?? $retail;

                return [
                    'id' => $product->id,
                    'name' => $product->display_name,
                    'product_type' => $product->product_type,
                    'slug' => $product->slug,
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
            ->sortBy(fn ($p) => (float) $p['final_price'])
            ->values();
    }
}
