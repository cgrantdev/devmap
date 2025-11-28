<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class EducationPostsController extends Controller
{
    public function index()
    {
        $posts = EducationPost::orderBy('created_at', 'desc')
            ->paginate(20)
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

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('education_posts', 'public');
            $validated['image'] = basename($imagePath);
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

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($post->image) {
                Storage::disk('public')->delete('education_posts/' . $post->image);
            }
            $imagePath = $request->file('image')->store('education_posts', 'public');
            $validated['image'] = basename($imagePath);
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
