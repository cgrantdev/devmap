<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Setting;
use App\Helpers\ImageHelper;
use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BannersController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('banners')) {
            $banners = [];
        } else {
            $banners = Banner::with('brand')
                ->orderBy('position')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($banner) {
                    return [
                        'id' => $banner->id,
                        'title' => $banner->title,
                        'image_url' => $banner->image ? asset('storage/' . $banner->image) : $banner->image_url,
                        'image' => $banner->image,
                        'position' => $banner->position,
                        'slot' => $banner->slot,
                        'link' => $banner->link,
                        'brand_id' => $banner->brand_id,
                        'vendor_name' => $banner->brand?->name,
                        'active' => $banner->active,
                        'impressions' => $banner->impressions ?? 0,
                        'clicks' => $banner->clicks ?? 0,
                        'ctr' => $banner->ctr,
                        'start_date' => $banner->start_date?->format('Y-m-d'),
                        'end_date' => $banner->end_date?->format('Y-m-d'),
                    ];
                });
        }

        $brands = \App\Models\Brand::orderBy('name')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                ];
            });

        // Get hero slides from settings. If the setting has never been
        // saved, seed it with the same Certified Peptides slide the live
        // homepage falls back to — otherwise the admin sees an empty form
        // even though the homepage clearly has a banner rendering.
        $heroSlidesSetting = Setting::where('key', 'hero_slides')->first();
        if (!$heroSlidesSetting) {
            $heroSlidesSetting = Setting::create([
                'key' => 'hero_slides',
                'value' => json_encode([[
                    'eyebrow' => 'Featured Partner',
                    'badge' => null,
                    'title' => 'Lab-tested research peptides from Certified Peptides',
                    'title_highlight' => 'Certified Peptides',
                    'subtitle' => '99% HPLC-verified COAs on every batch — BPC-157, TB-500, GHK-Cu, and the full catalog.',
                    'cta_text' => 'Browse catalog',
                    'cta_url' => '/brand/certified-pep/products',
                    'coupon_code' => 'pmap',
                    'target' => '_self',
                    'sponsored' => false,
                    'image' => '/images/banners/certified-peptides-3.png',
                    'image_mobile' => '/images/banners/cert-mobile.png',
                    'order' => 0,
                    'is_active' => true,
                ]]),
            ]);
        }
        $heroSlides = [];

        if ($heroSlidesSetting && $heroSlidesSetting->value) {
            $heroSlides = json_decode($heroSlidesSetting->value, true) ?? [];
            foreach ($heroSlides as &$slide) {
                $slide['image_url'] = $this->resolveHeroImageUrl($slide['image'] ?? null);
                $slide['image_mobile_url'] = $this->resolveHeroImageUrl($slide['image_mobile'] ?? null);
            }
            unset($slide);
        }

        // Get PHP upload limits for display
        $uploadMaxFilesize = $this->convertToBytes(ini_get('upload_max_filesize'));
        $postMaxSize = $this->convertToBytes(ini_get('post_max_size'));
        $maxFileSize = min($uploadMaxFilesize, $postMaxSize);
        $maxFileSizeFormatted = $this->formatBytes($maxFileSize);

        return Inertia::render('Admin/Banners', [
            'banners' => $banners,
            'brands' => $brands,
            'heroSlides' => $heroSlides,
            'maxFileSize' => $maxFileSizeFormatted,
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('banners')) {
            return redirect()->back()->with('error', 'Banners table does not exist. Please run migrations.');
        }

        $validated = $request->validate([
            'image' => 'nullable|image|max:2048',
            'image_url' => 'nullable|url',
            'position' => 'nullable|integer|min:1',
            'link' => 'nullable|url',
            'brand_id' => 'nullable|exists:brands,id',
            'active' => 'boolean',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'image.image' => 'The uploaded file must be an image.',
            'image.max' => 'The image must not be larger than 2MB.',
            'image_url.url' => 'The image URL must be a valid URL.',
        ]);

        // Require either image file or image_url
        if (!$request->hasFile('image') && !$request->input('image_url')) {
            return redirect()->back()->withErrors(['image' => 'Either image file or image URL is required.']);
        }

        if (!isset($validated['position'])) {
            $maxPosition = Banner::max('position') ?? 0;
            $validated['position'] = $maxPosition + 1;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $imageFilename = ImageHelper::convertToWebP($request->file('image'), 'banners');
            $validated['image'] = 'banners/' . $imageFilename;
            $validated['image_url'] = null; // Clear image_url if file is uploaded
        } else {
            unset($validated['image']); // Don't update image field if no file uploaded
        }

        Banner::create($validated);

        return redirect()->back()->with('success', 'Banner added successfully.');
    }

    public function update(Request $request, $id)
    {
        if (!Schema::hasTable('banners')) {
            return redirect()->back()->with('error', 'Banners table does not exist. Please run migrations.');
        }

        $banner = Banner::findOrFail($id);

        $validated = $request->validate([
            'image' => 'nullable|image|max:2048',
            'image_url' => 'nullable|url',
            'position' => 'nullable|integer|min:1',
            'link' => 'nullable|url',
            'brand_id' => 'nullable|exists:brands,id',
            'active' => 'boolean',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'image.image' => 'The uploaded file must be an image.',
            'image.max' => 'The image must not be larger than 2MB.',
            'image_url.url' => 'The image URL must be a valid URL.',
        ]);

        // Require either image file or image_url (unless updating and keeping existing)
        if (!$request->hasFile('image') && !$request->input('image_url') && !$banner->image && !$banner->image_url) {
            return redirect()->back()->withErrors(['image' => 'Either image file or image URL is required.']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($banner->image) {
                ImageHelper::deleteImage(basename($banner->image), 'banners');
            }
            $imageFilename = ImageHelper::convertToWebP($request->file('image'), 'banners');
            $validated['image'] = 'banners/' . $imageFilename;
            $validated['image_url'] = null; // Clear image_url if file is uploaded
        } else {
            unset($validated['image']); // Don't update image field if no file uploaded
        }

        $banner->update($validated);

        return redirect()->back()->with('success', 'Banner updated successfully.');
    }

    public function toggle($id)
    {
        if (!Schema::hasTable('banners')) {
            return redirect()->back()->with('error', 'Banners table does not exist. Please run migrations.');
        }

        $banner = Banner::findOrFail($id);
        $wasActive = $banner->active;
        $banner->active = !$banner->active;
        $banner->save();

        // Log activity only when banner is activated (not deactivated)
        if ($banner->active && !$wasActive) {
            $brand = $banner->brand;
            if ($brand) {
                ActivityLogger::bannerActivated($brand->name, $banner->id);
            }
        }

        return redirect()->back()->with('success', 'Banner status updated.');
    }

    public function destroy($id)
    {
        if (!Schema::hasTable('banners')) {
            return redirect()->back()->with('error', 'Banners table does not exist. Please run migrations.');
        }

        $banner = Banner::findOrFail($id);
        $banner->delete();

        return redirect()->back()->with('success', 'Banner deleted successfully.');
    }

    /**
     * Turn a stored image reference into a URL the browser can load.
     * Accepts three shapes:
     *  - null / empty        → null
     *  - starts with http(s) → returned as-is (external CDN URL)
     *  - starts with '/'     → returned as-is (bundled asset like /images/banners/foo.png)
     *  - anything else       → treated as a filename in storage/app/public/hero_slides/
     */
    private function resolveHeroImageUrl(?string $image): ?string
    {
        if (!$image) return null;
        if (preg_match('#^(https?:)?//#i', $image)) return $image;
        if (str_starts_with($image, '/')) return $image;
        return Storage::url('hero_slides/' . $image);
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
                "hero_slides.{$index}.title"           => 'required|string|max:255',
                "hero_slides.{$index}.title_highlight" => 'nullable|string|max:255',
                "hero_slides.{$index}.eyebrow"         => 'nullable|string|max:64',
                "hero_slides.{$index}.badge"           => 'nullable|string|max:64',
                "hero_slides.{$index}.subtitle"        => 'nullable|string|max:500',
                "hero_slides.{$index}.cta_text"        => 'nullable|string|max:64',
                "hero_slides.{$index}.cta_url"         => 'nullable|string|max:500',
                "hero_slides.{$index}.coupon_code"     => 'nullable|string|max:64',
                "hero_slides.{$index}.image"           => "nullable|image|max:{$maxFileSize}",
                "hero_slides.{$index}.image_mobile"    => "nullable|image|max:{$maxFileSize}",
                "hero_slides.{$index}.order"           => 'required|integer|min:0',
                "hero_slides.{$index}.is_active"       => 'nullable|boolean',
                "hero_slides.{$index}.sponsored"       => 'nullable|boolean',
                "hero_slides.{$index}.target"          => 'nullable|in:_self,_blank',
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
            // ---- Desktop image ----
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

            // ---- Mobile image ---- (same three-way resolve as desktop image)
            $mobileKey = "hero_slides.{$index}.image_mobile";
            if ($request->hasFile($mobileKey)) {
                $file = $request->file($mobileKey);
                if ($file->isValid()) {
                    try {
                        if (isset($existingSlides[$index]['image_mobile']) && $existingSlides[$index]['image_mobile']) {
                            ImageHelper::deleteImage($existingSlides[$index]['image_mobile'], 'hero_slides');
                        }
                        $slide['image_mobile'] = ImageHelper::convertToWebP($file, 'hero_slides');
                    } catch (\Exception $e) {
                        \Log::error('Hero slide mobile image conversion failed', [
                            'slide_index' => $index, 'error' => $e->getMessage(),
                        ]);
                        return redirect()->back()
                            ->withErrors([$mobileKey => 'Failed to process mobile image: ' . $e->getMessage()])
                            ->withInput();
                    }
                }
            } elseif (isset($slide['existing_image_mobile']) && !empty($slide['existing_image_mobile'])) {
                $slide['image_mobile'] = $slide['existing_image_mobile'];
            } elseif (isset($existingSlides[$index]['image_mobile']) && !empty($existingSlides[$index]['image_mobile'])) {
                $slide['image_mobile'] = $existingSlides[$index]['image_mobile'];
            } else {
                $slide['image_mobile'] = null;
            }

            // Coerce booleans coming in as '1'/'0' strings from FormData.
            $toBool = fn ($v) => $v === '1' || $v === 1 || $v === true || $v === 'true';
            $slide['is_active'] = $toBool($slide['is_active'] ?? true);
            $slide['sponsored'] = $toBool($slide['sponsored'] ?? false);

            // Optional string fields — normalize to string or null so JSON stays clean.
            foreach (['title_highlight', 'eyebrow', 'badge', 'subtitle', 'cta_text', 'cta_url', 'coupon_code', 'target'] as $k) {
                $slide[$k] = isset($slide[$k]) && $slide[$k] !== '' ? (string) $slide[$k] : null;
            }

            $slide['order'] = (int) ($slide['order'] ?? $index);

            unset(
                $slide['image_url'], $slide['imagePreview'], $slide['existing_image'],
                $slide['image_mobile_url'], $slide['imageMobilePreview'], $slide['existing_image_mobile'],
            );
        }

        // Store as JSON
        Setting::updateOrCreate(
            ['key' => 'hero_slides'],
            ['value' => json_encode($heroSlides)]
        );
        
        return redirect()->route('admin.banners.index')
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

