<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Helpers\ImageHelper;

class EducationPostsController extends Controller
{
    public function index(Request $request)
    {
        $query = EducationPost::query();
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortType = $request->get('sort_type', 'desc');
        
        // Validate sortBy
        $allowedSortColumns = ['id', 'title', 'status', 'rating', 'published_at', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        // Validate sortType
        $sortType = strtolower($sortType) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortBy, $sortType);
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $posts = $query->paginate($perPage)
            ->through(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'description' => $post->description,
                    'image' => $post->image ? (Storage::disk('public')->exists('education_posts/' . $post->image) ? asset('storage/education_posts/' . $post->image) : (file_exists(public_path('images/peptides/' . $post->image)) ? '/images/peptides/' . $post->image : null)) : null,
                    'rating' => $post->rating,
                    'rating_count' => $post->rating_count,
                    'published_at' => $post->published_at ? $post->published_at->format('Y-m-d') : null,
                    'status' => $post->status,
                    'created_at' => $post->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Admin/EducationPosts', [
            'posts' => $posts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/EducationPostEdit', [
            'post' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:education_posts,slug',
            'description' => 'nullable|string',
            'overview' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'rating' => 'nullable|numeric|min:0|max:5',
            'rating_count' => 'nullable|integer|min:0',
            'key_effects' => 'nullable|array',
            'key_effects.*' => 'string',
            'accordion_sections' => 'nullable|array',
            'accordion_sections.*.title' => 'required|string',
            'accordion_sections.*.content' => 'required|string',
            'shop_url' => 'nullable|url|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // Auto-generate slug from title
        $validated['slug'] = Str::slug($validated['title']);

        // Set published_at when status is published
        if ($validated['status'] === 'published' && empty($request->published_at)) {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        // Handle image upload and convert to WebP
        if ($request->hasFile('image')) {
            $validated['image'] = ImageHelper::convertToWebP($request->file('image'), 'education_posts');
        } else {
            unset($validated['image']);
        }

        EducationPost::create($validated);

        return redirect()->route('admin.education-posts.index')
            ->with('success', 'Education post created successfully.');
    }

    public function edit($id)
    {
        $post = EducationPost::findOrFail($id);

        return Inertia::render('Admin/EducationPostEdit', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'description' => $post->description,
                'overview' => $post->overview,
                'content' => $post->content,
                'image' => $post->image,
                'rating' => $post->rating,
                'rating_count' => $post->rating_count,
                'key_effects' => $post->key_effects ?? [],
                'accordion_sections' => $post->accordion_sections ?? [],
                'shop_url' => $post->shop_url,
                'published_at' => $post->published_at ? $post->published_at->format('Y-m-d') : null,
                'status' => $post->status,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $post = EducationPost::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:education_posts,slug,' . $id,
            'description' => 'nullable|string',
            'overview' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'rating' => 'nullable|numeric|min:0|max:5',
            'rating_count' => 'nullable|integer|min:0',
            'key_effects' => 'nullable|array',
            'key_effects.*' => 'string',
            'accordion_sections' => 'nullable|array',
            'accordion_sections.*.title' => 'required|string',
            'accordion_sections.*.content' => 'required|string',
            'shop_url' => 'nullable|url|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // Auto-generate slug from title
        $validated['slug'] = Str::slug($validated['title']);

        // Handle status change and published_at
        $oldStatus = $post->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            // Status changed to published - set published_at to now
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            // Status is draft - clear published_at
            $validated['published_at'] = null;
        } else {
            // Status remains published - keep existing published_at
            unset($validated['published_at']);
        }

        // Handle image upload and convert to WebP
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($post->image) {
                ImageHelper::deleteImage($post->image, 'education_posts');
            }
            $validated['image'] = ImageHelper::convertToWebP($request->file('image'), 'education_posts');
        } else {
            unset($validated['image']);
        }

        $post->update($validated);

        return redirect()->route('admin.education-posts.index')
            ->with('success', 'Education post updated successfully.');
    }

    public function destroy($id)
    {
        $post = EducationPost::findOrFail($id);
        $post->delete();

        return redirect()->route('admin.education-posts.index')
            ->with('success', 'Education post deleted successfully.');
    }
}
