<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Brand;
use App\Support\Og\OgCards;
use App\Support\OgImageRevision;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coupon social card: GET /og/coupon/{slug}.png (for /coupon/{slug}).
 * Vendor logo and a large coupon ticket with the code and its discount.
 */
class CouponOgImageController extends Controller
{
    use RendersOgImage;

    private const CACHE_DIR = 'og/coupon';

    private const FALLBACK_PNG = 'images/og-default-v8.png';

    public function show(string $slug): Response
    {
        $slug = strtolower($slug);
        $brand = Brand::query()->forPublic()
            ->with(['vendorSetting' => fn ($q) => $q->forPublic()])
            ->where('is_active', true)
            ->whereRaw('LOWER(slug) = ?', [$slug])
            ->orderBy('id')
            ->first();
        $vs = $brand?->vendorSetting;
        if (!$brand || !$vs || trim((string) $vs->coupon_code) === '') {
            return $this->fallbackOg(self::FALLBACK_PNG);
        }

        return $this->serveCard(
            self::CACHE_DIR,
            $slug.'-'.OgImageRevision::COPY,
            max($brand->updated_at?->timestamp ?? 0, $vs->updated_at?->timestamp ?? 0),
            self::FALLBACK_PNG,
            fn () => OgCards::coupon([
                'name' => (string) $brand->name,
                'logo' => $this->localImagePath($vs->logo),
                'code' => (string) $vs->coupon_code,
                'percent' => $vs->coupon_discount_percent !== null ? (float) $vs->coupon_discount_percent : null,
                'path' => '/coupon/'.$brand->slug,
            ])
        );
    }
}
