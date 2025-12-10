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
        // Get active brands with product counts and aggregated data
        $brands = Brand::where('is_active', true)
            ->with(['vendorSetting', 'vendorSetting.location'])
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(function ($brand) {
                // Prefer vendor setting location if set; otherwise derive from products
                $location = null;
                if ($brand->vendorSetting && $brand->vendorSetting->location) {
                    $location = $brand->vendorSetting->location;
                } else {
                    $locationId = Product::where('brand_id', $brand->id)
                        ->whereNotNull('location_id')
                        ->selectRaw('location_id, COUNT(*) as count')
                        ->groupBy('location_id')
                        ->orderByDesc('count')
                        ->first()
                        ?->location_id;
                    
                    if (!$locationId) {
                        $locationId = Product::where('brand_id', $brand->id)
                            ->whereNotNull('location_id')
                            ->value('location_id');
                    }
                    
                    $location = $locationId ? Location::find($locationId) : null;
                }
                
                // Get logo URL from vendor settings
                $logoUrl = null;
                if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                    $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                }
                
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'product_count' => $brand->products_count,
                    'slug' => $brand->slug ?? Str::slug($brand->name),
                    'initials' => self::getInitials($brand->name),
                    'logo' => $logoUrl,
                    'location' => $location ? $location->name : null,
                    'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                    'reviews' => (int) ($brand->rating_count ?? 0),
                ];
            });
        
        return Inertia::render('Frontend/Brands', [
            'brands' => $brands,
        ]);
    }

    /**
     * Get initials from brand name
     */
    private static function getInitials($name)
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

