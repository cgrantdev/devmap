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
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
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
            'site_name' => fn () => Setting::where('key', 'site_name')->value('value') ?? 'Peptidemap',
            'site_description' => fn () => Setting::where('key', 'site_description')->value('value') ?? 'Compare peptide brands, prices, and reviews',
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
            'site_locations' => fn () => Cache::remember('site_locations_v1', 600, function () {
                return Product::visible()
                    ->join('brands', 'products.brand_id', '=', 'brands.id')
                    ->join('vendor_settings', 'vendor_settings.brand_id', '=', 'brands.id')
                    ->join('locations', 'locations.id', '=', 'vendor_settings.location_id')
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