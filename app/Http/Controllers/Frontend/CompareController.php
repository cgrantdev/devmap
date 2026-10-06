<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\SeoPage;
use App\Support\CompareSlug;
use App\Support\EncyclopediaSlug;
use App\Support\CompoundDisplay;
use App\Support\RetatrutideCompareNarrative;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CompareController extends Controller
{
    /**
     * The ordered list of featured compound category IDs for the compare page.
     * Maintained manually to match the product team's priority list.
     */
    /**
     * Compounds shown on /compare, in priority order. First 13 are the
     * user-curated 'top' list — the obesity GLP-1s, the popular blends,
     * and the highest-traffic singles. The rest are filled out by remaining
     * categories with healthy product counts so the page displays ~25 compounds
     * total. Categories that don't exist in the DB are silently skipped, so
     * additions here are safe even if the underlying category doesn't exist
     * yet.
     */
    public const FEATURED_COMPOUND_NAMES = [
        // Top priority (in the order the user requested)
        'Retatrutide',
        'Tirzepatide',
        'Semaglutide',
        'Tesamorelin',
        'BPC-157 / TB-500',
        'GHK-Cu',
        'GLOW',
        'KLOW',
        'CJC-1295 / Ipamorelin',
        'MOTS-c',
        'BPC-157',
        'NAD+',
        'Glutathione',
        // Then other popular compounds, ranked by current product count
        'Ipamorelin',
        'AOD-9604',
        'CJC-1295',
        'TB-500',
        'Sermorelin',
        'Cagrilintide',
        '5-AMINO-1MQ',
        'PT-141',
        'IGF-1 LR3',
        'Kisspeptin',
        'SS-31',
        'Tesofensine',
        // Colin Sep 16 — added from GSC "buy {compound}" bleed report:
        //   buy epitalon (48 imp, pos 42.8)
        //   buy kpv 10mg (58 imp, pos 59.4)
        //   buy bremelanotide (30 imp, pos 61.5)
        // Each has 60+ active products so /compare/{slug} lands with
        // real data, not an empty shell.
        'Epitalon',
        'KPV',
        'Bremelanotide',
    ];

    /**
     * Curated head-to-head pairs surfaced on the /compare hub, in every
     * per-compound compare page's "Compare with…" strip, and in the sitemap.
     * Single source of truth so the sitemap and the UI never drift.
     * Slugs must be in alphabetical order (canonical form) and must resolve
     * to active ProductCategory rows.
     */
    public const FEATURED_VS_PAIRS = [
        ['a' => 'semaglutide',   'b' => 'tirzepatide',   'tagline' => 'The two big GLP-1s'],
        ['a' => 'bpc-157',       'b' => 'tb-500',        'tagline' => 'The classic healing stack'],
        ['a' => 'cjc-1295',      'b' => 'ipamorelin',    'tagline' => 'Growth-hormone secretagogue duo'],
        ['a' => 'bpc-157',       'b' => 'ghk-cu',        'tagline' => 'Skin & recovery'],
        ['a' => 'retatrutide',   'b' => 'tirzepatide',   'tagline' => 'Next-gen vs. current-gen GLP-1'],
        ['a' => 'mots-c',        'b' => 'nad',           'tagline' => 'Mitochondrial support'],
        ['a' => 'ipamorelin',    'b' => 'tesamorelin',   'tagline' => 'GHRH vs GHRP'],
        ['a' => 'ipamorelin',    'b' => 'sermorelin',    'tagline' => 'Two ways to boost GH'],
        ['a' => 'aod-9604',      'b' => 'tesamorelin',   'tagline' => 'Fat-loss peptides'],
        ['a' => 'retatrutide',   'b' => 'semaglutide',   'tagline' => 'Newer GLP-1 vs. proven'],
        ['a' => 'ghk-cu',        'b' => 'tb-500',        'tagline' => 'Regeneration alternatives'],
        ['a' => '5-amino-1mq',   'b' => 'mots-c',        'tagline' => 'Metabolic peptides'],
        ['a' => 'bpc-157',       'b' => 'ipamorelin',    'tagline' => 'Recovery vs. growth'],
        ['a' => 'kisspeptin',    'b' => 'pt-141',        'tagline' => 'Libido & fertility'],
        ['a' => 'cagrilintide',  'b' => 'semaglutide',   'tagline' => 'Newer weight-loss combo option'],
    ];

    // Display-name overrides live in App\Support\CompoundDisplay so
    // ProductsController + CompareController stay in sync. Semaglutide,
    // Tirzepatide, and Retatrutide map to GLP1-S / GLP2-T / GLP3-R for
    // vendor-friendly optics; SEO stays intact because we split the
    // display label from the raw compound name used in URLs, meta
    // title, meta description, and schema.org fields.

    public function index(Request $request)
    {
        // Fetch all featured categories in one query
        $categories = ProductCategory::where('is_active', true)
            ->whereIn('name', self::FEATURED_COMPOUND_NAMES)
            ->with(['educationPost' => function ($q) {
                $q->where('status', 'published')
                  ->select('id', 'product_category_id', 'slug', 'description', 'status');
            }])
            ->get()
            ->keyBy('name');

        // For each compound, get all visible products with brand info, sorted by effective price
        $compounds = collect();

        // Site-wide header location filter (?location=Country) applies here too.
        $locationFilter = trim((string) $request->get('location', ''));

        foreach (self::FEATURED_COMPOUND_NAMES as $catName) {
            $category = $categories->get($catName);
            if (!$category) {
                continue; // skip if category doesn't exist in DB
            }

            $products = Product::visible()
                ->where('status', 'active')
                ->where('product_category_id', $category->id)
                // Hide $0 / unpriced rows — they distort the price comparison
                // and usually indicate a stale import or a parent variable
                // product that didn't get its price resolved.
                ->where(function ($q) {
                    $q->where('discount_price', '>', 0)
                      ->orWhere(function ($qq) {
                          $qq->whereNull('discount_price')->where('price', '>', 0);
                      });
                })
                ->when($locationFilter, fn ($q) => $q->whereHas(
                    'brand.vendorSetting.location',
                    fn ($l) => $l->where('name', $locationFilter)
                ))
                ->with('brand.vendorSetting.location')
                ->get()
                ->map(function ($product) {
                    // Cast to float UP FRONT. Eloquent returns decimal columns
                    // as strings, and sortBy() comparing string "100.00" vs
                    // string "70.00" sorts lexically ('1' < '7'), putting the
                    // $100 rows in the middle of the list. Forcing float
                    // makes every comparison numeric.
                    $retail = (float) (
                        $product->discount_price && $product->discount_price < $product->price
                            ? $product->discount_price
                            : $product->price
                    );

                    // PeptideMap-applied price using the brand's discount %.
                    $pct = $product->brand?->vendorSetting?->coupon_discount_percent;
                    $pmapPrice = ($pct !== null && $pct > 0 && $pct < 100 && $retail > 0)
                        ? round($retail * (1 - ((float) $pct / 100)), 2)
                        : null;

                    // The figure we sort on per category — what the visitor
                    // actually pays. Falls back to retail when there's no
                    // PeptideMap discount configured for this vendor.
                    $finalPrice = $pmapPrice ?? $retail;

                    return [
                        'id' => $product->id,
                        'name' => $product->display_name,
                        'product_type' => $product->product_type,
                        'slug' => $product->slug,
                        // Cast prices to float so the Vue template can do
                        // numeric comparisons. Eloquent ships decimal columns
                        // as strings; '69.99' < '100.00' is FALSE in JS
                        // lexical comparison (because '6' > '1'), which broke
                        // the 'show discount + retail strikethrough' branch
                        // for vendors whose discount has more digits left of
                        // the decimal than retail.
                        'price' => $product->price !== null ? (float) $product->price : null,
                        'discount_price' => $product->discount_price !== null ? (float) $product->discount_price : null,
                        'effective_price' => $retail,
                        'final_price' => $finalPrice,
                        'currency_symbol' => \App\Support\Currency::symbolFor($product->brand?->vendorSetting?->location?->name),
                        'go_url' => "/go/{$product->id}",
                        'brand_name' => $product->brand?->name,
                        'brand_slug' => $product->brand?->slug,
                        'brand_location' => $product->brand?->vendorSetting?->location?->name,
                        'brand_location_id' => $product->brand?->vendorSetting?->location_id,
                        'brand_logo' => $product->brand?->vendorSetting?->logo
                            ? asset('storage/' . $product->brand->vendorSetting->logo)
                            : null,
                        'brand_coupon_code' => $product->brand?->vendorSetting?->coupon_code,
                        'brand_discount_percent' => $pct !== null ? (float) $pct : null,
                        'size_mg' => $product->size_mg,
                    ];
                })
                // Sort cheapest-first within this compound based on what the
                // visitor will actually pay (PMAP price when applicable,
                // retail otherwise). Numeric sort enforced even though
                // every final_price is now a float, in case a future change
                // reintroduces mixed types.
                ->sortBy(fn ($p) => (float) $p['final_price'])
                ->values();

            $displayName = CompoundDisplay::label($category->name);
            $educationPost = $category->educationPost;

            // Freshness signal — max(last_scraped_at | updated_at) across
            // this category's products. Google-friendly and honest: gives
            // us a "Prices updated 2 hours ago" line that competitors
            // ("18 hours ago" is what The Peptide Catalog uses) currently
            // beat us on.
            $freshRow = Product::visible()
                ->where('status', 'active')
                ->where('product_category_id', $category->id)
                ->selectRaw('MAX(last_scraped_at) as latest_scraped, MAX(updated_at) as latest_updated')
                ->first();
            $latestRaw = collect([$freshRow?->latest_scraped, $freshRow?->latest_updated])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->sort()
                ->last();
            $latestTs = $latestRaw ? \Carbon\Carbon::parse($latestRaw) : null;

            $compounds->push([
                'id' => $category->id,
                'name' => $displayName,
                // Hub cards link to /compare/{slug}. Use the route-safe slug
                // so "BPC-157" and "Vitamin B12" don't point at 404s.
                'slug' => CompareSlug::canonical($category->slug) ?? $category->slug,
                'anchor' => Str::slug($displayName),
                'description' => $educationPost?->description
                    ? Str::limit(strip_tags($educationPost->description), 200)
                    : null,
                'encyclopedia_url' => ($educationPost && $educationPost->status === 'published')
                    ? EncyclopediaSlug::path($category->slug)
                    : null,
                'product_count' => $products->count(),
                // Use final_price (post-coupon) so the 'from $X' badge
                // shown on the compound header matches what visitors actually
                // pay — same metric as the row sort below.
                'cheapest_price' => $products->first()['final_price'] ?? null,
                // Symbol of the cheapest row so "from £39" reads honestly
                // when the leading vendor is UK-based.
                'cheapest_currency_symbol' => $products->first()['currency_symbol'] ?? '$',
                'vendor_count' => $products->pluck('brand_name')->unique()->count(),
                'prices_updated_iso' => $latestTs?->toIso8601String(),
                'prices_updated_human' => $latestTs?->diffForHumans(),
                'products' => $products->values(),
            ]);
        }

        // Generate SEO data (editable via Admin -> Settings -> SEO Pages, key: "compare")
        $defaultTitle = 'Compare Peptide Vendors Side-by-Side — Peptidemap';
        $defaultDescription = 'Every vendor, every price, sorted cheapest-first. Compare peptide suppliers on GLP-1s, BPC-157, GHK-Cu, TB-500 and 100+ more compounds.';

        $seoPage = SeoPage::where('key', 'compare')->first();
        $seo = [
            'key' => 'compare',
            'title' => $seoPage?->title ?: $defaultTitle,
            'description' => $seoPage?->description ?: $defaultDescription,
            'og_title' => $seoPage?->og_title ?: ($seoPage?->title ?: $defaultTitle),
            'og_description' => $seoPage?->og_description ?: ($seoPage?->description ?: $defaultDescription),
            'og_image' => $seoPage?->og_image ?: null,
            'image' => $seoPage?->og_image ?: null,
            'url' => url('/compare'),
        ];

        // Store SEO data in session for Blade template access (server-rendered OG/Twitter tags)
        session(['page_seo_data' => $seo]);

        // Location filter options: only locations that actually have at
         // least one vendor with a product surfaced on this page. Sorted by
         // count desc so US-heavy inventory naturally leads the dropdown.
        $vendorLocationIds = collect($compounds)->flatMap(fn ($c) => collect($c['products'])
            ->pluck('brand_location_id')
            ->filter()
            ->unique()
        )->countBy();
        $locations = \App\Models\Location::whereIn('id', $vendorLocationIds->keys())
            ->get(['id', 'name'])
            ->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'count' => (int) $vendorLocationIds[$l->id],
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        return Inertia::render('Frontend/Compare', [
            'compounds' => $compounds,
            'featuredPairs' => $this->resolveFeaturedPairs(),
            'locations' => $locations,
            'seo' => $seo,
        ]);
    }

    /**
     * Turn FEATURED_VS_PAIRS into a Vue-consumable list — filters out any
     * pair where either side isn't a real active category (so if we retire
     * a compound, the sitemap and UI go quiet without a code change).
     */
    private function resolveFeaturedPairs(): array
    {
        $slugs = collect(self::FEATURED_VS_PAIRS)->flatMap(fn ($p) => [$p['a'], $p['b']])->unique()->all();
        // MySQL matches slug case-insensitively so the whereIn works, but
        // PHP's array lookups are case-sensitive — key by lowercased slug
        // so pairs referencing DB-uppercased slugs (CJC-1295 etc.) don't
        // silently drop through the isset() gate below.
        $categories = ProductCategory::where('is_active', true)
            ->whereIn('slug', $slugs)
            ->get(['id', 'slug', 'name'])
            ->keyBy(fn ($c) => strtolower($c->slug));
        $displayName = fn (string $slug) => isset($categories[$slug])
            ? (CompoundDisplay::label($categories[$slug]->name))
            : null;

        return collect(self::FEATURED_VS_PAIRS)
            ->filter(fn ($p) => isset($categories[$p['a']], $categories[$p['b']]))
            ->map(fn ($p) => [
                'url' => "/compare/{$p['a']}-vs-{$p['b']}",
                'a_slug' => $p['a'], 'a_name' => $displayName($p['a']),
                'b_slug' => $p['b'], 'b_name' => $displayName($p['b']),
                'tagline' => $p['tagline'],
            ])
            ->values()
            ->all();
    }

    /**
     * Per-compound compare page: /compare/{slug}
     *
     * Focused destination for "cheapest {compound}" / "{compound} vendor
     * comparison" queries. Shows every visible priced vendor for one
     * compound, cheapest first, with the compound's encyclopedia summary
     * and cross-links to related compare pages.
     *
     * Emits ItemList + Offer schema so Google can treat this as a
     * commercial-intent page (matches strategist rec #10 for encyclopedia).
     *
     * Non-canonical slugs (mixed case, spaces, slashes) 301 to the
     * [a-z0-9-]+ form so old sitemap locs resolve instead of 404ing.
     */
    public function show(Request $request, string $slug)
    {
        $canonical = CompareSlug::canonical($slug);

        // X-vs-Y compare pages piggyback on the same route via the -vs-
        // separator. Since no legit compound slug contains '-vs-', a single
        // strpos check disambiguates cleanly.
        if ($canonical && str_contains($canonical, '-vs-')) {
            // Case-fold and alphabetical order in one hop. Otherwise
            // /compare/Tirzepatide-vs-Retatrutide 301s to the lowercase
            // reverse pair, and showVs 301s again to the alpha URL.
            $final = $this->canonicalVsSlug($canonical);
            if (!$final) {
                abort(404);
            }
            if ($final !== $slug) {
                return redirect()->route('compare.compound', ['slug' => $final], 301);
            }

            return $this->showVs($final);
        }

        if (!$canonical) {
            abort(404);
        }

        $category = ProductCategory::findForCompareSlug($canonical);
        if (!$category) {
            abort(404);
        }

        if ($canonical !== $slug) {
            return redirect()->route('compare.compound', ['slug' => $canonical], 301);
        }

        $category->load(['educationPost' => function ($q) {
            $q->where('status', 'published')
              ->select('id', 'product_category_id', 'slug', 'overview', 'description');
        }]);

        $products = $this->productsForCategory($category);
        // Filtered URLs canonical to the clean compare page. Title, H1 source
        // counts, description, and FAQ schema stay on the unfiltered catalog
        // so ?location= / ?verified= cannot retitle the document to
        // "0 Vendors Compared". The table below still uses $products.
        $documentProducts = $this->compareListingIsFiltered()
            ? $this->productsForCategory($category, false)
            : $products;

        // Related compounds — up to 8 other featured compounds by product count.
        // Gives the reader natural next-clicks; also builds the internal-link
        // graph the strategist recommended (rec #16).
        $related = ProductCategory::where('is_active', true)
            ->where('id', '!=', $category->id)
            ->whereIn('name', self::FEATURED_COMPOUND_NAMES)
            ->withCount(['products as active_products' => function ($q) {
                $q->visible()->where('status', 'active')
                  ->where(function ($qq) {
                      $qq->where('discount_price', '>', 0)
                         ->orWhere(function ($qqq) { $qqq->whereNull('discount_price')->where('price', '>', 0); });
                  });
            }])
            ->orderByDesc('active_products')
            ->limit(8)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => CompoundDisplay::label($c->name),
                'slug' => CompareSlug::canonical($c->slug) ?? $c->slug,
                'url' => '/compare/' . (CompareSlug::canonical($c->slug) ?? $c->slug),
                'product_count' => $c->active_products,
            ])
            ->values();

        $displayName = CompoundDisplay::label($category->name);
        // Compare pages use the raw category name for the H1, visible
        // FAQs, and FAQPage schema. CompoundDisplay labels stay
        // secondary: the alias chip, plus a fifth FAQ when the label
        // differs. That map mixes GLP codes with descriptive expansions
        // (Blend, GLOW, KLOW), so the extra FAQ stays neutrally worded.
        // Product and storefront pages still lead with the display label.
        $seoName = $category->name;
        $alias = $displayName !== $seoName ? $displayName : null;
        $educationPost = $category->educationPost;
        $research = RetatrutideCompareNarrative::forSlug($canonical);
        // Compare copy for retatrutide lives in code so the encyclopedia
        // overview is never overwritten by the TRIUMPH refresh.
        $summary = is_array($research) ? ($research['lead'] ?? null) : null;
        if (!$summary) {
            $summary = $educationPost?->overview ?: $educationPost?->description;
            $summary = $summary ? strip_tags($summary) : null;
        }

        $vendorCount = $documentProducts->pluck('brand_name')->unique()->count();
        $productCount = $documentProducts->count();
        $cheapest = $documentProducts->first()['final_price'] ?? null;
        $priciest = $documentProducts->last()['final_price'] ?? null;

        // SEO — title leads with buying intent, description names the numbers.
        $cheapestFmt = $cheapest ? '$' . number_format($cheapest, 2) : null;
        $priciestFmt = $priciest ? '$' . number_format($priciest, 2) : null;
        $seoTitle = "Cheapest {$seoName} — {$vendorCount} Vendors Compared";
        $seoDescription = $vendorCount > 0
            ? "Compare {$productCount} {$seoName} product" . ($productCount === 1 ? '' : 's')
              . " across {$vendorCount} verified vendor" . ($vendorCount === 1 ? '' : 's')
              . ($cheapestFmt ? ". Prices from {$cheapestFmt}" . ($priciestFmt && $priciestFmt !== $cheapestFmt ? " to {$priciestFmt}" : '') : '')
              . ". Coupon codes and lab-testing status on every listing."
            : "Vendor comparison for {$seoName} — currently no in-stock listings on Peptidemap.";

        // One source for visible FAQs and FAQPage schema. Always $seoName
        // — never the CompoundDisplay label — so on-page copy matches the
        // rich result. The alias question is added only when the label
        // differs, and it does not call that label a vendor synonym.
        $faqPairs = ($vendorCount > 0 && $cheapest) ? [
            [
                'q' => "What is the cheapest {$seoName}?",
                'a' => "The lowest {$seoName} price on Peptidemap is {$cheapestFmt} from " . ($documentProducts->first()['brand_name'] ?? 'a verified vendor') . ". Peptidemap tracks {$productCount} {$seoName} listings across {$vendorCount} vendors and updates prices daily.",
            ],
            [
                'q' => "How many vendors sell {$seoName}?",
                'a' => "{$vendorCount} verified research-peptide vendors currently stock {$seoName} on Peptidemap, with {$productCount} distinct product listings.",
            ],
            [
                'q' => "Is there a coupon code for {$seoName}?",
                'a' => "Most vendors on Peptidemap offer a Peptidemap coupon code (usually 10–35% off). The exact code and discount for each vendor is listed in the pricing table on this page.",
            ],
            [
                'q' => "How does Peptidemap compare {$seoName} prices?",
                'a' => "Peptidemap ingests each vendor's live catalog daily, applies their current Peptidemap coupon discount, and sorts by the price you actually pay after code. All listings are for research use only (RUO).",
            ],
        ] : [];
        if ($alias && $faqPairs) {
            $faqPairs[] = [
                'q' => "Is {$alias} the same as {$seoName}?",
                'a' => "{$alias} is an alternate label PeptideMap uses for {$seoName}. PeptideMap lists {$seoName} as the primary name; {$alias} appears as an alias for search and catalog matching. Listings are research use only (RUO).",
            ];
        }

        // ItemList + Offer schema — turns this from an informational page into
        // a commercial-intent page for search engines.
        $itemList = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => "{$seoName} vendor comparison",
            'numberOfItems' => $productCount,
            'itemListElement' => $documentProducts->take(20)->values()->map(fn ($p, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@type' => 'Product',
                    'name' => $p['name'] ?? $seoName,
                    'brand' => ['@type' => 'Brand', 'name' => $p['brand_name']],
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => number_format($p['final_price'] ?? 0, 2, '.', ''),
                        'priceCurrency' => 'USD',
                        'availability' => 'https://schema.org/InStock',
                        'url' => url("/product/{$p['brand_slug']}/{$p['slug']}/{$p['id']}"),
                        'seller' => ['@type' => 'Organization', 'name' => $p['brand_name']],
                    ],
                ],
            ])->all(),
        ];

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',    'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Compare', 'item' => url('/compare')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $seoName, 'item' => url("/compare/{$slug}")],
            ],
        ];

        $seo = [
            'key' => 'compare-compound',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoTitle,
            'og_description' => $seoDescription,
            'og_image' => route('og.compound', ['slug' => $slug]) . '?v=' . ($category->updated_at?->timestamp ?? 0),
            'image' => route('og.compound', ['slug' => $slug]) . '?v=' . ($category->updated_at?->timestamp ?? 0),
            'url' => url("/compare/{$slug}"),
            // SSR H1 matches the visible Vue H1: primary compound name only.
            // Commercial "Cheapest …" stays in title and og_title. The GLP
            // pseudonym stays on the alias chip — it must not appear in H1.
            'h1' => $seoName,
            'schema' => array_values(array_filter([
                $itemList,
                $breadcrumb,
                // Product + AggregateOffer — surfaces price range ($X-$Y)
                // in the Google SERP snippet for high-commercial-intent
                // queries like "cheap {compound}" / "buy {compound}".
                // GSC data (Sep 2026) showed "cheap retatrutide" at 27% CTR
                // — huge signal that price-forward snippets convert here.
                $vendorCount > 0 && $cheapest ? [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    '@id' => url("/compare/{$slug}") . '#product',
                    'name' => $seoName,
                    'description' => $summary ? mb_substr(strip_tags($summary), 0, 300) : "Compare {$seoName} prices across {$vendorCount} verified research-peptide vendors.",
                    'offers' => [
                        '@type' => 'AggregateOffer',
                        'priceCurrency' => 'USD',
                        'lowPrice' => number_format((float) $cheapest, 2, '.', ''),
                        'highPrice' => number_format((float) ($priciest ?: $cheapest), 2, '.', ''),
                        'offerCount' => $productCount,
                        'availability' => 'https://schema.org/InStock',
                    ],
                ] : null,
                // FAQPage — same question/answer strings as the visible
                // FAQs ($faqPairs). $seoName on both sides so the rich
                // result matches on-page copy.
                $faqPairs ? [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    '@id' => url("/compare/{$slug}") . '#faq',
                    'mainEntity' => array_map(fn (array $faq) => [
                        '@type' => 'Question',
                        'name' => $faq['q'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['a'],
                        ],
                    ], $faqPairs),
                ] : null,
            ])),
        ];
        session(['page_seo_data' => $seo]);

        return Inertia::render('Frontend/CompareCompound', [
            // Echo the active filter state back to the view so the chip
            // row can render with the correct selection state.
            'trustFilters' => [
                'verified' => collect(explode(',', (string) request()->get('verified', '')))
                    ->map(fn ($t) => trim($t))
                    ->filter(fn ($t) => in_array($t, ['cgmp', 'testing_7x'], true))
                    ->unique()->values()->all(),
                'usp' => trim((string) request()->get('usp', '')) ?: null,
            ],
            'compound' => [
                'id' => $category->id,
                // Colin Sep 16 — compare pages lead with the SEO name in
                // the H1 to match "cheap {compound}" / "buy {compound}"
                // search intent. The display pseudonym (e.g. GLP3-R for
                // Retatrutide) rides as an `alias` subtitle chip so
                // vendor-friendly optics stay intact without sacrificing
                // rank. Product page + storefront still lead with the
                // pseudonym as they did.
                'name' => $seoName,
                'alias' => $alias,
                'raw_name' => $category->name,
                'slug' => $category->slug,
                'summary' => $summary,
                'research' => $research,
                'encyclopedia_url' => $educationPost ? EncyclopediaSlug::path($category->slug) : null,
                'product_count' => $productCount,
                'vendor_count' => $vendorCount,
                'cheapest_price' => $cheapest,
                'priciest_price' => $priciest,
                'products' => $products,
                'faqs' => $faqPairs,
            ],
            'related' => $related,
            'vsPairs' => collect($this->resolveFeaturedPairs())
                ->filter(function ($p) use ($category) {
                    // Case-insensitive: category->slug may be DB-uppercased
                    // ("CJC-1295") while pair slugs come from the lowercased
                    // FEATURED_VS_PAIRS constant.
                    $cs = strtolower($category->slug);
                    return strtolower($p['a_slug']) === $cs || strtolower($p['b_slug']) === $cs;
                })
                ->take(6)
                ->values()
                ->all(),
            'seo' => $seo,
        ]);
    }

    /**
     * Lowercase hyphenated pair in alphabetical order: a-vs-b with a < b.
     * Encyclopedia aliases (ss-31 / ss31 → elamipretide) are rewritten on
     * each side before ordering, so both directions 301 once to the live
     * pair. Null when the slug is not a real pair (missing side, or a vs a).
     */
    private function canonicalVsSlug(string $slug): ?string
    {
        $pos = strpos($slug, '-vs-');
        if ($pos === false) {
            return null;
        }
        $a = $this->canonicalCompareSide(substr($slug, 0, $pos));
        $b = $this->canonicalCompareSide(substr($slug, $pos + 4));
        if ($a === '' || $b === '' || $a === $b) {
            return null;
        }
        if (strcmp($a, $b) > 0) {
            return $b . '-vs-' . $a;
        }
        return $a . '-vs-' . $b;
    }

    /**
     * SS-31 is the research alias for the live elamipretide slug. Other
     * encyclopedia families stay put: their aliases are space or case
     * forms that CompareSlug::canonical already folds.
     */
    private function canonicalCompareSide(string $slug): string
    {
        $public = EncyclopediaSlug::publicSlug($slug);

        return $public === 'elamipretide' ? $public : $slug;
    }

    /**
     * X-vs-Y compare page: /compare/{a}-vs-{b}
     *
     * Head-to-head compound comparison. Two-column deep-dive using
     * structured EducationPost fields (mechanism, half-life, key effects,
     * common use cases) plus a side-by-side vendor + price snapshot.
     *
     * Canonical URL is alphabetical (a<b); reverse-order requests 301
     * to the canonical to avoid duplicate-content splits.
     */
    private function showVs(string $slug)
    {
        $final = $this->canonicalVsSlug($slug);
        if (!$final) abort(404);
        if ($final !== $slug) {
            return redirect()->route('compare.compound', ['slug' => $final], 301);
        }

        $pos = strpos($final, '-vs-');
        $aSlug = substr($final, 0, $pos);
        $bSlug = substr($final, $pos + 4);

        $a = ProductCategory::findForCompareSlug($aSlug);
        $b = ProductCategory::findForCompareSlug($bSlug);
        if (!$a || !$b) abort(404);
        $a->load(['educationPost' => fn ($q) => $q->where('status', 'published')]);
        $b->load(['educationPost' => fn ($q) => $q->where('status', 'published')]);

        $aData = $this->buildVsCompoundData($a);
        $bData = $this->buildVsCompoundData($b);

        // "Related pairs" — pick 6 other same-list pairs so users can hop
        // to adjacent comparisons. Rotated by day-of-year so the set feels
        // fresh across visits without needing per-user personalization.
        $related = $this->relatedVsPairs($a, $b);

        $displayA = $aData['name'];
        $displayB = $bData['name'];
        $cheapestA = $aData['cheapest_price'] ? '$' . number_format($aData['cheapest_price'], 2) : null;
        $cheapestB = $bData['cheapest_price'] ? '$' . number_format($bData['cheapest_price'], 2) : null;

        $seoTitle = "{$displayA} vs {$displayB} — Vendor & Price Comparison";
        $seoDescription = "{$displayA} vs {$displayB} side-by-side: mechanism, half-life, use cases, best vendors and current prices."
            . ($cheapestA && $cheapestB ? " Cheapest {$displayA} {$cheapestA}, cheapest {$displayB} {$cheapestB}." : '');

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',    'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Compare', 'item' => url('/compare')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => "{$displayA} vs {$displayB}", 'item' => url("/compare/{$aSlug}-vs-{$bSlug}")],
            ],
        ];

        $seo = [
            'key' => 'compare-vs',
            'title' => $seoTitle,
            'description' => $seoDescription,
            'og_title' => $seoTitle,
            'og_description' => $seoDescription,
            'og_image' => null,
            'image' => null,
            'url' => url("/compare/{$aSlug}-vs-{$bSlug}"),
            'h1' => "{$displayA} vs {$displayB}",
            'schema' => [$breadcrumb],
        ];
        session(['page_seo_data' => $seo]);

        $evidenceUrl = null;
        if ($aSlug === 'bpc-157' && $bSlug === 'tb-500') {
            $evidenceUrl = '/blog/bpc-157-vs-tb-500-evidence';
        }

        return Inertia::render('Frontend/CompareCompoundVs', [
            'a' => $aData,
            'b' => $bData,
            'related' => $related,
            'evidenceUrl' => $evidenceUrl,
            'seo' => $seo,
        ]);
    }

    /**
     * Build the compact per-compound payload used by the vs page.
     * Fewer products than the single-compound page (top 5 only) since the
     * point here is the head-to-head, not the full vendor drilldown —
     * a "See all N vendors" link routes to /compare/{slug} for that.
     */
    private function buildVsCompoundData(ProductCategory $category): array
    {
        $products = $this->productsForCategory($category);
        $ep = $category->educationPost;
        $displayName = CompoundDisplay::label($category->name);
        $vsNarrative = RetatrutideCompareNarrative::forSlug($category->slug);

        // These fields might be plain text, JSON array of subsections, or null.
        // Normalize to arrays of {heading, text} entries so the Vue can render
        // consistently without special-casing every field.
        $normalize = function ($value) {
            if (!$value) return [];
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) $value = $decoded;
                else return [['heading' => null, 'text' => strip_tags($value)]];
            }
            if (!is_array($value)) return [];
            $out = [];
            foreach ($value as $item) {
                if (is_string($item)) {
                    $out[] = ['heading' => null, 'text' => strip_tags($item)];
                } elseif (is_array($item)) {
                    $out[] = [
                        'heading' => $item['heading'] ?? $item['title'] ?? null,
                        'text' => isset($item['content']) ? strip_tags($item['content']) : (isset($item['text']) ? strip_tags($item['text']) : null),
                    ];
                }
            }
            return array_values(array_filter($out, fn ($x) => !empty($x['text']) || !empty($x['heading'])));
        };

        return [
            'id' => $category->id,
            'name' => $displayName,
            'slug' => $category->slug,
            'compare_url' => '/compare/' . (CompareSlug::canonical($category->slug) ?? $category->slug),
            'encyclopedia_url' => $ep ? EncyclopediaSlug::path($category->slug) : null,
            'vendor_count' => $products->pluck('brand_name')->unique()->count(),
            'product_count' => $products->count(),
            'cheapest_price' => $products->first()['final_price'] ?? null,
            'top_vendors' => $products->take(5)->values(),
            'summary' => (is_array($vsNarrative) ? ($vsNarrative['lead'] ?? null) : null)
                ?: ($ep?->overview ? strip_tags($ep->overview) : ($ep?->description ? strip_tags($ep->description) : null)),
            'mechanism' => $ep?->mechanism_of_action_intro ? strip_tags($ep->mechanism_of_action_intro) : null,
            'half_life' => $ep?->half_life,
            'administration' => $ep?->administration,
            'key_effects' => $normalize($ep?->key_effects ?? null),
            'use_cases' => $normalize($ep?->common_use_cases ?? null),
        ];
    }

    /**
     * A handful of curated compare-pair suggestions so every vs-page has
     * next-clicks. Ordered by relevance to the current pair (same class
     * of compound first, then popular pairs).
     */
    private function relatedVsPairs(ProductCategory $a, ProductCategory $b): array
    {
        // Curated set — pairs known to be commonly compared. Filter out any
        // that reference either of the current two compounds so we don't
        // suggest a duplicate.
        $curated = [
            ['semaglutide', 'tirzepatide', 'The two big GLP-1s'],
            ['bpc-157', 'tb-500', 'The classic healing stack'],
            ['ipamorelin', 'cjc-1295', 'Growth-hormone secretagogue duo'],
            ['ghk-cu', 'bpc-157', 'Skin & recovery comparison'],
            ['retatrutide', 'tirzepatide', 'Next-gen vs. current-gen GLP-1'],
            ['mots-c', 'nad', 'Mitochondrial support alternatives'],
            ['tesamorelin', 'ipamorelin', 'GHRH vs GHRP'],
            ['sermorelin', 'ipamorelin', 'Two ways to boost GH'],
        ];
        $excluded = [$a->slug, $b->slug];
        $out = [];
        foreach ($curated as [$s1, $s2, $tagline]) {
            if (in_array($s1, $excluded, true) || in_array($s2, $excluded, true)) continue;
            $out[] = [
                'url' => "/compare/{$s1}-vs-{$s2}",
                'title' => strtoupper($s1) . ' vs ' . strtoupper($s2),  // display names filled by Vue
                'raw_a' => $s1,
                'raw_b' => $s2,
                'tagline' => $tagline,
            ];
            if (count($out) >= 6) break;
        }
        return $out;
    }

    /**
     * True when the compare URL is a filtered view (location, verified
     * badges, or USP). Empty query values do not count.
     */
    private function compareListingIsFiltered(): bool
    {
        if (trim((string) request()->get('location', '')) !== '') {
            return true;
        }
        if (trim((string) request()->get('usp', '')) !== '') {
            return true;
        }

        return collect(explode(',', (string) request()->get('verified', '')))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => in_array($t, ['cgmp', 'testing_7x'], true))
            ->isNotEmpty();
    }

    /**
     * Shared product-mapping used by /compare (index) and /compare/{slug} (show).
     * Returns the vendor rows for one category, cheapest final_price first,
     * priced only ($0 excluded), with brand + coupon info attached.
     *
     * Pass $applyRequestFilters false when building the canonical document
     * (title, description, FAQ schema) for a filtered URL.
     */
    private function productsForCategory(ProductCategory $category, bool $applyRequestFilters = true)
    {
        $locationFilter = '';
        $verifiedTypes = collect();
        $uspFilter = '';

        if ($applyRequestFilters) {
            // Site-wide header location filter (?location=Country). When set,
            // scope to vendors based in that country so the compare table only
            // shows relevant rows.
            $locationFilter = trim((string) request()->get('location', ''));

            // Trust filters — Colin PMAP tab 1 asked for these on compare
            // pages too. Same URL contract as /vendors: ?verified=cgmp,testing_7x
            // (AND across selected badges) and ?usp=us_manufactured. Filter
            // narrows to products whose BRAND holds the required trust
            // signals — verified via approved VendorCertificationClaim rows
            // and self-declared usps on vendor_settings.
            $verifiedTypes = collect(explode(',', (string) request()->get('verified', '')))
                ->map(fn ($t) => trim($t))
                ->filter(fn ($t) => in_array($t, ['cgmp', 'testing_7x'], true))
                ->unique()
                ->values();
            $uspFilter = trim((string) request()->get('usp', ''));
        }

        return Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $category->id)
            ->where(function ($q) {
                $q->where('discount_price', '>', 0)
                  ->orWhere(function ($qq) {
                      $qq->whereNull('discount_price')->where('price', '>', 0);
                  });
            })
            ->when($locationFilter, fn ($q) => $q->whereHas(
                'brand.vendorSetting.location',
                fn ($l) => $l->where('name', $locationFilter)
            ))
            ->when($verifiedTypes->isNotEmpty(), function ($q) use ($verifiedTypes) {
                foreach ($verifiedTypes as $type) {
                    $q->whereIn('brand_id', function ($sub) use ($type) {
                        $sub->select('brand_id')
                            ->from('vendor_certification_claims')
                            ->where('type', $type)
                            ->where('status', 'approved');
                    });
                }
            })
            ->when($uspFilter, fn ($q) => $q->whereHas(
                'brand.vendorSetting',
                fn ($vs) => $vs->whereJsonContains('usps', $uspFilter)
            ))
            ->with('brand.vendorSetting.location')
            ->get()
            ->map(function ($product) {
                $retail = (float) (
                    $product->discount_price && $product->discount_price < $product->price
                        ? $product->discount_price
                        : $product->price
                );
                $pct = $product->brand?->vendorSetting?->coupon_discount_percent;
                $pmapPrice = ($pct !== null && $pct > 0 && $pct < 100 && $retail > 0)
                    ? round($retail * (1 - ((float) $pct / 100)), 2)
                    : null;
                $finalPrice = $pmapPrice ?? $retail;

                $countryName = $product->brand?->vendorSetting?->location?->name;
                [$currencyCode, $currencySymbol] = \App\Support\Currency::forCountry($countryName);

                return [
                    'id' => $product->id,
                    'name' => $product->display_name,
                    'product_type' => $product->product_type,
                    'slug' => $product->slug,
                    'price' => $product->price !== null ? (float) $product->price : null,
                    'discount_price' => $product->discount_price !== null ? (float) $product->discount_price : null,
                    'effective_price' => $retail,
                    'final_price' => $finalPrice,
                    'pmap_price' => $pmapPrice,
                    'currency_code' => $currencyCode,
                    'currency_symbol' => $currencySymbol,
                    'image_url' => $product->image_url,
                    'product_url' => $product->product_url,
                    'go_url' => "/go/{$product->id}",
                    'brand_name' => $product->brand?->name,
                    'brand_slug' => $product->brand?->slug,
                    'brand_location' => $countryName,
                    'brand_logo' => $product->brand?->vendorSetting?->logo
                        ? asset('storage/' . $product->brand->vendorSetting->logo)
                        : null,
                    'brand_coupon_code' => $product->brand?->vendorSetting?->coupon_code,
                    'brand_discount_percent' => $pct !== null ? (float) $pct : null,
                    'size_mg' => $product->size_mg,
                ];
            })
            ->sortBy(fn ($p) => (float) $p['final_price'])
            ->values();
    }
}
