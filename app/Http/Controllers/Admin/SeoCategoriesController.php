<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * SEO-agency editor for per-compound content on the Peptides page
 * (/product/{slug}) and the Compare page (/compare/{slug}).
 *
 * Ashton (Advantage Collective) blocker: the two pages were sharing
 * one custom H1/intro/FAQ set so Google read them as duplicates.
 * The editor now exposes a separate copy block for each page:
 *   Peptides -> products_custom_{title, meta_description, h1, intro_html, faqs}
 *   Compare  -> compare_custom_{title, meta_description, h1, intro_html, faqs}
 *
 * Writes nothing else. Price, stock, catalog taxonomy, admin flags
 * are not reachable from here.
 */
class SeoCategoriesController extends Controller
{
    private const EDITABLE = [
        'products' => [
            'title' => 'products_custom_title',
            'meta'  => 'products_custom_meta_description',
            'h1'    => 'products_custom_h1',
            'intro' => 'products_custom_intro_html',
            'faqs'  => 'products_custom_faqs',
        ],
        'compare' => [
            'title' => 'compare_custom_title',
            'meta'  => 'compare_custom_meta_description',
            'h1'    => 'compare_custom_h1',
            'intro' => 'compare_custom_intro_html',
            'faqs'  => 'compare_custom_faqs',
        ],
    ];

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $onlyMissing = $request->boolean('missing');

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->when($q, function ($c) use ($q) {
                $c->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->when($onlyMissing, function ($c) {
                $c->where(function ($w) {
                    $w->whereNull('products_custom_h1')
                      ->orWhereNull('products_custom_intro_html')
                      ->orWhereNull('compare_custom_h1')
                      ->orWhereNull('compare_custom_intro_html');
                });
            })
            ->withCount(['products' => function ($q) {
                $q->where('status', 'active')->where('hidden', false);
            }])
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString()
            ->through(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'products_count' => $c->products_count,
                    'peptides' => [
                        'has_title' => !empty($c->products_custom_title),
                        'has_meta'  => !empty($c->products_custom_meta_description),
                        'has_h1'    => !empty($c->products_custom_h1),
                        'has_intro' => !empty($c->products_custom_intro_html),
                        'faq_count' => is_array($c->products_custom_faqs) ? count($c->products_custom_faqs) : 0,
                    ],
                    'compare' => [
                        'has_title' => !empty($c->compare_custom_title),
                        'has_meta'  => !empty($c->compare_custom_meta_description),
                        'has_h1'    => !empty($c->compare_custom_h1),
                        'has_intro' => !empty($c->compare_custom_intro_html),
                        'faq_count' => is_array($c->compare_custom_faqs) ? count($c->compare_custom_faqs) : 0,
                    ],
                    'peptides_url' => '/product/' . $c->slug,
                    'compare_url' => '/compare/' . $c->slug,
                ];
            });

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
                'peptides' => [
                    'title' => $category->products_custom_title ?? '',
                    'meta'  => $category->products_custom_meta_description ?? '',
                    'h1'    => $category->products_custom_h1 ?? '',
                    'intro' => $category->products_custom_intro_html ?? '',
                    'faqs'  => is_array($category->products_custom_faqs)
                        ? array_values($category->products_custom_faqs)
                        : [],
                ],
                'compare' => [
                    'title' => $category->compare_custom_title ?? '',
                    'meta'  => $category->compare_custom_meta_description ?? '',
                    'h1'    => $category->compare_custom_h1 ?? '',
                    'intro' => $category->compare_custom_intro_html ?? '',
                    'faqs'  => is_array($category->compare_custom_faqs)
                        ? array_values($category->compare_custom_faqs)
                        : [],
                ],
                'peptides_url' => '/product/' . $category->slug,
                'compare_url' => '/compare/' . $category->slug,
            ],
        ]);
    }

    public function update(Request $request, ProductCategory $category)
    {
        $data = $request->validate([
            'peptides.title' => ['nullable', 'string', 'max:200'],
            'peptides.meta'  => ['nullable', 'string', 'max:2000'],
            'peptides.h1'    => ['nullable', 'string', 'max:180'],
            'peptides.intro' => ['nullable', 'string', 'max:50000'],
            'peptides.faqs'  => ['nullable', 'array', 'max:12'],
            'peptides.faqs.*.q' => ['required_with:peptides.faqs.*.a', 'string', 'max:300'],
            'peptides.faqs.*.a' => ['required_with:peptides.faqs.*.q', 'string', 'max:4000'],

            'compare.title'  => ['nullable', 'string', 'max:200'],
            'compare.meta'   => ['nullable', 'string', 'max:2000'],
            'compare.h1'     => ['nullable', 'string', 'max:180'],
            'compare.intro'  => ['nullable', 'string', 'max:50000'],
            'compare.faqs'   => ['nullable', 'array', 'max:12'],
            'compare.faqs.*.q' => ['required_with:compare.faqs.*.a', 'string', 'max:300'],
            'compare.faqs.*.a' => ['required_with:compare.faqs.*.q', 'string', 'max:4000'],
        ]);

        $cleanFaqs = function (array $rows) {
            return collect($rows)
                ->map(function ($row) {
                    return [
                        'q' => trim((string) ($row['q'] ?? '')),
                        'a' => trim((string) ($row['a'] ?? '')),
                    ];
                })
                ->filter(function ($row) {
                    return $row['q'] !== '' && $row['a'] !== '';
                })
                ->values()
                ->all();
        };

        $updates = [];
        foreach (self::EDITABLE as $page => $cols) {
            $block = $data[$page] ?? [];
            $updates[$cols['title']] = $this->nullIfBlank($block['title'] ?? null);
            $updates[$cols['meta']]  = $this->nullIfBlank($block['meta'] ?? null);
            $updates[$cols['h1']]    = $this->nullIfBlank($block['h1'] ?? null);
            $updates[$cols['intro']] = $this->nullIfBlank($block['intro'] ?? null);
            $faqs = $cleanFaqs($block['faqs'] ?? []);
            $updates[$cols['faqs']] = $faqs ?: null;
        }

        $category->forceFill($updates)->save();

        return redirect()
            ->route('admin.seo.categories.edit', $category)
            ->with('success', 'Saved.');
    }

    private function nullIfBlank($value): ?string
    {
        if ($value === null) return null;
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}
