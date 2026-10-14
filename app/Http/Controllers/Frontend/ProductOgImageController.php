<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Per-product OG image: GET /og/product/{id}.png
 * See RendersOgImage trait for the caching + chromium-invocation mechanics.
 */
class ProductOgImageController extends Controller
{
    use RendersOgImage;

    private const CACHE_DIR = 'og/product';
    private const FALLBACK_PNG = 'images/og-default-v8.png';

    public function show(int $id): Response|BinaryFileResponse
    {
        $product = Product::with('brand.vendorSetting')->find($id);
        if (!$product) return $this->fallbackOg(self::FALLBACK_PNG);

        return $this->serveOgImage(
            self::CACHE_DIR,
            $id.'-'.\App\Support\OgImageRevision::COPY.'-'.\App\Support\OgImageRevision::PRODUCT,
            $product->updated_at?->timestamp ?? 0,
            self::FALLBACK_PNG,
            fn () => View::make('og.product', [
                'product' => $product,
                'productImage' => $this->resolveStorageUrl($product->image_url),
                'vendorLogo' => $this->resolveStorageUrl($product->brand?->vendorSetting?->logo),
                'couponCode' => $product->brand?->vendorSetting?->coupon_code,
                'vendorCount' => $this->vendorCount($product),
            ])->render()
        );
    }

    private function vendorCount(Product $product): ?int
    {
        if (!$product->product_category_id) return null;
        return Product::visible()
            ->where('status', 'active')
            ->where('product_category_id', $product->product_category_id)
            ->distinct('brand_id')
            ->count('brand_id');
    }
}
