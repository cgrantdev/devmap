<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Concerns\RendersOgImage;
use App\Models\Product;
use App\Support\Og\OgCards;
use App\Support\OgImageRevision;
use App\Support\ProductRowCurrency;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Per-listing social card: GET /og/product/{id}.png. Product photo,
 * vendor, price with the code, listed price, $/mg and the coupon ticket.
 */
class ProductOgImageController extends Controller
{
    use RendersOgImage;

    private const CACHE_DIR = 'og/product';

    private const FALLBACK_PNG = 'images/og-default-v8.png';

    public function show(int $id): Response|BinaryFileResponse
    {
        $product = Product::with([
            'brand' => fn ($q) => $q->forCard(),
            'brand.vendorSetting' => fn ($q) => $q->forCard(),
            'brand.vendorSetting.location:id,name',
        ])->find($id);
        if (!$product || !$product->brand) {
            return $this->fallbackOg(self::FALLBACK_PNG);
        }

        return $this->serveCard(
            self::CACHE_DIR,
            $id.'-'.OgImageRevision::COPY.'-'.OgImageRevision::PRODUCT,
            $product->updated_at?->timestamp ?? 0,
            self::FALLBACK_PNG,
            function () use ($product) {
                $row = ProductRowCurrency::cardRow($product);
                $symbol = $row['currency_symbol'] ?? '$';
                $final = (float) ($row['final_price'] ?? 0);
                $listed = (float) ($product->discount_price ?: $product->price);
                $hasCode = $final > 0 && $listed > 0 && $final < $listed - 0.004;
                $perMg = $row['price_per_mg'] ?? null;
                $size = trim((string) $product->size_mg);
                if ($size !== '' && !preg_match('/[a-z]/i', $size)) {
                    $size .= 'mg';
                }
                $vs = $product->brand->vendorSetting;
                $code = $vs?->coupon_code ?: ($hasCode ? 'PMAP' : null);

                return OgCards::product([
                    'name' => (string) ($product->display_name ?: $product->name),
                    'vendor' => (string) $product->brand->name,
                    'image' => $this->localImagePath($product->mirrored_image_url ?: $product->image_url),
                    'price' => $symbol.number_format($final > 0 ? $final : $listed, 2),
                    'retail' => $hasCode ? $symbol.number_format($listed, 2) : null,
                    'per_mg' => $perMg !== null ? $symbol.number_format((float) $perMg, 2).'/mg' : null,
                    'size' => $size !== '' ? $size : null,
                    'code' => $hasCode ? $code : null,
                    'percent' => $hasCode && $vs?->coupon_discount_percent !== null ? (float) $vs->coupon_discount_percent : null,
                    'path' => '/product/'.$product->brand->slug.'/'.$product->slug.'/'.$product->id,
                ]);
            }
        );
    }
}
