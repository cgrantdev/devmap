<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compare URLs are canonical without a trailing slash. Nginx does not apply
 * the Apache rewrite in public/.htaccess, so /compare/ and /compare/{slug}/
 * were returning 200 alongside the slash-free URL.
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
        if ($trimmed !== '/compare' && ! str_starts_with($trimmed, '/compare/')) {
            return $next($request);
        }

        $query = $request->getQueryString();

        return redirect($trimmed.($query ? '?'.$query : ''), 301);
    }
}
