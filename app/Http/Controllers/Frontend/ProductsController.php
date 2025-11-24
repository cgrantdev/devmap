<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
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
        // Group products by name and count items
        $productGroups = Product::select('name')
            ->selectRaw('count(*) as total_items')
            ->selectRaw('MIN(image_url) as image_url')
            ->groupBy('name')
            ->orderBy('name')
            ->get()
            ->map(function ($group) {
                return [
                    'name' => $group->name,
                    'total_items' => $group->total_items,
                    'image' => $group->image_url ?: '/images/peptides/default.png',
                    'slug' => Str::slug($group->name),
                ];
            });
        
        return Inertia::render('Frontend/Products', [
            'productGroups' => $productGroups,
        ]);
    }

    public function show(Request $request, $slug)
    {
        // Find product name by slug - get distinct product names and find matching slug
        $productName = Product::select('name')
            ->distinct()
            ->get()
            ->first(function ($product) use ($slug) {
                return Str::slug($product->name) === $slug;
            })?->name;

        if (!$productName) {
            abort(404, 'Product not found');
        }

        // Start building query
        $query = Product::with(['user', 'brand', 'location', 'types', 'puses'])
            ->where('name', $productName);

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

        // Apply sorting
        $sortBy = $request->get('sort', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        // Paginate
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage)->withQueryString();

        // Get filter options for this product name
        $baseQuery = Product::where('name', $productName);

        $filterOptions = [
            'uses' => Puse::whereHas('products', function ($q) use ($productName) {
                $q->where('name', $productName);
            })->get(['id', 'name']),
            'types' => Type::whereHas('products', function ($q) use ($productName) {
                $q->where('name', $productName);
            })->get(['id', 'name']),
            'brands' => Brand::whereHas('products', function ($q) use ($productName) {
                $q->where('name', $productName);
            })->get(['id', 'name']),
            'locations' => Location::whereHas('products', function ($q) use ($productName) {
                $q->where('name', $productName);
            })->get(['id', 'name']),
        ];

        // Get price range
        $priceRange = $baseQuery->selectRaw('MIN(price) as min_price, MAX(price) as max_price')->first();

        return Inertia::render('Frontend/ProductListing', [
            'productName' => $productName,
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
}
