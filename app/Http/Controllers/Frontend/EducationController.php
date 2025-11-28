<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\EducationPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EducationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        // Get all education posts
        $query = EducationPost::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');

        $posts = $query->paginate($perPage)->withQueryString();

        // Format posts for frontend
        $formattedPosts = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'description' => $post->description,
                'image' => $post->image,
            ];
        });

        return Inertia::render('Frontend/EducationListing', [
            'posts' => [
                'data' => $formattedPosts,
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
            ],
        ]);
    }

    public function show($slug)
    {
        $post = EducationPost::where('slug', $slug)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        // Get related posts (exclude current)
        $related = EducationPost::where('id', '!=', $post->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'description' => $p->description,
                    'image' => $p->image,
                ];
            });

        return Inertia::render('Frontend/EducationDetail', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'description' => $post->description,
                'overview' => $post->overview,
                'content' => $post->content,
                'image' => $post->image,
                'rating' => number_format($post->rating, 2, '.', ''),
                'rating_count' => $post->rating_count,
                'key_effects' => $post->key_effects ?? [],
                'accordion_sections' => $post->accordion_sections ?? [],
                'shop_url' => $post->shop_url,
            ],
            'related' => $related,
        ]);
    }
}
