<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\SiteCopy;
use App\Models\SeoPage;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Location;
use App\Models\Blog;
use App\Models\Deal;
use App\Support\CompareSlug;
use App\Support\EncyclopediaSlug;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
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
                    // Get a sample product image for this category. Skip
                    // certified-pep.com — those images 403 externally
                    // (hotlink protection). See ProductsController for
                    // the fix and rationale.
                    $sampleProduct = Product::visible()
                        ->where('status', 'active')
                        ->where('product_category_id', $category->id)
                        ->whereNotNull('image_url')
                        ->where('image_url', 'not like', '%certified-pep.com%')
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
                    'image' => ImageHelper::listingImageUrl($blog->image),
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
                    'image' => ImageHelper::listingImageUrl($blog->image),
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
            ->with(['vendorSetting' => fn ($q) => $q->forPublic()->with('location')])
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
            ->with([
                'brand' => fn ($q) => $q->forPublic(),
                'brand.vendorSetting' => fn ($q) => $q->forPublic(),
            ])
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
                ->with(['vendorSetting' => fn ($q) => $q->forPublic()])
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
                ->with(['vendorSetting' => fn ($q) => $q->forPublic()])
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
        $siteName = Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap';
        // Colin 10/21 SEO push — homepage defaults now target the
        // high-intent query cluster (compare prices, cheap peptides,
        // vendor directory, coupon codes) instead of the generic
        // "Live prices and coupon codes" placeholder. Admin SeoPage
        // override still wins when Julia sets one.
        $defaultDescription = SiteCopy::description(
            Setting::where('key', 'site_description')->value('value')
        );
        $defaultTitle = 'Compare Research Peptide Prices & Vendors — Peptidemap';
        $defaultImage = $heroSlides[0]['image'] ?? null;

        $seoPage = SeoPage::where('key', 'home')->first();
        $seo = [
            'key' => 'home',
            'title' => $seoPage?->title ?: $defaultTitle,
            'description' => $seoPage?->description ?: $defaultDescription,
            'og_title' => $seoPage?->og_title ?: ($seoPage?->title ?: $defaultTitle),
            'og_description' => $seoPage?->og_description ?: ($seoPage?->description ?: $defaultDescription),
            'og_image' => $seoPage?->og_image ?: $defaultImage,
            // Backward-compatible field used by some pages
            'image' => $seoPage?->og_image ?: $defaultImage,
            'url' => url('/'),
            // Colin 10/21 — was 'noindex, follow'. Not currently
            // rendered to <meta robots>, but one layout wire-up away
            // from catastrophe. Dropping entirely so the homepage
            // defaults to Google's standard (index, follow).
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        // Listed prices — top 10 post-coupon prices
        // across the whole catalog, biased toward vendors with an active
        // coupon boost. Homepage above-the-fold slot: every visitor
        // sees an actionable buy-now card, driving outbound clicks to
        // affiliates. Colin Sep 6: revenue push.
        $bestDeals = \App\Models\Product::visible()
            ->where('status', 'active')
            ->with([
                'brand:id,name,slug',
                'brand.vendorSetting' => fn ($q) => $q->forPublic(true),
            ])
            ->orderByRaw('COALESCE(NULLIF(discount_price, 0), price) ASC')
            ->limit(60)
            ->get()
            ->map(function ($p) {
                $vs = $p->brand?->vendorSetting;
                $retail = (float) ($p->discount_price && $p->discount_price < $p->price ? $p->discount_price : $p->price);
                $pct = $vs && $vs->coupon_discount_percent && $vs->coupon_discount_percent > 0 && $vs->coupon_discount_percent < 100
                    ? (float) $vs->coupon_discount_percent : null;
                $final = $pct ? round($retail * (1 - $pct / 100), 2) : $retail;
                return [
                    'id' => $p->id,
                    'name' => $p->display_name ?? $p->name,
                    'brand_name' => $p->brand?->name,
                    'brand_slug' => $p->brand?->slug,
                    'category' => $p->productCategory?->name ?? null,
                    'retail' => $retail,
                    'final_price' => $final,
                    'coupon_code' => $vs?->coupon_code ?: 'PMAP',
                    'coupon_pct' => $pct,
                    'coupon_boost_active' => $vs?->couponBoostActive() ?? false,
                    'go_url' => "/go/{$p->id}?src=homepage-best-deals",
                    'image_url' => $p->image_url,
                ];
            })
            ->sortBy(function ($d) {
                // Boosted vendors surface first, then cheapest price.
                return ($d['coupon_boost_active'] ? 0 : 1) . '-' . str_pad(number_format($d['final_price'] * 100, 0, '.', ''), 12, '0', STR_PAD_LEFT);
            })
            ->take(10)
            ->values();

        return Inertia::render('Frontend/Welcome', [
            'heroSlides' => $heroSlides,
            'productGroups' => $categories,
            'topBrands' => $topBrands,
            'topBlogs' => $topBlogs,
            'latestBlogs' => $latestBlogs,
            'discountDeals' => $discountDeals,
            'bestDeals' => $bestDeals,
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
     * Live homepage (route "home"). The heavy payload is cached for ten
     * minutes per location for guests; FrontendCache skips the cache for
     * logged-in users so admin edits show immediately. SEO is built per
     * request because it travels through the session to app.blade.php.
     */
    public function v2(\Illuminate\Http\Request $request)
    {
        // Site-wide vendor-location filter set by the header CountrySelector.
        // Only exact location names are honored; anything else falls back to
        // all locations so arbitrary ?location= values can't fill the cache
        // or end up echoed in the H1.
        $locationFilter = $this->knownLocationName(trim((string) $request->get('location', '')));

        $payload = \App\Support\FrontendCache::remember(
            'home_v2:' . md5(mb_strtolower($locationFilter)),
            600,
            fn () => $this->buildHomePayload($locationFilter)
        );

        // Title and meta are agency-owned. h1 mirrors the visible H1 so the
        // server-rendered heading matches what visitors see.
        $seo = [
            'key'            => 'home',
            'title'          => 'Peptidemap — Compare Peptide Vendors, Prices & Coupons',
            'description'    => "Live prices and coupon codes. Check each vendor's site for COAs.",
            'url'            => 'https://peptidemap.com/',
            'og_title'       => 'Compare every peptide vendor — Peptidemap',
            'og_description' => "Live prices and coupon codes. Check each vendor's site for COAs.",
            'og_image'       => 'https://peptidemap.com/images/og-default-v8.png',
            'h1'             => $payload['heroHeading'],
            'preload_images' => $this->heroPreloadImages($payload['heroSlides'][0] ?? null),
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/WelcomeV2', array_merge($payload, ['seo' => $seo]));
    }

    /**
     * Case-insensitive match against the locations table. Returns the stored
     * name, or '' when the value is empty or unknown.
     */
    private function knownLocationName(string $value): string
    {
        if ($value === '') return '';
        $names = Cache::remember('location_names_v1', 3600, fn () => Location::query()->pluck('name')->all());
        foreach ($names as $name) {
            if (strcasecmp((string) $name, $value) === 0) return (string) $name;
        }
        return '';
    }

    /**
     * Preload hints for the first hero slide so the LCP image starts
     * downloading from the HTML head instead of after Vue mounts. Mirrors
     * HeroCarousel.vue: only slides drawn as <img> (cover/contain fits).
     */
    private function heroPreloadImages($slide): array
    {
        if (!is_array($slide)) return [];
        $desktop = $slide['image'] ?? null;
        $mobile = $slide['image_mobile'] ?? null;
        if (!$desktop && !$mobile) return [];

        $fitOk = fn ($v) => in_array((string) ($v ?? ''), ['', 'cover', 'contain'], true);
        if ($mobile) {
            $mobileFit = ($slide['bg_size_mobile'] ?? null) ?: ($slide['bg_size'] ?? null);
        } else {
            $mobileFit = $slide['bg_size'] ?? null;
        }
        if (!$fitOk($slide['bg_size'] ?? null) || !$fitOk($mobileFit)) return [];

        if ($desktop && $mobile) {
            return [
                ['href' => $mobile, 'media' => '(max-width: 767px)'],
                ['href' => $desktop, 'media' => '(min-width: 768px)'],
            ];
        }
        if ($desktop) return [['href' => $desktop, 'media' => null]];
        return [['href' => $mobile, 'media' => '(max-width: 767px)']];
    }

    /**
     * Everything the homepage renders except SEO. Cached by v2().
     */
    private function buildHomePayload(string $locationFilter): array
    {
        // A vendor appears under a country when its HQ is there OR its
        // ships-to list includes it (Colin PMAP Sep 30, #12).
        $applyBrandLocation = fn ($q) => $locationFilter
            ? $q->where(function ($q) use ($locationFilter) {
                $q->whereHas('vendorSetting.location', fn ($l) => $l->where('name', $locationFilter))
                  ->orWhereHas('vendorSetting.shipsToLocations', fn ($l) => $l->where('name', $locationFilter));
              })
            : $q;
        $applyProductLocation = fn ($q) => $locationFilter
            ? $q->whereHas('brand', function ($b) use ($locationFilter) {
                $b->where(function ($q) use ($locationFilter) {
                    $q->whereHas('vendorSetting.location', fn ($l) => $l->where('name', $locationFilter))
                      ->orWhereHas('vendorSetting.shipsToLocations', fn ($l) => $l->where('name', $locationFilter));
                });
              })
            : $q;

        // Headline stats for the H1 band and the compare card.
        $verifiedVendorCount = Brand::where('is_active', true)
            ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
            ->tap($applyBrandLocation)
            ->count();

        $totalVendorCount = Brand::where('is_active', true)
            ->tap($applyBrandLocation)
            ->count();

        $listingCount = Product::visible()
            ->where('status', 'active')
            ->tap($applyProductLocation)
            ->count();

        // Compounds that actually have at least one live listing.
        $compoundCount = ProductCategory::where('is_active', true)
            ->whereHas('products', fn ($q) => $q->visible()->where('status', 'active')->tap($applyProductLocation))
            ->count();

        $stats = [
            'verified_vendors' => $verifiedVendorCount,
            'total_vendors' => $totalVendorCount,
            'listings' => $listingCount,
            'compounds' => $compoundCount,
        ];

        $vendorWord = $totalVendorCount === 1 ? 'vendor' : 'vendors';
        if ($totalVendorCount <= 0) {
            $heroHeading = 'Compare research peptide prices across vendors';
        } elseif ($locationFilter !== '') {
            $heroHeading = "Compare research peptide prices from {$totalVendorCount} {$vendorWord} shipping to {$locationFilter}";
        } else {
            $heroHeading = "Compare research peptide prices from {$totalVendorCount} {$vendorWord}";
        }

        // Top vendors for the vendor grid.
        $verifiedVendors = Brand::where('is_active', true)
            ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
            ->tap($applyBrandLocation)
            ->with(['vendorSetting' => fn ($q) => $q->forPublic()->with('location')])
            ->withCount(['products as product_count' => function ($q) {
                $q->visible()->where('status', 'active');
            }])
            // Featured / partner vendors surface first (paid placement),
            // then rating, then catalog depth. Subquery avoids clashing
            // with withCount()'s product_count alias.
            ->orderByDesc(
                \App\Models\VendorSetting::query()
                    ->selectRaw('CASE WHEN COALESCE(is_partner, 0) >= COALESCE(featured, 0) THEN COALESCE(is_partner, 0) ELSE COALESCE(featured, 0) END')
                    ->whereColumn('brand_id', 'brands.id')
                    ->limit(1)
            )
            ->orderByDesc('rating_average')
            ->orderByDesc('product_count')
            ->take(8)
            ->get()
            ->map(function ($brand) {
                $vs = $brand->vendorSetting;

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'tagline' => $vs && $vs->description
                        ? Str::limit(strip_tags($vs->description), 80)
                        : null,
                    'url' => '/brand/' . ($brand->slug ?? Str::slug($brand->name)),
                    // Null when the file is gone, so the tile shows initials
                    // instead of a broken image.
                    'logo_url' => $vs?->publicLogoUrl(),
                    'rating_average' => (float) ($brand->rating_average ?? 0),
                    'rating_count' => (int) ($brand->rating_count ?? 0),
                    'product_count' => (int) $brand->product_count,
                    'location' => $vs && $vs->location ? $vs->location->name : null,
                    'founded_year' => $vs && $vs->founded_year ? (int) $vs->founded_year : null,
                    'is_partner' => $vs && (($vs->is_partner ?? false) || ($vs->featured ?? false)),
                    'coupon_code' => $vs->coupon_code ?? null,
                    'coupon_percent' => $vs && $vs->coupon_discount_percent !== null
                        ? (float) $vs->coupon_discount_percent
                        : null,
                ];
            });

        // Latest editorial - 4 research insights for the editorial section
        $editorial = Blog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->take(4)
            ->get()
            ->map(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'excerpt' => $blog->description,
                    'image' => ImageHelper::listingImageUrl($blog->image),
                    'date' => $blog->published_at ? $blog->published_at->format('M d, Y') : null,
                    'read_time' => $blog->read_time ?? '5 min read',
                ];
            });

        // Premium / featured vendors (paid placement). Used for the hero
        // fallback slides and the encyclopedia sponsor rotation. Falls back
        // to top-rated approved vendors so neither sits empty.
        $premiumVendors = Brand::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('vendorSetting', function ($s) {
                    $s->where('approval_status', 'approved')
                        ->where(function ($f) {
                            $f->where('is_partner', true)->orWhere('featured', true);
                        });
                });
            })
            ->tap($applyBrandLocation)
            ->with(['vendorSetting' => fn ($q) => $q->forPublic()->with('location')])
            ->withCount(['products as product_count' => function ($q) {
                $q->visible()->where('status', 'active');
            }])
            ->orderByDesc('rating_average')
            ->take(3)
            ->get();

        if ($premiumVendors->isEmpty()) {
            $premiumVendors = Brand::where('is_active', true)
                ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
                ->tap($applyBrandLocation)
                ->with(['vendorSetting' => fn ($q) => $q->forPublic()->with('location')])
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
                'slug' => $brand->slug ?? Str::slug($brand->name),
                'url' => '/brand/' . ($brand->slug ?? Str::slug($brand->name)),
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

        // Peptide encyclopedia highlights - categories with published education posts
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
                $rawImage = is_string($category->image_url) ? $category->image_url : null;
                $image = ($rawImage && (str_starts_with($rawImage, 'http') || str_starts_with($rawImage, '/')))
                    ? ImageHelper::listingImageUrl($rawImage)
                    : null;
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $post && $post->description
                        ? Str::limit(strip_tags($post->description), 140)
                        : ($category->description ? Str::limit(strip_tags($category->description), 140) : null),
                    'products_count' => (int) $category->products_count,
                    'image' => $image,
                    'url' => EncyclopediaSlug::path($category->slug)
                        ?? '/compare/' . (CompareSlug::canonical($category->slug) ?? $category->slug),
                ];
            });

        // Rotate a sponsor from premiumVendors onto each encyclopedia entry.
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

        // Hero carousel slides, managed at /admin/banners via the
        // hero_slides setting. Falls back to a seeded Certified Peptides
        // slide plus premium-vendor slides so the carousel never renders empty.
        $heroSlides = $this->loadAdminHeroSlides();
        if ($heroSlides->isEmpty()) {
            $heroSlides->push([
                'eyebrow' => 'Featured Partner',
                'title' => 'Research peptides from Certified Peptides',
                'title_highlight' => 'Certified Peptides',
                'subtitle' => 'Sponsored — vendor states HPLC COAs are published per batch',
                'cta' => 'Browse catalog',
                'url' => '/brand/certified-pep',
                'image' => '/images/banners/certified-peptides-3.png',
                'image_mobile' => '/images/banners/cert-mobile.png',
                'coupon_code' => 'pmap',
            ]);

            $skipSlugs = ['certified-pep', 'amino-club'];
            foreach ($premiumVendors->take(4) as $v) {
                if (in_array($v['slug'] ?? null, $skipSlugs, true)) continue;
                $heroSlides->push([
                    'eyebrow' => 'Featured partner',
                    'title' => $v['name'],
                    'subtitle' => $v['description'] ?? 'Listed on Peptidemap.',
                    'cta' => 'Visit vendor',
                    'url' => $v['url'],
                    'image' => null,
                    'badge' => 'Featured',
                    'sponsored' => true,
                ]);
            }
        }

        // Top compound tiles ("What are you researching?"). Same priority
        // list as the Compare page.
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

        $topCategories = ProductCategory::where('is_active', true)
            ->whereIn('name', $topCompoundNames)
            ->get(['id', 'name', 'slug']);

        // One grouped query for vendor counts and lowest listed price instead
        // of two queries per compound. visible() already drops $0 rows; the
        // CASE skips zero discount prices like the old per-compound MIN did.
        $compoundAgg = Product::visible()
            ->where('status', 'active')
            ->whereIn('product_category_id', $topCategories->pluck('id')->all())
            ->groupBy('product_category_id')
            ->selectRaw('product_category_id, COUNT(DISTINCT brand_id) as vendor_count, MIN(CASE WHEN discount_price > 0 THEN discount_price WHEN discount_price IS NULL AND price > 0 THEN price END) as min_price')
            ->toBase()
            ->get()
            ->keyBy('product_category_id');

        $topCompounds = $topCategories
            ->map(function ($cat) use ($displayNames, $compoundAgg) {
                $agg = $compoundAgg->get($cat->id);
                $cheapest = $agg?->min_price;

                return [
                    'id' => $cat->id,
                    'name' => $displayNames[$cat->name] ?? $cat->name,
                    'slug' => $cat->slug,
                    'vendor_count' => (int) ($agg?->vendor_count ?? 0),
                    'from_price' => $cheapest ? number_format((float) $cheapest, 2, '.', '') : null,
                ];
            })
            ->sortByDesc('vendor_count')
            ->values()
            ->take(16);

        // Listed prices - direct-to-affiliate cards. Ranking is biggest
        // savings + boosted vendors + higher retail price, not the lowest
        // absolute price: a $500 product 30% off is a better deal than a $10
        // product at full price and earns more per outbound click. Colin
        // Sep 7: sell the BEST products, not the cheapest. Only products with
        // a real coupon discount qualify.
        $bestDeals = \App\Models\Product::visible()
            ->where('status', 'active')
            ->whereHas('brand.vendorSetting', function ($q) {
                $q->where('coupon_discount_percent', '>', 0)
                  ->where('coupon_discount_percent', '<', 100);
            })
            ->with([
                'brand:id,name,slug',
                'brand.vendorSetting' => fn ($q) => $q->forPublic(true),
            ])
            // Accessible price band: $40-$500 retail. Excludes low-AOV noise
            // and bulk/kit products that price out a first-time visitor
            // (Colin Sep 7).
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNotNull('discount_price')
                       ->where('discount_price', '>=', 40)
                       ->where('discount_price', '<=', 500);
                })->orWhere(function ($qq) {
                    $qq->whereNull('discount_price')
                       ->where('price', '>=', 40)
                       ->where('price', '<=', 500);
                });
            })
            // Exclude bulk/kit/pack SKUs by name pattern.
            ->where(function ($q) {
                foreach (['%kit%', '%pack%', '% packs%', '%bundle%', '%pcs%', '%bulk%', '% set %', '5/10%', '%vials%'] as $needle) {
                    $q->where('name', 'not like', $needle);
                }
            })
            ->orderByRaw('COALESCE(discount_price, price) DESC')
            ->limit(80)
            ->get()
            ->map(function ($p) {
                $vs = $p->brand?->vendorSetting;
                $retail = (float) ($p->discount_price && $p->discount_price < $p->price ? $p->discount_price : $p->price);
                $pct = (float) $vs->coupon_discount_percent;
                $final = round($retail * (1 - $pct / 100), 2);
                $savings = round($retail - $final, 2);
                return [
                    'id' => $p->id,
                    'name' => $p->display_name ?? $p->name,
                    'brand_name' => $p->brand?->name,
                    'brand_slug' => $p->brand?->slug,
                    'retail' => $retail,
                    'final_price' => $final,
                    'savings' => $savings,
                    'coupon_code' => $vs?->coupon_code ?: 'PMAP',
                    'coupon_pct' => $pct,
                    'coupon_boost_active' => $vs?->couponBoostActive() ?? false,
                    'go_url' => "/go/{$p->id}?src=homepage-best-deals",
                ];
            })
            // Boosted vendors first, then by absolute savings desc.
            ->sortBy(function ($d) {
                $boostFlag = $d['coupon_boost_active'] ? '0' : '1';
                // Invert savings for ascending sort so the biggest savings come first
                $invSavings = str_pad(number_format(999999 - $d['savings'], 2, '.', ''), 12, '0', STR_PAD_LEFT);
                return $boostFlag . '-' . $invSavings;
            })
            ->take(10)
            ->values();

        return [
            'stats' => $stats,
            'heroHeading' => $heroHeading,
            'heroSlides' => $heroSlides,
            'verifiedVendors' => $verifiedVendors,
            'topCompounds' => $topCompounds,
            'encyclopediaCategories' => $encyclopediaCategories,
            'editorial' => $editorial,
            'bestDeals' => $bestDeals,
        ];
    }

    /**
     * Load hero-slide config saved by admins (/admin/banners) and map the
     * stored field names to the shape HeroCarousel.vue consumes.
     *
     * Storage schema (per slide, JSON in settings.hero_slides.value):
     *   title, title_highlight, eyebrow, badge, subtitle,
     *   cta_text, cta_url, coupon_code,
     *   image, image_mobile,          // filenames in storage/hero_slides/ OR
     *                                 // absolute /paths OR full https:// URLs
     *   order, is_active, sponsored, target
     */
    private function loadAdminHeroSlides(): \Illuminate\Support\Collection
    {
        $raw = Setting::where('key', 'hero_slides')->value('value');
        if (!$raw) return collect();

        $decoded = json_decode($raw, true) ?? [];

        return collect($decoded)
            ->filter(fn ($s) => ($s['is_active'] ?? true) && !empty($s['title'] ?? null))
            ->sortBy(fn ($s) => (int) ($s['order'] ?? 0))
            ->map(fn ($s) => [
                'eyebrow'          => $s['eyebrow'] ?? null,
                'badge'            => $s['badge'] ?? null,
                'title'            => $s['title'] ?? '',
                'title_highlight'  => $s['title_highlight'] ?? null,
                'subtitle'         => $s['subtitle'] ?? null,
                'cta'              => $s['cta_text'] ?? null,
                'url'              => $s['cta_url'] ?? null,
                'target'           => $s['target'] ?? null,
                'sponsored'        => (bool) ($s['sponsored'] ?? false),
                'image'            => $this->resolveHeroImage($s['image'] ?? null),
                'image_mobile'     => $this->resolveHeroImage($s['image_mobile'] ?? null),
                'coupon_code'      => $s['coupon_code'] ?? null,
                'analytics_label'  => $s['analytics_label'] ?? null,
                'bg_size'          => $s['bg_size'] ?? null,
                'bg_size_mobile'   => $s['bg_size_mobile'] ?? null,
                'bg_position_mobile' => $s['bg_position_mobile'] ?? null,
                'highlight_color'  => $s['highlight_color'] ?? null,
                'highlight_color_mobile' => $s['highlight_color_mobile'] ?? null,
                'no_overlay'       => (bool) ($s['no_overlay'] ?? false),
                'dark_text'        => (bool) ($s['dark_text'] ?? false),
            ])
            ->values();
    }

    private function resolveHeroImage(?string $image): ?string
    {
        if (!$image) return null;
        if (preg_match('#^(https?:)?//#i', $image)) return $image;
        if (str_starts_with($image, '/')) return $image;
        return \Storage::url('hero_slides/' . $image);
    }
}

