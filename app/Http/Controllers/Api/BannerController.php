<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Get a banner for a specific slot. Rotates through active banners
     * weighted by position (lower position = higher priority). Each
     * request logs an impression.
     *
     * GET /api/banners/{slot}
     */
    public function show(string $slot): JsonResponse
    {
        $banners = Banner::live()
            ->forSlot($slot)
            ->with('brand:id,name,slug')
            ->orderBy('position')
            ->get();

        if ($banners->isEmpty()) {
            return response()->json(['banner' => null]);
        }

        // Simple rotation: pick by current minute % count so it changes
        // periodically without needing client-side state.
        $index = now()->minute % $banners->count();
        $banner = $banners[$index];

        // Log impression (fire-and-forget, don't slow the response)
        Banner::where('id', $banner->id)->increment('impressions');

        return response()->json([
            'banner' => [
                'id' => $banner->id,
                'title' => $banner->title,
                'description' => $banner->description,
                'image' => $banner->image_url ?: ($banner->image ? asset('storage/banners/' . $banner->image) : null),
                'link' => $banner->link,
                'brand_name' => $banner->brand?->name,
                'brand_slug' => $banner->brand?->slug,
            ],
        ]);
    }

    /**
     * Log a click on a banner.
     *
     * POST /api/banners/{id}/click
     */
    public function click(Banner $banner): JsonResponse
    {
        Banner::where('id', $banner->id)->increment('clicks');

        return response()->json(['ok' => true]);
    }
}
