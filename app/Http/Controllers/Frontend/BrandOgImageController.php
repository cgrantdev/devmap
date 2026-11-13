<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Brand;
use App\Models\Product;
use App\Support\Og\OgCards;
use App\Support\OgImageRevision;
use App\Support\ProductRowCurrency;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-vendor social card: GET /og/brand/{slug}.png (for /brand/{slug}).
 * Logo, location, listing count, lowest price and the coupon ticket.
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

        $brand = $this->findBrand($slug);
        if (!$brand) {
            return $this->fallbackOg(self::FALLBACK_PNG);
        }

        $vs = $brand->vendorSetting;
        $mtime = max(
            $brand->updated_at?->timestamp ?? 0,
            $vs?->updated_at?->timestamp ?? 0,
            // Listing counts and prices move daily.
            now()->startOfDay()->timestamp,
        );

        return $this->serveCard(
            self::CACHE_DIR,
            $slug.'-'.OgImageRevision::COPY,
            $mtime,
            self::FALLBACK_PNG,
            fn () => OgCards::vendor([
                'name' => (string) $brand->name,
                'logo' => $this->localImagePath($vs?->logo),
                'location' => $vs?->location?->name,
                'listings' => $this->productCount($brand),
                'from' => $this->fromPrice($brand),
                'code' => $vs?->coupon_code ?: null,
                'percent' => $vs?->coupon_discount_percent !== null ? (float) $vs->coupon_discount_percent : null,
                'path' => '/brand/'.$brand->slug,
            ])
        );
    }

    private function findBrand(string $slug): ?Brand
    {
        $with = ['vendorSetting' => fn ($q) => $q->forPublic()->with('location:id,name')];
        $brand = Brand::query()->forPublic()->with($with)
            ->where('slug', $slug)->where('is_active', true)->first();

        return $brand ?: Brand::query()->forPublic()->with($with)
            ->where('is_active', true)
            ->whereRaw('LOWER(slug) = ?', [strtolower($slug)])
            ->orderBy('id')
            ->first();
    }

    private function productCount(Brand $brand): int
    {
        return Product::visible()
            ->where('status', 'active')
            ->where('brand_id', $brand->id)
            ->count();
    }

    /** "from C$61.20": the cheapest listing's price with the vendor's code. */
    private function fromPrice(Brand $brand): ?string
    {
        $cheapest = Product::visible()
            ->where('status', 'active')
            ->where('brand_id', $brand->id)
            ->with([
                'brand' => fn ($q) => $q->forCard(),
                'brand.vendorSetting' => fn ($q) => $q->forCard(),
                'brand.vendorSetting.location:id,name',
            ])
            ->orderByRaw('COALESCE(NULLIF(discount_price, 0), price) ASC')
            ->first();
        if (!$cheapest) {
            return null;
        }
        $row = ProductRowCurrency::cardRow($cheapest);
        $price = (float) ($row['final_price'] ?? 0);

        return $price > 0 ? ($row['currency_symbol'] ?? '$').number_format($price, 2) : null;
    }
}
