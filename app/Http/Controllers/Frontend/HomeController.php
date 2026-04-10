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

    /**
     * Resolve an image URL, falling back to a deterministic picsum.photos
     * placeholder when the real file is missing/unavailable. Temporary until
     * the production storage tree is rsync'd across.
     */
    private function resolveImage(?string $path, string $folder, string $seed, int $w, int $h): ?string
    {
        if ($path) {
            $full = storage_path('app/public/' . $folder . '/' . $path);
            if (file_exists($full)) {
                return asset('storage/' . $folder . '/' . $path);
            }
        }
        // Fallback: picsum.photos with a deterministic seed so each blog/product
        // always gets the same placeholder across reloads.
        return "https://picsum.photos/seed/{$seed}/{$w}/{$h}";
    }

    /**
     * Preview of the redesigned homepage at /home-v2.
     * Independent data-fetching from index() so the live site stays untouched
     * while we iterate on the new design.
     */
    public function v2()
    {
        // Headline stats for the hero trust row
        $verifiedVendorCount = Brand::where('is_active', true)
            ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
            ->count();

        $totalVendorCount = Brand::where('is_active', true)->count();

        $totalCompoundCount = Product::visible()
            ->where('status', 'active')
            ->count();

        $totalCategoryCount = ProductCategory::where('is_active', true)->count();

        $stats = [
            'verified_vendors' => $verifiedVendorCount,
            'total_vendors' => $totalVendorCount,
            'compounds' => $totalCompoundCount,
            'categories' => $totalCategoryCount,
        ];

        // Top 6 vendors for the vendor grid on the homepage.
        $verifiedVendors = Brand::where('is_active', true)
            ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
            ->with(['vendorSetting', 'vendorSetting.location'])
            ->withCount(['products as product_count' => function ($q) {
                $q->visible()->where('status', 'active');
            }])
            ->orderByDesc('rating_average')
            ->orderByDesc('product_count')
            ->take(8)
            ->get()
            ->map(function ($brand) {
                $vs = $brand->vendorSetting;

                $lastUpdated = Product::visible()
                    ->where('status', 'active')
                    ->where('brand_id', $brand->id)
                    ->max('updated_at');

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'tagline' => $vs && $vs->description
                        ? Str::limit(strip_tags($vs->description), 80)
                        : null,
                    'url' => '/shop/' . ($brand->slug ?? Str::slug($brand->name)),
                    'logo_url' => $vs && $vs->logo ? asset('storage/' . $vs->logo) : null,
                    'rating_average' => (float) ($brand->rating_average ?? 0),
                    'rating_count' => (int) ($brand->rating_count ?? 0),
                    'product_count' => (int) $brand->product_count,
                    'location' => $vs && $vs->location ? $vs->location->name : null,
                    'founded_year' => $vs && $vs->founded_year ? (int) $vs->founded_year : null,
                    'is_partner' => $vs && (($vs->is_partner ?? false) || ($vs->featured ?? false)),
                    'last_tested_label' => $lastUpdated
                        ? \Carbon\Carbon::parse($lastUpdated)->diffForHumans(null, true)
                        : null,
                ];
            });

        // Trending compounds — 8 highest-rated products. Later: swap to real
        // click-count ordering from product_clicks once we have enough data.
        $trendingProducts = Product::visible()
            ->where('status', 'active')
            ->with('brand.vendorSetting')
            ->orderByDesc('rating_count')
            ->orderByDesc('featured')
            ->orderByDesc('rating_average')
            ->take(8)
            ->get()
            ->map(function ($product) {
                $salePrice = $product->discount_price && $product->discount_price < $product->price
                    ? $product->discount_price
                    : $product->price;

                $brandVerified = $product->brand && $product->brand->vendorSetting
                    ? ($product->brand->vendorSetting->approval_status === 'approved')
                    : false;

                // Product image: may be a full URL (from scraper) or a local file path.
                $imageUrl = $product->image_url;
                if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                    // Local file path stored in DB — try resolve to asset URL
                    $full = storage_path('app/public/' . $imageUrl);
                    $imageUrl = file_exists($full) ? asset('storage/' . $imageUrl) : null;
                }
                // Fallback placeholder keyed to product id
                $placeholder = "https://picsum.photos/seed/pmap-prod-{$product->id}/600/600";

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $imageUrl ?: $placeholder,
                    'url' => '/product/' . ($product->slug ?? 'product') . '/' . $product->id,
                    'brand_name' => $product->brand?->name,
                    'brand_verified' => $brandVerified,
                    'price' => $salePrice,
                    'original_price' => ($product->discount_price && $product->discount_price < $product->price)
                        ? $product->price
                        : null,
                    'price_per_mg' => null,
                    'verified' => $brandVerified,
                    'lab_tested' => (bool) ($product->lab_tested ?? false),
                    'featured' => (bool) ($product->featured ?? false),
                ];
            });

        // Ticker strip — rotating live price snippets
        $tickerItems = $trendingProducts->take(12)->map(fn ($p) => [
            'name' => $p['name'],
            'brand' => $p['brand_name'],
            'price' => $p['price'],
        ])->values();

        // Latest editorial — 3 research insights for the editorial section
        $editorial = Blog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->take(3)
            ->get()
            ->map(function ($blog) {
                $image = $this->resolveImage($blog->image, 'blogs', "pmap-blog-{$blog->id}", 800, 500);
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'excerpt' => $blog->description,
                    'image' => $image,
                    'date' => $blog->published_at ? $blog->published_at->format('M d, Y') : null,
                    'read_time' => $blog->read_time ?? '5 min read',
                ];
            });

        // Premium / featured vendors for the homepage hero slot (paid placement).
        // Use vendor_settings.is_partner or featured as the selector, fall back
        // to top-rated approved vendors so the section never sits empty.
        $premiumVendors = Brand::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('vendorSetting', function ($s) {
                    $s->where('approval_status', 'approved')
                        ->where(function ($f) {
                            $f->where('is_partner', true)->orWhere('featured', true);
                        });
                });
            })
            ->with(['vendorSetting', 'vendorSetting.location'])
            ->withCount(['products as product_count' => function ($q) {
                $q->visible()->where('status', 'active');
            }])
            ->orderByDesc('rating_average')
            ->take(3)
            ->get();

        // Fallback if nobody is flagged partner/featured yet
        if ($premiumVendors->isEmpty()) {
            $premiumVendors = Brand::where('is_active', true)
                ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
                ->with(['vendorSetting', 'vendorSetting.location'])
                ->withCount(['products as product_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }])
                ->orderByDesc('rating_average')
                ->orderByDesc('product_count')
                ->take(3)
                ->get();
        }

        $premiumVendors = $premiumVendors->map(function ($brand) {
            $vs = $brand->vendorSetting;
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'url' => '/shop/' . ($brand->slug ?? Str::slug($brand->name)),
                'logo' => $vs && $vs->logo ? asset('storage/' . $vs->logo) : null,
                'verified' => $vs && $vs->approval_status === 'approved',
                'rating' => (float) ($brand->rating_average ?? 0),
                'reviews' => (int) ($brand->rating_count ?? 0),
                'product_count' => (int) $brand->product_count,
                'location' => $vs && $vs->location ? $vs->location->name : null,
                'description' => $vs && $vs->description
                    ? Str::limit(strip_tags($vs->description), 160)
                    : null,
            ];
        });

        // Limited-time deals: reuse the same logic pattern as index(), trimmed.
        $limitedDeals = Deal::where('active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
            })
            ->with(['brand.vendorSetting'])
            ->orderByDesc('discount')
            ->take(8)
            ->get()
            ->map(function ($deal) {
                $brand = $deal->brand;
                if (!$brand || !$brand->is_active) return null;

                $logoUrl = $brand->vendorSetting && $brand->vendorSetting->logo
                    ? asset('storage/' . $brand->vendorSetting->logo)
                    : null;

                return [
                    'id' => $deal->id,
                    'name' => $brand->name,
                    'logo' => $logoUrl,
                    'rating' => (float) ($brand->rating_average ?? 0),
                    'reviews' => (int) ($brand->rating_count ?? 0),
                    'discount' => (int) $deal->discount,
                    'code' => $deal->code,
                    'url' => '/shop/' . ($brand->slug ?? Str::slug($brand->name)),
                ];
            })
            ->filter()
            ->values();

        // Fallback: brands with coupon codes
        if ($limitedDeals->count() < 4) {
            $brandsWithCoupons = Brand::where('is_active', true)
                ->whereHas('vendorSetting', function ($q) {
                    $q->where('approval_status', 'approved')
                        ->whereNotNull('coupon_code')
                        ->where('coupon_code', '!=', '');
                })
                ->with(['vendorSetting'])
                ->take(8 - $limitedDeals->count())
                ->get()
                ->map(function ($brand) {
                    $vs = $brand->vendorSetting;
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'logo' => $vs && $vs->logo ? asset('storage/' . $vs->logo) : null,
                        'rating' => (float) ($brand->rating_average ?? 0),
                        'reviews' => (int) ($brand->rating_count ?? 0),
                        'discount' => 15,
                        'code' => $vs->coupon_code ?? 'PMAP',
                        'url' => '/shop/' . ($brand->slug ?? Str::slug($brand->name)),
                    ];
                });
            $limitedDeals = $limitedDeals->merge($brandsWithCoupons)->take(8)->values();
        }

        // Peptide encyclopedia highlights — categories with published education posts
        $encyclopediaCategories = ProductCategory::where('is_active', true)
            ->whereHas('educationPost', function ($q) {
                $q->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            })
            ->withCount(['products as products_count' => function ($q) {
                $q->visible()->where('status', 'active');
            }])
            ->with('educationPost')
            ->orderByDesc('products_count')
            ->take(6)
            ->get()
            ->map(function ($category) {
                $post = $category->educationPost;
                $image = $this->resolveImage($category->image_url, 'categories', "pmap-cat-{$category->id}", 800, 500);
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $post && $post->description
                        ? Str::limit(strip_tags($post->description), 140)
                        : ($category->description ? Str::limit(strip_tags($category->description), 140) : null),
                    'products_count' => (int) $category->products_count,
                    'image' => $image,
                    'url' => '/encyclopedia/' . $category->slug,
                ];
            });

        // Attach a rotating sponsor from premiumVendors to each encyclopedia
        // entry, so sponsored content is cycled deterministically.
        $sponsorPool = $premiumVendors->values();
        $encyclopediaCategories = $encyclopediaCategories->values()->map(function ($cat, $i) use ($sponsorPool) {
            if ($sponsorPool->isEmpty()) {
                return $cat;
            }
            $sponsor = $sponsorPool[$i % $sponsorPool->count()];
            $cat['sponsor'] = [
                'name' => $sponsor['name'],
                'logo' => $sponsor['logo'],
            ];
            return $cat;
        });

        // Build the hero carousel slides:
        //  - 1 platform intro slide (always first)
        //  - Up to 4 rotating premium vendor slides
        $heroSlides = collect();
        $heroSlides->push([
            'eyebrow' => 'Verification engine live · Updated continuously',
            'title' => 'The definitive platform for research peptide vendors.',
            'subtitle' => 'Compare verified suppliers, inspect lab testing, and discover new compounds — all in one place.',
            'cta' => 'Explore verified vendors',
            'url' => '/vendors',
            'badge' => 'Platform',
            'gradient' => ['#0A0B0E', '#4F46E5'],
        ]);

        foreach ($premiumVendors->take(4) as $v) {
            $heroSlides->push([
                'eyebrow' => 'Featured partner',
                'title' => $v['name'],
                'subtitle' => $v['description'] ?? 'Research-grade peptides. Lab tested. Verified on PeptideMap.',
                'cta' => 'Visit vendor',
                'url' => $v['url'],
                'image' => null, // use gradient fallback; can later support banner image
                'badge' => 'Featured',
                'sponsored' => true,
                'gradient' => null, // HeroCarousel picks a default gradient by index
            ]);
        }

        // Brand strip marquee — all verified vendors with a logo (or placeholder)
        $brandStripVendors = Brand::where('is_active', true)
            ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
            ->with('vendorSetting')
            ->orderByDesc('rating_average')
            ->take(16)
            ->get()
            ->map(function ($brand) {
                $vs = $brand->vendorSetting;
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'url' => '/shop/' . ($brand->slug ?? Str::slug($brand->name)),
                    'logo' => $vs && $vs->logo ? asset('storage/' . $vs->logo) : null,
                    'verified' => true,
                ];
            });

        // Top compound categories for the homepage "What are you researching?" grid.
        // Uses the same priority list as the Compare page.
        $topCompoundNames = [
            'BPC-157', 'GLP1-S (Semaglutide)', 'GLP2-T (Tirzepatide)', 'GLP3-R (Retatrutide)',
            'TB-500', 'Ipamorelin', 'CJC-1295 / Ipamorelin', 'GLOW', 'KLOW',
            'Tesamorelin', 'Sermorelin', 'NAD+', 'AOD-9604', 'GHK-Cu',
            'PT-141', 'MOTS-c', 'CJC-1295', 'BPC-157 / TB-500',
        ];

        $displayNames = [
            'GLP3-R (Retatrutide)' => 'Retatrutide',
            'GLP1-S (Semaglutide)' => 'Semaglutide',
            'GLP2-T (Tirzepatide)' => 'Tirzepatide',
            'BPC-157 / TB-500' => 'BPC-157 / TB-500 Blend',
            'CJC-1295 / Ipamorelin' => 'Ipamorelin / CJC-1295',
            'GLOW' => 'GLOW Blend',
            'KLOW' => 'KLOW Blend',
        ];

        $topCompounds = ProductCategory::where('is_active', true)
            ->whereIn('name', $topCompoundNames)
            ->withCount(['products as product_count' => fn ($q) => $q->visible()->where('status', 'active')])
            ->get()
            ->map(function ($cat) use ($displayNames) {
                $vendorCount = Product::visible()
                    ->where('status', 'active')
                    ->where('product_category_id', $cat->id)
                    ->distinct('brand_id')
                    ->count('brand_id');

                $cheapest = Product::visible()
                    ->where('status', 'active')
                    ->where('product_category_id', $cat->id)
                    ->selectRaw('MIN(COALESCE(discount_price, price)) as min_price')
                    ->value('min_price');

                return [
                    'id' => $cat->id,
                    'name' => $displayNames[$cat->name] ?? $cat->name,
                    'slug' => $cat->slug,
                    'vendor_count' => $vendorCount,
                    'from_price' => $cheapest ? number_format((float) $cheapest, 2, '.', '') : null,
                ];
            })
            ->sortByDesc('vendor_count')
            ->values()
            ->take(16);

        // SEO
        $seo = [
            'title' => 'PeptideMap — The definitive platform for research peptide vendors',
            'description' => 'Compare verified suppliers, inspect lab testing, and discover research peptides — all in one place.',
            'url' => url('/'),
        ];

        return Inertia::render('Frontend/WelcomeV2', [
            'stats' => $stats,
            'heroSlides' => $heroSlides,
            'brandStripVendors' => $brandStripVendors,
            'verifiedVendors' => $verifiedVendors,
            'topCompounds' => $topCompounds,
            'trendingProducts' => $trendingProducts,
            'limitedDeals' => $limitedDeals,
            'encyclopediaCategories' => $encyclopediaCategories,
            'editorial' => $editorial,
            'seo' => $seo,
        ]);
    }
}

