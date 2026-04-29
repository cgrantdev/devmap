<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Scopes\DemoScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Public "preview the vendor dashboard" entry-point used during outreach.
 *
 * Flow:
 *   1. Prospect lands on demo.peptidemap.com.
 *   2. Clicks "Try Vendor Dashboard" → POST /demo/preview-vendor.
 *   3. We auto-login them as the seeded "Helix Research Co." demo vendor
 *      and stamp a session flag so the vendor Layout shows a "Demo Mode"
 *      banner with an "Exit demo" button.
 *   4. They click around their future dashboard with real-feeling demo data.
 *
 * Restrictions:
 *   - Only works when host is demo.peptidemap.com (else 404). Prevents
 *     anyone from auto-logging in as a real vendor on dev/prod.
 *   - The demo vendor account is the seeded Helix Research Co. user
 *     (email: demo+helix-research-co@peptidemap.com), which has role:vendor
 *     and is_demo=true on its brand.
 */
class DemoPreviewController extends Controller
{
    private const DEMO_VENDOR_SLUG = 'helix-research-co';

    public function start(Request $request)
    {
        if ($request->getHost() !== 'demo.peptidemap.com') {
            abort(404);
        }

        $brand = Brand::withoutGlobalScope(DemoScope::class)
            ->where('slug', self::DEMO_VENDOR_SLUG)
            ->where('is_demo', true)
            ->with('user')
            ->first();

        if (!$brand || !$brand->user) {
            abort(404, 'Demo vendor account not found.');
        }

        Auth::guard('web')->login($brand->user);
        $request->session()->regenerate();
        $request->session()->put('demo_preview', true);
        $request->session()->put('demo_preview_brand_name', $brand->name);

        return redirect('/vendor/dashboard');
    }

    public function exit(Request $request)
    {
        $isDemoSession = $request->session()->get('demo_preview') === true;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Send them back to the demo home so they can keep browsing.
        if ($isDemoSession || $request->getHost() === 'demo.peptidemap.com') {
            return redirect('/');
        }

        return redirect('/');
    }
}
