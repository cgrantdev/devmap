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
        // Whitelist: assets, /join, the form submission endpoint, and CSRF.
        // Anything else gets redirected back to the invitation root.
        if ($host === 'join.peptidemap.com') {
            $allowed =
                str_starts_with($path, 'images/') ||
                str_starts_with($path, 'build/') ||
                str_starts_with($path, 'storage/') ||
                $path === 'join' ||
                str_starts_with($path, 'join/') ||
                $path === 'become-a-vendor' || // POST submission target
                $path === 'sanctum/csrf-cookie';

            if (!$allowed) {
                // Forward root and unknown paths to the join page (preserve query string)
                $query = $request->getQueryString();
                $target = '/join' . ($query ? ('?' . $query) : '');
                return redirect($target, 302);
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
