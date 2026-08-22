<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ExternalReview;
use App\Models\Product;
use App\Models\VendorReview;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

/**
 * Vendor Badge Widget.
 *
 * Vendors embed an SVG badge on their own site linking back to their
 * Peptidemap brand page. Every embed = one backlink from a real peptide
 * vendor site, which is far more valuable for our domain authority than
 * generic directory links. Also gives their visitors social proof
 * ("Rated 4.7★ on Peptidemap") and a UX their customers actually use.
 *
 * Two routes:
 *   GET /badge/{slug}.svg           — the actual embeddable image (dynamic SVG)
 *   GET /for-vendors/badge/{slug?}  — the "grab your badge" instructions page
 */
class VendorBadgeController extends Controller
{
    /**
     * Serve the badge SVG. Cached 1 hour so we don't rebuild the same file
     * for every impression on a busy vendor's site.
     */
    public function svg(Request $request, string $slug): Response
    {
        // Strip .svg from the tail if the router hasn't already.
        $slug = preg_replace('/\.svg$/i', '', $slug);

        $brand = Brand::where('slug', $slug)
            ->with('vendorSetting')
            ->first();

        if (!$brand || !$brand->is_active) {
            // Return a "not listed" fallback so vendors' pages don't 404 —
            // still a valid SVG so their <img> renders something reasonable
            // if they mistype the slug or we delist them.
            return $this->svgResponse($this->buildFallbackSvg());
        }

        $stats = $this->ratingStats($brand);
        $variant = $request->query('variant', 'horizontal'); // horizontal | vertical | compact
        $theme = $request->query('theme', 'light');           // light | dark

        $svg = $this->buildBadgeSvg($brand, $stats, $variant, $theme);
        return $this->svgResponse($svg);
    }

    /**
     * "Get your badge" page. Vendors visit /for-vendors/badge/{slug} and
     * copy the embed HTML.
     */
    public function show(Request $request, ?string $slug = null)
    {
        // Landing without a slug — searchable list of vendors so anyone can
        // find their brand.
        if (!$slug) {
            $vendors = Brand::where('is_active', true)
                ->with('vendorSetting')
                ->orderBy('name')
                ->get()
                ->map(fn ($b) => [
                    'slug' => $b->slug,
                    'name' => $b->name,
                    'rating_average' => (float) ($b->rating_average ?? 0),
                    'rating_count' => (int) ($b->rating_count ?? 0),
                ])
                ->values();

            return Inertia::render('Frontend/VendorBadge', [
                'brand' => null,
                'vendors' => $vendors,
            ]);
        }

        $brand = Brand::where('slug', $slug)
            ->with('vendorSetting')
            ->firstOrFail();

        $stats = $this->ratingStats($brand);
        $base = config('app.url', 'https://peptidemap.com');
        $brandUrl = $base . '/brand/' . $brand->slug;

        $variants = [
            ['key' => 'horizontal', 'label' => 'Horizontal',   'width' => 220, 'height' => 60],
            ['key' => 'vertical',   'label' => 'Vertical',      'width' => 140, 'height' => 140],
            ['key' => 'compact',    'label' => 'Compact chip',  'width' => 160, 'height' => 40],
        ];

        // Pre-render the HTML snippets so the copy button just grabs a string.
        $snippets = [];
        foreach ($variants as $v) {
            $svgUrl = $base . '/badge/' . $brand->slug . '.svg?variant=' . $v['key'];
            $snippets[$v['key']] = <<<HTML
<a href="{$brandUrl}?utm_source=vendor_badge&utm_medium=embed&utm_campaign={$brand->slug}"
   target="_blank" rel="noopener">
  <img src="{$svgUrl}"
       alt="{$brand->name} rated {$stats['rating']}/5 on Peptidemap"
       width="{$v['width']}" height="{$v['height']}"
       loading="lazy" />
</a>
HTML;
        }

        return Inertia::render('Frontend/VendorBadge', [
            'brand' => [
                'slug' => $brand->slug,
                'name' => $brand->name,
                'brand_url' => $brandUrl,
            ],
            'stats' => $stats,
            'variants' => $variants,
            'snippets' => $snippets,
        ]);
    }

    /**
     * Combined rating — native reviews + external (Trustpilot) reviews,
     * weighted by count. Same signal the brand page shows.
     */
    private function ratingStats(Brand $brand): array
    {
        $nativeAvg = (float) ($brand->rating_average ?? 0);
        $nativeCount = (int) ($brand->rating_count ?? 0);

        // Merge external reviews if present.
        $ext = ExternalReview::where('brand_id', $brand->id)
            ->whereNotNull('rating')
            ->selectRaw('AVG(rating) as avg, COUNT(*) as cnt')
            ->first();
        $extAvg = $ext && $ext->cnt ? (float) $ext->avg : 0;
        $extCount = $ext ? (int) $ext->cnt : 0;

        $totalCount = $nativeCount + $extCount;
        $avg = $totalCount > 0
            ? (($nativeAvg * $nativeCount) + ($extAvg * $extCount)) / $totalCount
            : 0;

        $productCount = Product::visible()->where('brand_id', $brand->id)->where('status', 'active')->count();

        return [
            'rating' => round($avg, 1),
            'rating_count' => $totalCount,
            'product_count' => $productCount,
            'has_rating' => $totalCount > 0,
        ];
    }

    /**
     * Assemble the SVG. Kept as a raw string (not a Blade view) so it's
     * cache-friendly, size-tight, and doesn't drag in the Vue layout.
     */
    private function buildBadgeSvg(Brand $brand, array $stats, string $variant, string $theme): string
    {
        $bg = $theme === 'dark' ? '#0F172A' : '#FFFFFF';
        $fg = $theme === 'dark' ? '#F8FAFC' : '#0F172A';
        $muted = $theme === 'dark' ? '#94A3B8' : '#64748B';
        $accent = '#4338CA';
        $star = '#F59E0B';

        $rating = $stats['has_rating'] ? number_format($stats['rating'], 1) : '—';
        $count = $stats['rating_count'];
        $countLabel = $count === 1 ? '1 review' : ($count > 0 ? "{$count} reviews" : 'Verified vendor');

        // Escape brand name for XML (< > & ' " → entities)
        $name = htmlspecialchars($brand->name, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        // Star polygon path (single 5-point star, filled)
        $starPath = 'M12 2 L15.09 8.26 L22 9.27 L17 14.14 L18.18 21.02 L12 17.77 L5.82 21.02 L7 14.14 L2 9.27 L8.91 8.26 Z';

        if ($variant === 'compact') {
            // 160×40 chip: [★] [4.7] · [reviews] · [Peptidemap]
            return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 40" width="160" height="40" role="img" aria-label="{$name} on Peptidemap">
  <title>{$name} on Peptidemap</title>
  <rect x="0.5" y="0.5" width="159" height="39" rx="6" fill="{$bg}" stroke="#E2E8F0"/>
  <g transform="translate(8, 8) scale(1)">
    <path d="{$starPath}" fill="{$star}" transform="scale(1)"/>
  </g>
  <text x="34" y="17" fill="{$fg}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="12" font-weight="700">{$rating}</text>
  <text x="34" y="30" fill="{$muted}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="9">{$countLabel}</text>
  <text x="154" y="24" text-anchor="end" fill="{$accent}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="10" font-weight="600">Peptidemap</text>
</svg>
SVG;
        }

        if ($variant === 'vertical') {
            // 140×140 square
            return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 140 140" width="140" height="140" role="img" aria-label="{$name} on Peptidemap">
  <title>{$name} on Peptidemap</title>
  <rect x="0.5" y="0.5" width="139" height="139" rx="10" fill="{$bg}" stroke="#E2E8F0"/>
  <text x="70" y="24" text-anchor="middle" fill="{$accent}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="10" font-weight="700" letter-spacing="1">PEPTIDEMAP</text>
  <g transform="translate(58, 40)">
    <path d="{$starPath}" fill="{$star}"/>
  </g>
  <text x="70" y="88" text-anchor="middle" fill="{$fg}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="28" font-weight="700">{$rating}</text>
  <text x="70" y="104" text-anchor="middle" fill="{$muted}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="10">out of 5</text>
  <line x1="30" y1="115" x2="110" y2="115" stroke="#E2E8F0" stroke-width="1"/>
  <text x="70" y="130" text-anchor="middle" fill="{$muted}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="9">{$countLabel}</text>
</svg>
SVG;
        }

        // Default: horizontal 220×60
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 60" width="220" height="60" role="img" aria-label="{$name} on Peptidemap">
  <title>{$name} on Peptidemap</title>
  <rect x="0.5" y="0.5" width="219" height="59" rx="8" fill="{$bg}" stroke="#E2E8F0"/>
  <g transform="translate(12, 18)">
    <path d="{$starPath}" fill="{$star}"/>
  </g>
  <text x="42" y="27" fill="{$fg}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="18" font-weight="700">{$rating}</text>
  <text x="72" y="27" fill="{$muted}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="10">/ 5</text>
  <text x="42" y="42" fill="{$muted}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="10">{$countLabel}</text>
  <line x1="128" y1="14" x2="128" y2="46" stroke="#E2E8F0" stroke-width="1"/>
  <text x="140" y="27" fill="{$accent}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="11" font-weight="700" letter-spacing="0.5">PEPTIDEMAP</text>
  <text x="140" y="42" fill="{$muted}" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="9">Verified vendor</text>
</svg>
SVG;
    }

    private function buildFallbackSvg(): string
    {
        return <<<'SVG'
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 60" width="220" height="60" role="img" aria-label="Peptidemap">
  <rect x="0.5" y="0.5" width="219" height="59" rx="8" fill="#FFFFFF" stroke="#E2E8F0"/>
  <text x="110" y="35" text-anchor="middle" fill="#4338CA" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif" font-size="14" font-weight="700">PEPTIDEMAP</text>
</svg>
SVG;
    }

    private function svgResponse(string $svg): Response
    {
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            // Cache 1h at browser + 1h at CDN. Rating changes take up to 1h
            // to reflect on embedded badges — acceptable tradeoff for a
            // widget rendered on every vendor's site pageview.
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
            // CORS + embed-friendly headers so browsers don't block the
            // <img> from cross-origin vendor sites.
            'Access-Control-Allow-Origin' => '*',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
