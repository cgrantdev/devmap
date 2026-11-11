<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Honor the top-nav CountrySelector's choice on every request.
 *
 * useGlobalLocation.js mirrors the selected country to a pmap_location
 * cookie. On a public GET with no explicit ?location= param, redirect
 * to the same URL with the cookie value appended as ?location=X — so
 * the URL always reflects the active filter (shareable, SEO-visible,
 * and Colin-approved: "it needs to hold the filter throughout the
 * whole site … forever"). Internal Inertia clicks have already been
 * pre-rewritten by the router.on('before') hook in app.js, so this
 * 302 only fires on the rare direct-URL navigation (typed address bar,
 * external backlink).
 *
 * Non-GET requests fall through silently merging the cookie into the
 * request input instead of redirecting: POSTs can't be redirected
 * without losing the body.
 *
 * Internal links / admin tooling always win: an explicit ?location= in
 * the URL short-circuits the redirect.
 */
class ApplyLocationCookie
{
    public function handle(Request $request, Closure $next)
    {
        // Admin, API, and asset routes have their own filter semantics.
        if ($request->is('admin/*') || $request->is('api/*') || $request->is('og/*') || $request->is('sitemap*')) {
            return $next($request);
        }

        // Explicit param wins.
        $explicit = (string) $request->query('location', '');
        if ($explicit !== '') {
            return $next($request);
        }

        $cookieVal = $request->cookie('pmap_location');
        if (!is_string($cookieVal) || $cookieVal === '') {
            return $next($request);
        }

        // For a GET pageload the URL itself gets rewritten — users see the
        // filter in their address bar, can copy/share it, and the before-hook
        // on subsequent clicks keeps every onward URL in sync. Skip Inertia
        // XHR requests (they're client-driven and the before-hook already
        // handled them) and non-GETs (can't redirect without dropping body).
        if ($request->isMethod('GET') && !$request->header('X-Inertia')) {
            $target = $request->url();
            $query = $request->query();
            $query['location'] = $cookieVal;
            $target .= '?' . http_build_query($query);
            return redirect($target, 302);
        }

        // Fallback: merge into the input so controllers' $request->location
        // filters still pick it up on POSTs and Inertia XHRs.
        $request->query->set('location', $cookieVal);
        $request->request->set('location', $cookieVal);
        return $next($request);
    }
}
