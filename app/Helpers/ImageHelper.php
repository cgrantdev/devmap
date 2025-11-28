<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImageHelper
{
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
                break;
            case 'image/gif':
                $image = imagecreatefromgif($uploadedFile->getRealPath());
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

