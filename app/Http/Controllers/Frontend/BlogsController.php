<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        // Get featured blog
        $featured = Blog::where('is_featured', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->first();

        // Get all blogs (excluding featured if it exists)
        $query = Blog::whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');

        if ($featured) {
            $query->where('id', '!=', $featured->id);
        }

        $blogs = $query->paginate($perPage)->withQueryString();

        // Format blogs for frontend
        $formattedBlogs = $blogs->map(function ($blog) {
            return [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'image' => $blog->image,
                'readTime' => $blog->read_time ?? '19 Min Read',
                'date' => $blog->published_at ? $blog->published_at->format('d M Y') : null,
            ];
        });

        $formattedFeatured = null;
        if ($featured) {
            $formattedFeatured = [
                'id' => $featured->id,
                'title' => $featured->title,
                'slug' => $featured->slug,
                'description' => $featured->description,
                'content' => $featured->content,
                'image' => $featured->image,
                'readTime' => $featured->read_time ?? '19 Min Read',
                'date' => $featured->published_at ? $featured->published_at->format('d M Y') : null,
            ];
        }

        return Inertia::render('Frontend/BlogListing', [
            'featured' => $formattedFeatured,
            'blogs' => [
                'data' => $formattedBlogs,
                'current_page' => $blogs->currentPage(),
                'last_page' => $blogs->lastPage(),
                'per_page' => $blogs->perPage(),
                'total' => $blogs->total(),
                'from' => $blogs->firstItem(),
                'to' => $blogs->lastItem(),
            ],
        ]);
    }

    public function show($slug)
    {
        $blog = Blog::where('slug', $slug)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        // Get related blogs (exclude current)
        $related = Blog::where('id', '!=', $blog->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'title' => $b->title,
                    'slug' => $b->slug,
                    'description' => $b->description,
                    'image' => $b->image,
                    'readTime' => $b->read_time ?? '19 Min Read',
                    'date' => $b->published_at ? $b->published_at->format('d M Y') : null,
                ];
            });

        return Inertia::render('Frontend/BlogDetail', [
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'content' => $blog->content,
                'image' => $blog->image,
                'readTime' => $blog->read_time ?? '19 Min Read',
                'date' => $blog->published_at ? $blog->published_at->format('d M Y') : null,
            ],
            'related' => $related,
        ]);
    }
}
