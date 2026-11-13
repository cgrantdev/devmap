<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    /** Longest side of a stored vendor logo after processing. */
    public const LOGO_MAX_SIDE = 600;

    /**
     * Resolve a vendor logo URL. Returns the real file when it exists and
     * null otherwise, so the frontend renders its own initials fallback
     * instead of an external placeholder image.
     */
    public static function resolveVendorLogo(?string $logoPath): ?string
    {
        $path = trim((string) $logoPath);
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }
        return asset('storage/' . $path);
    }

    /**
     * Processed logos are stored as {base}-lp.webp or {base}-lp-bgRRGGBB.webp.
     * The optional hex is the backdrop the logo needs (a dark block's own
     * edge colour, or a dark tile behind a white-on-transparent mark), so any
     * page that renders the URL can match it without a database lookup.
     */
    public static function processedLogoName(string $base, ?string $backdropHex): string
    {
        $suffix = $backdropHex ? '-bg' . strtolower(ltrim($backdropHex, '#')) : '';
        return $base . '-lp' . $suffix . '.webp';
    }

    public static function isProcessedLogo(?string $path): bool
    {
        return (bool) preg_match('/-lp(?:-bg[0-9a-f]{6})?\.webp$/i', (string) $path);
    }

    /** Backdrop colour encoded in a processed logo filename, e.g. "#111827". */
    public static function logoBackdropFromPath(?string $path): ?string
    {
        if (preg_match('/-lp-bg([0-9a-f]{6})\.webp$/i', (string) $path, $m) === 1) {
            return '#' . strtolower($m[1]);
        }
        return null;
    }

    /**
     * Trim near-uniform margins and fit inside LOGO_MAX_SIDE. Works on a
     * bounded copy first so oversized uploads scan quickly. Returns a new
     * image; the input is destroyed whenever it is replaced.
     */
    public static function trimAndFitLogo(\GdImage $image, int $maxSide = self::LOGO_MAX_SIDE): \GdImage
    {
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $image = self::fitWithin($image, 1200);

        $box = self::detectContentBox($image);
        if ($box !== null) {
            [$x, $y, $w, $h] = $box;
            $cropped = self::transparentCanvas($w, $h);
            imagecopy($cropped, $image, 0, 0, $x, $y, $w, $h);
            imagedestroy($image);
            $image = $cropped;
        }

        return self::fitWithin($image, $maxSide);
    }

    /**
     * Backdrop a logo needs on a white page, as "RRGGBB" (no hash), or null
     * when a plain white box is right. Opaque non-white edges return their
     * own colour (dark or coloured logo blocks); transparent edges around a
     * very light mark return a dark slate so the mark stays visible.
     */
    public static function logoBackdropHex(\GdImage $image): ?string
    {
        $edge = self::edgeColor($image);
        if ($edge === null) {
            return null;
        }

        if ($edge['transparent']) {
            $w = imagesx($image);
            $h = imagesy($image);
            $step = max(1, (int) floor(max($w, $h) / 80));
            $sum = 0.0;
            $count = 0;
            for ($y = 0; $y < $h; $y += $step) {
                for ($x = 0; $x < $w; $x += $step) {
                    $c = imagecolorat($image, $x, $y);
                    if ((($c >> 24) & 0x7F) < 64) {
                        $sum += self::luminance(($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF);
                        $count++;
                    }
                }
            }
            if ($count < 20) {
                return null;
            }
            return ($sum / $count) > 0.82 ? '111827' : null;
        }

        if (self::luminance($edge['r'], $edge['g'], $edge['b']) > 0.92) {
            return null;
        }
        return sprintf('%02x%02x%02x', $edge['r'], $edge['g'], $edge['b']);
    }

    private static function luminance(int $r, int $g, int $b): float
    {
        return (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
    }

    private static function transparentCanvas(int $w, int $h): \GdImage
    {
        $canvas = imagecreatetruecolor(max(1, $w), max(1, $h));
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle($canvas, 0, 0, $w, $h, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        return $canvas;
    }

    private static function fitWithin(\GdImage $image, int $maxSide): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = $maxSide / max($w, $h);
        if ($scale >= 1) {
            return $image;
        }
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $resized = self::transparentCanvas($nw, $nh);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($image);
        return $resized;
    }

    /**
     * Majority colour of the image border, or null when the edges disagree
     * (full-bleed artwork), in which case nothing is trimmed.
     *
     * @return array{transparent: bool, r: int, g: int, b: int}|null
     */
    private static function edgeColor(\GdImage $image): ?array
    {
        $w = imagesx($image);
        $h = imagesy($image);
        if ($w < 4 || $h < 4) {
            return null;
        }

        $points = [];
        $n = 24;
        for ($i = 0; $i < $n; $i++) {
            $x = (int) round(($w - 1) * $i / ($n - 1));
            $y = (int) round(($h - 1) * $i / ($n - 1));
            $points[] = [$x, 0];
            $points[] = [$x, $h - 1];
            $points[] = [0, $y];
            $points[] = [$w - 1, $y];
        }

        $transparent = 0;
        $opaque = [];
        foreach ($points as [$x, $y]) {
            $c = imagecolorat($image, $x, $y);
            if ((($c >> 24) & 0x7F) >= 110) {
                $transparent++;
            } else {
                $opaque[] = [($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF];
            }
        }

        $total = count($points);
        if ($transparent >= $total * 0.9) {
            return ['transparent' => true, 'r' => 0, 'g' => 0, 'b' => 0];
        }
        if (count($opaque) < $total * 0.9) {
            return null;
        }

        $r = (int) round(array_sum(array_column($opaque, 0)) / count($opaque));
        $g = (int) round(array_sum(array_column($opaque, 1)) / count($opaque));
        $b = (int) round(array_sum(array_column($opaque, 2)) / count($opaque));
        $close = 0;
        foreach ($opaque as [$pr, $pg, $pb]) {
            if (abs($pr - $r) + abs($pg - $g) + abs($pb - $b) <= 36) {
                $close++;
            }
        }
        if ($close < $total * 0.85) {
            return null;
        }

        return ['transparent' => false, 'r' => $r, 'g' => $g, 'b' => $b];
    }

    /**
     * Bounding box of everything that isn't border-coloured, padded a
     * little so anti-aliased edges survive. Null means leave the image
     * alone (no uniform border, nothing to trim, or almost blank).
     *
     * @return array{0: int, 1: int, 2: int, 3: int}|null [x, y, width, height]
     */
    private static function detectContentBox(\GdImage $image): ?array
    {
        $edge = self::edgeColor($image);
        if ($edge === null) {
            return null;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $step = max(1, (int) floor(min($w, $h) / 400));

        $isBackground = function (int $x, int $y) use ($image, $edge): bool {
            $c = imagecolorat($image, $x, $y);
            $alpha = ($c >> 24) & 0x7F;
            if ($alpha >= 110) {
                return true;
            }
            if ($edge['transparent']) {
                return false;
            }
            $diff = abs((($c >> 16) & 0xFF) - $edge['r'])
                + abs((($c >> 8) & 0xFF) - $edge['g'])
                + abs(($c & 0xFF) - $edge['b']);
            return $diff <= 36;
        };
        $rowEmpty = function (int $y) use ($w, $step, $isBackground): bool {
            for ($x = 0; $x < $w; $x += $step) {
                if (!$isBackground($x, $y)) {
                    return false;
                }
            }
            return true;
        };
        $colEmpty = function (int $x) use ($h, $step, $isBackground): bool {
            for ($y = 0; $y < $h; $y += $step) {
                if (!$isBackground($x, $y)) {
                    return false;
                }
            }
            return true;
        };

        $top = 0;
        while ($top < $h && $rowEmpty($top)) {
            $top++;
        }
        if ($top >= $h) {
            return null;
        }
        $bottom = $h - 1;
        while ($bottom > $top && $rowEmpty($bottom)) {
            $bottom--;
        }
        $left = 0;
        while ($left < $w && $colEmpty($left)) {
            $left++;
        }
        $right = $w - 1;
        while ($right > $left && $colEmpty($right)) {
            $right--;
        }

        $cw = $right - $left + 1;
        $ch = $bottom - $top + 1;
        if ($cw < 4 || $ch < 4 || ($cw * $ch) < ($w * $h) * 0.005) {
            return null;
        }

        $pad = max(2, (int) round(0.05 * min($cw, $ch)));
        $x = max(0, $left - $pad);
        $y = max(0, $top - $pad);
        $x2 = min($w - 1, $right + $pad);
        $y2 = min($h - 1, $bottom + $pad);

        if ($x === 0 && $y === 0 && $x2 === $w - 1 && $y2 === $h - 1) {
            return null;
        }
        return [$x, $y, $x2 - $x + 1, $y2 - $y + 1];
    }

    /**
     * Convert uploaded image to WebP format using GD library
     * 
     * @param \Illuminate\Http\UploadedFile $uploadedFile
     * @param string $directory Directory name in storage/app/public
     * @param int $quality WebP quality (0-100, default: 85)
     * @return string Filename of the saved WebP image
     * @throws \Exception
     */
    public static function convertToWebP($uploadedFile, $directory = 'images', $quality = 85)
    {
        if (!function_exists('imagewebp')) {
            throw new \Exception('WebP support is not available. Please install GD library with WebP support.');
        }

        $filename = Str::random(40) . '.webp';
        $path = storage_path('app/public/' . $directory . '/' . $filename);
        
        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);
        
        // Get image info
        $imageInfo = getimagesize($uploadedFile->getRealPath());
        if ($imageInfo === false) {
            throw new \Exception('Invalid image file.');
        }
        
        $mimeType = $imageInfo['mime'];
        
        // Don't process SVG files - they should be handled separately
        if ($mimeType === 'image/svg+xml') {
            throw new \Exception('SVG files should be saved as-is, not converted to WebP.');
        }
        
        // Create image resource based on MIME type
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($uploadedFile->getRealPath());
                break;
            case 'image/png':
                $image = imagecreatefrompng($uploadedFile->getRealPath());
                // Preserve transparency for PNG
                imagealphablending($image, false);
                imagesavealpha($image, true);
                
                // Convert palette images to true color to support WebP
                if (imageistruecolor($image) === false) {
                    $trueColorImage = imagecreatetruecolor(imagesx($image), imagesy($image));
                    imagealphablending($trueColorImage, false);
                    imagesavealpha($trueColorImage, true);
                    imagecopy($trueColorImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                    imagedestroy($image);
                    $image = $trueColorImage;
                }
                break;
            case 'image/gif':
                $image = imagecreatefromgif($uploadedFile->getRealPath());
                // Convert palette images to true color to support WebP
                if (imageistruecolor($image) === false) {
                    $trueColorImage = imagecreatetruecolor(imagesx($image), imagesy($image));
                    imagealphablending($trueColorImage, false);
                    imagesavealpha($trueColorImage, true);
                    imagecopy($trueColorImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
                    imagedestroy($image);
                    $image = $trueColorImage;
                }
                break;
            case 'image/webp':
                // Already WebP, just copy it
                $image = imagecreatefromwebp($uploadedFile->getRealPath());
                break;
            default:
                throw new \Exception('Unsupported image format: ' . $mimeType);
        }
        
        if ($image === false) {
            throw new \Exception('Failed to create image resource.');
        }

        // Vendor logos: trim baked-in margins, cap the size, and record the
        // backdrop the mark needs in the filename (see processedLogoName).
        // Any processing failure falls back to the plain conversion so an
        // upload never fails because of the trim step.
        if ($directory === 'vendor_logos') {
            try {
                $image = self::trimAndFitLogo($image);
                $filename = self::processedLogoName(Str::random(40), self::logoBackdropHex($image));
                $path = storage_path('app/public/' . $directory . '/' . $filename);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('vendor logo processing failed', ['err' => $e->getMessage()]);
            }
        }

        // Convert and save as WebP
        imagewebp($image, $path, $quality);
        imagedestroy($image);

        return $filename;
    }

    /**
     * Delete an image from storage
     * 
     * @param string $filename Image filename
     * @param string $directory Directory name in storage/app/public
     * @return bool
     */
    public static function deleteImage($filename, $directory)
    {
        if (empty($filename)) {
            return false;
        }
        
        return Storage::disk('public')->delete($directory . '/' . $filename);
    }

    /**
     * URL for a blog or guide listing image.
     *
     * Absolute URLs and root-relative public paths (for example
     * /images/educational/article.png) are returned as stored. Bare filenames
     * resolve to storage/app/public/{directory} when that file exists, otherwise
     * to /images/{directory}/{filename}.
     */
    public static function listingImageUrl(?string $image, string $storageDirectory = 'blogs'): ?string
    {
        if ($image === null || trim($image) === '') {
            return null;
        }

        if (self::isStockPlaceholder($image)) {
            return null;
        }

        if (preg_match('#^https?://#i', $image) === 1 || str_starts_with($image, '/')) {
            return $image;
        }

        if (Storage::disk('public')->exists($storageDirectory.'/'.$image)) {
            return Storage::url($storageDirectory.'/'.$image);
        }

        return '/images/'.$storageDirectory.'/'.$image;
    }

    /**
     * Fetch an external image URL server-side and store it on our public
     * disk as a WebP. Returns the public URL (served through /storage/…)
     * or null on any failure (network error, 403 hotlink block, invalid
     * image, GD without WebP support).
     *
     * Filename is content-addressed (sha1 of the source URL) so re-fetches
     * are idempotent and cache-hit instantly. Already-present files are
     * returned without re-downloading.
     *
     * Intended for mirroring vendor product imagery so the catalog never
     * breaks when a vendor rewrites their CDN, turns on hotlink protection,
     * or goes offline (Bioedge, Certified-Pep both block us at the origin
     * as of 2026-11).
     */
    public static function fetchAndStoreExternal(
        string $url,
        string $directory = 'vendor-mirror',
        int $quality = 82,
        int $timeoutSeconds = 12,
        ?array &$diagnostic = null
    ): ?string {
        $diagnostic = ['reason' => null, 'status' => null];

        if (!function_exists('imagewebp')) {
            $diagnostic['reason'] = 'no-gd-webp';
            return null;
        }
        if (self::isStockPlaceholder($url)) {
            $diagnostic['reason'] = 'stock-placeholder';
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $diagnostic['reason'] = 'invalid-scheme';
            return null;
        }

        $filename = sha1($url).'.webp';
        $diskPath = $directory.'/'.$filename;
        if (Storage::disk('public')->exists($diskPath)) {
            $diagnostic['reason'] = 'cache-hit';
            return Storage::url($diskPath);
        }

        // Honest UA. Vendors blocking on IP will still 403; the
        // diagnostic surfaces the status so Colin knows whether to ask
        // them to whitelist peptidemap.com's server IP.
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent'      => 'PeptidemapBot/1.0 (+https://peptidemap.com/robots)',
                    'Accept'          => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                    'Referer'         => 'https://peptidemap.com/',
                ])
                ->withOptions(['allow_redirects' => true, 'verify' => true])
                ->timeout($timeoutSeconds)
                ->get($url);
        } catch (\Throwable $e) {
            $diagnostic['reason'] = 'http-exception';
            return null;
        }

        $diagnostic['status'] = $response->status();
        if (!$response->ok()) {
            $diagnostic['reason'] = 'http-'.$response->status();
            return null;
        }

        $bytes = $response->body();
        if (strlen($bytes) < 128) {
            $diagnostic['reason'] = 'empty-body';
            return null;
        }

        $image = @imagecreatefromstring($bytes);
        if (!$image) {
            $diagnostic['reason'] = 'decode-failed';
            return null;
        }

        Storage::disk('public')->makeDirectory($directory);
        $absPath = storage_path('app/public/'.$diskPath);
        $ok = imagewebp($image, $absPath, $quality);
        imagedestroy($image);
        if (!$ok) {
            $diagnostic['reason'] = 'webp-encode-failed';
            return null;
        }

        $diagnostic['reason'] = 'ok';
        return Storage::url($diskPath);
    }

    /**
     * Hotlinked lorem/stock CDNs are not listing art. Callers should omit
     * the image instead of rendering these.
     */
    public static function isStockPlaceholder(?string $image): bool
    {
        $value = strtolower(trim((string) $image));
        if ($value === '') {
            return false;
        }

        foreach ([
            'picsum.photos',
            'unsplash.com',
            'placehold.co',
            'placeholder.com',
            'dummyimage.com',
            'loremflickr.com',
            'placekitten.com',
            'lorem.space',
        ] as $host) {
            if (str_contains($value, $host)) {
                return true;
            }
        }

        return false;
    }
}

