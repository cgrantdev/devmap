<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoPage;
use App\Services\SeoMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Agency-facing SEO dashboard. Lives at /admin/seo and renders:
 *   1. Overview — live GSC snapshot + index coverage + recovery signal
 *   5. Playbook — day-one orientation for the agency
 *
 * Sections 2/3/4 (page editor, opportunities, content editor) will
 * follow in subsequent commits.
 *
 * Access: anyone with admin role. Deliberately NOT ceo.only so the
 * agency contact can log in and work directly.
 */
class SeoDashboardController extends Controller
{
    public function __construct(private SeoMetrics $seo) {}

    public function index(Request $request): Response
    {
        $snap = $this->seo->snapshot(28);
        $snap7 = $this->seo->snapshot(7);
        $sitemapCount = $this->estimateSitemapCount();

        // Admins can preview the agency view with ?view=agency|exit.
        // Flipping the session flag means the preview applies to every
        // page they visit (sidebar, dashboard, etc), not just this one.
        if ($request->query('view') === 'agency' && !$request->user()?->isSeoAgency()) {
            session(['preview_as_agency' => true]);
            return redirect('/admin/seo');
        }
        if ($request->query('view') === 'exit' || $request->query('view') === 'admin') {
            session()->forget('preview_as_agency');
            return redirect('/admin/seo');
        }

        $isAgency = ($request->user()?->isSeoAgency() ?? false)
            || (bool) session('preview_as_agency', false);
        $isPreview = !($request->user()?->isSeoAgency() ?? false)
            && (bool) session('preview_as_agency', false);

        return Inertia::render('Admin/SeoDashboard', [
            'tab' => 'overview',
            'is_agency' => $isAgency,
            'is_agency_preview' => $isPreview,
            'agency_welcome' => $this->agencyWelcome(),
            'snapshot_28d' => $snap,
            'snapshot_7d' => $snap7,
            'index_coverage' => [
                'sitemap_total' => $sitemapCount['total'],
                'by_pattern' => $sitemapCount['by_pattern'],
            ],
            'recovery_context' => $this->recoveryContext(),
        ]);
    }

    /**
     * First-touch orientation shown to seo_agency users on the SEO
     * dashboard. Short, actionable, and tells them exactly where to
     * ask when they need something that isn't in the admin.
     */
    private function agencyWelcome(): array
    {
        return [
            'contact_email' => 'colin@sercas.com',
            'quick_links' => [
                ['label' => 'Read the Playbook first', 'url' => '/admin/seo/playbook', 'why' => 'Site taxonomy + conventions + what NOT to touch'],
                ['label' => 'Set per-URL SEO overrides', 'url' => '/admin/seo/pages', 'why' => 'Override title / meta / OG per page key'],
                ['label' => 'Edit Products', 'url' => '/admin/products', 'why' => '4,500+ product pages, each with its own SEO tab'],
                ['label' => 'Edit Blogs', 'url' => '/admin/blogs', 'why' => 'Long-form content + per-post SEO'],
                ['label' => 'Edit Encyclopedia', 'url' => '/admin/encyclopedia-entries', 'why' => 'Compound reference articles'],
                ['label' => 'Edit Compare pages', 'url' => '/admin/categories', 'why' => 'Price-comparison pages per compound'],
                ['label' => 'GSC dashboard', 'url' => '/admin/ceo', 'why' => 'Top queries, top pages, opportunities (read-only)'],
            ],
            'how_to_ask' => [
                'When your role is blocked from a page you need' => 'Email Colin with the page URL + what you need to do. He grants scoped access.',
                'When you need a bulk operation' => 'Email Colin — Claude (the SEO engineer) runs backfills, regenerations, re-indexing requests on demand.',
                'When you spot a bug' => 'Screenshot + URL + what you expected. Colin or Claude will fix within a day.',
                'When you want new fields exposed in admin' => 'Describe what you want to edit; Claude will ship the UI.',
            ],
        ];
    }

    public function pages(): Response
    {
        return Inertia::render('Admin/SeoDashboard', [
            'tab' => 'pages',
            'seo_pages' => SeoPage::orderBy('key')->get()->map(fn ($p) => [
                'id' => $p->id,
                'key' => $p->key,
                'title' => $p->title,
                'description' => $p->description,
                'og_title' => $p->og_title,
                'og_description' => $p->og_description,
                'og_image' => $p->og_image,
                'updated_at' => $p->updated_at?->toIso8601String(),
            ])->values(),
            'suggested_keys' => $this->suggestedKeys(),
        ]);
    }

    /**
     * Upsert a single SeoPage. Mirrors SettingsController::upsertSeoPage
     * but routed through the SEO dashboard so the agency's workflow
     * stays on one tab.
     */
    public function savePage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:seo_pages,id',
            'key' => 'required|string|max:100|regex:/^[a-z0-9_-]+$/|unique:seo_pages,key,' . ($request->input('id') ?? 'NULL') . ',id',
            'title' => 'nullable|string|max:70',
            'description' => 'nullable|string|max:200',
            'og_title' => 'nullable|string|max:120',
            'og_description' => 'nullable|string|max:300',
            'og_image' => 'nullable|string|max:500',
        ]);

        $data = collect($validated)->except('id')->toArray();
        if (!empty($validated['id'])) {
            SeoPage::findOrFail($validated['id'])->update($data);
        } else {
            SeoPage::create($data);
        }
        return back()->with('flash_success', 'SEO page saved.');
    }

    public function deletePage(SeoPage $seoPage): RedirectResponse
    {
        $seoPage->delete();
        return back()->with('flash_success', 'SEO page removed.');
    }

    public function playbook(): Response
    {
        return Inertia::render('Admin/SeoDashboard', [
            'tab' => 'playbook',
            'playbook' => $this->playbookMarkdown(),
        ]);
    }

    /**
     * Keys we expect the agency to want overrides for. Hints the UI
     * so they don't have to memorize our page-naming conventions.
     */
    private function suggestedKeys(): array
    {
        $existing = SeoPage::pluck('key')->all();
        $candidates = [
            'home' => 'Homepage',
            'brands' => '/brands directory',
            'products' => '/products catalog',
            'compare' => '/compare index',
            'encyclopedia' => '/encyclopedia index',
            'blogs' => '/blogs index',
            'guides' => '/guides index',
            'bacteriostatic-water' => '/bacteriostatic-water landing',
            'blends' => '/blends landing',
            'skincare' => '/skincare landing',
            'bulk' => '/bulk landing',
            'testing-labs' => '/testing-labs landing',
            'become-vendor' => '/become-a-vendor',
            'for-vendors-badge' => '/for-vendors/badge',
        ];
        $out = [];
        foreach ($candidates as $k => $label) {
            $out[] = [
                'key' => $k,
                'label' => $label,
                'exists' => in_array($k, $existing, true),
            ];
        }
        return $out;
    }

    /**
     * Live counts of what we're asking Google to index, grouped by URL
     * pattern. The agency uses this to prioritize — fixing the pattern
     * with the biggest page count has the biggest leverage.
     */
    private function estimateSitemapCount(): array
    {
        $brands = Brand::where('is_active', true)->whereNotNull('slug')->count();
        $products = Product::where('hidden', false)
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->whereHas('brand', fn ($q) => $q->whereNotNull('slug'))
            ->where(fn ($q) => $q->where('price', '>', 0)->orWhere('discount_price', '>', 0))
            ->count();
        $compounds = ProductCategory::where('is_active', true)->whereNotNull('slug')->count();
        $coupons = \App\Models\VendorSetting::whereNotNull('coupon_code')
            ->whereHas('brand', fn ($q) => $q->where('is_active', true))
            ->count();

        return [
            'total' => $brands + $products + $compounds + $coupons + 50, // + static pages
            'by_pattern' => [
                ['pattern' => '/product/*', 'count' => $products, 'status' => 'ok'],
                ['pattern' => '/brand/*', 'count' => $brands, 'status' => 'ok'],
                ['pattern' => '/compare/* (compound + VS)', 'count' => $compounds, 'status' => 'ok'],
                ['pattern' => '/encyclopedia/*', 'count' => $compounds, 'status' => 'ok'],
                ['pattern' => '/coupon/*', 'count' => $coupons, 'status' => 'ok'],
            ],
        ];
    }

    /**
     * Short narrative explaining the Oct 3 drop + current recovery
     * trajectory. Hard-coded so the agency sees a consistent story on
     * every refresh — Claude can revise it per real-world events.
     */
    private function recoveryContext(): array
    {
        return [
            'drop_date' => '2026-10-03',
            'drop_cause' => 'Combined effect of Google October core update + our own SEO change wave (canonical fixes, structured-data cleanups, Human-Use-section removal, encyclopedia-slug normalization). All intent-correct fixes shipped in the same 72-hour window; Google saw a wholesale re-signal and recalculated authority downward while re-crawling.',
            'current_action' => 'Dedup fix landed Oct 21: 4,541 unique AI-generated product descriptions + per-product meta descriptions + brand/coupon/VS/homepage SEO fixes. Expected recovery window: 1-3 weeks from Oct 21.',
            'leading_indicator' => 'Position already improving (22.4 current vs 27.5 one week prior). Clicks lag position by ~5-7 days.',
            'next_check' => 'Growth-watch report every Tue + Fri.',
        ];
    }

    private function playbookMarkdown(): string
    {
        return <<<'MD'
# Peptidemap SEO Playbook

> First read on day one. Everything you need to know about our site taxonomy, conventions, and current state.

## Site at a glance

- **Peptidemap** is a research-peptide vendor comparison directory. No fulfillment — we aggregate pricing and send users to the vendor. Monetization = affiliate.
- RUO (research use only) framing is non-negotiable. **Never** imply human use, dosing, or clinical application on any page. We removed the "Human Use & Evidence" section site-wide on Oct 7 for this reason.

## Page taxonomy

| URL pattern | What it is | Count | Who edits |
|---|---|---|---|
| `/` | Homepage | 1 | Admin via `/admin/seo-pages` |
| `/compare/{compound}` | Price comparison per compound (money page) | ~40 | Admin |
| `/compare/{a}-vs-{b}` | Pair comparison pages | ~15 | Code (CompareController) |
| `/encyclopedia/{compound}` | Informational "what is X" articles | ~70 | Admin via `/admin/encyclopedia-entries` |
| `/brand/{slug}` | Vendor storefront | ~58 | Admin via `/admin/vendors` |
| `/product/{vendor}/{slug}/{id}` | Individual product listing | ~4,500 | Mostly auto (sync + AI gen) |
| `/coupon/{brand}` | Vendor coupon landing | ~30 | Code (CouponController) + brand description |
| `/peptides/{category}` | Product listing by compound | ~30 | Admin |

## Meta conventions

- **Title limit**: 60 characters including " — Peptidemap" suffix
- **Description limit**: 155 characters
- **Compound display**: on-page we use the vendor's pseudonym (GLP3-R for Retatrutide) because Southern Aminos prefers it. **SEO meta always uses the real compound name** (Retatrutide). This split lives in `App\Support\CompoundDisplay` + `$category->name` (SEO) vs `CompoundDisplay::label($name)` (visible).

## Content pipeline

1. **Product descriptions** — scraped from vendor site (`vendor_description`) or AI-generated via Claude Haiku (`generated_description`). Fallback chain: vendor → AI → legacy template. **Don't rewrite** these manually; regenerate via `/admin/dev/console` → `products:generate-descriptions` with `--force` if quality is off.
2. **Brand descriptions** — vendor-authored (`description`) wins; AI gen (`generated_description`) is fallback.
3. **Encyclopedia** — hand-authored long-form. Edit via `/admin/encyclopedia-entries`.
4. **Compare page copy** — mostly auto-generated from compound data; price per mg + vendor count signals.

## What NOT to touch

- `vendor_settings.ships_to_locations` — our region filter uses this
- Admin field locks on sync (price/image/stock refresh; name/size/category never overwritten after admin edit)
- `/admin/dev/console` command whitelist — only Colin / Claude adds commands

## Recovery context (Oct 2026)

- **Oct 1-2**: traffic peak (23-26 clicks/day)
- **Oct 3**: cliff drop — Google October core update + our own SEO change wave
- **Oct 3-20**: trough (2-10 clicks/day)
- **Oct 21**: dedup fix shipped — 4,541 unique AI product descriptions, meta fixes, VS-page SEO fix
- **Expected recovery**: 1-3 weeks. Position is already improving (22.4 vs 27.5 one week prior).

## Agency priorities (in order)

1. **Opportunity queries** — anything at position 8-20 is one push from page 1. Find them in GSC Query report, improve the ranking page's content/meta.
2. **Encyclopedia expansion** — pages like `/encyclopedia/tirzepatide` sit at pos 74 with 1,138 impressions. Expand to 2,500+ words with FAQ sections to break into top 20.
3. **Vendor backlinks** — the badge widget at `/badge/{slug}.svg` is unused. Script a vendor-outreach email to adopt it. Each embedded badge = permanent backlink from aged peptide domain.
4. **Content velocity** — ship one new compare page or encyclopedia entry per week. Google rewards publishing cadence.

## Tools you'll use

- `/admin/seo` — this page (overview + playbook)
- `/admin/settings` — site-wide SEO Pages section — overrides for the key pages (home, brands, products, compare, encyclopedia) keyed by URL slug
- `/admin/encyclopedia-entries` — long-form encyclopedia content editor
- `/admin/vendors/{id}/edit` → SEO tab — per-vendor title/description/OG overrides
- `/admin/products/{id}/edit` → SEO tab — per-product overrides (rare — AI generation handles the baseline)
- `/admin/ceo` — GSC dashboard with top queries, pages, opportunities
- `/admin/dev/console` — artisan command runner (for Claude; agency rarely needs this)

## Who to ask

- **Colin** (CEO) — anything strategic, approvals, budget
- **Claude** (SEO automation / engineering) — anything requiring code change, data pull, bulk operation
- **Julia** (operations) — vendor onboarding, content moderation, admin workflow

MD;
    }
}
