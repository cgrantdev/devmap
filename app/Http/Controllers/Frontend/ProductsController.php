<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Type;
use App\Models\Puse;
use App\Models\Brand;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductsController extends Controller
{
    public function index()
    {
        // Get all active product categories with product counts
        $categories = ProductCategory::where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                // Get a sample product image for this category
                $sampleProduct = Product::where('product_category_id', $category->id)
                    ->whereNotNull('image_url')
                    ->first();
                
                return [
                    'id' => $category->id,
                    'name' => strtoupper($category->name),
                    'slug' => $category->slug,
                    'total_items' => $category->products_count,
                    'image' => $sampleProduct ? $sampleProduct->image_url : ($category->image_url ?: '/images/peptides/default.png'),
                    'description' => $category->description,
                ];
            });
        
        return Inertia::render('Frontend/Products', [
            'productGroups' => $categories,
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
            ->where('product_category_id', $category->id)
            ->where('status', 'active');

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
            $query->where('location_id', $request->location);
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

        // Apply sorting - default to price ascending
        $sortBy = $request->get('sort', 'price');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Paginate
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage)->withQueryString();

        // Get filter options for this category
        $baseQuery = Product::where('product_category_id', $category->id);

        $filterOptions = [
            'uses' => Puse::whereHas('products', function ($q) use ($category) {
                $q->where('product_category_id', $category->id);
            })->get(['id', 'name']),
            'types' => Type::whereHas('products', function ($q) use ($category) {
                $q->where('product_category_id', $category->id);
            })->get(['id', 'name']),
            'brands' => Brand::whereHas('products', function ($q) use ($category) {
                $q->where('product_category_id', $category->id);
            })->get(['id', 'name']),
            'locations' => Location::whereHas('products', function ($q) use ($category) {
                $q->where('product_category_id', $category->id);
            })->get(['id', 'name']),
        ];

        // Get price range
        $priceRange = $baseQuery->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

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
        ]);
    }

    public function byBrand(Request $request, $slug)
    {
        $brand = Brand::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $brandId = $brand->id;

        // Build query for all products of this brand
        $query = Product::with(['brand', 'location', 'types', 'puses', 'category'])
            ->where('brand_id', $brandId)
            ->where('status', 'active');

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
            $query->where('location_id', $request->location);
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

        // Apply sorting - default to price ascending
        $sortBy = $request->get('sort', 'price');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Paginate
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage)->withQueryString();

        // Get filter options for this brand
        $baseQuery = Product::where('brand_id', $brandId);

        $filterOptions = [
            'uses' => Puse::whereHas('products', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            })->get(['id', 'name']),
            'types' => Type::whereHas('products', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            })->get(['id', 'name']),
            'brands' => Brand::where('id', $brandId)->get(['id', 'name']),
            'locations' => Location::whereHas('products', function ($q) use ($brandId) {
                $q->where('brand_id', $brandId);
            })->get(['id', 'name']),
        ];

        // Get price range
        $priceRange = $baseQuery->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        // Get brand details with vendor settings and reviews
        $brand->load(['vendorSetting', 'approvedReviews.user']);
        
        // Get initials for logo
        $words = explode(' ', $brand->name);
        $initials = count($words) >= 2 
            ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
            : strtoupper(substr($brand->name, 0, 2));

        return Inertia::render('Frontend/BrandProducts', [
            'brand' => [
                'id' => $brand->id,
                'slug' => $brand->slug,
                'name' => $brand->name,
                'initials' => $initials,
                'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                'reviews' => (int) ($brand->rating_count ?? 0),
                'description' => $brand->vendorSetting->description ?? 'Premium quality peptides for research purposes.',
                'shop_url' => $brand->vendorSetting->shop_url ?? null,
                'contact_email' => $brand->vendorSetting->contact_email ?? null,
                'phone_number' => $brand->vendorSetting->phone_number ?? null,
                'logo' => $brand->vendorSetting && $brand->vendorSetting->logo ? asset('storage/' . $brand->vendorSetting->logo) : null,
                'banner' => $brand->vendorSetting && $brand->vendorSetting->banner ? asset('storage/' . $brand->vendorSetting->banner) : null,
            ],
            'reviews' => $brand->approvedReviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'user_name' => $review->user ? $review->user->name : $review->user_name,
                    'created_at' => $review->created_at->format('M d, Y'),
                ];
            }),
            'products' => $products,
            'filterOptions' => $filterOptions,
            'priceRange' => [
                'min' => $priceRange->min_price ?? 0,
                'max' => $priceRange->max_price ?? 1000,
            ],
            'filters' => $request->only(['use', 'type', 'location', 'verification', 'cost_min', 'cost_max']),
            'sort' => $sortBy,
            'sortDir' => $sortDir,
        ]);
    }
}
