<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

/**
 * Public technical docs at /vendors/integration.
 *
 * Written for vendor IT / dev teams who need to hook their catalog into
 * Peptidemap. Three integration paths documented — JSON feed (default),
 * Push API (built on request), and Custom scraper (for vendors whose
 * platform doesn't fit either of the first two).
 *
 * Page is static — SEO meta emitted via the session-based page_seo_data
 * pattern the rest of the site uses.
 */
class VendorIntegrationController extends Controller
{
    public function show()
    {
        $seo = [
            'key' => 'vendors-integration',
            'title' => 'Vendor Integration Guide — Peptidemap',
            'description' => 'Three ways to sync your product catalog with Peptidemap: JSON feed, Push API, or a custom scraper. Full spec + examples for vendor IT teams.',
            'og_title' => 'Vendor Integration Guide — Peptidemap',
            'og_description' => 'Three ways to sync your product catalog with Peptidemap: JSON feed, Push API, or custom scraper. Full spec + examples.',
            'og_image' => null,
            'image' => null,
            'url' => url('/vendors/integration'),
            'h1' => 'Vendor Integration Guide',
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/VendorIntegration', ['seo' => $seo]);
    }
}
