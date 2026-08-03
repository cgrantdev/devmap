<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Per-brand storefront OG image: GET /og/brand/{slug}.png
 */
class BrandOgImageController extends Controller
{
    use RendersOgImage;

    private const CACHE_DIR = 'og/brand';
    private const FALLBACK_PNG = 'images/og-default-v7.png';

    public function show(string $slug): Response|BinaryFileResponse
    {
        $brand = Brand::with('vendorSetting')->where('slug', $slug)->where('is_active', true)->first();
        if (!$brand) return $this->fallbackOg(self::FALLBACK_PNG);

        $vs = $brand->vendorSetting;

        // Cache mtime driven by brand OR vendor setting updated_at, whichever is newer.
        $mtime = max(
            $brand->updated_at?->timestamp ?? 0,
            $vs?->updated_at?->timestamp ?? 0,
        );

        return $this->serveOgImage(
            self::CACHE_DIR,
            $slug,
            $mtime,
            self::FALLBACK_PNG,
            fn () => View::make('og.brand', [
                'brand'        => $brand,
                'vendorLogo'   => $this->resolveStorageUrl($vs?->logo),
                'tagline'      => $this->tagline($brand),
                'productCount' => $this->productCount($brand),
                'fromPrice'    => $this->fromPrice($brand),
                'couponCode'   => $vs?->coupon_code,
            ])->render()
        );
    }

    private function tagline(Brand $brand): ?string
    {
        $raw = $brand->vendorSetting?->description;
        if (!$raw) return null;
        return Str::limit(strip_tags($raw), 180);
    }

    private function productCount(Brand $brand): int
    {
        return Product::visible()
            ->where('status', 'active')
            ->where('brand_id', $brand->id)
            ->count();
    }

    private function fromPrice(Brand $brand): ?float
    {
        $min = Product::visible()
            ->where('status', 'active')
            ->where('brand_id', $brand->id)
            ->where(function ($q) {
                $q->where('discount_price', '>', 0)
                  ->orWhere(function ($qq) {
                      $qq->whereNull('discount_price')->where('price', '>', 0);
                  });
            })
            ->selectRaw('MIN(COALESCE(NULLIF(discount_price, 0), price)) as min_price')
            ->value('min_price');
        return $min ? (float) $min : null;
    }
}
