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

        // Admin and the vendor dashboard see all data — no demo filter.
        // IMPORTANT: match `vendor/` (with slash) not `vendor` — otherwise
        // the public `/vendors` listing was treated like the dashboard and
        // demo brands leaked onto every host. Same care for `admin/`.
        if (
            $path === 'admin' || str_starts_with($path, 'admin/') ||
            $path === 'vendor' || str_starts_with($path, 'vendor/')
        ) {
            return $next($request);
        }

        $host = $request->getHost();
        $demoMode = $host === 'demo.peptidemap.com';

        app()->instance('demo_mode', $demoMode);

        return $next($request);
    }
}
