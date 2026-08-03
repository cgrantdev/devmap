<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ComingSoon
{
    /**
     * Serve the coming soon page for peptidemap.com (production only).
     * Dev subdomain, demo subdomain, and Forge deploy domain bypass this.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $path = $request->path();
        $siteLive = (bool) config('app.site_live');

        // GO-LIVE MODE: when SITE_LIVE=true is set in .env
        //  - peptidemap.com / www.peptidemap.com serve the real site (no coming-soon gate)
        //  - dev.peptidemap.com 301-redirects every path to the apex, preserving the URL
        //    so vendors who bookmarked dev.peptidemap.com/... still land on the right page
        //  - www.peptidemap.com 301-redirects to apex (single canonical hostname)
        if ($siteLive) {
            if ($host === 'www.peptidemap.com') {
                // Single canonical apex — always safe to 301 all methods here
                // because www.peptidemap.com was never handed out to vendors
                // as an integration endpoint.
                return redirect()->away('https://peptidemap.com/' . ltrim($request->getRequestUri(), '/'), 301);
            }
            if ($host === 'demo.peptidemap.com') {
                // Demo mode retired — all demo brands/products/users deleted
                // once vendors moved to their real dashboards. Any lingering
                // link to demo.peptidemap.com folds into the apex site.
                return redirect()->away('https://peptidemap.com/', 301);
            }
            if ($host === 'dev.peptidemap.com') {
                // Preserve dev.peptidemap.com as a live endpoint for anything
                // vendors may have integrated against (WordPress plugin,
                // WooCommerce OAuth callback, admin/vendor login sessions).
                // Only redirect user-facing GET traffic — 301 would drop POST
                // bodies on the floor and break their webhook posts.
                $isApi         = str_starts_with($path, 'api/');
                $isDownload    = str_starts_with($path, 'downloads/');
                $isAsset       = str_starts_with($path, 'build/')
                              || str_starts_with($path, 'storage/')
                              || str_starts_with($path, 'images/')
                              || str_starts_with($path, 'videos/');
                $isSanctum     = str_starts_with($path, 'sanctum/');

                if ($request->isMethod('GET') && !$isApi && !$isDownload && !$isAsset && !$isSanctum) {
                    return redirect()->away('https://peptidemap.com/' . ltrim($request->getRequestUri(), '/'), 301);
                }
                // Non-GET or infrastructure paths: serve normally on dev.
            }
        }

        // join.peptidemap.com — standalone vendor invitation page.
        // The root path "/" is served by a domain-scoped route in web.php.
        // /join is canonicalized → "/" so we always show join.peptidemap.com/
        // Whitelist: root, assets, the form submission endpoint, and CSRF.
        if ($host === 'join.peptidemap.com') {
            // Canonicalize /join → / on this subdomain
            if ($path === 'join') {
                $query = $request->getQueryString();
                return redirect('/' . ($query ? ('?' . $query) : ''), 301);
            }

            $allowed =
                $path === '/' ||
                $path === '' ||
                str_starts_with($path, 'images/') ||
                str_starts_with($path, 'build/') ||
                str_starts_with($path, 'storage/') ||
                str_starts_with($path, 'videos/') ||
                $path === 'become-a-vendor' || // POST submission target
                $path === 'registration-complete' || // success page after submit
                $path === 'sanctum/csrf-cookie';

            if (!$allowed) {
                // Anything unexpected → bounce to the invitation root
                $query = $request->getQueryString();
                return redirect('/' . ($query ? ('?' . $query) : ''), 302);
            }

            return $next($request);
        }

        // Only gate the bare production domains, and only while not live.
        if (!$siteLive && ($host === 'peptidemap.com' || $host === 'www.peptidemap.com')) {
            // Anyone landing on /become-a-vendor (or related signup paths) on the
            // bare production domain — usually from old links or search results —
            // gets bounced to the canonical invitation subdomain. Without this the
            // middleware silently serves the coming-soon HTML for GET and 404s
            // the POST, which is what stranded the Hydro Research signup.
            if (
                $path === 'become-a-vendor' ||
                $path === 'registration-complete' ||
                $path === 'join'
            ) {
                $query = $request->getQueryString();
                return redirect()->away('https://join.peptidemap.com/' . ($query ? ('?' . $query) : ''), 302);
            }

            // Allow through if it's a known asset request
            if (str_starts_with($path, 'images/') || str_starts_with($path, 'build/') || str_starts_with($path, 'storage/')) {
                return $next($request);
            }

            // Allow the newsletter subscribe endpoint
            if ($path === 'api/subscribe') {
                return $next($request);
            }

            // Allow the WordPress plugin connect endpoint
            if ($path === 'api/vendor-plugin/connect') {
                return $next($request);
            }

            // Allow plugin download
            if ($path === 'downloads/peptidemap-connect.zip') {
                return $next($request);
            }

            // Allow admin and vendor login + the protected admin/vendor areas
            // so staff can access the dashboard while the countdown is up.
            if (
                str_starts_with($path, 'admin') ||
                str_starts_with($path, 'vendor') ||
                $path === 'login' ||
                $path === 'logout' ||
                str_starts_with($path, 'email/')
            ) {
                return $next($request);
            }

            return response()->file(public_path('coming-soon.html'));
        }

        // All other hosts (demo.peptidemap.com, dev subdomain, Forge domain) pass through
        return $next($request);
    }
}
