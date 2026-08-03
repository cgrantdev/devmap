<?php

namespace App\Http\Controllers\Frontend\Concerns;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Shared headless-Chromium PNG rendering used by all /og/* image routes.
 * Renders a Blade view to a temp HTML file, screenshots it at 1200x630,
 * caches the PNG to storage/app/public/{$cacheDir}/{$cacheKey}.png, and
 * streams it back.
 *
 * Temp HTML lives under storage/framework/cache/og-src/ (NOT /tmp) because
 * chromium's snap sandbox on Forge can't read /tmp.
 */
trait RendersOgImage
{
    private const CHROMIUM_TIMEOUT_SECONDS = 20;

    /**
     * Serve a cached PNG or generate it on cache miss.
     *
     * @param string $cacheDir e.g. 'og/product'
     * @param string $cacheKey e.g. '334'
     * @param int $sourceMTime UNIX timestamp — cache regenerates when file mtime is older
     * @param string $fallbackPngPath Public path to fallback PNG on failure
     * @param callable $renderHtml No-arg callable that returns the HTML string to render
     */
    protected function serveOgImage(
        string $cacheDir,
        string $cacheKey,
        int $sourceMTime,
        string $fallbackPngPath,
        callable $renderHtml
    ): Response|BinaryFileResponse {
        $absPath = storage_path("app/public/{$cacheDir}/{$cacheKey}.png");

        if (!file_exists($absPath) || filemtime($absPath) < $sourceMTime) {
            $ok = $this->generateOgImage($absPath, $renderHtml(), $cacheDir);
            if (!$ok) return $this->fallbackOg($fallbackPngPath);
        }

        return response()->file($absPath, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function generateOgImage(string $outPath, string $html, string $cacheDir): bool
    {
        try {
            $dir = dirname($outPath);
            if (!is_dir($dir)) File::makeDirectory($dir, 0755, true);

            $tmpDir = storage_path('framework/cache/og-src');
            if (!is_dir($tmpDir)) File::makeDirectory($tmpDir, 0755, true);
            $tmpHtml = $tmpDir . '/' . str_replace('/', '-', $cacheDir) . '-' . uniqid() . '.html';
            file_put_contents($tmpHtml, $html);

            $cmd = sprintf(
                'chromium-browser --headless --disable-gpu --hide-scrollbars --no-sandbox ' .
                '--window-size=1200,630 --virtual-time-budget=3000 ' .
                '--screenshot=%s file://%s 2>&1',
                escapeshellarg($outPath),
                escapeshellarg($tmpHtml)
            );

            $descriptor = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
            $proc = proc_open($cmd, $descriptor, $pipes);
            if (!is_resource($proc)) return false;

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

    private function fallbackOg(string $publicPath): BinaryFileResponse
    {
        return response()->file(public_path($publicPath), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    protected function resolveStorageUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (preg_match('#^(https?:)?//#i', $path)) return $path;
        if (str_starts_with($path, '/')) return url($path);
        return asset('storage/' . $path);
    }
}
