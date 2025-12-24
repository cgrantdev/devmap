<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Helpers\ImageHelper;

class SettingsController extends Controller
{
    public function index()
    {
        // Get hero slides from settings
        $heroSlidesSetting = Setting::where('key', 'hero_slides')->first();
        $heroSlides = [];
        
        if ($heroSlidesSetting && $heroSlidesSetting->value) {
            $heroSlides = json_decode($heroSlidesSetting->value, true) ?? [];
            // Add full image URLs
            foreach ($heroSlides as &$slide) {
                if (isset($slide['image']) && $slide['image']) {
                    $slide['image_url'] = Storage::url('hero_slides/' . $slide['image']);
                } else {
                    $slide['image_url'] = null;
                }
            }
        }

        // Get PHP upload limits for display
        $uploadMaxFilesize = $this->convertToBytes(ini_get('upload_max_filesize'));
        $postMaxSize = $this->convertToBytes(ini_get('post_max_size'));
        $maxFileSize = min($uploadMaxFilesize, $postMaxSize);
        $maxFileSizeFormatted = $this->formatBytes($maxFileSize);

        // Get general settings
        $settings = [];
        $settingsKeys = [
            'site_name',
            'site_description',
            'contact_email',
            'email_new_vendor_registrations',
            'email_new_product_submissions',
            'email_review_moderation_alerts',
            'require_email_verification',
            'enable_2fa_for_admins',
            'maintenance_mode',
            'allow_registration',
            'require_email_verification_platform',
            'banner_ad_price',
            'top_vendor_spot_price',
        ];

        foreach ($settingsKeys as $key) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                $value = $setting->value;
                // Convert string booleans and numbers
                if ($value === 'true' || $value === '1') {
                    $value = true;
                } elseif ($value === 'false' || $value === '0') {
                    $value = false;
                } elseif (is_numeric($value)) {
                    $value = (float) $value;
                }
                $settings[$key] = $value;
            }
        }

        return Inertia::render('Admin/Settings', [
            'heroSlides' => $heroSlides,
            'maxFileSize' => $maxFileSizeFormatted,
            'settings' => $settings,
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'contact_email' => 'required|email|max:255',
            'email_new_vendor_registrations' => 'boolean',
            'email_new_product_submissions' => 'boolean',
            'email_review_moderation_alerts' => 'boolean',
            'require_email_verification' => 'boolean',
            'enable_2fa_for_admins' => 'boolean',
            'maintenance_mode' => 'boolean',
            'allow_registration' => 'boolean',
            'require_email_verification_platform' => 'boolean',
            'banner_ad_price' => 'nullable|numeric|min:0',
            'top_vendor_spot_price' => 'nullable|numeric|min:0',
        ]);

        // Save each setting
        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        return redirect()->route('admin.settings')
            ->with('success', 'Settings updated successfully.');
    }

    public function updateHeroSlides(Request $request)
    {
        // Get PHP upload limits
        $uploadMaxFilesize = $this->convertToBytes(ini_get('upload_max_filesize'));
        $postMaxSize = $this->convertToBytes(ini_get('post_max_size'));
        
        // Use the smaller of the two limits (in KB for Laravel validation)
        $maxFileSize = min($uploadMaxFilesize, $postMaxSize) / 1024; // Convert to KB
        
        // Get hero slides data as array from request
        $heroSlides = $request->input('hero_slides', []);
        
        // Validate slides data
        foreach ($heroSlides as $index => $slide) {
            $request->validate([
                "hero_slides.{$index}.title" => 'required|string|max:255',
                "hero_slides.{$index}.subtitle" => 'nullable|string|max:255',
                "hero_slides.{$index}.cta_text" => 'nullable|string|max:255',
                "hero_slides.{$index}.cta_url" => 'nullable|string|max:255',
                "hero_slides.{$index}.image" => "nullable|image|max:{$maxFileSize}",
                "hero_slides.{$index}.order" => 'required|integer|min:0',
                "hero_slides.{$index}.is_active" => 'nullable|boolean',
            ]);
        }
        
        // Get existing slides to preserve images
        $existingSetting = Setting::where('key', 'hero_slides')->first();
        $existingSlides = [];
        if ($existingSetting && $existingSetting->value) {
            $existingSlides = json_decode($existingSetting->value, true) ?? [];
        }

        // Handle image uploads and prepare data for storage
        foreach ($heroSlides as $index => &$slide) {
            // Check if there's a new image file uploaded for this slide
            $imageKey = "hero_slides.{$index}.image";
            
            if ($request->hasFile($imageKey)) {
                $file = $request->file($imageKey);
                
                // Check if file upload was successful
                if (!$file->isValid()) {
                    $errorMessage = 'File upload failed. ';
                    switch ($file->getError()) {
                        case UPLOAD_ERR_INI_SIZE:
                        case UPLOAD_ERR_FORM_SIZE:
                            $maxSize = $this->convertToBytes(ini_get('upload_max_filesize'));
                            $errorMessage .= 'File is too large. Maximum size is ' . $this->formatBytes($maxSize) . '.';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $errorMessage .= 'File was only partially uploaded.';
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $errorMessage .= 'No file was uploaded.';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $errorMessage .= 'Missing temporary folder.';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $errorMessage .= 'Failed to write file to disk.';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $errorMessage .= 'File upload stopped by extension.';
                            break;
                        default:
                            $errorMessage .= 'Unknown upload error (code: ' . $file->getError() . ').';
                    }
                    
                    return redirect()->back()
                        ->withErrors([$imageKey => $errorMessage])
                        ->withInput();
                }
                
                try {
                    // Delete old image if exists
                if (isset($existingSlides[$index]['image']) && $existingSlides[$index]['image']) {
                    ImageHelper::deleteImage($existingSlides[$index]['image'], 'hero_slides');
                }
                    $slide['image'] = ImageHelper::convertToWebP($file, 'hero_slides');
                } catch (\Exception $e) {
                    // Log the error for debugging
                    \Log::error('Hero slide image conversion failed', [
                        'slide_index' => $index,
                        'error' => $e->getMessage(),
                        'file_name' => $file->getClientOriginalName(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                    
                    // If image conversion fails, return validation error
                    return redirect()->back()
                        ->withErrors([$imageKey => 'Failed to process image: ' . $e->getMessage()])
                        ->withInput();
                }
            } elseif (isset($slide['existing_image']) && !empty($slide['existing_image'])) {
                // Keep existing image from existing_image field
                $slide['image'] = $slide['existing_image'];
                unset($slide['existing_image']);
            } elseif (isset($existingSlides[$index]['image']) && !empty($existingSlides[$index]['image'])) {
                // Keep existing image from database if no new upload and no existing_image field
                $slide['image'] = $existingSlides[$index]['image'];
            } else {
                // If no image provided and no existing image, set to null
                $slide['image'] = null;
            }
            
            // Convert is_active to boolean
            $slide['is_active'] = isset($slide['is_active']) && ($slide['is_active'] === '1' || $slide['is_active'] === true || $slide['is_active'] === 1);
            
            // Ensure order is integer
            $slide['order'] = (int) ($slide['order'] ?? $index);
            
            // Remove image_url and imagePreview from stored data
            unset($slide['image_url'], $slide['imagePreview'], $slide['existing_image']);
        }

        // Store as JSON
        Setting::updateOrCreate(
            ['key' => 'hero_slides'],
            ['value' => json_encode($heroSlides)]
        );
        
        return redirect()->route('admin.settings')
            ->with('success', 'Hero slides updated successfully.');
    }
    
    /**
     * Convert PHP ini size string to bytes
     * 
     * @param string $size Size string (e.g., "2M", "1024K", "1G")
     * @return int Size in bytes
     */
    private function convertToBytes($size)
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch ($last) {
            case 'g':
                $size *= 1024;
                // fall through
            case 'm':
                $size *= 1024;
                // fall through
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }
    
    /**
     * Format bytes to human readable format
     * 
     * @param int $bytes Size in bytes
     * @return string Formatted size (e.g., "2 MB", "1.5 GB")
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
