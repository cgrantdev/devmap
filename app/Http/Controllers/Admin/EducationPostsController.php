<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationPost;
use App\Models\ProductCategory;
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
        $posts = $query->with('category')->paginate($perPage)
            ->through(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'description' => $post->description,
                    'image' => $post->image ? (Storage::disk('public')->exists('education_posts/' . $post->image) ? asset('storage/education_posts/' . $post->image) : (file_exists(public_path('images/peptides/' . $post->image)) ? '/images/peptides/' . $post->image : null)) : null,
                    'rating' => number_format($post->rating, 2, '.', ''),
                    'rating_count' => $post->rating_count,
                    'published_at' => $post->published_at ? $post->published_at->format('Y-m-d') : null,
                    'status' => $post->status,
                    'created_at' => $post->created_at->format('Y-m-d H:i'),
                    'category_name' => $post->category ? $post->category->name : '-',
                ];
            });

        return Inertia::render('Admin/EducationPosts', [
            'posts' => $posts,
        ]);
    }

    public function create()
    {
        // Get all categories, excluding those that already have education posts
        $categories = ProductCategory::where('is_active', true)
            ->whereDoesntHave('educationPost')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });
        
        // Also include categories that have education posts (for editing existing posts)
        $allCategories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'has_education_post' => $category->educationPost ? true : false,
                ];
            });

        return Inertia::render('Admin/EducationPostEdit', [
            'post' => null,
            'categories' => $allCategories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:education_posts,slug',
            'description' => 'nullable|string',
            'education_tag' => 'nullable|string|max:255',
            'peptide_full_name' => 'nullable|string|max:255',
            'half_life' => 'nullable|string|max:255',
            'bioavailability' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'rating' => 'nullable|numeric|min:0|max:5',
            'rating_count' => 'nullable|integer|min:0',
            'key_effects' => 'nullable|array',
            'key_effects.*' => 'string',
            'common_use_cases' => 'nullable|array',
            'common_use_cases.*' => 'string',
            'how_it_works' => 'nullable|string',
            'typical_dosage' => 'nullable|string|max:255',
            'frequency' => 'nullable|string|max:255',
            'administration' => 'nullable|string|max:255',
            'cycle_duration' => 'nullable|string|max:255',
            'possible_side_effects' => 'nullable|array',
            'possible_side_effects.*' => 'string',
            'contraindications' => 'nullable|array',
            'contraindications.*' => 'string',
            'stacking_recommendations' => 'nullable|array',
            'stacking_recommendations.*' => 'nullable|integer|exists:product_categories,id',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',
            'status' => 'required|in:draft,published',
            'product_category_id' => 'nullable|exists:product_categories,id|unique:education_posts,product_category_id',
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
        $post = EducationPost::with('category')->findOrFail($id);
        
        // Get all categories
        $allCategories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) use ($post) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'has_education_post' => $category->educationPost && $category->educationPost->id !== $post->id ? true : false,
                ];
            });

        return Inertia::render('Admin/EducationPostEdit', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'description' => $post->description,
                'education_tag' => $post->education_tag,
                'peptide_full_name' => $post->peptide_full_name,
                'half_life' => $post->half_life,
                'bioavailability' => $post->bioavailability,
                'storage' => $post->storage,
                'image' => $post->image ? Storage::url('education_posts/' . $post->image) : null,
                'rating' => $post->rating,
                'rating_count' => $post->rating_count,
                'key_effects' => $post->key_effects ?? [],
                'common_use_cases' => $post->common_use_cases ?? [],
                'how_it_works' => $post->how_it_works,
                'typical_dosage' => $post->typical_dosage,
                'frequency' => $post->frequency,
                'administration' => $post->administration,
                'cycle_duration' => $post->cycle_duration,
                'possible_side_effects' => $post->possible_side_effects ?? [],
                'contraindications' => $post->contraindications ?? [],
                'stacking_recommendations' => $post->stacking_recommendations ?? [],
                'faqs' => $post->faqs ?? [],
                'published_at' => $post->published_at ? $post->published_at->format('Y-m-d') : null,
                'status' => $post->status,
                'product_category_id' => $post->product_category_id,
            ],
            'categories' => $allCategories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $post = EducationPost::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:education_posts,slug,' . $id,
            'description' => 'nullable|string',
            'education_tag' => 'nullable|string|max:255',
            'peptide_full_name' => 'nullable|string|max:255',
            'half_life' => 'nullable|string|max:255',
            'bioavailability' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
            'rating' => 'nullable|numeric|min:0|max:5',
            'rating_count' => 'nullable|integer|min:0',
            'key_effects' => 'nullable|array',
            'key_effects.*' => 'string',
            'common_use_cases' => 'nullable|array',
            'common_use_cases.*' => 'string',
            'how_it_works' => 'nullable|string',
            'typical_dosage' => 'nullable|string|max:255',
            'frequency' => 'nullable|string|max:255',
            'administration' => 'nullable|string|max:255',
            'cycle_duration' => 'nullable|string|max:255',
            'possible_side_effects' => 'nullable|array',
            'possible_side_effects.*' => 'string',
            'contraindications' => 'nullable|array',
            'contraindications.*' => 'string',
            'stacking_recommendations' => 'nullable|array',
            'stacking_recommendations.*' => 'nullable|integer|exists:product_categories,id',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',
            'status' => 'required|in:draft,published',
            'product_category_id' => 'nullable|exists:product_categories,id|unique:education_posts,product_category_id,' . $id,
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
