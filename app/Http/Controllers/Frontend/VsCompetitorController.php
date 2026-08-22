<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Inertia\Inertia;

/**
 * Position pages targeting people who search for a competing peptide
 * comparison site by name. Honest side-by-side — no fake numbers — that
 * surfaces our real depth advantage. Every page ranks on the competitor's
 * branded query without us having to touch their site.
 */
class VsCompetitorController extends Controller
{
    // Competitor facts audited Aug 2026. Numbers come from their public
    // sitemaps, homepage claims, and what our crawl could observe. Update
    // this table when they materially change — keep 'audited_at' honest.
    private const COMPETITORS = [
        'peptidecompare' => [
            'name' => 'PeptideCompare',
            'domain' => 'peptidecompare.com',
            'sitemap_urls' => 0,
            'vendor_count' => null,
            'product_count' => null,
            'freshness' => 'Unknown',
            'strengths' => ['Short memorable domain'],
            'gaps' => [
                'Empty sitemap — homepage only',
                'No indexed product or vendor pages',
                'No visible per-compound comparisons',
                'No coupon codes surfaced',
                'No freshness signal on prices',
            ],
        ],
        'peptidepricing' => [
            'name' => 'PeptidePricing',
            'domain' => 'peptidepricing.com',
            'sitemap_urls' => 0,
            'vendor_count' => 128,
            'vendor_count_note' => 'claimed but unverifiable',
            'product_count' => null,
            'freshness' => 'Unknown',
            'strengths' => ['Bold vendor-count headline'],
            'gaps' => [
                'Site behind a Vercel bot-check — Google crawler blocked',
                '"128+ verified suppliers" claim cannot be verified externally',
                'Sitemap unreachable',
                'No visible outbound affiliate infrastructure',
            ],
        ],
        'peptideprice' => [
            'name' => 'PeptidePrice.store',
            'domain' => 'peptideprice.store',
            'sitemap_urls' => 893,
            'vendor_count' => 50,
            'product_count' => null,
            'freshness' => 'Not surfaced',
            'strengths' => ['Reasonable indexed footprint (~900 URLs)'],
            'gaps' => [
                'Fewer indexed pages than Peptidemap (893 vs 3,387)',
                'No per-compound comparison pages',
                'No coupon-code widget on rows',
                'No freshness cue in listings',
            ],
        ],
        'thepeptidecatalog' => [
            'name' => 'The Peptide Catalog',
            'domain' => 'thepeptidecatalog.com',
            'sitemap_urls' => 974,
            'vendor_count' => 5,
            'product_count' => null,
            'freshness' => 'Yes — surfaces last-updated',
            'strengths' => [
                'Strong freshness signal in Google snippets',
                'Category verticals (/skincare, /blends, /bulk)',
                'Reconstitution calculator + supply pages',
            ],
            'gaps' => [
                'Only 5 vendors tracked (we track 33+)',
                'Fewer total indexed pages (974 vs 3,387)',
                'No coupon-code integration',
                'No verified customer reviews on brand pages',
            ],
        ],
    ];

    public function show(string $slug)
    {
        $slug = strtolower($slug);
        $competitor = self::COMPETITORS[$slug] ?? null;
        if (!$competitor) {
            abort(404);
        }

        // Our real, current numbers — pulled live so this page stays honest
        // as the catalog grows.
        $ourStats = [
            'name' => 'Peptidemap',
            'vendor_count' => Brand::where('is_active', true)
                ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved'))
                ->count(),
            'product_count' => Product::visible()->where('status', 'active')->count(),
            'category_count' => ProductCategory::where('is_active', true)->count(),
            'sitemap_urls' => 3387, // matches actual sitemap.xml — refresh if it grows meaningfully
            'freshness' => 'Prices refresh hourly',
            'strengths' => [
                'Largest indexed footprint of any peptide comparison site',
                'Per-compound compare tables with coupon codes',
                'Verified customer reviews + imported Trustpilot',
                'Live currency indicators per vendor country',
                'Public API for vendors to push catalog updates',
                'Discord bot + free deal alerts',
            ],
            'gaps' => [], // when we're missing something honest — add here.
        ];

        $seo = [
            'title' => "Peptidemap vs {$competitor['name']} — Side-by-Side Comparison",
            'description' => "How Peptidemap compares to {$competitor['name']}: {$ourStats['vendor_count']} vendors, {$ourStats['product_count']} tracked products, hourly-refreshed prices, and coupon codes on every listing.",
            'canonical' => url("/vs/{$slug}"),
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/VsCompetitor', [
            'competitor' => array_merge($competitor, ['slug' => $slug]),
            'ours' => $ourStats,
            'seo' => $seo,
        ]);
    }
}
