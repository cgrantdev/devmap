<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the 'seo_agency' role from admin routes it has no business
 * touching — vendor financial data, user management, affiliate payouts,
 * site settings, scraping config, dev console, etc.
 *
 * Returns 403 so the sidebar links still show (styling-wise harmless)
 * but clicking lands on a clear "not allowed" page instead of silently
 * breaking.
 *
 * Agency CAN access: /admin/seo/*, /admin/blogs/*,
 * /admin/encyclopedia-entries/*, /admin/analytics/*, /admin/ceo
 * (read-only GSC), /admin/dashboard.
 *
 * Raw /admin/products + /admin/categories are BLOCKED — those expose
 * price, stock, affiliate URLs, scraper config, and admin notes. The
 * agency uses the SEO-scoped equivalents at /admin/seo/products +
 * /admin/seo/brands instead.
 */
class BlockSeoAgency
{
    private const BLOCKED_PATH_PREFIXES = [
        'admin/vendors',
        'admin/applicants',
        'admin/users',
        'admin/affiliates',
        'admin/dev',
        'admin/settings',
        'admin/scraping',
        'admin/discover',
        'admin/staged-products',
        'admin/deals',
        'admin/banners',
        'admin/reviews',
        'admin/seo-wip',
        'admin/product-scraping',
        'admin/products',
        'admin/categories',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isRealAgency = $user && ($user->role ?? '') === 'seo_agency';
        $isPreview = !$isRealAgency && (bool) session('preview_as_agency', false);

        // Real agency = full enforcement. Admin previewing = same block
        // list applies so what Colin sees matches what the agency sees.
        if (!$isRealAgency && !$isPreview) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        foreach (self::BLOCKED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                if ($isPreview) {
                    // Preview: show the exit affordance instead of a 403 wall.
                    return redirect('/admin/seo')->with('error', "Agency can't access /{$path} — exit preview to view this page.");
                }
                abort(403, 'This section is not available to SEO agency accounts. Contact Colin for access.');
            }
        }
        return $next($request);
    }
}
