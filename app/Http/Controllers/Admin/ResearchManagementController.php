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
        $research = Research::orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'peptide' => $item->peptide,
                    'evidence_level' => $item->evidence_level,
                    'study_type' => $item->study_type,
                    'published_at' => $item->published_at ? $item->published_at->format('M j, Y') : null,
                    'status' => $item->status,
                    'created_at' => $item->created_at->format('M j, Y'),
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
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|url|max:500',
        ]);

        // Set published_at when status is published
        if ($validated['status'] === 'published' && empty($request->published_at)) {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        $paper = Research::create($validated);

        return redirect("/admin/research/{$paper->id}/edit")
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
                'seo_page_title' => $research->seo_page_title,
                'seo_description' => $research->seo_description,
                'seo_og_title' => $research->seo_og_title,
                'seo_og_description' => $research->seo_og_description,
                'seo_og_image' => $research->seo_og_image,
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
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string|max:500',
            'seo_og_image' => 'nullable|url|max:500',
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

        return redirect()->back()
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
