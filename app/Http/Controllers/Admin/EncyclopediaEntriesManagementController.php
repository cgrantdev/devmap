<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EncyclopediaEntriesManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = EducationPost::query()->with('category');
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('research_title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortType = $request->get('sort_type', 'desc');
        
        // Validate sortBy
        $allowedSortColumns = ['id', 'title', 'status', 'published_at', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        // Validate sortType
        $sortType = strtolower($sortType) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortBy, $sortType);
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $entries = $query->paginate($perPage)
            ->through(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->research_title ?? $item->title,
                    'research_title' => $item->research_title,
                    'slug' => $item->slug,
                    'category' => $item->category ? $item->category->name : null,
                    'description' => $item->description,
                    'peptide_full_name' => $item->peptide_full_name,
                    'status' => $item->status,
                    'published_at' => $item->published_at ? $item->published_at->format('Y-m-d') : null,
                    'created_at' => $item->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Admin/EncyclopediaEntries', [
            'entries' => $entries,
        ]);
    }

    public function create()
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });

        return Inertia::render('Admin/EncyclopediaEntryEdit', [
            'entry' => null,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'education_tag' => 'required|string|in:Healing & Recovery,Growth & Recovery,Performance,Anti-Aging',
            'research_title' => 'nullable|string|max:255',
            'research_outline' => 'nullable|string',
            'research_url' => 'nullable|url|max:500',
            'slug' => 'nullable|string|max:255|unique:education_posts,slug',
            'peptide_full_name' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'molecular_formula' => 'nullable|string|max:255',
            'molecular_weight' => 'nullable|string|max:255',
            'cas_registry_number' => 'nullable|string|max:255',
            'amino_acid_sequence' => 'nullable|string',
            'amino_acid_net_charge' => 'nullable|string|max:255',
            'amino_acid_hydrophobic' => 'nullable|string|max:255',
            'amino_acid_stability' => 'nullable|string|max:255',
            'amino_acid_solubility' => 'nullable|string|max:255',
            'key_points' => 'nullable|array',
            'key_points.*' => 'nullable|string',
            'overview' => 'nullable|string',
            'areas_of_research_intro' => 'nullable|string',
            'areas_of_research' => 'nullable|array',
            'areas_of_research.*.name' => 'nullable|string|max:255',
            'areas_of_research.*.description' => 'nullable|string',
            'background' => 'nullable|string',
            'mechanism_of_action_intro' => 'nullable|string',
            'mechanism_subsections' => 'nullable|array',
            'mechanism_subsections.*.title' => 'nullable|string|max:255',
            'mechanism_subsections.*.intro' => 'nullable|string',
            'mechanism_subsections.*.items' => 'nullable|array',
            'mechanism_subsections.*.items.*.item' => 'nullable|string',
            'mechanism_subsections.*.items.*.description' => 'nullable|string',
            'preclinical_intro' => 'nullable|string',
            'preclinical_subsections' => 'nullable|array',
            'preclinical_subsections.*.title' => 'nullable|string|max:255',
            'preclinical_subsections.*.findings' => 'nullable|array',
            'preclinical_subsections.*.findings.*.title' => 'nullable|string|max:255',
            'preclinical_subsections.*.findings.*.content' => 'nullable|string',
            'preclinical_disclaimer' => 'nullable|string',
            'human_use_intro' => 'nullable|string',
            'human_use_subsections' => 'nullable|array',
            'human_use_subsections.*.title' => 'nullable|string|max:255',
            'human_use_subsections.*.entries' => 'nullable|array',
            'human_use_subsections.*.entries.*.type' => 'nullable|string|in:item,content',
            'human_use_subsections.*.entries.*.value' => 'nullable|string',
            'regulatory_subsections' => 'nullable|array',
            'regulatory_subsections.*.title' => 'nullable|string|max:255',
            'regulatory_subsections.*.entries' => 'nullable|array',
            'regulatory_subsections.*.entries.*.type' => 'nullable|string|in:item,content',
            'regulatory_subsections.*.entries.*.value' => 'nullable|string',
            'regulatory_important_note' => 'nullable|string',
            'potential_applications_intro' => 'nullable|string',
            'potential_applications' => 'nullable|array',
            'potential_applications.*.title' => 'nullable|string|max:255',
            'potential_applications.*.description' => 'nullable|string',
            'potential_applications_important_context' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'references' => 'nullable|array',
            'references.*.authors' => 'nullable|string|max:255',
            'references.*.title' => 'nullable|string|max:255',
            'references.*.description' => 'nullable|string',
            'references.*.citation' => 'nullable|string',
            'references.*.links' => 'nullable|array',
            'references.*.links.*.url' => 'nullable|string|max:500',
            'references.*.links.*.label' => 'nullable|string|max:255',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'status' => 'required|in:draft,published',
            // SEO fields
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string',
            'seo_og_image' => 'nullable|string|max:500',
        ]);

        // Set published_at when status is published
        if ($validated['status'] === 'published' && empty($request->published_at)) {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        EducationPost::create($validated);

        return redirect()->route('admin.encyclopedia-entries.index')
            ->with('success', 'Encyclopedia entry created successfully.');
    }

    public function edit($id)
    {
        $entry = EducationPost::findOrFail($id);

        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });

        return Inertia::render('Admin/EncyclopediaEntryEdit', [
            'entry' => [
                'id' => $entry->id,
                'title' => $entry->title,
                'education_tag' => $entry->education_tag ?? null,
                'research_title' => $entry->research_title ?? '',
                'research_outline' => $entry->research_outline ?? '',
                'research_url' => $entry->research_url ?? '',
                'slug' => $entry->slug,
                'peptide_full_name' => $entry->peptide_full_name ?? '',
                'tags' => $entry->tags ?? [],
                'molecular_formula' => $entry->molecular_formula ?? '',
                'molecular_weight' => $entry->molecular_weight ?? '',
                'cas_registry_number' => $entry->cas_registry_number ?? '',
                'amino_acid_sequence' => $entry->amino_acid_sequence ?? '',
                'amino_acid_net_charge' => $entry->amino_acid_net_charge ?? '',
                'amino_acid_hydrophobic' => $entry->amino_acid_hydrophobic ?? '',
                'amino_acid_stability' => $entry->amino_acid_stability ?? '',
                'amino_acid_solubility' => $entry->amino_acid_solubility ?? '',
                'key_points' => $entry->key_points ?? [],
                'overview' => $entry->overview ?? '',
                'areas_of_research_intro' => $entry->areas_of_research_intro ?? '',
                'areas_of_research' => $entry->areas_of_research ?? [],
                'background' => $entry->background ?? '',
                'mechanism_of_action_intro' => $entry->mechanism_of_action_intro ?? '',
                'mechanism_subsections' => $entry->mechanism_subsections ?? [],
                'preclinical_intro' => $entry->preclinical_intro ?? '',
                'preclinical_subsections' => $entry->preclinical_subsections ?? [],
                'preclinical_disclaimer' => $entry->preclinical_disclaimer ?? '',
                'human_use_intro' => $entry->human_use_intro ?? '',
                'human_use_subsections' => $entry->human_use_subsections ?? [],
                'regulatory_subsections' => $entry->regulatory_subsections ?? [],
                'regulatory_important_note' => $entry->regulatory_important_note ?? '',
                'potential_applications_intro' => $entry->potential_applications_intro ?? '',
                'potential_applications' => $entry->potential_applications ?? [],
                'potential_applications_important_context' => $entry->potential_applications_important_context ?? '',
                'conclusion' => $entry->conclusion ?? '',
                'references' => $entry->references ?? [],
                'product_category_id' => $entry->product_category_id,
                'published_at' => $entry->published_at ? $entry->published_at->format('Y-m-d') : null,
                'status' => $entry->status,
                // SEO fields
                'seo_page_title' => $entry->seo_page_title ?? '',
                'seo_description' => $entry->seo_description ?? '',
                'seo_og_title' => $entry->seo_og_title ?? '',
                'seo_og_description' => $entry->seo_og_description ?? '',
                'seo_og_image' => $entry->seo_og_image ?? '',
            ],
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, $id)
    {
        $entry = EducationPost::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'education_tag' => 'required|string|in:Healing & Recovery,Growth & Recovery,Performance,Anti-Aging',
            'research_title' => 'nullable|string|max:255',
            'research_outline' => 'nullable|string',
            'research_url' => 'nullable|url|max:500',
            'slug' => 'nullable|string|max:255|unique:education_posts,slug,' . $id,
            'peptide_full_name' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string',
            'molecular_formula' => 'nullable|string|max:255',
            'molecular_weight' => 'nullable|string|max:255',
            'cas_registry_number' => 'nullable|string|max:255',
            'amino_acid_sequence' => 'nullable|string',
            'amino_acid_net_charge' => 'nullable|string|max:255',
            'amino_acid_hydrophobic' => 'nullable|string|max:255',
            'amino_acid_stability' => 'nullable|string|max:255',
            'amino_acid_solubility' => 'nullable|string|max:255',
            'key_points' => 'nullable|array',
            'key_points.*' => 'nullable|string',
            'overview' => 'nullable|string',
            'areas_of_research_intro' => 'nullable|string',
            'areas_of_research' => 'nullable|array',
            'areas_of_research.*.name' => 'nullable|string|max:255',
            'areas_of_research.*.description' => 'nullable|string',
            'background' => 'nullable|string',
            'mechanism_of_action_intro' => 'nullable|string',
            'mechanism_subsections' => 'nullable|array',
            'mechanism_subsections.*.title' => 'nullable|string|max:255',
            'mechanism_subsections.*.intro' => 'nullable|string',
            'mechanism_subsections.*.items' => 'nullable|array',
            'mechanism_subsections.*.items.*.item' => 'nullable|string',
            'mechanism_subsections.*.items.*.description' => 'nullable|string',
            'preclinical_intro' => 'nullable|string',
            'preclinical_subsections' => 'nullable|array',
            'preclinical_subsections.*.title' => 'nullable|string|max:255',
            'preclinical_subsections.*.findings' => 'nullable|array',
            'preclinical_subsections.*.findings.*.title' => 'nullable|string|max:255',
            'preclinical_subsections.*.findings.*.content' => 'nullable|string',
            'preclinical_disclaimer' => 'nullable|string',
            'human_use_intro' => 'nullable|string',
            'human_use_subsections' => 'nullable|array',
            'human_use_subsections.*.title' => 'nullable|string|max:255',
            'human_use_subsections.*.entries' => 'nullable|array',
            'human_use_subsections.*.entries.*.type' => 'nullable|string|in:item,content',
            'human_use_subsections.*.entries.*.value' => 'nullable|string',
            'regulatory_subsections' => 'nullable|array',
            'regulatory_subsections.*.title' => 'nullable|string|max:255',
            'regulatory_subsections.*.entries' => 'nullable|array',
            'regulatory_subsections.*.entries.*.type' => 'nullable|string|in:item,content',
            'regulatory_subsections.*.entries.*.value' => 'nullable|string',
            'regulatory_important_note' => 'nullable|string',
            'potential_applications_intro' => 'nullable|string',
            'potential_applications' => 'nullable|array',
            'potential_applications.*.title' => 'nullable|string|max:255',
            'potential_applications.*.description' => 'nullable|string',
            'potential_applications_important_context' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'references' => 'nullable|array',
            'references.*.authors' => 'nullable|string|max:255',
            'references.*.title' => 'nullable|string|max:255',
            'references.*.description' => 'nullable|string',
            'references.*.citation' => 'nullable|string',
            'references.*.links' => 'nullable|array',
            'references.*.links.*.url' => 'nullable|string|max:500',
            'references.*.links.*.label' => 'nullable|string|max:255',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'status' => 'required|in:draft,published',
            // SEO fields
            'seo_page_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'seo_og_title' => 'nullable|string|max:255',
            'seo_og_description' => 'nullable|string',
            'seo_og_image' => 'nullable|string|max:500',
        ]);

        // Handle status change and published_at
        $oldStatus = $entry->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        } else {
            unset($validated['published_at']);
        }

        $entry->update($validated);

        return redirect()->route('admin.encyclopedia-entries.index')
            ->with('success', 'Encyclopedia entry updated successfully.');
    }

    public function quickUpdate(Request $request, $id)
    {
        $entry = EducationPost::findOrFail($id);

        $validated = $request->validate([
            'research_title' => 'required|string|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // Handle status change and published_at
        $oldStatus = $entry->status;
        if ($validated['status'] === 'published' && $oldStatus !== 'published') {
            $validated['published_at'] = now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        } else {
            unset($validated['published_at']);
        }

        $entry->update($validated);

        return redirect()->back()->with('success', 'Encyclopedia entry updated successfully.');
    }

    public function destroy($id)
    {
        $entry = EducationPost::findOrFail($id);
        $entry->delete();

        return redirect()->route('admin.encyclopedia-entries.index')
            ->with('success', 'Encyclopedia entry deleted successfully.');
    }
}
