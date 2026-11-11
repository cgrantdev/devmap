<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Honor the top-nav CountrySelector's choice on every request.
 *
 * useGlobalLocation.js mirrors the selected country to a pmap_location
 * cookie. When present and the request doesn't already carry an explicit
 * ?location= param, merge the cookie value into the query so every
 * controller's ->request->location filter picks it up without the
 * frontend having to append ?location=X onto every internal Link.
 *
 * Internal links win: an explicit ?location= in the URL overrides the
 * cookie, so admin tooling, shared links, and the LocationFilterChip's
 * clear-via-applyLocation('') path keep working unchanged.
 */
class ApplyLocationCookie
{
    public function handle(Request $request, Closure $next)
    {
        // Admin, API, and asset routes have their own filter semantics —
        // keep the cookie scoped to the public, Inertia-rendered pages.
        if ($request->is('admin/*') || $request->is('api/*') || $request->is('og/*') || $request->is('sitemap*')) {
            return $next($request);
        }

        $explicit = (string) $request->query('location', '');
        if ($explicit !== '') {
            return $next($request);
        }

        $cookieVal = $request->cookie('pmap_location');
        if (is_string($cookieVal) && $cookieVal !== '') {
            // Merge into both the query bag and the request input so
            // $request->location, $request->has('location'), and
            // $request->query('location') all see it.
            $request->query->set('location', $cookieVal);
            $request->request->set('location', $cookieVal);
        }

        return $next($request);
    }
}
