<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\EducationalGuide;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\CompareSlug;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Generates /sitemap.xml. Cached for 6 hours since the underlying data
 * (products, brands, categories, blogs) changes infrequently. Skips
 * transactional/private routes (admin, vendor, login, become-a-vendor).
 * URLs are always built against peptidemap.com — never the current host —
 * so a request to the dev/join subdomain still lists canonical URLs.
 */
class SitemapController extends Controller
{
    private const CACHE_KEY = 'sitemap.xml.v2';
    private const CACHE_TTL = 21600; // 6h
    private const BASE_URL  = 'https://peptidemap.com';

    public function index(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->build());

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function build(): string
    {
        $urls = [];

        // Static top-level pages.
        $today = now()->toDateString();
        foreach ([
            ['/',              'daily',   '1.0'],
            ['/brands',        'daily',   '0.9'],
            ['/products',      'daily',   '0.9'],
            ['/compare',       'weekly',  '0.8'],
            ['/bacteriostatic-water', 'daily', '0.9'],  // High-intent commercial landing
            ['/blends',        'daily',   '0.85'],
            ['/skincare',      'daily',   '0.85'],
            ['/bulk',          'daily',   '0.85'],
            ['/testing-labs',  'weekly',  '0.75'],
            ['/vs/thepeptidecatalog', 'monthly', '0.6'],
            ['/vs/peptidecompare',    'monthly', '0.6'],
            ['/vs/peptidepricing',    'monthly', '0.6'],
            ['/vs/peptideprice',      'monthly', '0.6'],
            ['/for-vendors/badge',    'monthly', '0.5'],
            ['/vendors/integration',  'monthly', '0.5'],
            ['/encyclopedia',  'weekly',  '0.8'],
            ['/blogs',         'weekly',  '0.6'],
            ['/guides',        'weekly',  '0.6'],
        ] as [$path, $freq, $priority]) {
            $urls[] = ['loc' => self::BASE_URL . $path, 'lastmod' => $today, 'changefreq' => $freq, 'priority' => $priority];
        }

        // Brand storefronts + coupon landing pages. Coupon page only
        // emitted when the brand actually has a coupon code — the
        // controller 404s otherwise, and a sitemap URL to a 404
        // wastes Google's crawl budget.
        Brand::where('is_active', true)
            ->whereNotNull('slug')
            ->with('vendorSetting:brand_id,coupon_code')
            ->select('id', 'slug', 'updated_at')
            ->chunkById(500, function ($chunk) use (&$urls) {
                foreach ($chunk as $b) {
                    $urls[] = [
                        'loc'        => self::BASE_URL . '/brand/' . $b->slug,
                        'lastmod'    => $b->updated_at?->toDateString(),
                        'changefreq' => 'weekly',
                        'priority'   => '0.7',
                    ];
                    if ($b->vendorSetting?->coupon_code) {
                        $urls[] = [
                            'loc'        => self::BASE_URL . '/coupon/' . $b->slug,
                            'lastmod'    => $b->updated_at?->toDateString(),
                            'changefreq' => 'weekly',
                            'priority'   => '0.6',
                        ];
                    }
                }
            });

        // Product detail pages. Canonical route is
        // /product/{vendorSlug}/{productSlug}/{id} (product.detail).
        // /product/{id}/{slug} 301s there and is not listed.
        // Skip products with no real price (out-of-stock and $0-price rows).
        // These render as dead/thin pages in Google's index and dilute crawl
        // budget — the effective price fallback matches how the storefront
        // decides whether to show a product.
        Product::where('hidden', false)
            ->where('status', 'active')
            ->whereNotNull('slug')
            ->where(function ($q) {
                $q->where('price', '>', 0)
                  ->orWhere('discount_price', '>', 0);
            })
            ->whereHas('brand', fn ($q) => $q->whereNotNull('slug'))
            ->with(['brand:id,slug'])
            ->select('id', 'slug', 'brand_id', 'updated_at')
            ->chunkById(1000, function ($chunk) use (&$urls) {
                foreach ($chunk as $p) {
                    $urls[] = [
                        'loc'        => self::BASE_URL . '/product/' . $p->brand->slug . '/' . $p->slug . '/' . $p->id,
                        'lastmod'    => $p->updated_at?->toDateString(),
                        'changefreq' => 'weekly',
                        'priority'   => '0.6',
                    ];
                }
            });

        // Encyclopedia = active ProductCategory rows served at /encyclopedia/{slug}.
        // Same categories are also exposed as /compare/{slug} price-comparison
        // pages. Compare locs must be the route-safe slug ([a-z0-9-]+): raw
        // values like "BPC-157" and "Vitamin B12" 404. Emit each compare URL
        // once, and only when that slug actually resolves.
        $emittedCompareSlugs = [];
        ProductCategory::where('is_active', true)
            ->whereNotNull('slug')
            ->select('id', 'slug', 'updated_at')
            ->chunkById(500, function ($chunk) use (&$urls, &$emittedCompareSlugs) {
                foreach ($chunk as $c) {
                    $lastmod = $c->updated_at?->toDateString();
                    $urls[] = [
                        'loc'        => self::BASE_URL . '/encyclopedia/' . $c->slug,
                        'lastmod'    => $lastmod,
                        'changefreq' => 'monthly',
                        'priority'   => '0.6',
                    ];

                    $compareSlug = CompareSlug::canonical($c->slug);
                    if (!$compareSlug || isset($emittedCompareSlugs[$compareSlug])) {
                        continue;
                    }
                    $emittedCompareSlugs[$compareSlug] = true;

                    $owner = ProductCategory::findForCompareSlug($compareSlug);
                    if (!$owner) {
                        continue;
                    }
                    $urls[] = [
                        'loc'        => self::BASE_URL . '/compare/' . $compareSlug,
                        'lastmod'    => $owner->updated_at?->toDateString() ?? $lastmod,
                        'changefreq' => 'weekly',
                        'priority'   => '0.7',  // commercial intent > informational
                    ];
                }
            });

        // Curated X-vs-Y comparison pages. Filter to pairs whose both slugs
        // resolve to active categories — otherwise we'd emit 404 URLs to
        // Google after retiring a compound.
        // Lowercase both sides — MySQL matches case-insensitively but PHP
        // array-key lookups are case-sensitive; CJC-1295 in the DB would
        // silently drop pairs referencing 'cjc-1295' from the constant.
        $activeSlugs = ProductCategory::where('is_active', true)
            ->whereNotNull('slug')
            ->pluck('slug')
            ->map(fn ($s) => strtolower($s))
            ->flip();
        foreach (\App\Http\Controllers\Frontend\CompareController::FEATURED_VS_PAIRS as $p) {
            if (!isset($activeSlugs[$p['a']]) || !isset($activeSlugs[$p['b']])) continue;
            $urls[] = [
                'loc'        => self::BASE_URL . "/compare/{$p['a']}-vs-{$p['b']}",
                'lastmod'    => $today,
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }

        if (\Schema::hasTable('educational_guides') && \Schema::hasColumn('educational_guides', 'content')) {
            EducationalGuide::where('status', 'published')
                ->whereNotNull('slug')
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->select('id', 'slug', 'updated_at')
                ->chunkById(200, function ($chunk) use (&$urls) {
                    foreach ($chunk as $guide) {
                        $urls[] = [
                            'loc'        => self::BASE_URL . '/guides/' . $guide->slug,
                            'lastmod'    => $guide->updated_at?->toDateString(),
                            'changefreq' => 'monthly',
                            'priority'   => '0.6',
                        ];
                    }
                });
        }

        // Blog posts.
        if (\Schema::hasTable('blogs')) {
            Blog::where('status', 'published')
                ->whereNotNull('slug')
                ->select('id', 'slug', 'updated_at')
                ->chunkById(500, function ($chunk) use (&$urls) {
                    foreach ($chunk as $b) {
                        $urls[] = [
                            'loc'        => self::BASE_URL . '/blog/' . $b->slug,
                            'lastmod'    => $b->updated_at?->toDateString(),
                            'changefreq' => 'monthly',
                            'priority'   => '0.5',
                        ];
                    }
                });
        }

        return $this->render($urls);
    }

    private function render(array $urls): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $out .= "  <url>\n";
            $out .= '    <loc>' . $this->escapeLoc($u['loc']) . "</loc>\n";
            if (!empty($u['lastmod']))    $out .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            if (!empty($u['changefreq'])) $out .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            if (!empty($u['priority']))   $out .= '    <priority>' . $u['priority'] . "</priority>\n";
            $out .= "  </url>\n";
        }
        $out .= '</urlset>' . "\n";
        return $out;
    }

    /**
     * Percent-encode each path segment, then XML-escape the URL.
     * htmlspecialchars() leaves spaces untouched, which makes a loc with
     * "Vitamin B12" invalid sitemap XML.
     */
    private function escapeLoc(string $loc): string
    {
        $parts = parse_url($loc);
        if ($parts === false) {
            return htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }

        $path = implode('/', array_map(
            'rawurlencode',
            explode('/', $parts['path'] ?? '/')
        ));
        $url = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'peptidemap.com') . $path;
        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }

        return htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
