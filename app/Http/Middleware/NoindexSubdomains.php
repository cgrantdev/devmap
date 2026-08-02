<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds an `X-Robots-Tag: noindex, nofollow` HTTP header to every response
 * served on non-canonical subdomains (currently demo.peptidemap.com and
 * join.peptidemap.com). This tells Google to drop those hosts from its index
 * on next crawl — belt-and-suspenders with the <meta name="robots"> tag the
 * Blade layout also emits for HTML responses.
 *
 * Important interaction with robots.txt: for deindexing to actually work,
 * Google must be able to CRAWL the URL and see this header. Those subdomains
 * therefore serve a permissive robots.txt (see RobotsController) rather than
 * `Disallow: /`, which would block crawling and freeze old snippet-less
 * entries in the index forever.
 */
class NoindexSubdomains
{
    private const NOINDEX_HOSTS = ['demo.peptidemap.com', 'join.peptidemap.com'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array(strtolower($request->getHost()), self::NOINDEX_HOSTS, true)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
