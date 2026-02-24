<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
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
    public function index()
    {
        // Get all active product categories with product counts
        $categories = ProductCategory::where('is_active', true)
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                // Use category image if available, otherwise get a sample product image
                $image = null;
                if ($category->image_url) {
                    $image = \Illuminate\Support\Facades\Storage::url('categories/' . $category->image_url);
                } else {
                // Get a sample product image for this category
                $sampleProduct = Product::visible()
                    ->where('status', 'active')
                    ->where('product_category_id', $category->id)
                    ->whereNotNull('image_url')
                    ->first();
                    $image = $sampleProduct ? $sampleProduct->image_url : '/images/peptides/default.png';
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
        
        // Generate SEO data
        $seoData = new SEOData(
            title: 'Research Peptides - Browse All Products | PeptideSync',
            description: 'Browse our comprehensive collection of research peptides. Compare products, prices, and vendors to find the best peptides for your research needs.',
            url: url('/products'),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/Products', [
            'productGroups' => $categories,
        ]);
    }

    public function showProduct(Request $request, $slug, $id)
    {
        // Find product by id and slug
        $product = Product::with(['brand', 'location', 'types', 'puses', 'category', 'brand.vendorSetting'])
            ->visible()
            ->where('id', $id)
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            abort(404, 'Product not found');
        }

        // Get related products (same category, different product)
        $relatedProducts = Product::with(['brand'])
            ->visible()
            ->where('product_category_id', $product->product_category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'image_url' => $p->image_url,
                    'price' => $p->price,
                    'discount_price' => $p->discount_price,
                    'brand' => $p->brand ? ['name' => $p->brand->name] : null,
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
            
            $reviews = $approvedReviews->map(function ($review) {
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
        $productImage = $product->image_url ? (str_starts_with($product->image_url, 'http') ? $product->image_url : url($product->image_url)) : null;
        $seoData = new SEOData(
            title: $product->name . ' - Product Details | PeptideSync',
            description: $product->description ? Str::limit(strip_tags($product->description), 160) : 'View detailed information about ' . $product->name . '. Compare prices, read reviews, and find the best deals.',
            image: $productImage,
            url: url("/product/{$product->slug}/{$product->id}"),
        );
        session(['page_seo_data' => $seoData]);

        return Inertia::render('Frontend/ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
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
            ] : null,
            'relatedProducts' => $relatedProducts,
            'reviews' => $reviews,
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
        $query = Product::with(['brand', 'location', 'types', 'puses', 'category'])
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

        // Paginate
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage)->withQueryString();

        // Get filter options for this category
        $baseQuery = Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id);

        $filterOptions = [
            'uses' => Puse::whereHas('products', function ($q) use ($category) {
                $q->visible()
                  ->where('status', 'active')
                  ->where('product_category_id', $category->id);
            })->get(['id', 'name']),
            'types' => Type::whereHas('products', function ($q) use ($category) {
                $q->visible()
                  ->where('status', 'active')
                  ->where('product_category_id', $category->id);
            })->get(['id', 'name']),
            'brands' => Brand::whereHas('products', function ($q) use ($category) {
                $q->visible()
                  ->where('status', 'active')
                  ->where('product_category_id', $category->id);
            })->get(['id', 'name']),
            // Provide all locations from the locations table so the filter always shows full list
            'locations' => Location::orderBy('name')->get(['id', 'name']),
        ];

        // Get price range
        $priceRange = $baseQuery->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        // Generate SEO data for category listing
        $categoryImage = null;
        if ($category->image_url) {
            $categoryImage = Storage::url('categories/' . $category->image_url);
        }
        $seoData = new SEOData(
            title: $category->name . ' - Research Peptides | PeptideSync',
            description: $category->description ? Str::limit(strip_tags($category->description), 160) : 'Browse ' . $category->name . ' research peptides. Compare products, prices, and vendors.',
            image: $categoryImage,
            url: url("/product/{$slug}"),
        );
        session(['page_seo_data' => $seoData]);

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
        ]);
    }

    public function byBrand(Request $request, $slug)
    {
        $brand = Brand::with(['approvedReviews.user'])->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $brandId = $brand->id;

        // Build query for all products of this brand
        $query = Product::with(['brand', 'location', 'types', 'puses', 'category'])
            ->visible()
            ->where('brand_id', $brandId)
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

        // Paginate
        $perPage = $request->get('per_page', 20);
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
        $mappedReviews = $approvedReviews->map(function ($review) {
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
        $brandImage = $this->getBrandLogoUrl($brand);
        $seoData = new SEOData(
            title: $brand->name . ' - Products & Reviews | PeptideSync',
            description: ($brand->vendorSetting->description ?? '') ? Str::limit(strip_tags($brand->vendorSetting->description), 160) : 'Browse products from ' . $brand->name . '. Read reviews, compare prices, and find the best deals.',
            image: $brandImage,
            url: url("/brand/{$slug}/products"),
        );
        session(['page_seo_data' => $seoData]);

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
                'location' => $location ? $location->name : null,
                'is_partner' => $brand->vendorSetting && $brand->vendorSetting->is_partner ? true : false,
                'founded_year' => $brand->vendorSetting && $brand->vendorSetting->founded_year ? $brand->vendorSetting->founded_year : null,
                'shipping_info' => $brand->vendorSetting && $brand->vendorSetting->shipping_info ? $brand->vendorSetting->shipping_info : null,
                'return_policy' => $brand->vendorSetting && $brand->vendorSetting->return_policy ? $brand->vendorSetting->return_policy : null,
                'discount_code' => $discountCode,
                'shipping_time' => round($shippingTime, 1),
                'customer_service' => round($customerService, 1),
                'quality' => round($quality, 1),
                'cost' => round($cost, 1),
                'packaging' => round($packaging, 1),
            ],
            'reviews' => $mappedReviews,
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
}
