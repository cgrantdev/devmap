<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompareController extends Controller
{
    public function index(Request $request)
    {
        // Get products for search/selection
        $query = Product::with(['brand', 'category', 'location'])
            ->visible()
            ->where('status', 'active');

        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Limit results for search
        $products = $query->orderBy('name')
            ->take(50) // Limit to 50 for performance
            ->get()
            ->map(function ($product) {
                // Extract purity from name
                $purity = null;
                if (preg_match('/(\d+(?:\.\d+)?)\s*%/i', $product->name, $matches)) {
                    $purity = (float) $matches[1];
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'discount_price' => $product->discount_price,
                    'image_url' => $product->image_url,
                    'brand_name' => $product->brand ? $product->brand->name : null,
                    'brand_slug' => $product->brand ? ($product->brand->slug ?? null) : null,
                    'rating_average' => number_format($product->rating_average ?? 0, 1, '.', ''),
                    'rating_count' => (int) ($product->rating_count ?? 0),
                    'purity' => $purity,
                    'size_mg' => $product->size_mg,
                    'availability' => $product->availability,
                    'verified' => $product->verified ?? false,
                    'category_name' => $product->category ? $product->category->name : null,
                ];
            });

        // Get selected product IDs from query string
        $selectedIds = $request->get('selected', '');
        $selectedIds = $selectedIds ? explode(',', $selectedIds) : [];

        // Get selected products details
        $selectedProducts = [];
        if (!empty($selectedIds)) {
            $selectedProducts = Product::with(['brand', 'category', 'location'])
                ->visible()
                ->where('status', 'active')
                ->whereIn('id', $selectedIds)
                ->get()
                ->map(function ($product) {
                    $purity = null;
                    if (preg_match('/(\d+(?:\.\d+)?)\s*%/i', $product->name, $matches)) {
                        $purity = (float) $matches[1];
                    }

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'price' => $product->price,
                        'discount_price' => $product->discount_price,
                        'image_url' => $product->image_url,
                        'brand_name' => $product->brand ? $product->brand->name : null,
                        'brand_slug' => $product->brand ? ($product->brand->slug ?? null) : null,
                        'rating_average' => number_format($product->rating_average ?? 0, 1, '.', ''),
                        'rating_count' => (int) ($product->rating_count ?? 0),
                        'purity' => $purity,
                        'size_mg' => $product->size_mg,
                        'availability' => $product->availability,
                        'verified' => $product->verified ?? false,
                        'category_name' => $product->category ? $product->category->name : null,
                        'description' => $product->description,
                        'product_url' => $product->product_url,
                    ];
                })
                ->values();
        }

        return Inertia::render('Frontend/Compare', [
            'products' => $products,
            'selectedProducts' => $selectedProducts,
            'search' => $request->get('search', ''),
            'selectedIds' => $selectedIds,
        ]);
    }
}
