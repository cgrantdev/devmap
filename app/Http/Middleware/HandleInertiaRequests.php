<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use App\Models\VendorReview;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    /**
     * Resolve the admin "Edit this page" URL based on the current
     * frontend route + its route parameters. Returns null for guest
     * users or non-admins so the AdminBar hides entirely.
     */
    private function buildAdminBar(Request $request): ?array
    {
        $user = $request->user();
        if (!$user || !$user->canAccessAdmin()) return null;

        $route = $request->route();
        $editUrl = null;
        $pageType = null;

        if ($route) {
            $name = $route->getName() ?? '';
            $params = $route->parameters();

            // Products — route is product.detail with (vendorSlug, productSlug, id)
            if (str_starts_with($name, 'product.') && isset($params['id'])) {
                $editUrl = url('/admin/products/' . $params['id'] . '/edit');
                $pageType = 'Product';
            }
            // Brand storefront — route brand.products with (slug)
            elseif ($name === 'brand.products' && isset($params['slug'])) {
                $brand = \App\Models\Brand::where('slug', $params['slug'])->first();
                if ($brand) {
                    $editUrl = url('/admin/vendors/' . $brand->id . '/edit');
                    $pageType = 'Vendor';
                }
            }
            // Encyclopedia article
            elseif (str_starts_with($name, 'encyclopedia.') && isset($params['slug'])) {
                $ep = \App\Models\EducationPost::where('slug', $params['slug'])
                    ->orWhereHas('category', fn ($q) => $q->where('slug', $params['slug']))->first();
                if ($ep) {
                    $editUrl = url('/admin/encyclopedia-entries/' . $ep->id . '/edit');
                    $pageType = 'Encyclopedia';
                }
            }
            // Blog post
            elseif (str_starts_with($name, 'blog') && isset($params['slug'])) {
                $blog = \App\Models\Blog::where('slug', $params['slug'])->first();
                if ($blog) {
                    $editUrl = url('/admin/blogs/' . $blog->id . '/edit');
                    $pageType = 'Blog';
                }
            }
            // Compare compound
            elseif (str_starts_with($name, 'compare.') && isset($params['slug'])) {
                $cat = \App\Models\ProductCategory::where('slug', $params['slug'])->first();
                if ($cat) {
                    $editUrl = url('/admin/categories/' . $cat->id . '/edit');
                    $pageType = 'Compare';
                }
            }
            // Coupon
            elseif (str_starts_with($name, 'coupon.') && isset($params['slug'])) {
                $brand = \App\Models\Brand::where('slug', $params['slug'])->first();
                if ($brand) {
                    $editUrl = url('/admin/vendors/' . $brand->id . '/edit');
                    $pageType = 'Coupon';
                }
            }
        }

        return [
            'user_label' => $user->email,
            'user_role' => $user->role ?? 'admin',
            'edit_url' => $editUrl,
            'page_type' => $pageType,
            'admin_home' => url('/admin/dashboard'),
            'seo_dashboard' => url('/admin/seo'),
        ];
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            // Colin 10/22 — WordPress-style admin bar on frontend pages.
            // Only populated for users with admin access. Carries the
            // contextual edit-URL for the current page + user label so
            // the AdminBar component can render without extra lookups.
            'admin_bar' => fn () => $this->buildAdminBar($request),
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'csrf_expired' => fn () => $request->session()->get('csrf_expired'),
            ],
            'csrf_token' => csrf_token(),
            'impersonating' => fn () => $request->session()->has('impersonating_from'),
            'demo_preview' => fn () => $request->session()->get('demo_preview') === true,
            'demo_preview_brand_name' => fn () => $request->session()->get('demo_preview_brand_name'),
            'is_demo_host' => fn () => $request->getHost() === 'demo.peptidemap.com',
            // Sidebar in Admin/Layout renders the CEO tab only when this is true.
            // Route is separately gated by the ceo.only middleware — this prop
            // is purely a UI hint, not a security boundary.
            'is_ceo' => fn () => strtolower((string) ($request->user()?->email ?? '')) === 'info@peptidemap.com',
            // seo_agency role — the agency sees a limited sidebar and
            // lands on /admin/seo instead of /admin/dashboard.
            'is_seo_agency' => fn () => $request->user()?->role === 'seo_agency',
            'site_name' => fn () => Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap',
            'site_description' => fn () => \App\Support\SiteCopy::description(
                Setting::where('key', 'site_description')->value('value')
            ),
            'contact_email' => fn () => Setting::where('key', 'contact_email')->value('value') ?? 'contact@peptidemaps.com',
            'pending_vendors_count' => fn () => $request->user() && $request->user()->canAccessAdmin()
                ? Brand::whereNotNull('user_id')->whereHas('vendorSetting', function ($query) {
                    $query->where('approval_status', 'pending');
                })->count() 
                : 0,
            'pending_reviews_count' => fn () => $request->user() && $request->user()->canAccessAdmin()
                ? VendorReview::where('status', 'pending')->count()
                : 0,
            // Shared wishlist membership arrays. Frontend heart-icons read
            // these to render active/inactive state without needing every
            // controller to pass in_wishlist_* props. Only fires for logged-in
            // customers; two small pluck queries per request. Wrapped in
            // Schema::hasTable so requests during a fresh install don't 500
            // before the migration runs.
            'wishlist' => function () use ($request) {
                $u = $request->user();
                if (!$u) return ['product_ids' => [], 'category_ids' => [], 'count' => 0];
                if (!\Illuminate\Support\Facades\Schema::hasTable('wishlists')) {
                    return ['product_ids' => [], 'category_ids' => [], 'count' => 0];
                }
                $rows = Wishlist::where('user_id', $u->id)
                    ->select('wishable_type', 'product_id', 'category_id')
                    ->get();
                return [
                    'product_ids' => $rows->where('wishable_type', 'product')->pluck('product_id')->filter()->values()->all(),
                    'category_ids' => $rows->where('wishable_type', 'category')->pluck('category_id')->filter()->values()->all(),
                    'count' => $rows->count(),
                ];
            },
            // Distinct vendor locations with live-inventory counts, powering the
            // global location dropdown in Header.vue. Cached 10min — this shape
            // rarely changes and the query joins products→brands→vendor_settings.
            // Ships-to counts (Sep 1 2026 — was HQ-location before). Uses
            // the vendor_ships_to_locations pivot so a US-based vendor
            // shipping to CA + UK counts in all three buckets. Falls back
            // to HQ location for vendors that never set a ships-to list
            // (migration backfilled from location_id so they should have one).
            // Cache key bumped to v2 to force fresh compute post-schema change.
            'site_locations' => fn () => Cache::remember('site_locations_v2', 600, function () {
                return Product::visible()
                    ->join('brands', 'products.brand_id', '=', 'brands.id')
                    ->join('vendor_settings', 'vendor_settings.brand_id', '=', 'brands.id')
                    ->join('vendor_ships_to_locations', 'vendor_ships_to_locations.vendor_setting_id', '=', 'vendor_settings.id')
                    ->join('locations', 'locations.id', '=', 'vendor_ships_to_locations.location_id')
                    ->selectRaw('locations.name as name, COUNT(DISTINCT brands.id) as vendor_count')
                    ->groupBy('locations.name')
                    ->orderByDesc('vendor_count')
                    ->get()
                    ->map(fn ($r) => ['name' => $r->name, 'vendor_count' => (int) $r->vendor_count])
                    ->values()
                    ->all();
            }),
            'approved_reviews_count' => fn () => $request->user() && $request->user()->isVendor()
                ? (function () use ($request) {
                    $brand = Brand::where('user_id', $request->user()->id)->first();
                    if (!$brand) {
                        return 0;
                    }

                    return VendorReview::where('brand_id', $brand->id)
                        ->where('status', 'approved')
                        ->whereNull('vendor_replied_at')
                        ->count();
                })()
                : 0,
        ]);
    }
} 