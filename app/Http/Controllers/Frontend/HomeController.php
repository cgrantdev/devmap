<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SeoPage;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Location;
use App\Models\Blog;
use App\Models\Deal;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class HomeController extends Controller
{
    public function index()
    {
        // Hero slides (active, ordered)
        $heroSlides = [];
        $heroSlidesSetting = Setting::where('key', 'hero_slides')->first();
        if ($heroSlidesSetting && $heroSlidesSetting->value) {
            $slides = json_decode($heroSlidesSetting->value, true) ?? [];

            $activeSlides = array_filter($slides, fn ($slide) => isset($slide['is_active']) && $slide['is_active']);
            usort($activeSlides, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

            foreach ($activeSlides as $slide) {
                $heroSlides[] = [
                    'title' => $slide['title'] ?? '',
                    'subtitle' => $slide['subtitle'] ?? '',
                    'heading' => $slide['heading'] ?? '',
                    'ctaText' => $slide['cta_text'] ?? '',
                    'ctaUrl' => $slide['cta_url'] ?? '',
                    'promoCode' => $slide['promo_code'] ?? '',
                    'image' => isset($slide['image']) && $slide['image']
                        ? Storage::url('hero_slides/' . $slide['image'])
                        : null,
                ];
            }
        }

        // Education categories - only show categories with published encyclopedia articles
        $categories = ProductCategory::where('is_active', true)
            ->whereHas('educationPost', function ($epQuery) {
                $epQuery->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            })
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ])
            ->with('educationPost')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                $educationPost = $category->educationPost;
                // Use category image if available, otherwise get a sample product image
                $image = null;
                if ($category->image_url) {
                    $image = Storage::url('categories/' . $category->image_url);
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
                    'description' => $educationPost ? $educationPost->description : $category->description,
                    'peptide_full_name' => $educationPost ? $educationPost->peptide_full_name : null,
                    'education_tag' => $educationPost ? $educationPost->education_tag : null,
                ];
            });

        // Top blogs / research insights (4 for Research Insights section)
        $topBlogs = Blog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->take(4)
            ->get()
            ->map(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'description' => $blog->description,
                    'image' => $blog->image ? Storage::url('blogs/' . $blog->image) : null,
                    'date' => $blog->published_at ? $blog->published_at->format('M d, Y') : null,
                    'readTime' => $blog->read_time ?? '5 min read',
                ];
            });

        // Latest blogs (8 for Latest from Our Blog section)
        $latestBlogs = Blog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->take(8)
            ->get()
            ->map(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'description' => $blog->description,
                    'image' => $blog->image ? Storage::url('blogs/' . $blog->image) : null,
                    'date' => $blog->published_at ? $blog->published_at->format('M d, Y') : null,
                ];
            });

        // Top brands (vendors) limited list for homepage - only approved vendors
        $topBrands = Brand::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('vendorSetting', function ($subQ) {
                    $subQ->where('approval_status', 'approved');
                })
                ->orWhereDoesntHave('vendorSetting'); // For backwards compatibility
            })
            ->with(['vendorSetting', 'vendorSetting.location'])
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ])
            ->orderByDesc('products_count')
            ->get()
            ->map(function ($brand) {
                // Get most common location from products
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

                // Prefer vendor setting location if set; otherwise fall back to product-derived location
                $location = null;
                if ($brand->vendorSetting && $brand->vendorSetting->location) {
                    $location = $brand->vendorSetting->location;
                } elseif ($locationId) {
                    $location = Location::find($locationId);
                }

                $logoUrl = null;
                if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                    $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                }

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'product_count' => $brand->products_count,
                    'slug' => $brand->slug ?? Str::slug($brand->name),
                    'initials' => $this->getInitials($brand->name),
                    'logo' => $logoUrl,
                    'location' => $location ? $location->name : null,
                    'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                    'reviews' => (int) ($brand->rating_count ?? 0),
                ];
            });

        // Limited Time Discounts - Get active deals with vendor info
        $discountDeals = Deal::where('active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereRaw('used_count < usage_limit');
            })
            ->with(['brand.vendorSetting'])
            ->orderByDesc('discount')
            ->take(8)
            ->get()
            ->map(function ($deal) {
                $brand = $deal->brand;
                if (!$brand || !$brand->is_active) {
                    return null;
                }

                $logoUrl = null;
                if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                    $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                }

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug ?? Str::slug($brand->name),
                    'initials' => $this->getInitials($brand->name),
                    'logo' => $logoUrl,
                    'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                    'reviews' => (int) ($brand->rating_count ?? 0),
                    'discount' => $deal->discount,
                    'code' => $deal->code,
                ];
            })
            ->filter() // Remove null entries
            ->values()
            ->toBase();

        // If we don't have enough deals, supplement with top brands that have coupon codes - only approved vendors
        if ($discountDeals->count() < 8) {
            $brandsWithCoupons = Brand::where('is_active', true)
                ->where(function ($q) {
                    $q->whereHas('vendorSetting', function ($subQ) {
                        $subQ->where('approval_status', 'approved')
                            ->whereNotNull('coupon_code')
                            ->where('coupon_code', '!=', '');
                    })
                    ->orWhereDoesntHave('vendorSetting'); // For backwards compatibility
                })
                ->with(['vendorSetting'])
                ->withCount([
                    'products as products_count' => function ($q) {
                        $q->visible()->where('status', 'active');
                    }
                ])
                ->whereNotIn('id', $discountDeals->pluck('id'))
                ->orderByDesc('products_count')
                ->take(8 - $discountDeals->count())
                ->get()
                ->map(function ($brand) {
                    $logoUrl = null;
                    if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                        $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                    }

                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug ?? Str::slug($brand->name),
                        'initials' => $this->getInitials($brand->name),
                        'logo' => $logoUrl,
                        'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                        'reviews' => (int) ($brand->rating_count ?? 0),
                        'discount' => 15, // Default discount for coupon codes
                        'code' => $brand->vendorSetting->coupon_code ?? 'PMAP',
                    ];
                })
                ->toBase();

            $discountDeals = $discountDeals->merge($brandsWithCoupons)->take(8)->values();
        }

        // If still no deals, use top brands as fallback with default discount - only approved vendors
        if ($discountDeals->count() === 0) {
            $discountDeals = Brand::where('is_active', true)
                ->where(function ($q) {
                    $q->whereHas('vendorSetting', function ($subQ) {
                        $subQ->where('approval_status', 'approved');
                    })
                    ->orWhereDoesntHave('vendorSetting'); // For backwards compatibility
                })
                ->with(['vendorSetting'])
                ->withCount([
                    'products as products_count' => function ($q) {
                        $q->visible()->where('status', 'active');
                    }
                ])
                ->orderByDesc('products_count')
                ->take(8)
                ->get()
                ->map(function ($brand) {
                    $logoUrl = null;
                    if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                        $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                    }

                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug ?? Str::slug($brand->name),
                        'initials' => $this->getInitials($brand->name),
                        'logo' => $logoUrl,
                        'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                        'reviews' => (int) ($brand->rating_count ?? 0),
                        'discount' => 10, // Default discount
                        'code' => $brand->vendorSetting->coupon_code ?? 'PMAP',
                    ];
                });
        }

        // Generate SEO data (editable via Admin -> Settings -> SEO Pages, key: "home")
        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'PeptideSync';
        $defaultDescription = Setting::where('key', 'site_description')->value('value') ?? 'Discover top-rated peptide vendors, compare products, and access comprehensive research information. Find the best deals on premium research peptides with verified discount codes.';
        $defaultImage = $heroSlides[0]['image'] ?? null;

        $seoPage = SeoPage::where('key', 'home')->first();
        $seo = [
            'key' => 'home',
            'title' => $seoPage?->title ?: $siteName,
            'description' => $seoPage?->description ?: $defaultDescription,
            'og_title' => $seoPage?->og_title ?: ($seoPage?->title ?: $siteName),
            'og_description' => $seoPage?->og_description ?: ($seoPage?->description ?: $defaultDescription),
            'og_image' => $seoPage?->og_image ?: $defaultImage,
            // Backward-compatible field used by some pages
            'image' => $seoPage?->og_image ?: $defaultImage,
            'url' => url('/'),
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/Welcome', [
            'heroSlides' => $heroSlides,
            'productGroups' => $categories,
            'topBrands' => $topBrands,
            'topBlogs' => $topBlogs,
            'latestBlogs' => $latestBlogs,
            'discountDeals' => $discountDeals,
            'seo' => $seo,
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

