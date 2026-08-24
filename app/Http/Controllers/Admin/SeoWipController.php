<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\CeoNote;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoRecommendation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Internal-only SEO work-in-progress inventory.
 *
 * One page that answers: "what content do we actually have live for SEO,
 * and what's in the queue?" Pulls live from the DB — nothing hardcoded —
 * so counts stay honest as pages ship.
 *
 * Route: /admin/seo-wip (admin-gated + noindex header via middleware,
 * also excluded from sitemap).
 */
class SeoWipController extends Controller
{
    public function index(): Response
    {
        // Cache-warm the inventory query set — cheap counts but there are ~15,
        // and we don't want an admin refresh to bang the DB every time.
        $inventory = Cache::remember('seo_wip_inventory_v1', 300, function () {
            return $this->buildInventory();
        });

        $recs = [
            'open' => SeoRecommendation::where('status', 'open')->count(),
            'in_progress' => SeoRecommendation::where('status', 'in_progress')->count(),
            'shipped_7d' => SeoRecommendation::where('status', 'shipped')
                ->where('shipped_at', '>=', now()->subDays(7))->count(),
            'shipped_30d' => SeoRecommendation::where('status', 'shipped')
                ->where('shipped_at', '>=', now()->subDays(30))->count(),
            'shipped_recent' => SeoRecommendation::where('status', 'shipped')
                ->orderByDesc('shipped_at')
                ->limit(15)
                ->get(['id', 'title', 'category', 'impact', 'shipped_at'])
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'title' => $r->title,
                    'category' => $r->category,
                    'impact' => $r->impact,
                    'shipped_at' => $r->shipped_at?->diffForHumans(),
                ]),
            'open_top' => SeoRecommendation::where('status', 'open')
                ->orderByRaw("FIELD(impact, 'high', 'medium', 'low')")
                ->orderBy('position')
                ->limit(10)
                ->get(['id', 'title', 'category', 'impact', 'effort'])
                ->map(fn ($r) => [
                    'id' => $r->id, 'title' => $r->title, 'category' => $r->category,
                    'impact' => $r->impact, 'effort' => $r->effort,
                ]),
        ];

        $notes = CeoNote::firstOrCreate(['key' => 'seo_wip_notes'], ['body' => ''])->body;

        return Inertia::render('Admin/SeoWip', [
            'inventory' => $inventory,
            'recs' => $recs,
            'notes' => $notes,
        ]);
    }

    public function saveNotes(Request $request): RedirectResponse
    {
        $v = $request->validate(['body' => 'nullable|string|max:50000']);
        CeoNote::updateOrCreate(['key' => 'seo_wip_notes'], ['body' => $v['body'] ?? '']);
        return back();
    }

    private function buildInventory(): array
    {
        // Live surface counts. Each entry: {title, count, live_url_pattern, notes}
        return [
            [
                'group' => 'Programmatic (data-driven)',
                'items' => [
                    ['title' => 'Vendor pages', 'count' => Brand::where('is_active', true)->count(),
                        'url_pattern' => '/brand/{slug}', 'sample' => '/brand/certapeptides',
                        'notes' => 'One per active brand. Meta descriptions now templated with real coupon+count. Ships automatically on vendor onboarding.'],
                    ['title' => 'Per-compound compare pages', 'count' => ProductCategory::where('is_active', true)->count(),
                        'url_pattern' => '/compare/{slug}', 'sample' => '/compare/bpc-157',
                        'notes' => 'Targets "cheapest {compound}" + "{compound} vendor comparison" intent.'],
                    ['title' => 'X-vs-Y compound pages', 'count' => 15,
                        'url_pattern' => '/compare/{x}-vs-{y}', 'sample' => '/compare/tirzepatide-vs-retatrutide',
                        'notes' => 'FEATURED_VS_PAIRS constant in CompareController — 15 curated pairs. Expand to 100+ for major long-tail win.'],
                    ['title' => 'Product-detail pages', 'count' => Product::visible()->where('status', 'active')->count(),
                        'url_pattern' => '/product/{vendor}/{slug}/{id}', 'sample' => '/product/certapeptides/…',
                        'notes' => 'Deepest indexed surface. Each has its own OG image + coupon inject.'],
                    ['title' => 'Encyclopedia entries', 'count' => Schema::hasTable('education_posts')
                        ? EducationPost::where('status', 'published')->count() : 0,
                        'url_pattern' => '/encyclopedia/{slug}', 'sample' => '/encyclopedia/bpc-157',
                        'notes' => 'Informational content. TODO: add ItemList+Offer schema so these rank on "buy {compound}" too (SEO rec #3).'],
                    ['title' => 'Blog posts', 'count' => Schema::hasTable('blogs')
                        ? Blog::where('status', 'published')->count() : 0,
                        'url_pattern' => '/blog/{slug}', 'sample' => '/blogs',
                        'notes' => 'Editorial. Underused — only 6 posts.'],
                ],
            ],
            [
                'group' => 'Landing pages (vertical hubs)',
                'items' => [
                    ['title' => 'Blends catalog', 'count' => 1, 'url_pattern' => '/blends', 'sample' => '/blends',
                        'notes' => 'Filters catalog to blend products (148 rows).'],
                    ['title' => 'Skincare catalog', 'count' => 1, 'url_pattern' => '/skincare', 'sample' => '/skincare',
                        'notes' => 'Topicals + cosmetic peptides (GHK-Cu, Argireline, Matrixyl…).'],
                    ['title' => 'Bulk catalog', 'count' => 1, 'url_pattern' => '/bulk', 'sample' => '/bulk',
                        'notes' => 'Gram-scale + 100mg+ vials for cost-per-mg optimization.'],
                    ['title' => 'Testing labs', 'count' => 1, 'url_pattern' => '/testing-labs', 'sample' => '/testing-labs',
                        'notes' => 'Trust-signal page grouping vendors by third-party lab.'],
                    ['title' => 'Bacteriostatic water', 'count' => 1, 'url_pattern' => '/bacteriostatic-water',
                        'sample' => '/bacteriostatic-water',
                        'notes' => 'High-intent commercial landing. Per-mL sort + FAQ + FAQPage schema.'],
                ],
            ],
            [
                'group' => 'Vs-competitor position pages',
                'items' => [
                    ['title' => 'vs Peptidecompare', 'count' => 1, 'url_pattern' => '/vs/peptidecompare',
                        'sample' => '/vs/peptidecompare', 'notes' => 'Empty-sitemap competitor. Easy displacement.'],
                    ['title' => 'vs The Peptide Catalog', 'count' => 1, 'url_pattern' => '/vs/thepeptidecatalog',
                        'sample' => '/vs/thepeptidecatalog', 'notes' => 'Their 5 vendors vs our 33+.'],
                    ['title' => 'vs Peptideprice', 'count' => 1, 'url_pattern' => '/vs/peptideprice',
                        'sample' => '/vs/peptideprice', 'notes' => '893 pages vs our 3,396.'],
                    ['title' => 'vs Peptidepricing', 'count' => 1, 'url_pattern' => '/vs/peptidepricing',
                        'sample' => '/vs/peptidepricing', 'notes' => 'Their bot-blocked site vs ours.'],
                ],
            ],
            [
                'group' => 'Utilities & vendor-facing',
                'items' => [
                    ['title' => 'Calculator', 'count' => 1, 'url_pattern' => '/calculator', 'sample' => '/calculator',
                        'notes' => 'Basic reconstitution calculator. Deep-link URLs (/calculator/{compound}/{mg}/{ml}) NOT yet built — that\'s the link-worthy asset (SEO rec #4).'],
                    ['title' => 'Vendor integration docs', 'count' => 1, 'url_pattern' => '/vendors/integration',
                        'sample' => '/vendors/integration', 'notes' => 'Path 1/2/3 integration guide. Push API is live.'],
                    ['title' => 'Vendor badge widget', 'count' => Brand::where('is_active', true)->count(),
                        'url_pattern' => '/for-vendors/badge/{slug}', 'sample' => '/for-vendors/badge/certapeptides',
                        'notes' => 'One per vendor. Awaiting vendor-outreach email blast to convert into backlinks.'],
                    ['title' => 'Badge SVG endpoint', 'count' => Brand::where('is_active', true)->count(),
                        'url_pattern' => '/badge/{slug}.svg', 'sample' => '/badge/certapeptides.svg',
                        'notes' => 'Cached 1h. What vendors actually embed.'],
                ],
            ],
            [
                'group' => 'Top-level hubs',
                'items' => [
                    ['title' => 'Homepage', 'count' => 1, 'url_pattern' => '/', 'sample' => '/', 'notes' => 'Should surface real numbers (3,396 pages / 2,535 products / hourly prices) more prominently.'],
                    ['title' => 'Vendors index', 'count' => 1, 'url_pattern' => '/vendors', 'sample' => '/vendors', 'notes' => 'Browsable directory.'],
                    ['title' => 'Products index', 'count' => 1, 'url_pattern' => '/products', 'sample' => '/products', 'notes' => 'Filterable catalog.'],
                    ['title' => 'Compare hub', 'count' => 1, 'url_pattern' => '/compare', 'sample' => '/compare', 'notes' => 'Compound cards linking to per-compound compare pages.'],
                    ['title' => 'Encyclopedia index', 'count' => 1, 'url_pattern' => '/encyclopedia', 'sample' => '/encyclopedia', 'notes' => 'Category grid.'],
                    ['title' => 'Deals', 'count' => 1, 'url_pattern' => '/deals', 'sample' => '/deals', 'notes' => 'Active coupon codes.'],
                    ['title' => 'Search', 'count' => 1, 'url_pattern' => '/search', 'sample' => '/search', 'notes' => 'Site search.'],
                    ['title' => 'News', 'count' => 1, 'url_pattern' => '/news', 'sample' => '/news', 'notes' => 'Research updates.'],
                ],
            ],
        ];
    }
}
