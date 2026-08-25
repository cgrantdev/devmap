<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoPage;
use App\Models\Setting;
use App\Models\Type;
use App\Models\Puse;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class ProductsController extends Controller
{
    /**
     * Safely truncate a string to a given length
     * Works without mbstring extension
     */
    /**
     * Peptidemap-branded product meta description. Every product page gets
     * something unique (vendor + price + comparison-shopping angle) instead
     * of the raw vendor description that would compete against the vendor's
     * own SERP result.
     *
     * Format examples (150-160 chars, always <= 160):
     *   "SELANK 10mg from Certified Pep — $50.00. Compare 12 vendors on
     *    Peptidemap, verified COAs, save 10% with code PMAP. Research use only."
     *   "BPC-157 (10mg) from Amino Club — $39.99 (was $49.99). Compare 25
     *    vendors, verified COAs, PMAP coupons. Research use only."
     */
    private function buildProductMetaDescription($product, $brand): string
    {
        $vendorName = $brand?->name ?? 'verified vendors';
        $productLabel = $product->display_name ?? $product->name;
        $vs = $brand?->vendorSetting;

        // Price segment
        $priceSegment = '';
        $retail = (float) ($product->price ?? 0);
        $discount = (float) ($product->discount_price ?? 0);
        if ($discount > 0 && $discount < $retail) {
            $priceSegment = ' — $' . number_format($discount, 2) . ' (was $' . number_format($retail, 2) . ')';
        } elseif ($retail > 0) {
            $priceSegment = ' — $' . number_format($retail, 2);
        }

        // Comparison-shopping angle: how many other vendors carry this compound
        $compareSegment = '';
        if ($product->product_category_id) {
            $vendorCount = \App\Models\Product::visible()
                ->where('status', 'active')
                ->where('product_category_id', $product->product_category_id)
                ->distinct('brand_id')
                ->count('brand_id');
            if ($vendorCount >= 2) {
                $compareSegment = " Compare {$vendorCount} vendors on Peptidemap,";
            }
        }
        if (!$compareSegment) {
            $compareSegment = ' Compare verified peptide vendors on Peptidemap,';
        }

        // Coupon segment (PMAP savings)
        $couponSegment = '';
        $pct = $vs?->coupon_discount_percent;
        $code = $vs?->coupon_code;
        if ($pct && $pct > 0 && $pct < 100 && $code) {
            // Store may keep pct as decimal ("15.00"). Show whole number when possible.
            $pctLabel = ((float) $pct == (int) $pct) ? (int) $pct : rtrim(rtrim(number_format((float) $pct, 2), '0'), '.');
            $couponSegment = " save {$pctLabel}% with code " . strtoupper($code) . '.';
        } else {
            $couponSegment = ' verified COAs, PMAP coupons.';
        }

        // Compose
        $desc = "{$productLabel} from {$vendorName}{$priceSegment}.{$compareSegment}{$couponSegment} Research use only.";

        // Safety trim to 158 chars (Google truncates around 155-160)
        if (function_exists('mb_strlen') && mb_strlen($desc) > 158) {
            $desc = mb_substr($desc, 0, 155) . '…';
        }
        return $desc;
    }

    private function safeLimit($value, $limit = 100, $end = '...')
    {
        if (empty($value)) {
            return '';
        }

        $value = strip_tags($value);
        
        // If mbstring is available, use it
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value) <= $limit) {
                return $value;
            }
            return mb_substr($value, 0, $limit) . $end;
        }
        
        // Fallback to regular string functions
        if (strlen($value) <= $limit) {
            return $value;
        }
        return substr($value, 0, $limit) . $end;
    }
    public function index()
    {
        // Get all active product categories with product counts
        $categories = ProductCategory::where('is_active', true)
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ])
            ->having('products_count', '>', 0)
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                // Priority order:
                //  1. A product explicitly flagged is_peptide_thumb for
                //     this category — this is the VA's deliberate pick.
                //  2. The category's own image_url (only if the underlying
                //     file actually exists on disk — stale DB references
                //     from deleted uploads otherwise render as broken).
                //  3. Any visible product in this category with an image.
                //  4. null — Vue card renders the themed SVG placeholder.
                $image = null;

                $flagged = Product::visible()
                    ->where('status', 'active')
                    ->where('product_category_id', $category->id)
                    ->where('is_peptide_thumb', true)
                    ->whereNotNull('image_url')
                    ->where('image_url', '!=', '')
                    ->first();

                if ($flagged) {
                    $image = $flagged->image_url;
                } elseif ($category->image_url
                    && \Illuminate\Support\Facades\Storage::disk('public')->exists('categories/' . $category->image_url)
                ) {
                    $image = \Illuminate\Support\Facades\Storage::url('categories/' . $category->image_url);
                } else {
                    $sample = Product::visible()
                        ->where('status', 'active')
                        ->where('product_category_id', $category->id)
                        ->whereNotNull('image_url')
                        ->where('image_url', '!=', '')
                        ->first();
                    $image = $sample ? $sample->image_url : null;
                }
                
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'total_items' => $category->products_count,
                    'image' => $image,
                    'description' => $category->description,
                    'research_area' => $category->research_area,
                ];
            });
        
        // Generate SEO data (editable via Admin -> Settings -> SEO Pages, key: "products")
        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap';
        $defaultTitle = 'Peptides - Browse All Products';
        $defaultDescription = 'Browse our comprehensive collection of peptides. Compare products, prices, and vendors to find the best peptides for your research needs.';

        $seoPage = SeoPage::where('key', 'products')->first();

        // ItemList JSON-LD for the category groups shown on this page (rendered by app.blade.php)
        $itemListSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Research Peptide Categories',
            'numberOfItems' => $categories->count(),
            'itemListElement' => $categories->take(20)->values()->map(fn ($c, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => url("/product/{$c['slug']}"),
                'name' => $c['name'],
            ])->all(),
        ];

        $seo = [
            'key' => 'products',
            'title' => $seoPage?->title ?: $defaultTitle,
            'description' => $seoPage?->description ?: $defaultDescription,
            'og_title' => $seoPage?->og_title ?: ($seoPage?->title ?: $defaultTitle),
            'og_description' => $seoPage?->og_description ?: ($seoPage?->description ?: $defaultDescription),
            'og_image' => $seoPage?->og_image ?: null,
            // Backward-compatible field used by some pages
            'image' => $seoPage?->og_image ?: null,
            'url' => url('/products'),
            'schema' => [$itemListSchema],
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/Products', [
            'productGroups' => $categories,
            'seo' => $seo,
        ]);
    }

    public function showProduct(Request $request, $vendorSlug, $productSlug, $id)
    {
        // Find product by id and slug
        $product = Product::with(['brand', 'location', 'types', 'puses', 'category', 'brand.vendorSetting'])
            ->visible()
            ->where('id', $id)
            ->where('slug', $productSlug)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            abort(404, 'Product not found');
        }

        // Related products: comparison-shopping angle first — same compound
        // (category) from OTHER vendors so the visitor can price-compare, then
        // fill from same brand's other products if there's still room. Cap at
        // 12 total to keep the section scannable.
        //
        // Guard against the NULL-category footgun: without this, an uncategorized
        // product would "match" every OTHER uncategorized product in the DB
        // because Laravel translates ->where('col', null) into `col IS NULL`.
        $relatedLimit = 12;
        $related = collect();

        if ($product->product_category_id) {
            // Prefer other vendors' same-compound products first.
            $otherVendorQuery = Product::with(['brand'])
                ->visible()
                ->where('status', 'active')
                ->where('product_category_id', $product->product_category_id)
                ->where('id', '!=', $product->id)
                ->where('brand_id', '!=', $product->brand_id);
            // If the current product has a type, prefer same-type matches.
            if ($product->product_type) {
                $otherVendorQuery->where('product_type', $product->product_type);
            }
            $related = $otherVendorQuery
                ->orderByDesc('rating_count')
                ->orderByDesc('featured')
                ->take($relatedLimit)
                ->get();
        }

        // Top-up from same brand's other products if we still have room.
        if ($related->count() < $relatedLimit && $product->brand_id) {
            $fill = Product::with(['brand'])
                ->visible()
                ->where('status', 'active')
                ->where('brand_id', $product->brand_id)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $related->pluck('id')->all())
                ->orderByDesc('rating_count')
                ->orderByDesc('featured')
                ->take($relatedLimit - $related->count())
                ->get();
            $related = $related->concat($fill);
        }

        $relatedProducts = $related->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->display_name,
                'product_type' => $p->product_type,
                'slug' => $p->slug,
                'image_url' => $p->image_url,
                'price' => $p->price,
                'discount_price' => $p->discount_price,
                'brand' => $p->brand ? ['name' => $p->brand->name, 'slug' => $p->brand->slug] : null,
            ];
        });

        // Get brand initials
        $brand = $product->brand;
        $initials = 'PS';
        if ($brand && !empty($brand->name)) {
            // Split by spaces and filter out empty strings
            $words = array_filter(explode(' ', trim($brand->name)));
            $words = array_values($words); // Re-index array
            
            if (count($words) >= 2) {
                // Take first letter of first two words
                $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
            } elseif (count($words) == 1) {
                // If only one word, take first two characters
                $word = $words[0];
                $initials = strtoupper(substr($word, 0, 2));
                // If word is only one character, pad with first character
                if (strlen($initials) < 2) {
                    $initials = strtoupper($word[0] . $word[0]);
                }
            } else {
                // Fallback
                $initials = strtoupper(substr($brand->name, 0, 2));
            }
        }

        // Get brand reviews if brand exists
        $reviews = [];
        if ($brand) {
            $approvedReviews = $brand->approvedReviews()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();
            
            $verifiedMap = \App\Models\VendorReview::computeVerifiedMap($approvedReviews);
            $reviews = $approvedReviews->map(function ($review) use ($verifiedMap) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review' => $review->review ?? '',
                    'user_name' => $review->user ? $review->user->name : $review->user_name,
                    'user_email' => $review->user_email,
                    'user_id' => $review->user_id,
                    'created_at' => $review->created_at->format('Y-m-d'),
                    'shipping_time' => $review->shipping_time,
                    'customer_service' => $review->customer_service,
                    'quality' => $review->quality,
                    'cost' => $review->cost,
                    'packaging' => $review->packaging,
                    'verified' => $verifiedMap[$review->id] ?? false,
                ];
            })->toArray();
        }

        // Get discount code: check for active deal first, then vendorSetting coupon_code, then default to PMAP
        $discountCode = 'PMAP';
        if ($brand) {
            if (Schema::hasTable('deals')) {
                $activeDeal = Deal::where('brand_id', $brand->id)
                    ->where('active', true)
                    ->where(function ($query) {
                        $query->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>=', now());
                    })
                    ->where(function ($query) {
                        $query->whereNull('usage_limit')
                            ->orWhereRaw('used_count < usage_limit');
                    })
                    ->first();
                
                if ($activeDeal) {
                    $discountCode = $activeDeal->code;
                } elseif ($brand->vendorSetting && $brand->vendorSetting->coupon_code) {
                    $discountCode = $brand->vendorSetting->coupon_code;
                }
            } elseif ($brand->vendorSetting && $brand->vendorSetting->coupon_code) {
                $discountCode = $brand->vendorSetting->coupon_code;
            }
        }

        // Generate SEO data for product detail
        // Priority: Use stored SEO data from database, fallback to auto-generated from product fields
        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'PeptideMap';
        $productSlugForUrl = $product->slug ?: Str::slug($product->display_name ?? $product->name ?? 'product');
        $productUrl = $brand && $brand->slug
            ? url("/product/{$brand->slug}/{$productSlugForUrl}/{$product->id}")
            : url("/product/{$productSlugForUrl}/{$product->id}");
        
        // Build product image URL - handle both absolute URLs and relative paths
        $productImage = null;
        if ($product->image_url) {
            if (str_starts_with($product->image_url, 'http')) {
                $productImage = $product->image_url;
            } else {
                $productImage = url($product->image_url);
            }
        }
        
        // Check if stored SEO data exists
        $hasStoredSeo = !empty($product->seo_page_title) || !empty($product->seo_description);

        // Auto-build a Peptidemap-flavored meta description with vendor + price +
        // comparison-shopping angle. Beats "Research Peptide (Selank) — 10 MG Key
        // Features…" scraped verbatim from the vendor's own page (identical to
        // their SERP result → Google will just prefer the vendor's, not ours).
        $autoSeoDescription = $this->buildProductMetaDescription($product, $brand);

        if ($hasStoredSeo) {
            // Use stored SEO data from database (admin override wins)
            $seoTitle = $product->seo_page_title ?: ($product->name . ' – ' . $siteName);
            $seoDescription = $product->seo_description ?: $autoSeoDescription;
            $seoOgTitle = $product->seo_og_title ?: $seoTitle;
            $seoOgDescription = $product->seo_og_description ?: $seoDescription;
            // Version param tied to updated_at — when the product changes
            // (price, image, name, category), the OG URL changes too, which
            // busts every downstream cache (Cloudflare edge + Discord/FB/X/
            // LinkedIn OG scrapers that key on URL). Without this, a shared
            // link's preview never updates after the first scrape.
            $ogV = $product->updated_at?->timestamp ?? 0;
            $seoOgImage = $product->seo_og_image
                ? (str_starts_with($product->seo_og_image, 'http') ? $product->seo_og_image : url($product->seo_og_image))
                : route('og.product', ['id' => $product->id]) . '?v=' . $ogV;
        } else {
            $vendorName = $brand ? $brand->name : 'our store';
            $seoTitle = "Buy {$product->name} from {$vendorName} - {$siteName}";
            $seoDescription = $autoSeoDescription;
            $seoOgTitle = $seoTitle;
            $seoOgDescription = $seoDescription;
            $ogV = $product->updated_at?->timestamp ?? 0;
            $seoOgImage = route('og.product', ['id' => $product->id]) . '?v=' . $ogV;
        }
        
        // Product + BreadcrumbList JSON-LD (rendered by app.blade.php)
        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->display_name,
            'image' => $productImage ? [$productImage] : null,
            'description' => $product->description ? strip_tags($product->description) : null,
            'brand' => $brand ? ['@type' => 'Brand', 'name' => $brand->name] : null,
            'sku' => (string) $product->id,
            'offers' => [
                '@type' => 'Offer',
                'url' => $productUrl,
                'priceCurrency' => 'USD',
                'price' => number_format((float) ($product->discount_price ?: $product->price), 2, '.', ''),
                'availability' => 'https://schema.org/' . ($product->availability === 'in_stock' ? 'InStock' : 'OutOfStock'),
                'seller' => $brand ? ['@type' => 'Organization', 'name' => $brand->name] : null,
            ],
        ];
        if (($product->rating_count ?? 0) > 0) {
            $productSchema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $product->rating_average,
                'reviewCount' => (int) $product->rating_count,
            ];
        }

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_filter([
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://peptidemap.com/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Vendors', 'item' => 'https://peptidemap.com/brands'],
                $brand ? ['@type' => 'ListItem', 'position' => 3, 'name' => $brand->name, 'item' => 'https://peptidemap.com/brand/' . $brand->slug] : null,
                ['@type' => 'ListItem', 'position' => $brand ? 4 : 3, 'name' => $product->display_name],
            ])),
        ];

        // Build SEO array (same format as products/brands pages)
        $seo = [
            'key' => 'product',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoOgTitle,
            'og_description' => $seoOgDescription,
            'og_image' => $seoOgImage,
            // Backward-compatible field used by some pages
            'image' => $seoOgImage,
            'url' => $productUrl,
            'canonical' => $productUrl,
            'schema' => [$productSchema, $breadcrumbSchema],
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name' => $product->display_name,
                'original_name' => $product->getRawOriginal('name'),
                'product_type' => $product->product_type,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'size_mg' => $product->size_mg,
                'availability' => $product->availability,
                'verified' => $product->verified,
                'rating_average' => (float) ($product->rating_average ?? 0),
                'rating_count' => (int) ($product->rating_count ?? 0),
                'image_url' => $product->image_url,
                'product_url' => $product->product_url,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
            ],
            'brand' => $brand ? [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'is_active' => $brand->is_active,
                'initials' => $initials,
                'logo' => $brand->vendorSetting && $brand->vendorSetting->logo 
                    ? asset('storage/' . $brand->vendorSetting->logo) 
                    : null,
                'shop_url' => $brand->vendorSetting->shop_url ?? null,
                'discount_code' => $discountCode,
                'discount_percent' => $brand->vendorSetting?->coupon_discount_percent !== null
                    ? (float) $brand->vendorSetting->coupon_discount_percent
                    : null,
            ] : null,
            'relatedProducts' => $relatedProducts,
            'reviews' => $reviews,
            'seo' => $seo,
        ]);
    }

    public function show(Request $request, $slug)
    {
        // Find category by slug
        $category = ProductCategory::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            abort(404, 'Category not found');
        }

        // Start building query for products in this category
        $query = Product::with(['brand.vendorSetting', 'location', 'types', 'puses', 'category'])
            ->visible()
            ->where('product_category_id', $category->id)
            ->where('status', 'active');

        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
                //   ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if ($request->has('use') && $request->use) {
            $query->whereHas('puses', function ($q) use ($request) {
                $q->where('puses.id', $request->use);
            });
        }

        if ($request->has('type') && $request->type) {
            $query->whereHas('types', function ($q) use ($request) {
                $q->where('types.id', $request->type);
            });
        }

        if ($request->has('location') && $request->location) {
            // Match products by their own location or by the vendor's location (via brand vendor settings)
            $query->where(function ($q) use ($request) {
                $q->where('location_id', $request->location)
                  ->orWhereHas('brand.vendorSetting', function ($vendorSettingQuery) use ($request) {
                      $vendorSettingQuery->where('location_id', $request->location);
                  });
            });
        }

        if ($request->has('verification') && $request->verification !== '') {
            $query->where('verified', $request->verification === '1' || $request->verification === 'true');
        }

        if ($request->has('brand') && $request->brand) {
            $query->where('brand_id', $request->brand);
        }

        // Dosage size filter — exact string match (size_mg is now varchar).
        if ($request->has('size') && $request->size) {
            $query->where('size_mg', (string) $request->size);
        }

        if ($request->has('cost_min') && $request->cost_min) {
            $query->where('price', '>=', $request->cost_min);
        }

        if ($request->has('cost_max') && $request->cost_max) {
            $query->where('price', '<=', $request->cost_max);
        }

        if ($request->has('in_stock') && $request->in_stock === '1') {
            $query->where('availability', 'in_stock');
        }

        // On Sale filter - product has a discount price
        if ($request->has('on_sale') && $request->on_sale === '1') {
            $query->whereNotNull('discount_price');
        }

        // Lab Tested filter
        if ($request->has('lab_tested') && $request->lab_tested === '1') {
            $query->where('lab_tested', true);
        }

        // First-Timer Deals filter
        if ($request->has('first_timer_deals') && $request->first_timer_deals === '1') {
            $query->where('first_timer_deals', true);
        }

        if ($request->has('min_purity') && $request->min_purity) {
            $minPurity = (float) $request->min_purity;
            // Use real purity column from database
            $query->whereNotNull('purity')
                  ->where('purity', '>=', $minPurity);
        }

        // Apply sorting - default to price ascending
        // When sorting by price, use discount_price if available, otherwise use price
        $sortBy = $request->get('sort', 'price');
        $sortDir = in_array(strtolower($request->get('sort_dir', 'asc')), ['asc', 'desc']) 
            ? strtolower($request->get('sort_dir', 'asc')) 
            : 'asc';
        
        if ($sortBy === 'price') {
            $query->orderByRaw('COALESCE(discount_price, price) ' . $sortDir);
        } elseif ($sortBy === 'popular') {
            // Sort by review count (rating_count) in descending order
            $query->orderBy('rating_count', 'desc');
        } elseif ($sortBy === 'reviews') {
            // Sort by review count (rating_count) in the specified direction
            $query->orderBy('rating_count', $sortDir);
        } elseif ($sortBy === 'rating') {
            // Sort by rating average (rating_average) in the specified direction
            $query->orderBy('rating_average', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        // Category pages should show every product in the category, not a
        // paginated slice — researchers compare vendor pricing across the
        // whole catalog for one compound, so cutting it off at page 1 hides
        // exactly the data they came for. Default per_page to a high cap
        // and respect an explicit smaller override if a user/bot wants one.
        $perPage = (int) $request->get('per_page', 1000);
        if ($perPage < 1 || $perPage > 1000) {
            $perPage = 1000;
        }
        $products = $query->paginate($perPage)->withQueryString();

        // Get filter options for this category
        $baseQuery = Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id);

        // Available dosage sizes for this category — string column now,
        // sort by the leading numeric token so "5mg" comes before "10mg"
        // and bare blends sort to the end naturally.
        $availableSizes = (clone $baseQuery)
            ->whereNotNull('size_mg')
            ->where('size_mg', '!=', '')
            ->selectRaw('DISTINCT size_mg')
            ->pluck('size_mg')
            ->filter()
            ->sortBy(fn ($s) => (float) preg_replace('/^([0-9.]+).*/', '$1', (string) $s))
            ->values();

        $filterOptions = [
            'brands' => Brand::whereHas('products', function ($q) use ($category) {
                $q->visible()
                  ->where('status', 'active')
                  ->where('product_category_id', $category->id);
            })->get(['id', 'name']),
            'sizes' => $availableSizes,
        ];

        // Get price range
        $priceRange = $baseQuery->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        // Generate SEO data for category listing (automatically from category fields)
        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap';
        $categoryUrl = url("/product/{$slug}");
        
        // Build category image URL
        $categoryImage = null;
        if ($category->image_url) {
            $categoryImage = Storage::url('categories/' . $category->image_url);
        }
        
        // Build title: {CategoryName} – Peptidemap
        $seoTitle = $category->name . ' – ' . $siteName;
        
        // Build description: first ~150-160 chars of category description
        $seoDescription = $category->description 
            ? $this->safeLimit($category->description, 155) 
            : 'Browse ' . $category->name . ' peptides. Compare products, prices, and vendors.';
        
        // Build SEO array (same format as other pages)
        $seo = [
            'key' => 'product-listing',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoTitle,
            'og_description' => $seoDescription,
            'og_image' => $categoryImage,
            // Backward-compatible field used by some pages
            'image' => $categoryImage,
            'url' => $categoryUrl,
            'canonical' => $categoryUrl,
        ];
        
        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/ProductListing', [
            'category' => [
                'id' => $category->id,
                'name' => strtoupper($category->name),
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'productName' => strtoupper($category->name), // Keep for backward compatibility
            'slug' => $slug,
            'products' => $products,
            'filterOptions' => $filterOptions,
            'priceRange' => [
                'min' => $priceRange->min_price ?? 0,
                'max' => $priceRange->max_price ?? 1000,
            ],
            'filters' => $request->only(['use', 'type', 'location', 'verification', 'brand', 'cost_min', 'cost_max']),
            'sort' => $sortBy,
            'sortDir' => $sortDir,
            'search' => $request->get('search', ''),
            'seo' => $seo,
        ]);
    }

    public function byBrand(Request $request, $slug)
    {
        $brand = Brand::with(['approvedReviews.user'])->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $brandId = $brand->id;

        // Build query for all products of this brand. Filter out $0 rows —
        // those are broken scrapes (Peptiva has 11 like this today) and
        // they render as ghost cards on the brand page with no price/image.
        $query = Product::with(['brand.vendorSetting', 'location', 'types', 'puses', 'category'])
            ->visible()
            ->where('brand_id', $brandId)
            ->where('status', 'active')
            ->where('price', '>', 0);

        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
                //   ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if ($request->has('use') && $request->use) {
            $query->whereHas('puses', function ($q) use ($request) {
                $q->where('puses.id', $request->use);
            });
        }

        if ($request->has('type') && $request->type) {
            $query->whereHas('types', function ($q) use ($request) {
                $q->where('types.id', $request->type);
            });
        }

        if ($request->has('location') && $request->location) {
            // Match products by their own location or by the vendor's location (via brand vendor settings)
            $query->where(function ($q) use ($request) {
                $q->where('location_id', $request->location)
                  ->orWhereHas('brand.vendorSetting', function ($vendorSettingQuery) use ($request) {
                      $vendorSettingQuery->where('location_id', $request->location);
                  });
            });
        }

        if ($request->has('verification') && $request->verification !== '') {
            $query->where('verified', $request->verification === '1' || $request->verification === 'true');
        }

        if ($request->has('cost_min') && $request->cost_min) {
            $query->where('price', '>=', $request->cost_min);
        }

        if ($request->has('cost_max') && $request->cost_max) {
            $query->where('price', '<=', $request->cost_max);
        }

        if ($request->has('in_stock') && $request->in_stock === '1') {
            $query->where('availability', 'in_stock');
        }

        if ($request->has('min_purity') && $request->min_purity) {
            $minPurity = (float) $request->min_purity;
            // Use real purity column from database
            $query->whereNotNull('purity')
                  ->where('purity', '>=', $minPurity);
        }

        // Apply sorting - default to price ascending
        // When sorting by price, use discount_price if available, otherwise use price
        $sortBy = $request->get('sort', 'price');
        $sortDir = in_array(strtolower($request->get('sort_dir', 'asc')), ['asc', 'desc']) 
            ? strtolower($request->get('sort_dir', 'asc')) 
            : 'asc';
        
        if ($sortBy === 'price') {
            $query->orderByRaw('COALESCE(discount_price, price) ' . $sortDir);
        } elseif ($sortBy === 'popular') {
            // Sort by review count (rating_count) in descending order
            $query->orderBy('rating_count', 'desc');
        } elseif ($sortBy === 'reviews') {
            // Sort by review count (rating_count) in the specified direction
            $query->orderBy('rating_count', $sortDir);
        } elseif ($sortBy === 'rating') {
            // Sort by rating average (rating_average) in the specified direction
            $query->orderBy('rating_average', $sortDir);
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        // Paginate — vendor pages now use a 12-item grid so each product
        // image gets room to breathe. Users can opt into larger pages via
        // ?per_page=N up to a sane cap.
        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 1 || $perPage > 1000) {
            $perPage = 12;
        }
        $products = $query->paginate($perPage)->withQueryString();

        // Get filter options for this brand
        $baseQuery = Product::visible()
            ->where('status', 'active')
            ->where('brand_id', $brandId);

        $filterOptions = [
            'uses' => Puse::whereHas('products', function ($q) use ($brandId) {
                $q->visible()
                  ->where('status', 'active')
                  ->where('brand_id', $brandId);
            })->get(['id', 'name']),
            'types' => Type::whereHas('products', function ($q) use ($brandId) {
                $q->visible()
                  ->where('status', 'active')
                  ->where('brand_id', $brandId);
            })->get(['id', 'name']),
            'brands' => Brand::where('id', $brandId)->get(['id', 'name']),
            // Provide all locations from the locations table so the filter always shows full list
            'locations' => Location::orderBy('name')->get(['id', 'name']),
        ];

        // Get price range
        $priceRange = $baseQuery->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        // Get brand details with vendor settings and reviews
        $brand->load(['vendorSetting.location', 'approvedReviews.user']);
        
        // Get initials for logo
        $words = explode(' ', $brand->name);
        $initials = count($words) >= 2 
            ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
            : strtoupper(substr($brand->name, 0, 2));

        // Get location - prefer vendor setting location if set; otherwise derive from products
        $location = null;
        if ($brand->vendorSetting && $brand->vendorSetting->location) {
            $location = $brand->vendorSetting->location;
        } else {
            $locationId = Product::visible()
                ->where('status', 'active')
                ->where('brand_id', $brand->id)
                ->whereNotNull('location_id')
                ->selectRaw('location_id, COUNT(*) as count')
                ->groupBy('location_id')
                ->orderByDesc('count')
                ->first()
                ?->location_id;
            
            if (!$locationId) {
                $locationId = Product::visible()
                    ->where('status', 'active')
                    ->where('brand_id', $brand->id)
                    ->whereNotNull('location_id')
                    ->value('location_id');
            }
            
            $location = $locationId ? Location::find($locationId) : null;
        }

        // Calculate grading averages from reviews if available
        $approvedReviews = $brand->approvedReviews;
        $shippingTime = $approvedReviews->whereNotNull('shipping_time')->avg('shipping_time') ?? 0;
        $customerService = $approvedReviews->whereNotNull('customer_service')->avg('customer_service') ?? 0;
        $quality = $approvedReviews->whereNotNull('quality')->avg('quality') ?? 0;
        $cost = $approvedReviews->whereNotNull('cost')->avg('cost') ?? 0;
        $packaging = $approvedReviews->whereNotNull('packaging')->avg('packaging') ?? 0;

        // Map reviews with all fields including review comment
        $verifiedMap = \App\Models\VendorReview::computeVerifiedMap($approvedReviews);
        $mappedReviews = $approvedReviews->map(function ($review) use ($verifiedMap) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'review' => $review->review ?? '', // Ensure review field is included
                'user_name' => $review->user ? $review->user->name : $review->user_name,
                'user_email' => $review->user_email,
                'user_id' => $review->user_id,
                'created_at' => $review->created_at->format('Y-m-d'),
                'shipping_time' => $review->shipping_time,
                'customer_service' => $review->customer_service,
                'quality' => $review->quality,
                'cost' => $review->cost,
                'packaging' => $review->packaging,
                'verified' => $verifiedMap[$review->id] ?? false,
            ];
        });

        // Get discount code: check for active deal first, then vendorSetting coupon_code, then default to PMAP
        $discountCode = 'PMAP';
        if (Schema::hasTable('deals')) {
            $activeDeal = Deal::where('brand_id', $brand->id)
                ->where('active', true)
                ->where(function ($query) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('usage_limit')
                        ->orWhereRaw('used_count < usage_limit');
                })
                ->first();
            
            if ($activeDeal) {
                $discountCode = $activeDeal->code;
            } elseif ($brand->vendorSetting && $brand->vendorSetting->coupon_code) {
                $discountCode = $brand->vendorSetting->coupon_code;
            }
        } elseif ($brand->vendorSetting && $brand->vendorSetting->coupon_code) {
            $discountCode = $brand->vendorSetting->coupon_code;
        }

        // Generate SEO data for brand products
        // Priority: Use stored SEO data from database, fallback to auto-generated from vendor fields
        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'PeptideMap';
        $brandUrl = url("/brand/{$slug}");
        $brandImage = $this->getBrandLogoUrl($brand);
        
        // Check if stored SEO data exists in vendorSetting
        $vendorSetting = $brand->vendorSetting;
        $hasStoredSeo = $vendorSetting && (!empty($vendorSetting->seo_page_title) || !empty($vendorSetting->seo_description));

        // Search-intent-optimized default. Beats the previous "we are a
        // family-owned company…" truncation which had zero query match.
        // Uses real counts so the description carries specificity Google
        // rewards, plus the "coupon codes" trigger that competitors miss.
        $brandProductCount = \App\Models\Product::visible()->where('brand_id', $brand->id)->count();
        $otherVendorCount = \App\Models\Brand::where('is_active', true)
            ->where('id', '!=', $brand->id)
            ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
            ->count();
        $couponClause = ($vendorSetting && !empty($vendorSetting->coupon_code))
            ? "Coupon code {$vendorSetting->coupon_code}, "
            : '';
        $defaultBrandDescription = "{$couponClause}real customer reviews, and live prices for {$brandProductCount} peptides from {$brand->name}. Compare against {$otherVendorCount} other verified vendors on Peptidemap.";

        if ($hasStoredSeo) {
            // Use stored SEO data from database
            $seoTitle = $vendorSetting->seo_page_title ?: ($brand->name . ': Coupon Codes, Reviews & Prices - ' . $siteName);
            $seoDescription = $vendorSetting->seo_description ?: $defaultBrandDescription;
            $seoOgTitle = $vendorSetting->seo_og_title ?: $seoTitle;
            $seoOgDescription = $vendorSetting->seo_og_description ?: $seoDescription;
            // updated_at cache-buster so social platforms refetch when the brand changes.
            $ogV = ($brand->updated_at?->timestamp) ?: ($vendorSetting->updated_at?->timestamp ?? 0);
            $seoOgImage = $vendorSetting->seo_og_image
                ? (str_starts_with($vendorSetting->seo_og_image, 'http') ? $vendorSetting->seo_og_image : url($vendorSetting->seo_og_image))
                : route('og.brand', ['slug' => $brand->slug]) . '?v=' . $ogV;
        } else {
            $seoTitle = $brand->name . ': Coupon Codes, Reviews & Prices - ' . $siteName;
            $seoDescription = $defaultBrandDescription;
            $seoOgTitle = $seoTitle;
            $seoOgDescription = $seoDescription;
            $ogV = ($brand->updated_at?->timestamp) ?: ($vendorSetting->updated_at?->timestamp ?? 0);
            $seoOgImage = route('og.brand', ['slug' => $brand->slug]) . '?v=' . $ogV;
        }
        
        // ItemList JSON-LD for this brand's products (rendered by app.blade.php)
        $itemListSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => "Products from {$brand->name}",
            'numberOfItems' => $products->total(),
            'itemListElement' => collect($products->items())->take(20)->values()->map(fn ($p, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'url' => url("/product/{$p->slug}/{$p->id}"),
                'name' => $p->display_name,
            ])->all(),
        ];

        // Build SEO array (same format as other pages)
        $seo = [
            'key' => 'brand',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoOgTitle,
            'og_description' => $seoOgDescription,
            'og_image' => $seoOgImage,
            // Backward-compatible field used by some pages
            'image' => $seoOgImage,
            'url' => $brandUrl,
            'canonical' => $brandUrl,
            'schema' => [$itemListSchema],
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/BrandProducts', [
            'brand' => [
                'id' => $brand->id,
                'slug' => $brand->slug,
                'name' => $brand->name,
                'initials' => $initials,
                'rating' => (float) ($brand->rating_average ?? 0),
                'reviews' => (int) ($brand->rating_count ?? 0),
                'description' => $brand->vendorSetting->description ?? 'Premium quality peptides for research purposes.',
                'shop_url' => $brand->vendorSetting->shop_url ?? null,
                'contact_email' => $brand->vendorSetting->contact_email ?? null,
                'phone_number' => $brand->vendorSetting->phone_number ?? null,
                'logo' => $this->getBrandLogoUrl($brand),
                'banner' => $brand->vendorSetting && $brand->vendorSetting->banner ? asset('storage/' . $brand->vendorSetting->banner) : null,
                'banner_image_url' => $brand->vendorSetting->banner_image_url ?? null,
                'location' => $location ? $location->name : null,
                'is_partner' => $brand->vendorSetting && $brand->vendorSetting->is_partner ? true : false,
                'founded_year' => $brand->vendorSetting && $brand->vendorSetting->founded_year ? $brand->vendorSetting->founded_year : null,
                'trustpilot_url' => $brand->vendorSetting->trustpilot_url ?? null,
                'google_reviews_url' => $brand->vendorSetting->google_reviews_url ?? null,
                'pepreviewpro_url' => $brand->vendorSetting->pepreviewpro_url ?? null,
                // External review platforms + aggregated scores. Populated
                // by `php artisan reviews:refresh {slug}` (weekly cron).
                // `external_ratings` is per-platform data; `external_rating_avg`
                // is the count-weighted mean across platforms with real numbers.
                'external_ratings' => $brand->vendorSetting->external_ratings_json ?? [],
                'external_rating_avg' => $brand->vendorSetting->external_rating_avg
                    ? (float) $brand->vendorSetting->external_rating_avg : null,
                'external_rating_count' => (int) ($brand->vendorSetting->external_rating_count ?? 0),
                // Vendor-declared certifications only. Empty array hides the
                // panel — never fall back to a default list (IDUN flagged that
                // as an unearned FDA/ISO claim in Aug 2026).
                'certifications' => (function () use ($brand) {
                    $raw = $brand->vendorSetting->certifications ?? null;
                    if (is_array($raw)) return array_values(array_filter($raw));
                    if (is_string($raw) && trim($raw)) {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded)) return array_values(array_filter($decoded));
                    }
                    return [];
                })(),
                'shipping_info' => $brand->vendorSetting && $brand->vendorSetting->shipping_info ? $brand->vendorSetting->shipping_info : null,
                'return_policy' => $brand->vendorSetting && $brand->vendorSetting->return_policy ? $brand->vendorSetting->return_policy : null,
                'payment_methods' => $brand->vendorSetting && $brand->vendorSetting->payment_methods ? $brand->vendorSetting->payment_methods : [],
                'discount_code' => $discountCode,
                'affiliate_visit_url' => $this->buildAffiliateVisitUrl($brand),
                'shipping_time' => round($shippingTime, 1),
                'customer_service' => round($customerService, 1),
                'quality' => round($quality, 1),
                'cost' => round($cost, 1),
                'packaging' => round($packaging, 1),
            ],
            'reviews' => $mappedReviews,
            // External reviews (Trustpilot, etc.) seeded via
            // `php artisan reviews:import-trustpilot`. Displayed under the
            // native reviews section with attribution + link back to the
            // source page.
            'externalReviews' => \App\Models\ExternalReview::where('brand_id', $brand->id)
                ->orderByDesc('published_at')
                ->limit(30)
                ->get(['id', 'source', 'author', 'author_location', 'rating', 'title', 'body', 'source_url', 'published_at'])
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'source' => $r->source,
                    'author' => $r->author,
                    'author_location' => $r->author_location,
                    'rating' => $r->rating,
                    'title' => $r->title,
                    'body' => $r->body,
                    'source_url' => $r->source_url,
                    'published_at' => $r->published_at?->format('M j, Y'),
                ]),
            'products' => $products,
            'filterOptions' => $filterOptions,
            'priceRange' => [
                'min' => $priceRange->min_price ?? 0,
                'max' => $priceRange->max_price ?? 1000,
            ],
            'filters' => $request->only(['use', 'type', 'location', 'verification', 'cost_min', 'cost_max']),
            'sort' => $sortBy,
            'sortDir' => $sortDir,
            'search' => $request->get('search', ''),
            'seo' => $seo,
        ]);
    }

    /**
     * Get brand logo URL if file exists in storage
     */
    private function getBrandLogoUrl($brand)
    {
        if ($brand->vendorSetting && $brand->vendorSetting->logo) {
            // Check if the file actually exists in storage
            if (Storage::disk('public')->exists($brand->vendorSetting->logo)) {
                return asset('storage/' . $brand->vendorSetting->logo);
            }
        }
        return null;
    }

    /**
     * Build an affiliate-tagged URL for the 'Visit website' button.
     * Priority order matches OutboundClickController::resolveDestinationUrl:
     *   1. vendor_settings.referral_url (the affiliate program's canonical
     *      tracked URL — single source of truth per vendor)
     *   2. brands.affiliate_url_template applied to shop_url
     *   3. raw shop_url
     */
    protected function buildAffiliateVisitUrl($brand): ?string
    {
        $referral = $brand->vendorSetting->referral_url ?? null;
        if (!empty($referral)) {
            return $referral;
        }

        $shopUrl = $brand->vendorSetting->shop_url ?? null;
        if (!$shopUrl) return null;

        $template = $brand->affiliate_url_template;
        if (empty($template)) return $shopUrl;

        return strtr($template, [
            '{product_url}' => $shopUrl,
            '{slug}' => $brand->slug ?? '',
            '{id}' => (string) $brand->id,
            '{affiliate_tag}' => (string) ($brand->affiliate_tag ?? ''),
        ]);
    }
}
