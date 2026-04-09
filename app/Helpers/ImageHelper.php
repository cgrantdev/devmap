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
}

