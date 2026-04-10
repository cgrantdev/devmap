<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ComingSoon
{
    /**
     * Serve the coming soon page for peptidemap.com (non-dev domain).
     * Dev subdomain and admin routes bypass this.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Only intercept the production domain (not dev subdomain)
        if ($host === 'peptidemap.com' || $host === 'www.peptidemap.com') {
            // Allow through if it's a known asset request
            $path = $request->path();
            if (str_starts_with($path, 'images/') || str_starts_with($path, 'build/') || str_starts_with($path, 'storage/')) {
                return $next($request);
            }

            return response()->file(public_path('coming-soon.html'));
        }

        return $next($request);
    }
}
