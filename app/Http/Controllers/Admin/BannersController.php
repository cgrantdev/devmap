<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
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
            $banners = Banner::with('vendor')
                ->orderBy('position')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($banner) {
                    return [
                        'id' => $banner->id,
                        'title' => $banner->title,
                        'image_url' => $banner->image_url,
                        'position' => $banner->position,
                        'link' => $banner->link,
                        'vendor_id' => $banner->vendor_id,
                        'vendor_name' => $banner->vendor?->name,
                        'active' => $banner->active,
                    ];
                });
        }

        return Inertia::render('Admin/Banners', [
            'banners' => $banners
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('banners')) {
            return redirect()->back()->with('error', 'Banners table does not exist. Please run migrations.');
        }

        $validated = $request->validate([
            'image_url' => 'required|url',
            'position' => 'nullable|integer|min:1',
            'link' => 'nullable|url',
            'vendor_name' => 'nullable|string',
            'vendor_id' => 'nullable|exists:users,id',
            'active' => 'boolean',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if (!isset($validated['position'])) {
            $maxPosition = Banner::max('position') ?? 0;
            $validated['position'] = $maxPosition + 1;
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
            'image_url' => 'required|url',
            'position' => 'nullable|integer|min:1',
            'link' => 'nullable|url',
            'vendor_name' => 'nullable|string',
            'vendor_id' => 'nullable|exists:users,id',
            'active' => 'boolean',
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

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

