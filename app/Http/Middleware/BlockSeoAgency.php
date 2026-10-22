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
 * Agency CAN access: /admin/seo/*, /admin/products/*, /admin/blogs/*,
 * /admin/encyclopedia-entries/*, /admin/categories/*,
 * /admin/analytics/*, /admin/ceo (read-only GSC), /admin/dashboard.
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
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || ($user->role ?? '') !== 'seo_agency') {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        foreach (self::BLOCKED_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                abort(403, 'This section is not available to SEO agency accounts. Contact Colin for access.');
            }
        }
        return $next($request);
    }
}
