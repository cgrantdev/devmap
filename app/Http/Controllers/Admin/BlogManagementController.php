<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Helpers\ImageHelper;

class BlogManagementController extends Controller
{
    public function index(Request $request)
    {
        $blogs = Blog::orderBy('created_at', 'desc')
            ->get()
            ->map(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'blog_type' => $blog->blog_type,
                    'author_name' => $blog->author_name,
                    'description' => $blog->description,
                    'image' => $blog->image ? (Storage::disk('public')->exists('blogs/' . $blog->image) ? asset('storage/blogs/' . $blog->image) : '/images/blogs/' . $blog->image) : null,
                    'read_time' => $blog->read_time,
                    'published_at' => $blog->published_at ? $blog->published_at->format('M j, Y') : null,
                    'is_featured' => $blog->is_featured,
                    'status' => $blog->status,
                    'created_at' => $blog->created_at->format('M j, Y'),
                ];
            });

        return Inertia::render('Admin/Blogs', [
            'blogs' => $blogs,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/BlogEdit', [
            'blog' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blog_type' => 'nullable|string|in:Regulation,Research,Community,Guides,Industry',
            'author_name' => 'nullable|string|max:255',
            'author_job' => 'nullable|string|max:255',
            'outline' => 'nullable|string',
            'slug' => 'nullable|string|max:255|unique:blogs,slug',
            'description' => 'nullable|string',
            'introduction' => 'nullable|string',
            'key_points' => 'nullable|array',
            'key_points.*' => 'nullable|string',
            'detailed_analysis' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'read_time' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|url|max:500',
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
            $validated['image'] = ImageHelper::convertToWebP($request->file('image'), 'blogs');
        } else {
            unset($validated['image']);
        }

        $blog = Blog::create($validated);

        return redirect("/admin/blogs/{$blog->id}/edit")
            ->with('success', 'Blog post created successfully.');
    }

    public function edit($id)
    {
        $blog = Blog::findOrFail($id);

        return Inertia::render('Admin/BlogEdit', [
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'blog_type' => $blog->blog_type,
                'author_name' => $blog->author_name,
                'author_job' => $blog->author_job,
                'outline' => $blog->outline,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'introduction' => $blog->introduction,
                'key_points' => $blog->key_points ?? [],
                'detailed_analysis' => $blog->detailed_analysis,
                'conclusion' => $blog->conclusion,
                'tags' => $blog->tags ?? [],
                'content' => $blog->content,
                'image' => $blog->image ? (Storage::disk('public')->exists('blogs/' . $blog->image) ? asset('storage/blogs/' . $blog->image) : '/images/blogs/' . $blog->image) : null,
                'read_time' => $blog->read_time,
                'published_at' => $blog->published_at ? $blog->published_at->format('Y-m-d') : null,
                'is_featured' => $blog->is_featured,
                'status' => $blog->status,
                'seo_page_title' => $blog->seo_page_title,
                'seo_description' => $blog->seo_description,
                'seo_og_title' => $blog->seo_og_title,
                'seo_og_description' => $blog->seo_og_description,
                'seo_og_image' => $blog->seo_og_image,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'blog_type' => 'nullable|string|in:Regulation,Research,Community,Guides,Industry',
            'author_name' => 'nullable|string|max:255',
            'author_job' => 'nullable|string|max:255',
            'outline' => 'nullable|string',
            'slug' => 'nullable|string|max:255|unique:blogs,slug,' . $id,
            'description' => 'nullable|string',
            'introduction' => 'nullable|string',
            'key_points' => 'nullable|array',
            'key_points.*' => 'nullable|string',
            'detailed_analysis' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'read_time' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|url|max:500',
        ]);

        // Auto-generate slug from title
        $validated['slug'] = Str::slug($validated['title']);

        // Handle status change and published_at
        $oldStatus = $blog->status;
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
            if ($blog->image) {
                ImageHelper::deleteImage($blog->image, 'blogs');
            }
            $validated['image'] = ImageHelper::convertToWebP($request->file('image'), 'blogs');
        } else {
            unset($validated['image']);
        }

        $blog->update($validated);

        return redirect()->back()
            ->with('success', 'Blog post updated successfully.');
    }

    public function quickUpdate(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // Auto-generate slug from title if title changed
        if ($validated['title'] !== $blog->title) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Handle status change and published_at
        $oldStatus = $blog->status;
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

        $blog->update($validated);

        return redirect()->back()->with('success', 'Blog post updated successfully.');
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog post deleted successfully.');
    }
}
