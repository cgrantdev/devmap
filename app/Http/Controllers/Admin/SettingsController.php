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

        return Inertia::render('Admin/Settings', [
            'heroSlides' => $heroSlides,
        ]);
    }

    public function updateHeroSlides(Request $request)
    {
        // Get hero slides data as array from request
        $heroSlides = $request->input('hero_slides', []);
        
        // Validate slides data
        foreach ($heroSlides as $index => $slide) {
            $request->validate([
                "hero_slides.{$index}.title" => 'required|string|max:255',
                "hero_slides.{$index}.subtitle" => 'nullable|string|max:255',
                "hero_slides.{$index}.cta_text" => 'nullable|string|max:255',
                "hero_slides.{$index}.cta_url" => 'nullable|string|max:255',
                "hero_slides.{$index}.image" => 'nullable|image|max:2048',
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
                // Delete old image if exists
                if (isset($existingSlides[$index]['image']) && $existingSlides[$index]['image']) {
                    ImageHelper::deleteImage($existingSlides[$index]['image'], 'hero_slides');
                }
                $slide['image'] = ImageHelper::convertToWebP($file, 'hero_slides');
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
}
