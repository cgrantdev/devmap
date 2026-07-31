<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Host-aware robots.txt.
 *
 *  peptidemap.com / www.peptidemap.com → allow crawling + sitemap reference
 *  dev.peptidemap.com                  → Disallow: / (avoid duplicate index)
 *  demo.peptidemap.com                 → Disallow: / (demo data isn't public)
 *  join.peptidemap.com                 → Disallow: / (transactional landing)
 *  anything else (e.g. Forge subdomain)→ Disallow: / (defensive)
 */
class RobotsController extends Controller
{
    public function show(Request $request): Response
    {
        $host = strtolower($request->getHost());

        $isCanonical = ($host === 'peptidemap.com' || $host === 'www.peptidemap.com');

        if ($isCanonical) {
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
        } else {
            $body = "User-agent: *\n"
                  . "Disallow: /\n";
        }

        return response($body, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
