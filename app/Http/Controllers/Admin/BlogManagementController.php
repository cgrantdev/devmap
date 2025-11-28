<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class BlogManagementController extends Controller
{
    public function index()
    {
        $blogs = Blog::orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($blog) {
                return [
                    'id' => $blog->id,
                    'title' => $blog->title,
                    'slug' => $blog->slug,
                    'description' => $blog->description,
                    'image' => $blog->image ? (Storage::disk('public')->exists('blogs/' . $blog->image) ? asset('storage/blogs/' . $blog->image) : '/images/blogs/' . $blog->image) : null,
                    'read_time' => $blog->read_time,
                    'published_at' => $blog->published_at ? $blog->published_at->format('Y-m-d') : null,
                    'is_featured' => $blog->is_featured,
                    'status' => $blog->status,
                    'created_at' => $blog->created_at->format('Y-m-d H:i'),
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
            'slug' => 'nullable|string|max:255|unique:blogs,slug',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'read_time' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
        ]);

        // Auto-generate slug from title
        $validated['slug'] = Str::slug($validated['title']);

        // Set published_at when status is published
        if ($validated['status'] === 'published' && empty($request->published_at)) {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogs', 'public');
            $validated['image'] = basename($imagePath);
        } else {
            unset($validated['image']);
        }

        Blog::create($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog post created successfully.');
    }

    public function edit($id)
    {
        $blog = Blog::findOrFail($id);

        return Inertia::render('Admin/BlogEdit', [
            'blog' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'description' => $blog->description,
                'content' => $blog->content,
                'image' => $blog->image ? (Storage::disk('public')->exists('blogs/' . $blog->image) ? asset('storage/blogs/' . $blog->image) : '/images/blogs/' . $blog->image) : null,
                'read_time' => $blog->read_time,
                'published_at' => $blog->published_at ? $blog->published_at->format('Y-m-d') : null,
                'is_featured' => $blog->is_featured,
                'status' => $blog->status,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blogs,slug,' . $id,
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'read_time' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($blog->image) {
                Storage::disk('public')->delete('blogs/' . $blog->image);
            }
            $imagePath = $request->file('image')->store('blogs', 'public');
            $validated['image'] = basename($imagePath);
        } else {
            unset($validated['image']);
        }

        $blog->update($validated);

        return redirect()->route('admin.blogs.index')
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
