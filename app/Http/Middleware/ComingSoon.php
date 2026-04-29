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
                $path === 'sanctum/csrf-cookie';

            if (!$allowed) {
                // Anything unexpected → bounce to the invitation root
                $query = $request->getQueryString();
                return redirect('/' . ($query ? ('?' . $query) : ''), 302);
            }

            return $next($request);
        }

        // Only gate the bare production domains
        if ($host === 'peptidemap.com' || $host === 'www.peptidemap.com') {
            // Allow through if it's a known asset request
            if (str_starts_with($path, 'images/') || str_starts_with($path, 'build/') || str_starts_with($path, 'storage/')) {
                return $next($request);
            }

            // Allow the newsletter subscribe endpoint
            if ($path === 'api/subscribe') {
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
