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
                'outline' => $blog->outline,
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
                'outline' => $featured->outline,
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
                    'outline' => $b->outline,
                    'image' => $imageUrl,
                    'readTime' => $b->read_time ?? '19 Min Read',
                    'date' => $b->published_at ? $b->published_at->format('d M Y') : null,
                ];
            });

        $imageUrl = $blog->image ? Storage::url('blogs/' . $blog->image) : null;
        
        // Use blog_type for category tag, fallback to computed tag if not set
        $combinedContent = $blog->introduction . ' ' . $blog->detailed_analysis . ' ' . $blog->conclusion;
        $categoryTag = $blog->blog_type ?: $this->getCategoryTag($blog->title, $blog->description, $combinedContent);
        
        return Inertia::render('Frontend/BlogDetail', [
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'outline' => $blog->outline,
                'introduction' => $blog->introduction,
                'key_points' => $blog->key_points ?? [],
                'detailed_analysis' => $blog->detailed_analysis,
                'conclusion' => $blog->conclusion,
                'tags' => $blog->tags ?? [],
                'author_name' => $blog->author_name,
                'author_job' => $blog->author_job,
                'blog_type' => $blog->blog_type,
                'image' => $imageUrl,
                'readTime' => $blog->read_time ?? '19 Min Read',
                'date' => $blog->published_at ? $blog->published_at->format('F d, Y') : null,
                'is_featured' => $blog->is_featured ?? false,
                'categoryTag' => $categoryTag,
            ],
            'related' => $related,
        ]);
    }

    /**
     * Get category tag based on content
     */
    private function getCategoryTag($title, $description, $content = '')
    {
        $combined = strtolower($title . ' ' . $description . ' ' . $content);

        if (stripos($combined, 'fda') !== false || stripos($combined, 'regulation') !== false || 
            stripos($combined, 'regulatory') !== false || stripos($combined, 'compounding') !== false) {
            return 'Regulation';
        }

        if (stripos($combined, 'study') !== false || stripos($combined, 'research') !== false || 
            stripos($combined, 'clinical') !== false || stripos($combined, 'trial') !== false) {
            return 'Research';
        }

        if (stripos($combined, 'market') !== false || stripos($combined, 'industry') !== false || 
            stripos($combined, 'price') !== false || stripos($combined, 'pricing') !== false) {
            return 'Industry';
        }

        if (stripos($combined, 'guide') !== false || stripos($combined, 'how to') !== false || 
            stripos($combined, 'tutorial') !== false || stripos($combined, 'education') !== false ||
            stripos($combined, 'coa') !== false || stripos($combined, 'purity') !== false) {
            return 'Guides';
        }

        if (stripos($combined, 'success') !== false || stripos($combined, 'story') !== false || 
            stripos($combined, 'experience') !== false || stripos($combined, 'user') !== false ||
            stripos($combined, 'community') !== false) {
            return 'Community';
        }

        return 'Research'; // Default
    }
}
