<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Location;
use App\Models\SeoPage;
use App\Models\Setting;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class BrandsController extends Controller
{
    public function index(Request $request)
    {
        // Get active and approved brands with product counts and aggregated data
        $query = Brand::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('vendorSetting', function ($subQ) {
                    $subQ->where('approval_status', 'approved');
                })
                ->orWhereDoesntHave('vendorSetting'); // For backwards compatibility with vendors without settings
            })
            ->with(['vendorSetting', 'vendorSetting.location'])
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ]);
        
        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('vendorSetting.location', function ($locationQuery) use ($search) {
                      $locationQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Apply location filter
        if ($request->has('location') && $request->location) {
            $locationName = $request->location;
            // Normalize location names - treat "United States" and "USA" as the same
            $normalizedLocation = $this->normalizeLocationName($locationName);
            
            $query->where(function ($q) use ($normalizedLocation) {
                $q->whereHas('vendorSetting.location', function ($locationQuery) use ($normalizedLocation) {
                    $locationQuery->where(function ($locQ) use ($normalizedLocation) {
                        $locQ->where('name', $normalizedLocation)
                             ->orWhereIn('name', $this->getLocationAliases($normalizedLocation));
                    });
                })->orWhereHas('products', function ($productQuery) use ($normalizedLocation) {
                    $productQuery->visible()
                        ->where('status', 'active')
                        ->whereHas('location', function ($locQuery) use ($normalizedLocation) {
                            $locQuery->where(function ($locQ) use ($normalizedLocation) {
                                $locQ->where('name', $normalizedLocation)
                                     ->orWhereIn('name', $this->getLocationAliases($normalizedLocation));
                            });
                        });
                });
            });
        }
        
        // Apply minimum rating filter
        if ($request->has('min_rating') && $request->min_rating) {
            $minRating = (float) $request->min_rating;
            $query->where('rating_average', '>=', $minRating);
        }
        
        // Apply top vendors only filter (based on top_vendor status property)
        if ($request->has('top_vendors_only') && $request->top_vendors_only === '1') {
            $query->whereHas('vendorSetting', function ($vendorSettingQuery) {
                $vendorSettingQuery->where('top_vendor', true);
            });
        }
        
        // Apply sorting
        $sortBy = $request->get('sort', 'rating');
        $sortDir = in_array(strtolower($request->get('sort_dir', 'desc')), ['asc', 'desc']) 
            ? strtolower($request->get('sort_dir', 'desc')) 
            : 'desc';
        
        if ($sortBy === 'rating') {
            $query->orderBy('rating_average', $sortDir);
        } elseif ($sortBy === 'reviews') {
            $query->orderBy('rating_count', $sortDir);
        } elseif ($sortBy === 'products') {
            $query->orderBy('products_count', $sortDir);
        } elseif ($sortBy === 'name') {
            $query->orderBy('name', $sortDir);
        } else {
            $query->orderBy('name', 'asc');
        }
        
        $brands = $query->get()->map(function ($brand) {
                // Prefer vendor setting location if set; otherwise derive from products
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
                
                $logoUrl = \App\Helpers\ImageHelper::resolveVendorLogo(
                    $brand->vendorSetting?->logo,
                    $brand->name
                );
                
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
                    'is_partner' => $brand->vendorSetting && $brand->vendorSetting->is_partner ? true : false,
                    'featured' => $brand->vendorSetting && $brand->vendorSetting->featured ? true : false,
                    'last_updated' => $brand->updated_at?->diffForHumans(null, true) ?? null,
                ];
            });
        
        // Generate SEO data (editable via Admin -> Settings -> SEO Pages, key: "brands")
        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap';
        $defaultTitle = 'Top Rated Peptide Vendors & Brands';
        $defaultDescription = 'Browse and compare top-rated peptide vendors and brands. Read reviews, compare prices, and find trusted suppliers for your research needs.';

        $seoPage = SeoPage::where('key', 'brands')->first();
        $seo = [
            'key' => 'brands',
            'title' => $seoPage?->title ?: $defaultTitle,
            'description' => $seoPage?->description ?: $defaultDescription,
            'og_title' => $seoPage?->og_title ?: ($seoPage?->title ?: $defaultTitle),
            'og_description' => $seoPage?->og_description ?: ($seoPage?->description ?: $defaultDescription),
            'og_image' => $seoPage?->og_image ?: null,
            // Backward-compatible field used by some pages
            'image' => $seoPage?->og_image ?: null,
            'url' => url('/brands'),
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/Brands', [
            'brands' => $brands,
            'search' => $request->get('search', ''),
            'sort' => $request->get('sort', 'rating'),
            'sortDir' => $request->get('sort_dir', 'desc'),
            'filters' => [
                'location' => $request->get('location', ''),
                'min_rating' => $request->get('min_rating', ''),
                'top_vendors_only' => $request->get('top_vendors_only', '0'),
            ],
            'seo' => $seo,
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

    /**
     * Normalize location name - treat "United States" and "USA" as the same
     */
    private function normalizeLocationName($locationName)
    {
        $normalized = trim($locationName);
        
        // Map variations to a canonical name
        $locationMap = [
            'usa' => 'United States',
            'united states' => 'United States',
            'us' => 'United States',
        ];
        
        $lowercase = strtolower($normalized);
        return $locationMap[$lowercase] ?? $normalized;
    }

    /**
     * Get all aliases for a location name
     */
    private function getLocationAliases($locationName)
    {
        $aliases = [];
        
        // If it's United States, also search for USA and US
        if (strtolower($locationName) === 'united states') {
            $aliases = ['USA', 'US', 'United States'];
        } elseif (in_array(strtoupper($locationName), ['USA', 'US'])) {
            $aliases = ['United States', 'USA', 'US'];
        }
        
        return array_unique($aliases);
    }
}

