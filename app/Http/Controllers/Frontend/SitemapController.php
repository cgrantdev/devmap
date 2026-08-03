<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
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
    private const CACHE_KEY = 'sitemap.xml.v1';
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
            ['/encyclopedia',  'weekly',  '0.8'],
            ['/blogs',         'weekly',  '0.6'],
        ] as [$path, $freq, $priority]) {
            $urls[] = ['loc' => self::BASE_URL . $path, 'lastmod' => $today, 'changefreq' => $freq, 'priority' => $priority];
        }

        // Brand storefronts.
        Brand::where('is_active', true)
            ->whereNotNull('slug')
            ->select('id', 'slug', 'updated_at')
            ->chunkById(500, function ($chunk) use (&$urls) {
                foreach ($chunk as $b) {
                    $urls[] = [
                        'loc'        => self::BASE_URL . '/brand/' . $b->slug . '/products',
                        'lastmod'    => $b->updated_at?->toDateString(),
                        'changefreq' => 'weekly',
                        'priority'   => '0.7',
                    ];
                }
            });

        // Product detail pages. Route is /product/{vendorSlug}/{productSlug}/{id}
        // (product.detail — the working one that Vue-renders). The
        // /product/{id}/{slug} route (product.public) exists but 404s.
        Product::where('hidden', false)
            ->where('status', 'active')
            ->whereNotNull('slug')
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
        ProductCategory::where('is_active', true)
            ->whereNotNull('slug')
            ->select('id', 'slug', 'updated_at')
            ->chunkById(500, function ($chunk) use (&$urls) {
                foreach ($chunk as $c) {
                    $urls[] = [
                        'loc'        => self::BASE_URL . '/encyclopedia/' . $c->slug,
                        'lastmod'    => $c->updated_at?->toDateString(),
                        'changefreq' => 'monthly',
                        'priority'   => '0.6',
                    ];
                }
            });

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
            $out .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if (!empty($u['lastmod']))    $out .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            if (!empty($u['changefreq'])) $out .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            if (!empty($u['priority']))   $out .= '    <priority>' . $u['priority'] . "</priority>\n";
            $out .= "  </url>\n";
        }
        $out .= '</urlset>' . "\n";
        return $out;
    }
}
