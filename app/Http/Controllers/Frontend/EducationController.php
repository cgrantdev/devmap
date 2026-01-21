<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EducationController extends Controller
{
    public function index(Request $request)
    {
        // Get all active product categories (display all, not just ones with education posts)
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
                
                // Use only category image, no fallbacks
                $image = null;
                if ($category->image_url) {
                    $image = \Illuminate\Support\Facades\Storage::url('categories/' . $category->image_url);
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

        return Inertia::render('Frontend/Education', [
            'productGroups' => $categories,
        ]);
    }

    public function show($slug)
    {
        // Find category by slug and get its education post
        $category = ProductCategory::where('slug', $slug)
            ->where('is_active', true)
            ->with('educationPost')
            ->firstOrFail();

        $post = $category->educationPost;
        
        // If no education post exists or not published, show a basic page or create one
        // For now, we'll show the category info even if no education post exists
        if (!$post || $post->status !== 'published' || !$post->published_at || $post->published_at->isFuture()) {
            // Show a basic education page with category info
            return Inertia::render('Frontend/EducationDetail', [
                'post' => [
                    'id' => null,
                    'title' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'overview' => $category->description,
                    'content' => null,
                    'image' => $category->image_url ? \Illuminate\Support\Facades\Storage::url('categories/' . $category->image_url) : null,
                    'rating' => '0.00',
                    'rating_count' => 0,
                    'key_effects' => [],
                    'accordion_sections' => [],
                    'shop_url' => '/product/' . $category->slug,
                ],
                'category' => [
                    'id' => $category->id,
                    'name' => strtoupper($category->name),
                    'slug' => $category->slug,
                ],
                'related' => [],
            ]);
        }

        // Get related posts from other categories
        $related = EducationPost::where('id', '!=', $post->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('category')
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->category ? $p->category->slug : $p->slug,
                    'description' => $p->description,
                    'image' => $p->image ? \Illuminate\Support\Facades\Storage::url('education_posts/' . $p->image) : null,
                ];
            });

        return Inertia::render('Frontend/EducationDetail', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $category->slug,
                'description' => $post->description,
                'overview' => $post->overview,
                'content' => $post->content,
                'image' => $post->image ? \Illuminate\Support\Facades\Storage::url('education_posts/' . $post->image) : null,
                'rating' => number_format($post->rating, 2, '.', ''),
                'rating_count' => $post->rating_count,
                'key_effects' => $post->key_effects ?? [],
                'accordion_sections' => $post->accordion_sections ?? [],
                'shop_url' => '/product/' . $category->slug, // Link to product category page
            ],
            'category' => [
                'id' => $category->id,
                'name' => strtoupper($category->name),
                'slug' => $category->slug,
            ],
            'related' => $related,
        ]);
    }
}
