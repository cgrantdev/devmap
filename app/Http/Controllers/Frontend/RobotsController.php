<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Host-aware robots.txt.
 *
 *  peptidemap.com / www.peptidemap.com → allow crawling + sitemap reference
 *  demo.peptidemap.com                 → allow crawling (so Google sees the
 *                                        `X-Robots-Tag: noindex` header served
 *                                        by NoindexSubdomains middleware and
 *                                        actually deindexes these pages).
 *                                        Disallow: / here would freeze old
 *                                        snippet-less entries in the index.
 *  join.peptidemap.com                 → same reasoning as demo.
 *  dev.peptidemap.com                  → Disallow: / (all user-facing GETs
 *                                        already 301 to apex; blocking helps
 *                                        crawlers save budget).
 *  anything else (e.g. Forge subdomain)→ Disallow: / (defensive).
 */
class RobotsController extends Controller
{
    private const CANONICAL_HOSTS = ['peptidemap.com', 'www.peptidemap.com'];
    private const NOINDEX_ALLOW_CRAWL_HOSTS = ['demo.peptidemap.com', 'join.peptidemap.com'];

    public function show(Request $request): Response
    {
        $host = strtolower($request->getHost());

        if (in_array($host, self::CANONICAL_HOSTS, true)) {
            $body = "User-agent: *\n"
                  . "Disallow: /admin/\n"
                  . "Disallow: /vendor/\n"
                  . "Disallow: /login\n"
                  . "Disallow: /logout\n"
                  . "Disallow: /register\n"
                  . "Disallow: /password/\n"
                  . "Disallow: /email/\n"
                  . "Disallow: /api/\n"
                  . "Disallow: /sanctum/\n"
                  . "Allow: /\n\n"
                  . "Sitemap: https://peptidemap.com/sitemap.xml\n";
        } elseif (in_array($host, self::NOINDEX_ALLOW_CRAWL_HOSTS, true)) {
            // Deliberately permissive: pair with X-Robots-Tag: noindex header.
            $body = "User-agent: *\n"
                  . "Disallow: /admin/\n"
                  . "Disallow: /vendor/\n"
                  . "Disallow: /api/\n"
                  . "Disallow: /sanctum/\n"
                  . "Allow: /\n";
        } else {
            $body = "User-agent: *\n"
                  . "Disallow: /\n";
        }

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
