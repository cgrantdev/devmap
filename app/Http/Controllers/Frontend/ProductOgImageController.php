<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Per-product OG image renderer.
 *
 *   GET /og/product/{id}.png
 *
 * Renders resources/views/og/product.blade.php with the product's data
 * (name, brand, price, image) via headless Chromium and caches the PNG
 * under storage/app/public/og/product/{id}.png. Cache invalidates when
 * the product's updated_at moves past the file's mtime — subsequent
 * requests overwrite the stale PNG.
 *
 * If Chromium fails or the product doesn't exist, falls back to the
 * site-default OG so social previews never break.
 */
class ProductOgImageController extends Controller
{
    private const CACHE_DIR = 'og/product';
    private const CHROMIUM_TIMEOUT_SECONDS = 20;
    private const FALLBACK_PNG = 'images/og-default-v7.png';

    public function show(int $id): Response|BinaryFileResponse
    {
        $product = Product::with('brand.vendorSetting')->find($id);
        if (!$product) {
            return $this->fallback();
        }

        $absPath = storage_path('app/public/' . self::CACHE_DIR . "/{$id}.png");
        $productMTime = $product->updated_at?->timestamp ?? 0;

        // Regenerate if missing or the product has changed since last render.
        if (!file_exists($absPath) || filemtime($absPath) < $productMTime) {
            $generated = $this->generate($product, $absPath);
            if (!$generated) return $this->fallback();
        }

        return response()->file($absPath, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function generate(Product $product, string $outPath): bool
    {
        try {
            $dir = dirname($outPath);
            if (!is_dir($dir)) File::makeDirectory($dir, 0755, true);

            // Render the Blade template to a temp HTML file that Chromium can load.
            $html = View::make('og.product', [
                'product' => $product,
                'productImage' => $this->resolveImageUrl($product),
                'displayPrice' => $this->displayPrice($product),
                'strikePrice' => $this->strikePrice($product),
                'discountPct' => $this->discountPct($product),
            ])->render();

            $tmpHtml = tempnam(sys_get_temp_dir(), 'ogsrc_') . '.html';
            file_put_contents($tmpHtml, $html);

            $cmd = sprintf(
                'chromium-browser --headless --disable-gpu --hide-scrollbars --no-sandbox ' .
                '--window-size=1200,630 --virtual-time-budget=3000 ' .
                '--screenshot=%s file://%s 2>&1',
                escapeshellarg($outPath),
                escapeshellarg($tmpHtml)
            );

            $descriptor = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $proc = proc_open($cmd, $descriptor, $pipes);
            if (!is_resource($proc)) return false;

            // Kill chromium if it hangs
            $start = time();
            while (true) {
                $status = proc_get_status($proc);
                if (!$status['running']) break;
                if (time() - $start > self::CHROMIUM_TIMEOUT_SECONDS) {
                    proc_terminate($proc, 9);
                    break;
                }
                usleep(100_000);
            }
            foreach ($pipes as $p) { if (is_resource($p)) fclose($p); }
            proc_close($proc);
            @unlink($tmpHtml);

            return file_exists($outPath) && filesize($outPath) > 1000;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    private function fallback(): BinaryFileResponse
    {
        return response()->file(public_path(self::FALLBACK_PNG), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function resolveImageUrl(Product $product): ?string
    {
        $img = $product->image_url;
        if (!$img) return null;
        // Absolute URLs (external scrapers): use as-is.
        if (preg_match('#^(https?:)?//#i', $img)) return $img;
        if (str_starts_with($img, '/')) return url($img);
        return asset('storage/' . $img);
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
