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

        // Only gate the bare production domains
        if ($host === 'peptidemap.com' || $host === 'www.peptidemap.com') {
            // Allow through if it's a known asset request
            $path = $request->path();
            if (str_starts_with($path, 'images/') || str_starts_with($path, 'build/') || str_starts_with($path, 'storage/')) {
                return $next($request);
            }

            // Allow the newsletter subscribe endpoint
            if ($path === 'api/subscribe') {
                return $next($request);
            }

            return response()->file(public_path('coming-soon.html'));
        }

        // All other hosts (demo.peptidemap.com, dev subdomain, Forge domain) pass through
        return $next($request);
    }
}
