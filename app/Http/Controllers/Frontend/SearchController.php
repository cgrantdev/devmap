<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Blog;
use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $location = $request->get('location', 'all');
        $tab = $request->get('tab', 'all');
        
        // Filters
        $verifiedOnly = $request->get('verified', false);
        $inStock = $request->get('in_stock', false);
        $ratingMin = $request->get('rating_min', 0);
        $usaBased = $request->get('usa_based', false);

        $results = [
            'vendors' => [],
            'products' => [],
            'encyclopedia' => [],
            'news' => [],
        ];

        if (!empty($query)) {
            // Search Vendors (Brands)
            if ($tab === 'all' || $tab === 'vendors') {
                $vendorsQuery = Brand::where('is_active', true)
                    ->with(['vendorSetting.location'])
                    ->withCount(['products' => function ($q) {
                        $q->visible()->where('status', 'active');
                    }]);

                if (!empty($query)) {
                    $vendorsQuery->where('name', 'like', "%{$query}%");
                }

                if ($verifiedOnly) {
                    // Consider vendors with reviews as verified, or use featured/top_vendor flags
                    $vendorsQuery->where(function ($q) {
                        $q->where('rating_count', '>', 0)
                          ->orWhereHas('vendorSetting', function ($vsQuery) {
                              $vsQuery->where('featured', true)
                                      ->orWhere('top_vendor', true);
                          });
                    });
                }

                if ($usaBased) {
                    $vendorsQuery->whereHas('vendorSetting.location', function ($q) {
                        $q->where('name', 'like', '%United States%');
                    });
                }

                if ($ratingMin > 0) {
                    $vendorsQuery->where('rating_average', '>=', $ratingMin);
                }

                $results['vendors'] = $vendorsQuery->limit(3)->get()->map(function ($brand) {
                    $location = $brand->vendorSetting && $brand->vendorSetting->location 
                        ? $brand->vendorSetting->location->name 
                        : 'Unknown';
                    
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'rating' => (float) ($brand->rating_average ?? 0),
                        'reviews_count' => (int) ($brand->rating_count ?? 0),
                        'location' => $location,
                        'products_count' => $brand->products_count ?? 0,
                        'verified' => ($brand->rating_count > 0) || ($brand->vendorSetting && ($brand->vendorSetting->featured || $brand->vendorSetting->top_vendor)),
                        'icon' => $this->getVendorIcon($brand->name),
                    ];
                });
            }

            // Search Products
            if ($tab === 'all' || $tab === 'products') {
                $productsQuery = Product::with(['brand', 'category'])
                    ->visible()
                    ->where('status', 'active');

                if (!empty($query)) {
                    $productsQuery->where('name', 'like', "%{$query}%");
                }

                if ($inStock) {
                    $productsQuery->where('availability', 'in_stock');
                }

                if ($ratingMin > 0) {
                    $productsQuery->where('rating_average', '>=', $ratingMin);
                }

                if ($usaBased) {
                    $productsQuery->whereHas('brand.vendorSetting.location', function ($q) {
                        $q->where('name', 'like', '%United States%');
                    });
                }

                $results['products'] = $productsQuery->limit(4)->get()->map(function ($product) {
                    $categoryName = $product->category ? $product->category->name : '';
                    $tag = $this->getProductTag($categoryName);
                    
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'brand_name' => $product->brand ? $product->brand->name : '',
                        'brand_slug' => $product->brand ? $product->brand->slug : '',
                        'price' => $product->price,
                        'discount_price' => $product->discount_price,
                        'rating' => (float) ($product->rating_average ?? 0),
                        'reviews_count' => (int) ($product->rating_count ?? 0),
                        'availability' => $product->availability,
                        'tag' => $tag,
                    ];
                });
            }

            // Search Encyclopedia (Product Categories)
            if ($tab === 'all' || $tab === 'encyclopedia') {
                $encyclopediaQuery = ProductCategory::where('is_active', true)
                    ->with('educationPost')
                    ->withCount([
                        'products as products_count' => function ($q) {
                            $q->visible()->where('status', 'active');
                        }
                    ]);

                if (!empty($query)) {
                    $encyclopediaQuery->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%")
                          ->orWhereHas('educationPost', function ($epQuery) use ($query) {
                              $epQuery->where('title', 'like', "%{$query}%")
                                      ->orWhere('description', 'like', "%{$query}%");
                          });
                    });
                }

                $results['encyclopedia'] = $encyclopediaQuery->limit(3)->get()->map(function ($category) {
                    $educationPost = $category->educationPost;
                    $categoryTag = $this->getCategoryTag($category->name, $category->description);
                    
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'subtitle' => $this->getSubtitle($category->name),
                        'description' => $educationPost ? $educationPost->description : $category->description,
                        'categoryTag' => $categoryTag,
                        'popularity' => min(95, 70 + (($category->products_count ?? 0) * 2)),
                    ];
                });
            }

            // Search News (Blogs)
            if ($tab === 'all' || $tab === 'news') {
                $newsQuery = Blog::where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());

                if (!empty($query)) {
                    $newsQuery->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                          ->orWhere('description', 'like', "%{$query}%");
                    });
                }

                $results['news'] = $newsQuery->orderBy('published_at', 'desc')
                    ->limit(2)
                    ->get()
                    ->map(function ($blog) {
                        return [
                            'id' => $blog->id,
                            'title' => $blog->title,
                            'slug' => $blog->slug,
                            'description' => $blog->description,
                            'published_at' => $blog->published_at ? $blog->published_at->diffForHumans() : 'Recently',
                            'category' => $this->getNewsCategory($blog->title, $blog->description),
                            'source' => $this->getNewsSource($blog->title),
                        ];
                    });
            }
        }

        // Calculate totals
        $totals = [
            'all' => count($results['vendors']) + count($results['products']) + count($results['encyclopedia']) + count($results['news']),
            'vendors' => count($results['vendors']),
            'products' => count($results['products']),
            'encyclopedia' => count($results['encyclopedia']),
            'news' => count($results['news']),
        ];

        return Inertia::render('Frontend/SearchResults', [
            'query' => $query,
            'location' => $location,
            'tab' => $tab,
            'filters' => [
                'verified' => $verifiedOnly,
                'in_stock' => $inStock,
                'rating_min' => $ratingMin,
                'usa_based' => $usaBased,
            ],
            'results' => $results,
            'totals' => $totals,
        ]);
    }

    private function getVendorIcon($name)
    {
        $nameLower = strtolower($name);
        if (strpos($nameLower, 'limitless') !== false) {
            return 'candy';
        } elseif (strpos($nameLower, 'science') !== false) {
            return 'beaker';
        } else {
            return 'flask';
        }
    }

    private function getProductTag($categoryName)
    {
        $nameLower = strtolower($categoryName ?? '');
        if (strpos($nameLower, 'weight') !== false || strpos($nameLower, 'semaglutide') !== false || strpos($nameLower, 'tirzepatide') !== false) {
            return 'Weight Management';
        } elseif (strpos($nameLower, 'bpc') !== false || strpos($nameLower, 'tb-500') !== false || strpos($nameLower, 'recovery') !== false) {
            return 'Recovery';
        } elseif (strpos($nameLower, 'growth') !== false) {
            return 'Growth';
        }
        return 'Research';
    }

    private function getCategoryTag($name, $description)
    {
        $nameLower = strtolower($name ?? '');
        $descLower = strtolower($description ?? '');
        $combined = $nameLower . ' ' . $descLower;

        if (strpos($combined, 'bpc') !== false || strpos($combined, 'tb-500') !== false || 
            strpos($combined, 'healing') !== false || strpos($combined, 'recovery') !== false) {
            return 'Healing & Recovery';
        }

        if (strpos($combined, 'cjc') !== false || strpos($combined, 'ipamorelin') !== false || 
            strpos($combined, 'ghrp') !== false || strpos($combined, 'growth') !== false) {
            return 'Growth & Recovery';
        }

        if (strpos($combined, 'performance') !== false || strpos($combined, 'pt-141') !== false) {
            return 'Performance';
        }

        if (strpos($combined, 'anti-aging') !== false || strpos($combined, 'aging') !== false) {
            return 'Anti-Aging';
        }

        return 'Healing & Recovery';
    }

    private function getSubtitle($name)
    {
        $subtitles = [
            'BPC-157' => 'Body Protection Compound-157',
            'TB-500' => 'Thymosin Beta-4',
            'CJC-1295' => 'CJC-1295 (No DAC)',
            'Ipamorelin' => 'Ipamorelin Acetate',
            'PT-141' => 'Bremelanotide',
        ];

        foreach ($subtitles as $key => $subtitle) {
            if (stripos($name, $key) !== false) {
                return $subtitle;
            }
        }

        return $name;
    }

    private function getNewsCategory($title, $description)
    {
        $combined = strtolower($title . ' ' . $description);
        if (strpos($combined, 'research') !== false || strpos($combined, 'study') !== false) {
            return 'Research';
        } elseif (strpos($combined, 'fda') !== false || strpos($combined, 'regulatory') !== false || strpos($combined, 'guideline') !== false) {
            return 'Regulatory';
        } elseif (strpos($combined, 'industry') !== false) {
            return 'Industry';
        }
        return 'Research';
    }

    private function getNewsSource($title)
    {
        $titleLower = strtolower($title);
        if (strpos($titleLower, 'research') !== false) {
            return 'Peptide Research Journal';
        } elseif (strpos($titleLower, 'fda') !== false || strpos($titleLower, 'regulatory') !== false) {
            return 'Industry News';
        }
        return 'Peptide News';
    }
}
