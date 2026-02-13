<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Research;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResearchManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Research::query();
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('peptide', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortType = $request->get('sort_type', 'desc');
        
        // Validate sortBy
        $allowedSortColumns = ['id', 'title', 'category', 'status', 'published_at', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        // Validate sortType
        $sortType = strtolower($sortType) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortBy, $sortType);
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $research = $query->paginate($perPage)
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'category' => $item->peptide, // Display peptide name in category column
                    'peptide' => $item->peptide,
                    'evidence_level' => $item->evidence_level,
                    'journal_type' => $item->journal_type,
                    'study_type' => $item->study_type,
                    'study_summary' => $item->study_summary,
                    'background' => $item->background,
                    'key_findings' => $item->key_findings ?? [],
                    'methodology' => $item->methodology,
                    'clinical_implications' => $item->clinical_implications,
                    'limitations' => $item->limitations,
                    'pubmed_url' => $item->pubmed_url,
                    'tags' => $item->tags ?? [],
                    'published_at' => $item->published_at ? $item->published_at->format('Y-m-d') : null,
                    'status' => $item->status,
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Admin/Research', [
            'research' => $research,
        ]);
    }

    public function create()
    {
        $peptides = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });

        return Inertia::render('Admin/ResearchEdit', [
            'research' => null,
            'peptides' => $peptides,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'peptide' => 'nullable|string|max:255',
            'evidence_level' => 'nullable|string|in:High,Medium,Low',
            'journal_type' => 'required|string|max:255',
            'study_type' => 'required|string|max:255',
            'study_summary' => 'required|string',
            'background' => 'nullable|string',
            'key_findings' => 'nullable|array',
            'key_findings.*' => 'nullable|string',
            'methodology' => 'nullable|string',
            'clinical_implications' => 'nullable|string',
            'limitations' => 'nullable|string',
            'pubmed_url' => 'nullable|url|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'status' => 'required|in:draft,published',
        ]);

        // Set published_at when status is published
        if ($validated['status'] === 'published' && empty($request->published_at)) {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        Research::create($validated);

        return redirect()->route('admin.research.index')
            ->with('success', 'Research paper created successfully.');
    }

    public function edit($id)
    {
        $research = Research::findOrFail($id);

        $peptides = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });

        return Inertia::render('Admin/ResearchEdit', [
            'research' => [
                'id' => $research->id,
                'title' => $research->title,
                'peptide' => $research->peptide,
                'evidence_level' => $research->evidence_level,
                'journal_type' => $research->journal_type,
                'study_type' => $research->study_type,
                'study_summary' => $research->study_summary,
                'background' => $research->background,
                'key_findings' => $research->key_findings ?? [],
                'methodology' => $research->methodology,
                'clinical_implications' => $research->clinical_implications,
                'limitations' => $research->limitations,
                'pubmed_url' => $research->pubmed_url,
                'tags' => $research->tags ?? [],
                'published_at' => $research->published_at ? $research->published_at->format('Y-m-d') : null,
                'status' => $research->status,
            ],
            'peptides' => $peptides,
        ]);
    }

    public function update(Request $request, $id)
    {
        $research = Research::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'peptide' => 'nullable|string|max:255',
            'evidence_level' => 'nullable|string|in:High,Medium,Low',
            'journal_type' => 'required|string|max:255',
            'study_type' => 'required|string|max:255',
            'study_summary' => 'required|string',
            'background' => 'nullable|string',
            'key_findings' => 'nullable|array',
            'key_findings.*' => 'nullable|string',
            'methodology' => 'nullable|string',
            'clinical_implications' => 'nullable|string',
            'limitations' => 'nullable|string',
            'pubmed_url' => 'nullable|url|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'status' => 'required|in:draft,published',
        ]);

        // Handle status change and published_at
        $oldStatus = $research->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        } else {
            unset($validated['published_at']);
        }

        $research->update($validated);

        return redirect()->route('admin.research.index')
            ->with('success', 'Research paper updated successfully.');
    }

    public function quickUpdate(Request $request, $id)
    {
        $research = Research::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // Handle status change and published_at
        $oldStatus = $research->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        } else {
            unset($validated['published_at']);
        }

        $research->update($validated);

        return redirect()->back()->with('success', 'Research paper updated successfully.');
    }

    public function destroy($id)
    {
        $research = Research::findOrFail($id);
        $research->delete();

        return redirect()->route('admin.research.index')
            ->with('success', 'Research paper deleted successfully.');
    }
}
