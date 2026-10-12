<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Brand;
use App\Models\Product;
use App\Support\OgImageRevision;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-brand storefront OG image: GET /og/brand/{slug}.png
 */
class BrandOgImageController extends Controller
{
    use RendersOgImage;

    private const CACHE_DIR = 'og/brand';
    private const FALLBACK_PNG = 'images/og-default-v8.png';

    public function show(string $slug): Response
    {
        if ($redirect = $this->redirectMixedCaseSlug('og.brand', $slug)) {
            return $redirect;
        }

        $brand = Brand::with('vendorSetting')->where('slug', $slug)->where('is_active', true)->first();
        if (!$brand) {
            $brand = Brand::with('vendorSetting')
                ->where('is_active', true)
                ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
                ->orderBy('id')
                ->first();
        }
        if (!$brand) return $this->fallbackOg(self::FALLBACK_PNG);

        $vs = $brand->vendorSetting;

        // Cache mtime driven by brand OR vendor setting updated_at, whichever is newer.
        $mtime = max(
            $brand->updated_at?->timestamp ?? 0,
            $vs?->updated_at?->timestamp ?? 0,
        );

        return $this->serveOgImage(
            self::CACHE_DIR,
            $slug.'-'.OgImageRevision::COPY,
            $mtime,
            self::FALLBACK_PNG,
            fn () => View::make('og.brand', [
                'brand'        => $brand,
                'vendorLogo'   => $this->resolveStorageUrl($vs?->logo),
                'tagline'      => $this->tagline($brand),
                'productCount' => $this->productCount($brand),
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
}
