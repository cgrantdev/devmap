<?php

namespace App\Http\Middleware;

use App\Models\ProductCategory;
use App\Support\EncyclopediaSlug;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compare and encyclopedia URLs are canonical without a trailing slash.
 * Nginx does not apply the Apache rewrite in public/.htaccess, so a
 * slash-suffixed path was returning 200 alongside the slash-free URL.
 * Encyclopedia aliases 301 to the final canonical path in that same hop.
 */
class RedirectCompareTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = $request->getPathInfo();
        if ($path === '/' || ! str_ends_with($path, '/')) {
            return $next($request);
        }

        $trimmed = rtrim($path, '/') ?: '/';
        $target = $this->canonicalTarget($trimmed);
        if ($target === null) {
            return $next($request);
        }

        $query = $request->getQueryString();

        return redirect($target.($query ? '?'.$query : ''), 301);
    }

    /**
     * Trailing-slash requests 301 straight to the final canonical URL.
     * Encyclopedia case aliases must not stop on an intermediate slug
     * (Elamipretide/ → elamipretide, VITAMIN--B12/ → vitamin-b12).
     */
    private function canonicalTarget(string $trimmed): ?string
    {
        if ($trimmed === '/compare' || str_starts_with($trimmed, '/compare/')) {
            return $trimmed;
        }

        if ($trimmed !== '/encyclopedia' && ! str_starts_with($trimmed, '/encyclopedia/')) {
            return null;
        }

        if ($trimmed === '/encyclopedia') {
            return '/encyclopedia';
        }

        $slug = rawurldecode(substr($trimmed, strlen('/encyclopedia/')));
        if (str_starts_with($slug, 'article/')) {
            $slug = substr($slug, strlen('article/'));
        }

        return ProductCategory::encyclopediaRedirectPath($slug)
            ?? EncyclopediaSlug::path($slug)
            ?? $trimmed;
    }
}
