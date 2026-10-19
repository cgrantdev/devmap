<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Guarded field updates for cagrilintide, GHK-Cu, and BPC-157
 * (19 October 2026).
 *
 * Each post is matched with LOWER(slug). Zero rows is a no-op. More than
 * one row is a collision: nothing is written. JSON columns are assigned as
 * PHP arrays. A column is written only when its op's exact old value
 * matches; a skipped column does not block the others. Nothing is appended
 * unless that column's op says append.
 */
class EncyclopediaFieldUpdatesOctober19Seeder extends Seeder
{
    public const CAGRI_SLUG = 'cagrilintide';

    public const GHK_SLUG = 'ghk-cu';

    public const BPC_SLUG = 'bpc-157';

    public const CAGRI_CATEGORY_ID = 437;

    /** @var array<string, string> */
    private const FILES = [
        self::CAGRI_SLUG => 'cagrilintide.json',
        self::GHK_SLUG => 'ghk-cu.json',
        self::BPC_SLUG => 'bpc-157.json',
    ];

    /** @var array<string, string> */
    public array $report = [
        self::CAGRI_SLUG => 'missing',
        self::GHK_SLUG => 'missing',
        self::BPC_SLUG => 'missing',
    ];

    /** @var list<string> */
    public array $log = [];

    private bool $wroteCategory = false;

    public function run(): void
    {
        $this->log = [];
        $this->report = [
            self::CAGRI_SLUG => 'missing',
            self::GHK_SLUG => 'missing',
            self::BPC_SLUG => 'missing',
        ];

        foreach (self::FILES as $slug => $file) {
            $this->updateSlug($slug, $this->payload($file));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateSlug(string $slug, array $payload): void
    {
        $match = (string) ($payload['slug_match'] ?? '');
        if (! str_contains(strtolower($match), "lower(slug) = '".$slug."'")) {
            throw new RuntimeException('Field payload slug_match does not target '.$slug.'.');
        }

        $posts = EducationPost::query()
            ->whereRaw('LOWER(slug) = ?', [$slug])
            ->orderBy('id')
            ->get();

        if ($posts->count() !== 1) {
            $this->report[$slug] = $posts->isEmpty() ? 'missing' : 'collision';
            if ($posts->count() > 1) {
                $stored = $posts->map(fn (EducationPost $post) => $post->slug)->implode(', ');
                $this->log($slug, '*', 'skipped: slug collision ('.$stored.')');
                $this->command?->warn('19 Oct 2026 encyclopedia update: skipped '.$slug.' slug collision ('.$stored.').');
            }

            return;
        }

        /** @var EducationPost $post */
        $post = $posts->first();
        $columns = $payload['columns'] ?? null;
        if (! is_array($columns)) {
            throw new RuntimeException('Field payload is missing columns for '.$slug.'.');
        }

        $this->wroteCategory = false;
        $fields = array_keys($columns);
        $before = $this->snapshot($post, $fields);
        $this->apply($slug, $post, $columns);
        $postChanged = $this->snapshot($post, $fields) !== $before;

        if (! $postChanged && ! $this->wroteCategory) {
            $this->report[$slug] = 'unchanged';
            $this->command?->info('19 Oct 2026 encyclopedia update: '.$post->slug.' already current.');

            return;
        }

        if ($postChanged) {
            // saveQuietly skips the model's JSON unwrapping hook, which would
            // otherwise mark every JSON column dirty and rewrite it.
            $post->saveQuietly();
        }

        $this->report[$slug] = 'updated';
        $this->command?->info('19 Oct 2026 encyclopedia update: updated '.$post->slug.'.');
    }

    /**
     * @param  array<string, mixed>  $columns
     */
    private function apply(string $slug, EducationPost $post, array $columns): void
    {
        if ($slug === self::CAGRI_SLUG) {
            $this->applyCagrilintide($post, $columns);

            return;
        }
        if ($slug === self::GHK_SLUG) {
            $this->applyGhk($post, $columns);

            return;
        }

        $this->applyBpc($post, $columns);
    }

    /**
     * @param  array<string, mixed>  $columns
     */
    private function applyCagrilintide(EducationPost $post, array $columns): void
    {
        $slug = self::CAGRI_SLUG;
        $this->writeIfExact($post, $slug, 'overview', $columns['overview'], fn (mixed $current) => is_string($current) && str_contains($current, 'Status as of 19 Oct 2026'));
        $this->writeIfExact($post, $slug, 'background', $columns['background'], fn (mixed $current) => is_string($current) && str_contains($current, '159–195 hours'));
        $this->writeIfExact($post, $slug, 'half_life', $columns['half_life'], fn (mixed $current) => is_string($current) && str_starts_with($current, 'Human:'));
        $this->writeIfExact($post, $slug, 'molecular_formula', $columns['molecular_formula'], null);
        $this->writeIfExact($post, $slug, 'molecular_weight', $columns['molecular_weight'], null);
        $this->writeIfExact($post, $slug, 'cas_registry_number', $columns['cas_registry_number'], null);
        $this->writeIfExact($post, $slug, 'human_use_intro', $columns['human_use_intro'], fn (mixed $current) => is_string($current) && str_contains($current, 'This page gives no dose'));
        $this->writeIfExact($post, $slug, 'human_use_subsections', $columns['human_use_subsections'], fn (mixed $current) => $this->anyTitleStartsWith($current, 'Clinical program (as of'));
        $this->writeIfExact($post, $slug, 'regulatory_subsections', $columns['regulatory_subsections'], fn (mixed $current) => $this->anyTitleStartsWith($current, 'Development and regulatory status (as of'));
        $this->writeIfExact($post, $slug, 'regulatory_important_note', $columns['regulatory_important_note'], fn (mixed $current) => is_string($current) && str_contains($current, 'not Novo Nordisk trial supply'));
        $this->writeIfExact($post, $slug, 'potential_applications_important_context', $columns['potential_applications_important_context'], fn (mixed $current) => is_string($current) && str_contains($current, 'does not predict'));
        $this->assignIfChanged($post, $slug, 'potential_applications', $this->applyReplacements($post->potential_applications, $slug, $columns['potential_applications']['replacements'] ?? []));
        $this->assignIfChanged($post, $slug, 'areas_of_research', $this->replaceAreaZero($post->areas_of_research, $slug, $columns['areas_of_research']));
        $this->writeIfExact($post, $slug, 'conclusion', $columns['conclusion'], fn (mixed $current) => is_string($current) && str_contains($current, 'As of 19 Oct 2026'));
        $this->writeIfExact($post, $slug, 'references', $columns['references'], fn (mixed $current) => $this->anyCitationContains($current, 'PMID 40544433'));
        $this->writeIfExact($post, $slug, 'faqs', $columns['faqs'], function (mixed $current) use ($columns) {
            $question = $columns['faqs']['new_value'][3]['question'] ?? null;

            return is_string($question) && $this->anyQuestionEquals($current, $question);
        });

        $operations = $columns['key_points']['operations'] ?? [];
        $points = $post->key_points;
        foreach ($operations as $operation) {
            if (! is_array($operation)) {
                continue;
            }
            if (($operation['type'] ?? null) === 'replace_in_place') {
                $points = $this->replaceKeyPoint($points, $slug, (string) $operation['old_item'], (string) $operation['new_item']);
            } elseif (($operation['type'] ?? null) === 'append') {
                $prefix = (string) ($operation['skip_if_any_item_starts_with'] ?? 'Status (as of ');
                $points = $this->appendKeyPoint($points, $slug, $prefix, (string) $operation['new_item']);
            }
        }
        $this->assignIfChanged($post, $slug, 'key_points', $points);

        $this->applyDescription($post, $columns['description']);
        $this->assignIfChanged($post, $slug, 'preclinical_subsections', $this->replacePreclinicalFinding(
            $post->preclinical_subsections,
            $slug,
            $columns['preclinical_subsections']['old_finding'],
            $columns['preclinical_subsections']['new_finding']
        ));
    }

    /**
     * @param  array<string, mixed>  $columns
     */
    private function applyGhk(EducationPost $post, array $columns): void
    {
        $slug = self::GHK_SLUG;
        $spec = $columns['human_use_subsections'];
        $sections = $this->asList($post->human_use_subsections, $slug, 'human_use_subsections');
        if (is_array($sections)) {
            $sections = $this->replaceNamedSubsection(
                $sections,
                $slug,
                'human_use_subsections',
                'Injectable Research',
                $spec['old_injectable_subsection'],
                $spec['new_injectable_subsection'],
                fn (array $section) => str_contains((string) $this->firstEntryValue($section), 'has not been tested on its own'),
                'first entry already says it has not been tested on its own'
            );
            $sections = $this->replaceNamedSubsection(
                $sections,
                $slug,
                'human_use_subsections',
                'Dermatological Studies',
                $spec['old_dermatological_subsection'],
                $spec['new_dermatological_subsection'],
                fn (array $section) => ! $this->entriesContain($section, 'vitamin C'),
                'no entry contains vitamin C'
            );
            $sections = $this->appendSubsection($sections, $slug, 'human_use_subsections', $spec['new_subsection'], 'Evidence limits (as of');
            $this->assignIfChanged($post, $slug, 'human_use_subsections', $sections);
        }

        $regulatory = $this->asList($post->regulatory_subsections, $slug, 'regulatory_subsections');
        if (is_array($regulatory)) {
            $regulatory = $this->appendSubsection(
                $regulatory,
                $slug,
                'regulatory_subsections',
                $columns['regulatory_subsections']['new_subsection'],
                'FDA compounding status (as of'
            );
            $this->assignIfChanged($post, $slug, 'regulatory_subsections', $regulatory);
        }

        $this->assignIfChanged($post, $slug, 'potential_applications', $this->applyReplacements(
            $post->potential_applications,
            $slug,
            $columns['potential_applications']['replacements'] ?? []
        ));
        $this->assignIfChanged($post, $slug, 'key_points', $this->appendKeyPoint(
            $post->key_points,
            $slug,
            'Evidence limits (as of ',
            (string) $columns['key_points']['new_item']
        ));
        $this->writeIfExact($post, $slug, 'faqs', $columns['faqs'], function (mixed $current) use ($columns) {
            $question = $columns['faqs']['new_value'][3]['question'] ?? null;

            return is_string($question) && $this->anyQuestionEquals($current, $question);
        });
        $this->writeIfExact($post, $slug, 'references', $columns['references'], fn (mixed $current) => $this->anyCitationContains($current, 'PMID 27489425'));
        $this->writeIfExact($post, $slug, 'conclusion', $columns['conclusion'], fn (mixed $current) => is_string($current) && str_contains($current, 'Topical and injected evidence should be kept separate'));
        $this->writeIfExact($post, $slug, 'research_url', $columns['research_url'], null);
        $this->writeIfExact($post, $slug, 'molecular_formula', $columns['molecular_formula'], null);
        $this->writeIfExact($post, $slug, 'molecular_weight', $columns['molecular_weight'], null);
        $this->writeIfExact($post, $slug, 'cas_registry_number', $columns['cas_registry_number'], null);
        $this->writeIfEmpty($post, $slug, 'half_life', $columns['half_life']['new_value']);
    }

    /**
     * @param  array<string, mixed>  $columns
     */
    private function applyBpc(EducationPost $post, array $columns): void
    {
        $slug = self::BPC_SLUG;
        $this->applyBpcRegulatory($post, $columns['regulatory_subsections']);
        $this->writeIfExact($post, $slug, 'regulatory_important_note', $columns['regulatory_important_note'], fn (mixed $current) => is_string($current) && str_contains($current, 'Regulatory status (as of 19 October 2026)'));
        $this->assignIfChanged($post, $slug, 'key_points', $this->replaceKeyPoint(
            $post->key_points,
            $slug,
            (string) $columns['key_points']['old_item'],
            (string) $columns['key_points']['new_item']
        ));
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function writeIfExact(EducationPost $post, string $slug, string $column, array $spec, ?callable $guard): void
    {
        $current = $post->{$column};
        $new = $spec['new_value'];
        if ($current === $new || ($guard !== null && $guard($current))) {
            $this->log($slug, $column, 'skipped: already applied');

            return;
        }
        if ($current !== $spec['old_value']) {
            $this->log($slug, $column, 'skipped: old value does not match');

            return;
        }

        $post->{$column} = $new;
        $this->log($slug, $column, 'wrote');
    }

    private function writeIfEmpty(EducationPost $post, string $slug, string $column, mixed $new): void
    {
        $current = $post->{$column};
        if ($current === null || $current === '') {
            $post->{$column} = $new;
            $this->log($slug, $column, 'wrote');

            return;
        }

        $this->log($slug, $column, 'skipped: value is not empty');
    }

    private function assignIfChanged(EducationPost $post, string $slug, string $column, mixed $value): void
    {
        if ($value !== $post->{$column}) {
            $post->{$column} = $value;
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyDescription(EducationPost $post, array $spec): void
    {
        $slug = self::CAGRI_SLUG;
        $old = $spec['old_value'];
        $new = $spec['new_value'];
        $category = $this->cagrilintideCategory437(false);
        $postDescription = $post->description;
        $categoryDescription = $category?->description;
        $postHasMarker = is_string($postDescription) && str_contains($postDescription, 'REDEFINE 1');
        $categoryHasMarker = is_string($categoryDescription) && str_contains($categoryDescription, 'REDEFINE 1');
        if ($postHasMarker || $categoryHasMarker) {
            $this->log($slug, 'description', 'skipped: REDEFINE 1 already present');

            return;
        }

        if ($postDescription === $old) {
            $post->description = $new;
            $this->log($slug, 'description', 'wrote education_posts.description');

            return;
        }

        if ($postDescription !== null && $postDescription !== '') {
            $this->log($slug, 'description', 'skipped: old value does not match');

            return;
        }

        $category = $this->cagrilintideCategory437(true);
        if (! $category instanceof ProductCategory) {
            return;
        }
        if ((int) $category->id === 592) {
            $this->log($slug, 'description', 'skipped: category 592 is not touched');

            return;
        }
        if ($category->description !== $old) {
            $this->log($slug, 'description', 'skipped: category 437 description does not match');

            return;
        }

        $category->description = $new;
        $category->saveQuietly();
        $this->wroteCategory = true;
        $this->log($slug, 'description', 'wrote product_categories.description for category 437');
    }

    private function cagrilintideCategory437(bool $logFailure): ?ProductCategory
    {
        $rows = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', [self::CAGRI_SLUG])
            ->orderBy('id')
            ->get();

        if ($rows->count() !== 1) {
            if ($logFailure) {
                $this->log(self::CAGRI_SLUG, 'description', 'skipped: cagrilintide category match count is '.$rows->count());
            }

            return null;
        }

        /** @var ProductCategory $category */
        $category = $rows->first();
        if ((int) $category->id !== self::CAGRI_CATEGORY_ID) {
            if ($logFailure) {
                $this->log(self::CAGRI_SLUG, 'description', 'skipped: category id is '.$category->id.' not 437');
            }

            return null;
        }

        return $category;
    }

    /**
     * @param  list<array<string, mixed>>  $replacements
     */
    private function applyReplacements(mixed $current, string $slug, array $replacements): mixed
    {
        if ($current === null) {
            $items = [];
        } elseif (! is_array($current) || ! array_is_list($current)) {
            $this->log($slug, 'potential_applications', 'skipped: value is not a list');

            return $current;
        } else {
            $items = $current;
        }

        $changed = false;
        foreach ($replacements as $replacement) {
            if (! is_array($replacement)) {
                continue;
            }
            $index = $replacement['index'];
            if ($this->replacementSkipped($items, $replacement)) {
                $this->log($slug, 'potential_applications', 'skipped ['.$index.'] (guard)');

                continue;
            }
            if (! array_key_exists($index, $items) || $items[$index] !== ($replacement['old_item'] ?? null)) {
                $this->log($slug, 'potential_applications', 'skipped ['.$index.'] (old value does not match)');

                continue;
            }
            $items[$index] = $replacement['new_item'];
            $changed = true;
            $this->log($slug, 'potential_applications', 'replaced ['.$index.']');
        }

        return $changed ? $items : $current;
    }

    /**
     * @param  list<mixed>  $items
     * @param  array<string, mixed>  $replacement
     */
    private function replacementSkipped(array $items, array $replacement): bool
    {
        $skip = (string) ($replacement['skip_if'] ?? '');
        $new = $replacement['new_item'] ?? null;
        if ($skip === 'any item title equals new_item.title') {
            return $this->anyItemTitleEquals($items, is_array($new) ? ($new['title'] ?? null) : null);
        }
        if (preg_match('/^item \[(\d+)\]\.description equals new_item\.description$/', $skip, $match) === 1) {
            $index = (int) $match[1];
            $description = is_array($new) ? ($new['description'] ?? null) : null;

            return is_array($items[$index] ?? null) && ($items[$index]['description'] ?? null) === $description;
        }
        if (preg_match("/^any item title equals '(.+)'$/", $skip, $match) === 1) {
            return $this->anyItemTitleEquals($items, $match[1]);
        }
        if (preg_match("/^any item title starts with '(.+)'$/", $skip, $match) === 1) {
            foreach ($items as $item) {
                if (is_array($item) && is_string($item['title'] ?? null) && str_starts_with($item['title'], $match[1])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function replaceAreaZero(mixed $current, string $slug, array $spec): mixed
    {
        if ($current === null) {
            $items = [];
        } elseif (! is_array($current) || ! array_is_list($current)) {
            $this->log($slug, 'areas_of_research', 'skipped: value is not a list');

            return $current;
        } else {
            $items = $current;
        }

        $item = $items[0] ?? null;
        if (is_array($item) && is_string($item['description'] ?? null) && str_contains($item['description'], 'NDA submitted')) {
            $this->log($slug, 'areas_of_research', 'skipped: already applied');

            return $current;
        }
        if ($item !== ($spec['old_item'] ?? null)) {
            $this->log($slug, 'areas_of_research', 'skipped: old value does not match');

            return $current;
        }

        $items[0] = $spec['new_item'];
        $this->log($slug, 'areas_of_research', 'replaced [0]');

        return $items;
    }

    private function replaceKeyPoint(mixed $current, string $slug, string $old, string $new): mixed
    {
        if ($current === null) {
            $points = [];
        } elseif (! is_array($current) || ! array_is_list($current)) {
            $this->log($slug, 'key_points', 'skipped: value is not a list');

            return $current;
        } else {
            $points = $current;
        }

        foreach ($points as $point) {
            if ($point === $new) {
                $this->log($slug, 'key_points', 'skipped: already applied');

                return $current;
            }
        }
        foreach ($points as $index => $point) {
            if ($point === $old) {
                $points[$index] = $new;
                $this->log($slug, 'key_points', 'replaced in place');

                return $points;
            }
        }

        $this->log($slug, 'key_points', 'old item not found; skipped');

        return $current;
    }

    private function appendKeyPoint(mixed $current, string $slug, string $prefix, string $item): mixed
    {
        if ($current === null) {
            $points = [];
        } elseif (! is_array($current) || ! array_is_list($current)) {
            $this->log($slug, 'key_points', 'skipped: value is not a list');

            return $current;
        } else {
            $points = $current;
        }

        foreach ($points as $point) {
            if (is_string($point) && str_starts_with($point, $prefix)) {
                $this->log($slug, 'key_points', 'append skipped (prefix already present)');

                return $current;
            }
        }

        $points[] = $item;
        $this->log($slug, 'key_points', 'appended');

        return $points;
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function replacePreclinicalFinding(mixed $current, string $slug, array $old, array $new): mixed
    {
        if ($current === null) {
            $sections = [];
        } elseif (! is_array($current) || ! array_is_list($current)) {
            $this->log($slug, 'preclinical_subsections', 'skipped: value is not a list');

            return $current;
        } else {
            $sections = $current;
        }

        $newContent = $new['content'] ?? null;
        foreach ($sections as $section) {
            if (! is_array($section) || ! is_array($section['findings'] ?? null)) {
                continue;
            }
            foreach ($section['findings'] as $finding) {
                if (is_array($finding) && ($finding['content'] ?? null) === $newContent) {
                    $this->log($slug, 'preclinical_subsections', 'skipped: already applied');

                    return $current;
                }
            }
        }

        $section = $sections[0] ?? null;
        if (! is_array($section) || ($section['title'] ?? null) !== 'Metabolic Effects') {
            $this->log($slug, 'preclinical_subsections', 'skipped: subsection [0] is not Metabolic Effects');

            return $current;
        }
        $findings = $section['findings'] ?? null;
        if (! is_array($findings) || ! array_key_exists(1, $findings) || $findings[1] !== $old) {
            $this->log($slug, 'preclinical_subsections', 'skipped: old value does not match');

            return $current;
        }

        $findings[1] = $new;
        $sections[0]['findings'] = $findings;
        $this->log($slug, 'preclinical_subsections', 'replaced [0].findings[1]');

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyBpcRegulatory(EducationPost $post, array $spec): void
    {
        $slug = self::BPC_SLUG;
        $column = 'regulatory_subsections';
        $sections = $this->asList($post->regulatory_subsections, $slug, $column);
        if (! is_array($sections)) {
            return;
        }

        $newSubsection = $spec['new_compounding_subsection'];
        $newTitle = is_array($newSubsection) ? ($newSubsection['title'] ?? null) : null;
        foreach ($sections as $section) {
            if (is_array($section) && ($section['title'] ?? null) === $newTitle) {
                $this->log($slug, $column, 'skipped: already applied');

                return;
            }
        }

        $changed = false;
        foreach ($sections as $index => $section) {
            if (! is_array($section) || ($section['title'] ?? null) !== 'United States') {
                continue;
            }
            $entries = $section['entries'] ?? null;
            if (! is_array($entries) || ! isset($entries[0]) || ! is_array($entries[0])) {
                $this->log($slug, $column, 'skipped United States (entries[0] is missing)');

                break;
            }
            if (($entries[0]['value'] ?? null) !== $spec['old_united_states_value']) {
                $this->log($slug, $column, 'skipped United States (old value does not match)');

                break;
            }
            $entries[0]['value'] = $spec['new_united_states_value'];
            $sections[$index]['entries'] = $entries;
            $changed = true;
            $this->log($slug, $column, 'replaced United States entries[0].value');

            break;
        }

        $oldSubsection = $spec['old_compounding_subsection'];
        $oldTitle = is_array($oldSubsection) ? ($oldSubsection['title'] ?? null) : null;
        $found = false;
        foreach ($sections as $index => $section) {
            if (! is_array($section) || ($section['title'] ?? null) !== $oldTitle) {
                continue;
            }
            $found = true;
            if ($section !== $oldSubsection) {
                $this->log($slug, $column, 'skipped compounding subsection (old value does not match)');

                break;
            }
            $sections[$index] = $newSubsection;
            $changed = true;
            $this->log($slug, $column, 'replaced compounding subsection');

            break;
        }
        if (! $found) {
            $this->log($slug, $column, 'skipped compounding subsection (not found)');
        }

        if ($changed) {
            $post->regulatory_subsections = $sections;
        }
    }

    /**
     * Null becomes an empty list so an append op can run. A non-list is logged
     * and left untouched.
     *
     * @return list<mixed>|null
     */
    private function asList(mixed $current, string $slug, string $column): ?array
    {
        if ($current === null) {
            return [];
        }
        if (! is_array($current) || ! array_is_list($current)) {
            $this->log($slug, $column, 'skipped: value is not a list');

            return null;
        }

        return $current;
    }

    /**
     * @param  list<mixed>  $sections
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return list<mixed>
     */
    private function replaceNamedSubsection(
        array $sections,
        string $slug,
        string $column,
        string $title,
        array $old,
        array $new,
        callable $skipIf,
        string $skipReason
    ): array {
        foreach ($sections as $section) {
            if (is_array($section) && ($section['title'] ?? null) === $title && $skipIf($section)) {
                $this->log($slug, $column, 'skipped '.$title.' ('.$skipReason.')');

                return $sections;
            }
        }

        foreach ($sections as $index => $section) {
            if (! is_array($section) || ($section['title'] ?? null) !== $title) {
                continue;
            }
            if ($section !== $old) {
                $this->log($slug, $column, 'skipped '.$title.' (old value does not match)');

                return $sections;
            }
            $sections[$index] = $new;
            $this->log($slug, $column, 'replaced '.$title);

            return $sections;
        }

        $this->log($slug, $column, 'skipped '.$title.' (subsection not found)');

        return $sections;
    }

    /**
     * @param  list<mixed>  $sections
     * @param  array<string, mixed>  $new
     * @return list<mixed>
     */
    private function appendSubsection(array $sections, string $slug, string $column, array $new, string $titlePrefix): array
    {
        foreach ($sections as $section) {
            $title = is_array($section) ? ($section['title'] ?? null) : null;
            if (is_string($title) && str_starts_with($title, $titlePrefix)) {
                $this->log($slug, $column, 'append skipped (title prefix already present)');

                return $sections;
            }
        }

        $sections[] = $new;
        $this->log($slug, $column, 'appended subsection');

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function firstEntryValue(array $section): ?string
    {
        $entries = $section['entries'] ?? null;
        if (! is_array($entries) || ! isset($entries[0]) || ! is_array($entries[0])) {
            return null;
        }
        $value = $entries[0]['value'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function entriesContain(array $section, string $needle): bool
    {
        $entries = $section['entries'] ?? null;
        if (! is_array($entries)) {
            return false;
        }
        foreach ($entries as $entry) {
            if (is_array($entry) && is_string($entry['value'] ?? null) && str_contains($entry['value'], $needle)) {
                return true;
            }
        }

        return false;
    }

    private function anyTitleStartsWith(mixed $sections, string $prefix): bool
    {
        if (! is_array($sections)) {
            return false;
        }
        foreach ($sections as $section) {
            $title = is_array($section) ? ($section['title'] ?? null) : null;
            if (is_string($title) && str_starts_with($title, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function anyCitationContains(mixed $references, string $needle): bool
    {
        if (! is_array($references)) {
            return false;
        }
        foreach ($references as $reference) {
            $citation = is_array($reference) ? ($reference['citation'] ?? null) : null;
            if (is_string($citation) && str_contains($citation, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function anyQuestionEquals(mixed $faqs, string $question): bool
    {
        if (! is_array($faqs)) {
            return false;
        }
        foreach ($faqs as $faq) {
            if (is_array($faq) && ($faq['question'] ?? null) === $question) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<mixed>  $items
     */
    private function anyItemTitleEquals(array $items, mixed $title): bool
    {
        if (! is_string($title)) {
            return false;
        }
        foreach ($items as $item) {
            if (is_array($item) && ($item['title'] ?? null) === $title) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $fields
     */
    private function snapshot(EducationPost $post, array $fields): string
    {
        return json_encode($post->only($fields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function log(string $slug, string $column, string $message): void
    {
        $line = $slug.' '.$column.': '.$message;
        $this->log[] = $line;
        $this->command?->line($line);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $path = database_path('seeders/data/encyclopedia-field-updates/2026-10-19/'.$file);
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Encyclopedia field payload is not an object: '.$file);
        }

        return $decoded;
    }
}
