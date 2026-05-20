<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Binds a "demo_mode" flag in the container based on the request host.
 *
 *   demo.peptidemap.com  → demo_mode = true   (only is_demo=true rows surface)
 *   any other host       → demo_mode = false  (only is_demo=false rows surface)
 *
 * Admin routes are exempt — staff need to see all vendors regardless
 * of the demo flag, so the binding is skipped for /admin/* paths and
 * the global scope falls back to "show everything".
 */
class BindDemoMode
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();
        $host = $request->getHost();
        $demoMode = $host === 'demo.peptidemap.com';

        // Vendor dashboard routes need to bypass the demo filter so a vendor
        // can always see their own data regardless of host (a real vendor on
        // demo.peptidemap.com still needs their dashboard to work).
        // IMPORTANT: match `vendor/` not `vendor` to avoid matching `vendors`
        // (the public listing).
        if ($path === 'vendor' || str_starts_with($path, 'vendor/')) {
            return $next($request);
        }

        // Admin routes follow the host rule like everything else:
        //   dev/peptidemap.com/admin/vendors  → only real brands
        //   demo.peptidemap.com/admin/vendors → only demo brands
        // This keeps demo data out of every non-demo surface, including admin.
        app()->instance('demo_mode', $demoMode);

        return $next($request);
    }
}
