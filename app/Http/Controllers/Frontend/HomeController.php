<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Location;
use App\Models\Blog;
use Illuminate\Support\Str;
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
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ])
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

        // Top blogs / research insights
        $topBlogs = Blog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->take(4)
            ->get()
            ->map(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'description' => $blog->description,
                    'image' => $blog->image ? Storage::url('blogs/' . $blog->image) : null,
                    'date' => $blog->published_at ? $blog->published_at->format('M d, Y') : null,
                    'readTime' => $blog->read_time ?? '5 min read',
                ];
            });

        // Top brands (vendors) limited list for homepage
        $topBrands = Brand::where('is_active', true)
            ->with(['vendorSetting', 'vendorSetting.location'])
            ->withCount([
                'products as products_count' => function ($q) {
                    $q->visible()->where('status', 'active');
                }
            ])
            ->orderByDesc('products_count')
            ->take(5)
            ->get()
            ->map(function ($brand) {
                // Get most common location from products
                $locationId = Product::visible()
                    ->where('status', 'active')
                    ->where('brand_id', $brand->id)
                    ->whereNotNull('location_id')
                    ->selectRaw('location_id, COUNT(*) as count')
                    ->groupBy('location_id')
                    ->orderByDesc('count')
                    ->first()
                    ?->location_id;

                if (!$locationId) {
                    $locationId = Product::visible()
                        ->where('status', 'active')
                        ->where('brand_id', $brand->id)
                        ->whereNotNull('location_id')
                        ->value('location_id');
                }

                // Prefer vendor setting location if set; otherwise fall back to product-derived location
                $location = null;
                if ($brand->vendorSetting && $brand->vendorSetting->location) {
                    $location = $brand->vendorSetting->location;
                } elseif ($locationId) {
                    $location = Location::find($locationId);
                }

                $logoUrl = null;
                if ($brand->vendorSetting && $brand->vendorSetting->logo) {
                    $logoUrl = asset('storage/' . $brand->vendorSetting->logo);
                }

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'product_count' => $brand->products_count,
                    'slug' => $brand->slug ?? Str::slug($brand->name),
                    'initials' => $this->getInitials($brand->name),
                    'logo' => $logoUrl,
                    'location' => $location ? $location->name : null,
                    'rating' => number_format($brand->rating_average ?? 0, 2, '.', ''),
                    'reviews' => (int) ($brand->rating_count ?? 0),
                ];
            });

        return Inertia::render('Frontend/Welcome', [
            'heroSlides' => $heroSlides,
            'productGroups' => $categories,
            'topBrands' => $topBrands,
            'topBlogs' => $topBlogs,
        ]);
    }

    /**
     * Get initials from brand name
     */
    private function getInitials($name)
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }
}

