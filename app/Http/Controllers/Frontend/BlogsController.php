<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class BlogsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        // Get featured blog
        $featured = Blog::where('status', 'published')
            ->where('is_featured', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->first();

        // Get all blogs (excluding featured if it exists)
        $query = Blog::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');

        if ($featured) {
            $query->where('id', '!=', $featured->id);
        }

        $blogs = $query->paginate($perPage)->withQueryString();

        // Format blogs for frontend
        $formattedBlogs = $blogs->map(function ($blog) {
            $imageUrl = null;
            if ($blog->image) {
                $imageUrl = Storage::url('blogs/' . $blog->image);
            }
            return [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'image' => $imageUrl,
                'readTime' => $blog->read_time ?? '19 Min Read',
                'date' => $blog->published_at ? $blog->published_at->format('d M Y') : null,
            ];
        });

        $formattedFeatured = null;
        if ($featured) {
            $featuredImage = $featured->image ? Storage::url('blogs/' . $featured->image) : null;
            $formattedFeatured = [
                'id' => $featured->id,
                'title' => $featured->title,
                'slug' => $featured->slug,
                'description' => $featured->description,
                'content' => $featured->content,
                'image' => $featuredImage,
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
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        // Get related blogs (exclude current)
        $related = Blog::where('id', '!=', $blog->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(4)
            ->get()
            ->map(function ($b) {
                $imageUrl = $b->image ? Storage::url('blogs/' . $b->image) : null;
                return [
                    'id' => $b->id,
                    'title' => $b->title,
                    'slug' => $b->slug,
                    'description' => $b->description,
                    'image' => $imageUrl,
                    'readTime' => $b->read_time ?? '19 Min Read',
                    'date' => $b->published_at ? $b->published_at->format('d M Y') : null,
                ];
            });

        $imageUrl = $blog->image ? Storage::url('blogs/' . $blog->image) : null;
        return Inertia::render('Frontend/BlogDetail', [
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'content' => $blog->content,
                'image' => $imageUrl,
                'readTime' => $blog->read_time ?? '19 Min Read',
                'date' => $blog->published_at ? $blog->published_at->format('d M Y') : null,
            ],
            'related' => $related,
        ]);
    }
}
