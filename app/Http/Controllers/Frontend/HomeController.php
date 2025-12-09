<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Hero slides (active, ordered)
        $heroSlides = [];
        $heroSlidesSetting = Setting::where('key', 'hero_slides')->first();
        if ($heroSlidesSetting && $heroSlidesSetting->value) {
            $slides = json_decode($heroSlidesSetting->value, true) ?? [];

            $activeSlides = array_filter($slides, fn ($slide) => isset($slide['is_active']) && $slide['is_active']);
            usort($activeSlides, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

            foreach ($activeSlides as $slide) {
                $heroSlides[] = [
                    'title' => $slide['title'] ?? '',
                    'subtitle' => $slide['subtitle'] ?? '',
                    'ctaText' => $slide['cta_text'] ?? '',
                    'ctaUrl' => $slide['cta_url'] ?? '',
                    'image' => isset($slide['image']) && $slide['image']
                        ? Storage::url('hero_slides/' . $slide['image'])
                        : null,
                ];
            }
        }

        // Education categories (align with education page)
        $categories = ProductCategory::where('is_active', true)
            ->withCount('products')
            ->with('educationPost')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                $educationPost = $category->educationPost;
                $image = null;
                if ($category->image_url) {
                    $image = Storage::url('categories/' . $category->image_url);
                }

                return [
                    'id' => $category->id,
                    'name' => strtoupper($category->name),
                    'slug' => $category->slug,
                    'total_items' => $category->products_count,
                    'image' => $image,
                    'description' => $educationPost ? $educationPost->description : $category->description,
                ];
            });

        return Inertia::render('Frontend/Welcome', [
            'heroSlides' => $heroSlides,
            'productGroups' => $categories,
        ]);
    }
}

