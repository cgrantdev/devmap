<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Location;
use Inertia\Inertia;
use Illuminate\Support\Str;

class BrandsController extends Controller
{
    public function index()
    {
        // Get brands that have products, with product counts and aggregated data
        $brands = Brand::withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get()
            ->map(function ($brand) {
                // Get most common location from products
                $locationId = Product::where('brand_id', $brand->id)
                    ->whereNotNull('location_id')
                    ->selectRaw('location_id, COUNT(*) as count')
                    ->groupBy('location_id')
                    ->orderByDesc('count')
                    ->first()
                    ?->location_id;
                
                // If no location from grouping, try to get any location from products
                if (!$locationId) {
                    $locationId = Product::where('brand_id', $brand->id)
                        ->whereNotNull('location_id')
                        ->value('location_id');
                }
                
                // If still no location, try to get from brand's user's products
                if (!$locationId && $brand->user_id) {
                    $locationId = Product::where('user_id', $brand->user_id)
                        ->whereNotNull('location_id')
                        ->selectRaw('location_id, COUNT(*) as count')
                        ->groupBy('location_id')
                        ->orderByDesc('count')
                        ->first()
                        ?->location_id;
                }
                
                $location = $locationId ? Location::find($locationId) : null;
                
                // Calculate average rating and total reviews
                $ratingData = Product::where('brand_id', $brand->id)
                    ->selectRaw('AVG(rating_average) as avg_rating, SUM(rating_count) as total_reviews')
                    ->first();
                
                $avgRating = $ratingData->avg_rating ?? 0;
                $totalReviews = $ratingData->total_reviews ?? 0;
                
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'product_count' => $brand->products_count,
                    'slug' => Str::slug($brand->name),
                    'initials' => $this->getInitials($brand->name),
                    'location' => $location ? $location->name : null,
                    'rating' => number_format($avgRating, 2, '.', ''),
                    'reviews' => (int) $totalReviews,
                ];
            });
        
        return Inertia::render('Frontend/Brands', [
            'brands' => $brands,
        ]);
    }

    /**
     * Get initials from brand name
     */
    private function getInitials($name)
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

