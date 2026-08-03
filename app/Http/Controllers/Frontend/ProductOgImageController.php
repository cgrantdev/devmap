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
    private const FALLBACK_PNG = 'images/og-default-v7.png';

    public function show(int $id): Response|BinaryFileResponse
    {
        $product = Product::with('brand.vendorSetting')->find($id);
        if (!$product) return $this->fallbackOg(self::FALLBACK_PNG);

        return $this->serveOgImage(
            self::CACHE_DIR,
            (string) $id,
            $product->updated_at?->timestamp ?? 0,
            self::FALLBACK_PNG,
            fn () => View::make('og.product', [
                'product' => $product,
                'productImage' => $this->resolveStorageUrl($product->image_url),
                'vendorLogo' => $this->resolveStorageUrl($product->brand?->vendorSetting?->logo),
                'displayPrice' => $this->displayPrice($product),
                'strikePrice' => $this->strikePrice($product),
                'discountPct' => $this->discountPct($product),
            ])->render()
        );
    }

    private function displayPrice(Product $product): ?float
    {
        $d = (float) ($product->discount_price ?? 0);
        $r = (float) ($product->price ?? 0);
        if ($d > 0 && $d < $r) return $d;
        return $r > 0 ? $r : null;
    }

    private function strikePrice(Product $product): ?float
    {
        $d = (float) ($product->discount_price ?? 0);
        $r = (float) ($product->price ?? 0);
        return ($d > 0 && $d < $r) ? $r : null;
    }

    private function discountPct(Product $product): ?int
    {
        $strike = $this->strikePrice($product);
        $display = $this->displayPrice($product);
        if (!$strike || !$display || $strike <= $display) return null;
        return (int) round((1 - $display / $strike) * 100);
    }
}
