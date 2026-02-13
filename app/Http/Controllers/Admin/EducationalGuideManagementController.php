<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EducationalGuideManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = EducationalGuide::query();
        
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
        $allowedSortColumns = ['id', 'title', 'status', 'is_featured', 'published_at', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        // Validate sortType
        $sortType = strtolower($sortType) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortBy, $sortType);
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $guides = $query->paginate($perPage)
            ->through(function ($guide) {
                return [
                    'id' => $guide->id,
                    'title' => $guide->title,
                    'slug' => $guide->slug,
                    'tag' => $guide->tag,
                    'reading_time' => $guide->reading_time,
                    'description' => $guide->description,
                    'peptides' => $guide->peptides ?? [],
                    'guide_url' => $guide->guide_url,
                    'published_at' => $guide->published_at ? $guide->published_at->format('Y-m-d') : null,
                    'is_featured' => $guide->is_featured,
                    'status' => $guide->status,
                    'created_at' => $guide->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Admin/EducationalGuides', [
            'guides' => $guides,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/EducationalGuideEdit', [
            'guide' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:educational_guides,slug',
            'tag' => 'nullable|string|max:255',
            'reading_time' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'peptides' => 'nullable|array',
            'peptides.*' => 'nullable|string',
            'guide_url' => 'nullable|string|max:500',
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

        EducationalGuide::create($validated);

        return redirect()->route('admin.educational-guides.index')
            ->with('success', 'Educational guide created successfully.');
    }

    public function edit($id)
    {
        $guide = EducationalGuide::findOrFail($id);

        return Inertia::render('Admin/EducationalGuideEdit', [
            'guide' => [
                'id' => $guide->id,
                'title' => $guide->title,
                'slug' => $guide->slug,
                'tag' => $guide->tag,
                'reading_time' => $guide->reading_time,
                'description' => $guide->description,
                'peptides' => $guide->peptides ?? [],
                'guide_url' => $guide->guide_url,
                'published_at' => $guide->published_at ? $guide->published_at->format('Y-m-d') : null,
                'is_featured' => $guide->is_featured,
                'status' => $guide->status,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $guide = EducationalGuide::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:educational_guides,slug,' . $id,
            'tag' => 'nullable|string|max:255',
            'reading_time' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'peptides' => 'nullable|array',
            'peptides.*' => 'nullable|string',
            'guide_url' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
        ]);

        // Auto-generate slug from title
        $validated['slug'] = Str::slug($validated['title']);

        // Handle status change and published_at
        $oldStatus = $guide->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        } else {
            unset($validated['published_at']);
        }

        $guide->update($validated);

        return redirect()->route('admin.educational-guides.index')
            ->with('success', 'Educational guide updated successfully.');
    }

    public function quickUpdate(Request $request, $id)
    {
        $guide = EducationalGuide::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // Auto-generate slug from title if title changed
        if ($validated['title'] !== $guide->title) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Handle status change and published_at
        $oldStatus = $guide->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        } else {
            unset($validated['published_at']);
        }

        $guide->update($validated);

        return redirect()->back()->with('success', 'Educational guide updated successfully.');
    }

    public function destroy($id)
    {
        $guide = EducationalGuide::findOrFail($id);
        $guide->delete();

        return redirect()->route('admin.educational-guides.index')
            ->with('success', 'Educational guide deleted successfully.');
    }
}
