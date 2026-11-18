<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculatorPresetSeo;
use App\Models\PageContent;
use App\Support\AgencyFields;
use App\Support\CalculatorPresets;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * SEO-agency editor for the 55+ /calculator/{slug} preset pages.
 * Lists every preset from CalculatorPresets with a title + description
 * field; any blank field falls back to the compound-generated default
 * baked into the route.
 *
 * Also accepts a CSV upload (slug,title,description) so the agency
 * can bulk-load everything they have pre-written for their first push.
 */
class SeoCalculatorsController extends Controller
{
    public function index(Request $request)
    {
        $overrides = CalculatorPresetSeo::overrideMap();
        $onlyMissing = $request->boolean('missing');
        $q = strtolower(trim((string) $request->get('q', '')));

        $rows = collect(CalculatorPresets::all())
            ->map(fn ($p) => [
                'slug' => $p['slug'],
                'name' => $p['name'],
                'group' => $p['group'],
                'title' => $overrides[$p['slug']]['title'] ?? '',
                'description' => $overrides[$p['slug']]['description'] ?? '',
                'url' => '/calculator/' . $p['slug'],
            ])
            ->filter(function ($r) use ($onlyMissing, $q) {
                if ($q !== '' && !str_contains(strtolower($r['name'] . ' ' . $r['slug'] . ' ' . $r['group']), $q)) {
                    return false;
                }
                if ($onlyMissing && ($r['title'] !== '' || $r['description'] !== '')) {
                    return false;
                }
                return true;
            })
            ->values();

        return Inertia::render('Admin/SeoCalculators', [
            'rows' => $rows,
            'q' => $q,
            'onlyMissing' => $onlyMissing,
            // Pages with agency copy (H1 / intro / body / FAQs).
            'withCopy' => PageContent::keysWithCopy(PageContent::CALCULATOR),
        ]);
    }

    /**
     * Page copy editor for one calculator page (Jake, Nov 17 2026): H1,
     * intro under it, the body below the calculator and the FAQs. Slug
     * "index" is /calculator itself.
     */
    public function content(string $slug)
    {
        $page = $this->calculatorPage($slug);
        $copy = PageContent::forPage(PageContent::CALCULATOR, $slug);

        return Inertia::render('Admin/SeoCalculatorContent', [
            'page' => $page,
            'content' => [
                'h1' => $copy?->h1 ?? '',
                'intro_html' => $copy?->intro_html ?? '',
                'body_html' => $copy?->body_html ?? '',
                'faqs' => $copy ? $copy->faqPairs() : [],
            ],
            'ready' => PageContent::ready(),
        ]);
    }

    public function saveContent(Request $request, string $slug)
    {
        $this->calculatorPage($slug);
        if (! PageContent::ready()) {
            return back()->with('error', 'Calculator page copy turns on after the pending database update.');
        }

        $data = $request->validate(AgencyFields::rules(), AgencyFields::messages());
        AgencyFields::save(PageContent::CALCULATOR, $slug, $data);

        return back()->with('success', 'Calculator page copy saved.');
    }

    /** Label, URL and default H1 for a calculator page; 404 for unknown slugs. */
    private function calculatorPage(string $slug): array
    {
        if ($slug === PageContent::CALCULATOR_INDEX) {
            return [
                'slug' => $slug,
                'label' => 'Peptide calculator',
                'url' => '/calculator',
                'default_h1' => 'The Best Peptide Calculator',
            ];
        }

        $preset = CalculatorPresets::findBySlug($slug);
        abort_unless($preset, 404);
        $compound = CalculatorPresets::compoundName($preset);

        return [
            'slug' => $preset['slug'],
            'label' => "{$compound} calculator",
            'url' => '/calculator/'.$preset['slug'],
            'default_h1' => "{$compound} Calculator",
        ];
    }

    /**
     * Save a single row. Called inline from the Vue table on blur.
     */
    public function save(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        // Guard: slug must be a real preset. Anything else is rejected
        // so the table can't accumulate orphan rows.
        if (!CalculatorPresets::findBySlug($data['slug'])) {
            return response()->json(['error' => 'Unknown preset slug'], 422);
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        // Both empty? Remove the row so the route falls back cleanly.
        if ($title === '' && $description === '') {
            CalculatorPresetSeo::where('slug', $data['slug'])->delete();
            return response()->json(['ok' => true, 'deleted' => true]);
        }

        CalculatorPresetSeo::updateOrCreate(
            ['slug' => $data['slug']],
            ['title' => $title ?: null, 'description' => $description ?: null],
        );
        return response()->json(['ok' => true]);
    }

    /**
     * CSV upload: slug,title,description (header row required).
     * Rows with unknown slugs are reported but not written.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ]);

        $path = $request->file('csv')->getRealPath();
        $fh = fopen($path, 'r');
        if (!$fh) {
            return back()->with('error', 'Could not read the uploaded CSV.');
        }

        $header = fgetcsv($fh);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header ?: []);
        $slugIdx = array_search('slug', $header, true);
        $titleIdx = array_search('title', $header, true);
        $descIdx = array_search('description', $header, true);
        if ($slugIdx === false || ($titleIdx === false && $descIdx === false)) {
            fclose($fh);
            return back()->with('error', 'CSV must have a slug column and at least one of title/description.');
        }

        $imported = 0;
        $skipped = [];
        while (($row = fgetcsv($fh)) !== false) {
            $slug = trim((string) ($row[$slugIdx] ?? ''));
            if ($slug === '') continue;
            if (!CalculatorPresets::findBySlug($slug)) {
                $skipped[] = $slug;
                continue;
            }
            $title = $titleIdx !== false ? trim((string) ($row[$titleIdx] ?? '')) : '';
            $description = $descIdx !== false ? trim((string) ($row[$descIdx] ?? '')) : '';
            if ($title === '' && $description === '') continue;

            CalculatorPresetSeo::updateOrCreate(
                ['slug' => $slug],
                ['title' => $title ?: null, 'description' => $description ?: null],
            );
            $imported++;
        }
        fclose($fh);

        $msg = "Imported {$imported} preset rows.";
        if ($skipped) {
            $msg .= ' Skipped unknown slugs: ' . implode(', ', array_slice($skipped, 0, 8));
            if (count($skipped) > 8) $msg .= ' (+' . (count($skipped) - 8) . ' more)';
        }
        return redirect()->route('admin.seo.calculators')->with('success', $msg);
    }
}
