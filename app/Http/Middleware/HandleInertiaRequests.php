<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\Brand;
use Illuminate\Http\Request;
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
            ],
            'csrf_token' => csrf_token(),
            'impersonating' => fn () => $request->session()->has('impersonating_from'),
            'site_name' => fn () => Setting::where('key', 'site_name')->value('value') ?? 'PeptideSync',
            'site_description' => fn () => Setting::where('key', 'site_description')->value('value') ?? 'Compare peptide brands, prices, and reviews',
            'contact_email' => fn () => Setting::where('key', 'contact_email')->value('value') ?? 'contact@peptidemaps.com',
            'pending_vendors_count' => fn () => $request->user() && $request->user()->isAdmin() 
                ? Brand::whereHas('vendorSetting', function ($query) {
                    $query->where('approval_status', 'pending');
                })->count() 
                : 0,
            'pending_reviews_count' => fn () => $request->user() && $request->user()->isAdmin()
                ? VendorReview::where('status', 'pending')->count()
                : 0,
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