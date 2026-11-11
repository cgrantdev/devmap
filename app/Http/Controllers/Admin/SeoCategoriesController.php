<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * SEO-agency editor for compound-level content on /product/{slug} and
 * /compare/{slug}. Both routes render the same ProductCategory row, so
 * one editor covers both page types — Advantage Collective's first
 * batch is 10 compounds × 2 pages = 20 URLs (Advantage, 2026-11-11).
 *
 * Writes only three columns: custom_h1, custom_intro_html, custom_faqs.
 * Price, stock, catalog taxonomy, admin flags — none of that is
 * reachable from here.
 */
class SeoCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $onlyMissing = $request->boolean('missing');

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->when($q, fn ($c) => $c->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('slug', 'like', "%{$q}%")))
            ->when($onlyMissing, fn ($c) => $c->where(function ($w) {
                $w->whereNull('custom_h1')
                  ->orWhereNull('custom_intro_html')
                  ->orWhereNull('custom_faqs');
            }))
            ->withCount(['products' => fn ($q) => $q
                ->where('status', 'active')
                ->where('hidden', false)])
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->paginate(50)->withQueryString()
            ->through(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'products_count' => $c->products_count,
                'has_h1' => !empty($c->custom_h1),
                'has_intro' => !empty($c->custom_intro_html),
                'has_faqs' => is_array($c->custom_faqs) && count($c->custom_faqs) > 0,
                'faq_count' => is_array($c->custom_faqs) ? count($c->custom_faqs) : 0,
                'peptides_url' => '/product/' . $c->slug,
                'compare_url' => '/compare/' . $c->slug,
            ]);

        return Inertia::render('Admin/SeoCategories', [
            'categories' => $categories,
            'q' => $q,
            'onlyMissing' => $onlyMissing,
        ]);
    }

    public function edit(ProductCategory $category)
    {
        return Inertia::render('Admin/SeoCategoryEdit', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'custom_h1' => $category->custom_h1 ?? '',
                'custom_intro_html' => $category->custom_intro_html ?? '',
                'custom_faqs' => is_array($category->custom_faqs)
                    ? array_values($category->custom_faqs)
                    : [],
                'peptides_url' => '/product/' . $category->slug,
                'compare_url' => '/compare/' . $category->slug,
            ],
        ]);
    }

    public function update(Request $request, ProductCategory $category)
    {
        $data = $request->validate([
            'custom_h1' => ['nullable', 'string', 'max:180'],
            'custom_intro_html' => ['nullable', 'string', 'max:50000'],
            'custom_faqs' => ['nullable', 'array', 'max:12'],
            'custom_faqs.*.q' => ['required_with:custom_faqs.*.a', 'string', 'max:300'],
            'custom_faqs.*.a' => ['required_with:custom_faqs.*.q', 'string', 'max:4000'],
        ]);

        // Drop blank faq rows (admin pressed "add" but didn't fill).
        $faqs = collect($data['custom_faqs'] ?? [])
            ->map(fn ($row) => [
                'q' => trim((string) ($row['q'] ?? '')),
                'a' => trim((string) ($row['a'] ?? '')),
            ])
            ->filter(fn ($row) => $row['q'] !== '' && $row['a'] !== '')
            ->values()
            ->all();

        // Field-level lock: only write the three agency-editable columns.
        $category->forceFill([
            'custom_h1' => $data['custom_h1'] ?? null,
            'custom_intro_html' => $data['custom_intro_html'] ?? null,
            'custom_faqs' => $faqs ?: null,
        ])->save();

        return redirect()
            ->route('admin.seo.categories.edit', $category)
            ->with('success', 'Saved.');
    }
}
