<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;

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
                        'link' => $banner->link,
                        'brand_id' => $banner->brand_id,
                        'vendor_name' => $banner->brand?->name,
                        'active' => $banner->active,
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

        return Inertia::render('Admin/Banners', [
            'banners' => $banners,
            'brands' => $brands
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
        $banner->active = !$banner->active;
        $banner->save();

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
}

