<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Brand;
use App\Models\Product;
use App\Support\CalculatorPresets;
use App\Support\Og\OgCards;
use App\Support\OgImageRevision;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Social cards for site sections: GET /og/page/{key}.png, and one per
 * calculator preset: GET /og/calculator/{slug}.png. Live counts, so the
 * cards are redrawn once a day.
 */
class PageOgImageController extends Controller
{
    use RendersOgImage;

    public const KEYS = ['home', 'products', 'vendors', 'deals', 'compare', 'calculator', 'research', 'encyclopedia'];

    private const FALLBACK_PNG = 'images/og-default-v8.png';

    public function show(string $key): Response
    {
        if (!in_array($key, self::KEYS, true)) {
            return $this->fallbackOg(self::FALLBACK_PNG);
        }

        return $this->serveCard(
            'og/page',
            $key.'-'.OgImageRevision::COPY,
            now()->startOfDay()->timestamp,
            self::FALLBACK_PNG,
            fn () => OgCards::page($this->content($key))
        );
    }

    public function calculator(string $slug): Response
    {
        $preset = CalculatorPresets::findBySlug(strtolower($slug));
        if (!$preset) {
            return $this->fallbackOg(self::FALLBACK_PNG);
        }
        $compound = CalculatorPresets::compoundName($preset);

        return $this->serveCard(
            'og/calculator',
            strtolower($slug).'-'.OgImageRevision::COPY,
            0,
            self::FALLBACK_PNG,
            fn () => OgCards::page([
                'eyebrow' => 'Reconstitution calculator',
                'title' => $compound.' calculator',
                'subtitle' => 'Concentration, dose volume and syringe units for a '.$preset['name'].' vial.',
                'chips' => array_values(array_filter([
                    !empty($preset['mg']) ? $preset['mg'].'mg vial' : null,
                    'mg and mcg',
                    'U-100 syringe units',
                ])),
                'path' => '/calculator/'.strtolower($slug),
            ])
        );
    }

    /** @return array{eyebrow: string, title: string, subtitle: ?string, chips: list<string>, path: string} */
    private function content(string $key): array
    {
        $s = $this->stats();
        $vendors = number_format($s['vendors']).' vendors';
        $listings = number_format($s['listings']).' listings';
        $codes = $s['max_pct'] ? 'Codes up to '.$s['max_pct'].'% off' : 'Vendor coupon codes';

        return match ($key) {
            'home' => [
                'eyebrow' => 'Peptide price comparison',
                'title' => 'Compare research peptide prices from '.number_format($s['vendors']).' vendors',
                'subtitle' => 'Live prices, price per mg and coupon codes, side by side.',
                'chips' => [$vendors, $listings, $codes],
                'path' => '/',
            ],
            'products' => [
                'eyebrow' => 'Peptide catalog',
                'title' => 'Every research peptide, every vendor, one price list',
                'subtitle' => number_format($s['compounds']).' compounds with prices from '.number_format($s['vendors']).' vendors.',
                'chips' => [number_format($s['compounds']).' compounds', $listings, 'Price per mg'],
                'path' => '/products',
            ],
            'vendors' => [
                'eyebrow' => 'Vendor directory',
                'title' => number_format($s['vendors']).' peptide vendors, compared',
                'subtitle' => 'Coupon codes, where they ship from, testing and reviews.',
                'chips' => [$vendors, $codes, 'Ships-to filter'],
                'path' => '/vendors',
            ],
            'deals' => [
                'eyebrow' => 'Vendor coupon codes',
                'title' => number_format($s['codes']).' live peptide coupon codes',
                'subtitle' => $s['max_pct'] ? 'Save up to '.$s['max_pct'].'% at '.number_format($s['codes']).' vendors.' : 'Codes from '.number_format($s['codes']).' vendors.',
                'chips' => [number_format($s['codes']).' codes', $codes, 'Tap to copy'],
                'path' => '/deals',
            ],
            'compare' => [
                'eyebrow' => 'Price comparison',
                'title' => "See every vendor's price, side by side",
                'subtitle' => 'Lowest price, price per mg and coupon codes for each compound.',
                'chips' => [$vendors, $listings, 'Prices checked daily'],
                'path' => '/compare',
            ],
            'calculator' => [
                'eyebrow' => 'Free tool',
                'title' => 'Peptide reconstitution calculator',
                'subtitle' => 'Concentration, dose volume and syringe units in seconds.',
                'chips' => ['55+ presets', 'mg and mcg', 'U-100 syringe units'],
                'path' => '/calculator',
            ],
            'research' => [
                'eyebrow' => 'Research',
                'title' => 'Peptide research, explained',
                'subtitle' => 'Plain-English summaries of trials, mechanisms and regulation.',
                'chips' => ['Primary sources', 'Updated regularly'],
                'path' => '/research',
            ],
            'encyclopedia' => [
                'eyebrow' => 'Encyclopedia',
                'title' => 'The peptide encyclopedia',
                'subtitle' => 'What each compound is, what the research says and who sells it.',
                'chips' => [number_format($s['compounds']).' compounds', $vendors],
                'path' => '/encyclopedia',
            ],
        };
    }

    /** @return array{vendors: int, listings: int, compounds: int, codes: int, max_pct: ?int} */
    private function stats(): array
    {
        return Cache::remember('og:page-stats:v1', 3600, function () {
            $listings = Product::visible()->where('status', 'active');
            $codes = Brand::where('is_active', true)
                ->whereHas('vendorSetting', fn ($q) => $q->where('approval_status', 'approved')
                    ->whereNotNull('coupon_code')->where('coupon_code', '!=', ''))
                ->with(['vendorSetting' => fn ($q) => $q->forPublic()])
                ->get();
            $maxPct = $codes->map(fn ($b) => (float) ($b->vendorSetting?->coupon_discount_percent ?? 0))->max();

            return [
                'vendors' => (int) (clone $listings)->distinct('brand_id')->count('brand_id'),
                'listings' => (int) (clone $listings)->count(),
                'compounds' => (int) (clone $listings)->whereNotNull('product_category_id')->distinct('product_category_id')->count('product_category_id'),
                'codes' => $codes->count(),
                'max_pct' => $maxPct > 0 ? (int) round($maxPct) : null,
            ];
        });
    }
}
