<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    /**
     * Resolve a vendor logo URL. Returns the real file if it exists,
     * otherwise a generated placeholder from ui-avatars.com.
     */
    public static function resolveVendorLogo(?string $logoPath, string $vendorName): string
    {
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            return asset('storage/' . $logoPath);
        }
        $encoded = urlencode($vendorName);
        return "https://ui-avatars.com/api/?name={$encoded}&background=4F46E5&color=fff&size=128&font-size=0.38&bold=true&format=svg";
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

