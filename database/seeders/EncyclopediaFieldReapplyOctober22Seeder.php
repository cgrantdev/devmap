<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaPostMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Re-apply retatrutide, GHK-Cu, and cagrilintide encyclopedia fields
 * (22 October 2026) onto the category-linked post the page renders.
 *
 * Each slug's writes run in one DB::transaction. A legacy unlinked row
 * is never updated. When normalize(current) equals normalize(new_value),
 * the column is reported as "skipped (marker present)" so a second run
 * stays idempotent even for ops that have no skip marker of their own
 * (GHK-Cu research_url, molecular_formula, molecular_weight, cas).
 *
 * Source payload sha256 19ff7f45b21a5819d547cea0855d0327d633b2f288895d493494de6984956ef3.
 */
class EncyclopediaFieldReapplyOctober22Seeder extends Seeder
{
    public const SOURCE_PAYLOAD_SHA256 = '19ff7f45b21a5819d547cea0855d0327d633b2f288895d493494de6984956ef3';

    public const RETA_SLUG = 'retatrutide';

    public const GHK_SLUG = 'ghk-cu';

    public const CAGRI_SLUG = 'cagrilintide';

    public const CAGRI_CATEGORY_ID = 437;

    public const UPDATED = 'updated';

    public const MARKER = 'skipped (marker present)';

    public const MISMATCH = 'skipped (old mismatch)';

    private const LABEL = 'reapply 2026-10-22';

    /** @var array<string, string> */
    private const FILES = [
        self::RETA_SLUG => 'retatrutide.json',
        self::CAGRI_SLUG => 'cagrilintide.json',
        self::GHK_SLUG => 'ghk-cu.json',
    ];

    /** @var array<string, int> */
    private const RANK = [
        self::MARKER => 1,
        self::MISMATCH => 2,
        self::UPDATED => 3,
    ];

    /** @var array<string, string> */
    public array $report = [];

    /** @var list<string> */
    public array $log = [];

    /** @var list<string> */
    public array $warnings = [];

    /** @var list<string> */
    public array $summary = [];

    /** @var array<string, array<string, string>> */
    private array $columnStatus = [];

    public function run(): void
    {
        $this->log = [];
        $this->warnings = [];
        $this->summary = [];
        $this->columnStatus = [];
        $this->report = [];

        foreach (self::FILES as $slug => $file) {
            $this->updateSlug($slug, $this->payload($file));
        }
    }

    /**
     * Recursive comparison normalizer from the payload: CRLF/CR to LF,
     * NBSP to space, rtrim each line, then trim. Writes stay verbatim.
     */
    public static function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = str_replace(["\r\n", "\r"], "\n", $value);
            $value = str_replace("\u{00A0}", ' ', $value);
            $lines = array_map(rtrim(...), explode("\n", $value));

            return trim(implode("\n", $lines));
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = self::normalize($item);
            }

            return $normalized;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateSlug(string $slug, array $payload): void
    {
        $columns = $payload['columns'] ?? null;
        if (! is_array($columns)) {
            throw new RuntimeException('Field payload is missing columns for '.$slug.'.');
        }

        $match = EncyclopediaPostMatch::find($slug, $this->command, self::LABEL);
        foreach ($match->lines as $line) {
            $this->log[] = $line;
        }

        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost || ! $match->category instanceof ProductCategory) {
            $this->report[$slug] = $match->status;
            if (is_string($match->warning)) {
                $this->warnings[] = $match->warning;
                $this->log[] = $match->warning;
            }
            $this->emitSummary($slug.': '.$match->status);

            return;
        }

        $post = $match->post;
        $category = $match->category;
        DB::transaction(function () use ($slug, $post, $category, $columns): void {
            $this->apply($slug, $post, $category, $columns);
            if ($post->isDirty()) {
                $post->saveQuietly();
            }
        });

        $overall = self::MARKER;
        foreach ($this->columnSpecs($columns) as $column => $spec) {
            $status = $this->columnStatus[$slug][$column] ?? self::MISMATCH;
            if (! isset($this->columnStatus[$slug][$column])) {
                $this->mismatch($slug, $column, $post->{$column});
                $status = self::MISMATCH;
            }
            if (self::RANK[$status] > self::RANK[$overall]) {
                $overall = $status;
            }
            $this->emitSummary($slug.'.'.$column.': '.$status);
        }

        $this->report[$slug] = $overall;
        $this->emitSummary($slug.': '.$overall);
    }

    /**
     * @param  array<string, mixed>  $columns
     */
    private function apply(string $slug, EducationPost $post, ProductCategory $category, array $columns): void
    {
        foreach ($this->columnSpecs($columns) as $column => $spec) {
            $this->applyColumn($slug, $post, $category, $column, $spec);
        }
    }

    /**
     * @param  array<string, mixed>  $columns
     * @return array<string, array<string, mixed>>
     */
    private function columnSpecs(array $columns): array
    {
        $specs = [];
        foreach ($columns as $column => $spec) {
            if (is_string($column) && is_array($spec)) {
                $specs[$column] = $spec;
            }
        }

        return $specs;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyColumn(string $slug, EducationPost $post, ProductCategory $category, string $column, array $spec): void
    {
        if ($slug === self::RETA_SLUG && $column === 'human_use_subsections') {
            $this->appendSubsectionIfOld($post, $slug, $column, $spec, 'Reported safety signals outside clinical trials');

            return;
        }
        if ($slug === self::RETA_SLUG && $column === 'regulatory_important_note') {
            $this->applyRetatrutideNote($post, $spec);

            return;
        }
        if ($slug === self::RETA_SLUG && $column === 'key_points') {
            $this->applyRetatrutideKeyPoints($post, $spec);

            return;
        }
        if ($slug === self::CAGRI_SLUG && $column === 'description') {
            $this->applyDescription($post, $category, $spec);

            return;
        }
        if ($slug === self::CAGRI_SLUG && $column === 'potential_applications') {
            $this->applyReplacements($post, $slug, $spec);

            return;
        }
        if ($slug === self::GHK_SLUG && $column === 'potential_applications') {
            $this->applyReplacements($post, $slug, $spec);

            return;
        }
        if ($slug === self::CAGRI_SLUG && $column === 'areas_of_research') {
            $this->applyAreas($post, $spec);

            return;
        }
        if ($slug === self::CAGRI_SLUG && $column === 'key_points') {
            $this->applyCagrilintideKeyPoints($post, $spec);

            return;
        }
        if ($slug === self::CAGRI_SLUG && $column === 'preclinical_subsections') {
            $this->applyPreclinical($post, $spec);

            return;
        }
        if ($slug === self::CAGRI_SLUG && $column === 'faqs') {
            $this->writeFaqs($post, $slug, $spec);

            return;
        }
        if ($slug === self::GHK_SLUG && $column === 'faqs') {
            $this->writeFaqs($post, $slug, $spec);

            return;
        }
        if ($slug === self::GHK_SLUG && $column === 'human_use_subsections') {
            $this->applyGhkHumanUse($post, $spec);

            return;
        }
        if ($slug === self::GHK_SLUG && $column === 'regulatory_subsections') {
            $this->applyGhkRegulatory($post, $spec);

            return;
        }
        if ($slug === self::GHK_SLUG && $column === 'references') {
            $this->applyGhkReferences($post, $spec);

            return;
        }
        if ($slug === self::GHK_SLUG && $column === 'key_points') {
            $this->appendItemIfOld($post, $slug, $column, $spec['old_value'], $spec['new_item'], 'Evidence limits (as of ');

            return;
        }
        if ($slug === self::GHK_SLUG && $column === 'half_life') {
            $this->writeIfEmpty($post, $slug, $column, $spec['new_value']);

            return;
        }

        $marker = $this->markerFor($slug, $column, $spec);
        $this->writeNewIfOld($post, $slug, $column, $spec, $marker);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function markerFor(string $slug, string $column, array $spec): ?callable
    {
        if ($slug === self::CAGRI_SLUG) {
            return match ($column) {
                'overview' => fn (mixed $current) => $this->stringContains($current, 'Status as of '),
                'background' => fn (mixed $current) => $this->stringContains($current, "159\u{2013}195 hours"),
                'half_life' => fn (mixed $current) => is_string($current) && str_starts_with($current, 'Human:'),
                'human_use_intro' => fn (mixed $current) => $this->stringContains($current, 'This page gives no dose'),
                'human_use_subsections' => fn (mixed $current) => $this->anyTitleStartsWith($current, 'Clinical program (as of'),
                'regulatory_subsections' => fn (mixed $current) => $this->anyTitleStartsWith($current, 'Development and regulatory status (as of'),
                'regulatory_important_note' => fn (mixed $current) => $this->stringContains($current, 'not Novo Nordisk trial supply'),
                'potential_applications_important_context' => fn (mixed $current) => $this->stringContains($current, 'does not predict'),
                'conclusion' => fn (mixed $current) => $this->stringContains($current, 'neither CagriSema nor cagrilintide is approved'),
                'references' => fn (mixed $current) => $this->anyCitationContains($current, 'PMID 40544433'),
                default => null,
            };
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyRetatrutideNote(EducationPost $post, array $spec): void
    {
        $column = 'regulatory_important_note';
        $current = $post->{$column};
        if ($this->stringContains($current, 'do not establish causation')) {
            $this->mark(self::RETA_SLUG, $column, self::MARKER);

            return;
        }
        foreach ($spec['new_values'] ?? [] as $new) {
            if ($this->same($current, $new)) {
                $this->mark(self::RETA_SLUG, $column, self::MARKER);

                return;
            }
        }
        foreach ($spec['old_values'] ?? [] as $index => $old) {
            if ($this->same($current, $old)) {
                $post->{$column} = $spec['new_values'][$index];
                $this->mark(self::RETA_SLUG, $column, self::UPDATED);

                return;
            }
        }

        $this->mismatch(self::RETA_SLUG, $column, $current);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyRetatrutideKeyPoints(EducationPost $post, array $spec): void
    {
        $column = 'key_points';
        $current = $post->{$column};
        if ($this->anyStringStartsWith($current, 'Safety reports outside trials (Oct 2026)')) {
            $this->mark(self::RETA_SLUG, $column, self::MARKER);

            return;
        }
        if (! is_array($current) || ! array_is_list($current)) {
            $this->mismatch(self::RETA_SLUG, $column, $current);

            return;
        }
        foreach ($spec['old_values'] ?? [] as $old) {
            if ($this->same($current, $old)) {
                $current[] = $spec['new_item'];
                $post->{$column} = $current;
                $this->mark(self::RETA_SLUG, $column, self::UPDATED);

                return;
            }
        }

        $this->mismatch(self::RETA_SLUG, $column, $current);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function appendSubsectionIfOld(EducationPost $post, string $slug, string $column, array $spec, string $titlePrefix): void
    {
        $current = $post->{$column};
        if ($this->anyTitleStartsWith($current, $titlePrefix)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if (! is_array($current) || ! array_is_list($current) || ! $this->same($current, $spec['old_value'] ?? null)) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $current[] = $spec['new_subsection'];
        $post->{$column} = $current;
        $this->mark($slug, $column, self::UPDATED);
    }

    private function appendItemIfOld(EducationPost $post, string $slug, string $column, mixed $old, mixed $item, string $prefix): void
    {
        $current = $post->{$column};
        if ($this->anyStringStartsWith($current, $prefix)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if (! is_array($current) || ! array_is_list($current) || ! $this->same($current, $old)) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $current[] = $item;
        $post->{$column} = $current;
        $this->mark($slug, $column, self::UPDATED);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function writeNewIfOld(EducationPost $post, string $slug, string $column, array $spec, ?callable $marker): void
    {
        $current = $post->{$column};
        $new = $spec['new_value'] ?? null;
        if ($this->same($current, $new)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if ($marker !== null && $marker($current)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if (! array_key_exists('old_value', $spec) || ! $this->same($current, $spec['old_value'])) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $post->{$column} = $new;
        $this->mark($slug, $column, self::UPDATED);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function writeFaqs(EducationPost $post, string $slug, array $spec): void
    {
        $column = 'faqs';
        $current = $post->{$column};
        $new = $spec['new_value'];
        if ($this->same($current, $new)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        $question = $new[3]['question'] ?? null;
        if (is_string($question) && $this->anyQuestionEquals($current, $question)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if ($this->normalize($this->projectFaqs($current)) !== $this->normalize($this->projectFaqs($spec['old_value'] ?? null))) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $post->{$column} = $new;
        $this->mark($slug, $column, self::UPDATED);
    }

    private function writeIfEmpty(EducationPost $post, string $slug, string $column, mixed $new): void
    {
        $current = $post->{$column};
        if ($this->same($current, $new)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if ($current === null || $current === '') {
            $post->{$column} = $new;
            $this->mark($slug, $column, self::UPDATED);

            return;
        }

        $this->mark($slug, $column, self::MARKER);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyDescription(EducationPost $post, ProductCategory $category, array $spec): void
    {
        $slug = self::CAGRI_SLUG;
        $column = 'description';
        $old = $spec['old_value'];
        $new = $spec['new_value'];
        $postDescription = $post->description;
        $categoryDescription = $category->description;
        if ($this->stringContains($postDescription, 'REDEFINE 1') || $this->stringContains($categoryDescription, 'REDEFINE 1')) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if ($this->same($postDescription, $new) || $this->same($categoryDescription, $new)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if ($this->same($postDescription, $old)) {
            $post->description = $new;
            $this->mark($slug, $column, self::UPDATED);

            return;
        }
        if ($postDescription !== null && $postDescription !== '') {
            $this->mismatch($slug, $column, $postDescription);

            return;
        }
        if ((int) $category->id === 592 || (int) $category->id !== self::CAGRI_CATEGORY_ID) {
            $message = self::LABEL.' '.$slug.'.'.$column.': category id '.$category->id.' is not '.self::CAGRI_CATEGORY_ID.'; category description not written';
            $this->warnings[] = $message;
            $this->log[] = $message;
            Log::warning($message);
            $this->command?->error($message);
            fwrite(STDERR, $message.PHP_EOL);
            $this->mark($slug, $column, self::MISMATCH);

            return;
        }
        if (! $this->same($categoryDescription, $old)) {
            $this->mismatch($slug, $column, $categoryDescription);

            return;
        }

        $category->description = $new;
        $category->saveQuietly();
        $this->mark($slug, $column, self::UPDATED);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyReplacements(EducationPost $post, string $slug, array $spec): void
    {
        $column = 'potential_applications';
        $current = $post->{$column};
        if (! is_array($current) || ! array_is_list($current)) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $items = $current;
        $changed = false;
        $sawMismatch = false;
        foreach ($spec['replacements'] ?? [] as $replacement) {
            if (! is_array($replacement)) {
                continue;
            }
            $index = $replacement['index'];
            if ($this->replacementSkipped($items, $replacement)) {
                continue;
            }
            if (! array_key_exists($index, $items) || ! $this->same($items[$index], $replacement['old_item'] ?? null)) {
                $this->mismatch($slug, $column, $items[$index] ?? $current);
                $sawMismatch = true;

                continue;
            }
            $items[$index] = $replacement['new_item'];
            $changed = true;
        }

        if ($changed) {
            $post->{$column} = $items;
            $this->mark($slug, $column, self::UPDATED);

            return;
        }
        if (! $sawMismatch) {
            $this->mark($slug, $column, self::MARKER);
        }
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

            return is_array($items[$index] ?? null) && $this->same($items[$index]['description'] ?? null, $description);
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
    private function applyAreas(EducationPost $post, array $spec): void
    {
        $slug = self::CAGRI_SLUG;
        $column = 'areas_of_research';
        $current = $post->{$column};
        if (! is_array($current) || ! array_is_list($current)) {
            $this->mismatch($slug, $column, $current);

            return;
        }
        $item = $current[0] ?? null;
        if (is_array($item) && $this->stringContains($item['description'] ?? null, 'NDA submitted')) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if (! $this->same($item, $spec['old_item'] ?? null)) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $current[0] = $spec['new_item'];
        $post->{$column} = $current;
        $this->mark($slug, $column, self::UPDATED);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyCagrilintideKeyPoints(EducationPost $post, array $spec): void
    {
        $slug = self::CAGRI_SLUG;
        $column = 'key_points';
        $current = $post->{$column};
        if (! is_array($current) || ! array_is_list($current)) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $points = $current;
        $changed = false;
        $sawMismatch = false;
        foreach ($spec['operations'] ?? [] as $operation) {
            if (! is_array($operation)) {
                continue;
            }
            if (($operation['type'] ?? null) === 'replace_in_place') {
                $new = $operation['new_item'];
                $old = $operation['old_item'];
                $already = false;
                foreach ($points as $point) {
                    if ($this->same($point, $new)) {
                        $already = true;
                        break;
                    }
                }
                if ($already) {
                    continue;
                }
                $replaced = false;
                foreach ($points as $index => $point) {
                    if ($this->same($point, $old)) {
                        $points[$index] = $new;
                        $replaced = true;
                        $changed = true;
                        break;
                    }
                }
                if (! $replaced) {
                    $this->mismatch($slug, $column, $current);
                    $sawMismatch = true;
                }
            } elseif (($operation['type'] ?? null) === 'append') {
                $prefix = (string) ($operation['skip_if_any_item_starts_with'] ?? 'Status (as of ');
                if ($this->anyStringStartsWith($points, $prefix)) {
                    continue;
                }
                $points[] = $operation['new_item'];
                $changed = true;
            }
        }

        if ($changed) {
            $post->{$column} = $points;
            $this->mark($slug, $column, self::UPDATED);

            return;
        }
        if (! $sawMismatch) {
            $this->mark($slug, $column, self::MARKER);
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyPreclinical(EducationPost $post, array $spec): void
    {
        $slug = self::CAGRI_SLUG;
        $column = 'preclinical_subsections';
        $current = $post->{$column};
        if (! is_array($current) || ! array_is_list($current)) {
            $this->mismatch($slug, $column, $current);

            return;
        }
        $new = $spec['new_finding'];
        foreach ($current as $section) {
            if (! is_array($section)) {
                continue;
            }
            foreach ($section['findings'] ?? [] as $finding) {
                if (is_array($finding) && $this->same($finding['content'] ?? null, $new['content'] ?? null)) {
                    $this->mark($slug, $column, self::MARKER);

                    return;
                }
            }
        }
        $section = $current[0] ?? null;
        if (! is_array($section) || ($section['title'] ?? null) !== 'Metabolic Effects' || ! is_array($section['findings'] ?? null)) {
            $this->mismatch($slug, $column, $current);

            return;
        }
        if (! array_key_exists(1, $section['findings']) || ! $this->same($section['findings'][1], $spec['old_finding'] ?? null)) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $current[0]['findings'][1] = $new;
        $post->{$column} = $current;
        $this->mark($slug, $column, self::UPDATED);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyGhkHumanUse(EducationPost $post, array $spec): void
    {
        $slug = self::GHK_SLUG;
        $column = 'human_use_subsections';
        $current = $post->{$column};
        if ($this->anyTitleStartsWith($current, 'Evidence limits (as of')) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if (! is_array($current) || ! array_is_list($current) || ! $this->same($current, $spec['old_value_whole_array'] ?? null)) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $sections = $current;
        $sections = $this->replaceNamed(
            $sections,
            'Injectable Research',
            $spec['old_injectable_subsection'],
            $spec['new_injectable_subsection'],
            fn (array $section) => str_contains($this->firstEntryValue($section), 'has not been tested on its own'),
        );
        $sections = $this->replaceNamed(
            $sections,
            'Dermatological Studies',
            $spec['old_dermatological_subsection'],
            $spec['new_dermatological_subsection'],
            fn (array $section) => ! $this->entriesContain($section, 'vitamin C'),
        );
        if (! $this->anyTitleStartsWith($sections, 'Evidence limits (as of')) {
            $sections[] = $spec['new_subsection'];
        }
        $post->{$column} = $sections;
        $this->mark($slug, $column, self::UPDATED);
    }

    /**
     * @param  list<mixed>  $sections
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return list<mixed>
     */
    private function replaceNamed(array $sections, string $title, array $old, array $new, callable $skip): array
    {
        foreach ($sections as $index => $section) {
            if (! is_array($section) || ($section['title'] ?? null) !== $title) {
                continue;
            }
            if ($skip($section) || ! $this->same($section, $old)) {
                return $sections;
            }
            $sections[$index] = $new;

            return $sections;
        }

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyGhkRegulatory(EducationPost $post, array $spec): void
    {
        $slug = self::GHK_SLUG;
        $column = 'regulatory_subsections';
        $current = $post->{$column};
        if ($this->anyTitleStartsWith($current, 'FDA compounding status (as of')) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if (! is_array($current) || ! array_is_list($current)) {
            $this->mismatch($slug, $column, $current);

            return;
        }
        $matched = false;
        foreach ($spec['old_values'] ?? [] as $old) {
            if ($this->same($current, $old)) {
                $matched = true;
                break;
            }
        }
        if (! $matched) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $insertAt = (int) ($spec['insert_at'] ?? 1);
        array_splice($current, $insertAt, 0, [$spec['new_subsection']]);
        $post->{$column} = $current;
        $this->mark($slug, $column, self::UPDATED);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyGhkReferences(EducationPost $post, array $spec): void
    {
        $slug = self::GHK_SLUG;
        $column = 'references';
        $current = $post->{$column};
        if ($this->anyCitationContains($current, 'PMID 27489425') || $this->same($current, $spec['new_value'] ?? null)) {
            $this->mark($slug, $column, self::MARKER);

            return;
        }
        if (! is_array($current) || ! array_is_list($current)) {
            $this->mismatch($slug, $column, $current);

            return;
        }
        $matched = false;
        foreach ($spec['old_values'] ?? [] as $old) {
            if ($this->same($current, $old)) {
                $matched = true;
                break;
            }
        }
        if (! $matched) {
            $this->mismatch($slug, $column, $current);

            return;
        }

        $tailFrom = count($spec['old_values'][0] ?? []);
        $post->{$column} = array_merge($spec['new_value'], array_slice($current, $tailFrom));
        $this->mark($slug, $column, self::UPDATED);
    }

    private function projectFaqs(mixed $faqs): mixed
    {
        if (! is_array($faqs) || ! array_is_list($faqs)) {
            return $faqs;
        }

        $projected = [];
        foreach ($faqs as $faq) {
            if (! is_array($faq)) {
                $projected[] = $faq;

                continue;
            }
            $projected[] = [
                'question' => trim((string) ($faq['question'] ?? '')),
                'answer' => trim(strip_tags((string) ($faq['answer'] ?? ''))),
            ];
        }

        return $projected;
    }

    private function same(mixed $left, mixed $right): bool
    {
        return self::normalize($left) === self::normalize($right);
    }

    private function stringContains(mixed $value, string $needle): bool
    {
        return is_string($value) && str_contains($value, $needle);
    }

    private function anyTitleStartsWith(mixed $value, string $prefix): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }
            $title = $item['title'] ?? null;
            if (is_string($title) && str_starts_with($title, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function anyStringStartsWith(mixed $value, string $prefix): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (is_string($item) && str_starts_with($item, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function anyCitationContains(mixed $value, string $needle): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (is_array($item) && is_string($item['citation'] ?? null) && str_contains($item['citation'], $needle)) {
                return true;
            }
        }

        return false;
    }

    private function anyQuestionEquals(mixed $value, string $question): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (is_array($item) && $this->same($item['question'] ?? null, $question)) {
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
        foreach ($items as $item) {
            if (is_array($item) && $this->same($item['title'] ?? null, $title)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function firstEntryValue(array $section): string
    {
        $entry = $section['entries'][0] ?? null;

        return is_array($entry) && is_string($entry['value'] ?? null) ? $entry['value'] : '';
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function entriesContain(array $section, string $needle): bool
    {
        foreach ($section['entries'] ?? [] as $entry) {
            $value = is_array($entry) ? ($entry['value'] ?? null) : $entry;
            if (is_string($value) && str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function mark(string $slug, string $column, string $status): void
    {
        $current = $this->columnStatus[$slug][$column] ?? null;
        if ($current === null || self::RANK[$status] > self::RANK[$current]) {
            $this->columnStatus[$slug][$column] = $status;
        }
    }

    private function mismatch(string $slug, string $column, mixed $current): void
    {
        $this->mark($slug, $column, self::MISMATCH);
        $message = self::LABEL.' '.$slug.'.'.$column.': skipped (old mismatch) excerpt='.$this->excerpt($current);
        $this->warnings[] = $message;
        $this->log[] = $message;
        Log::warning($message);
        $this->command?->error($message);
        fwrite(STDERR, $message.PHP_EOL);
    }

    private function excerpt(mixed $current): string
    {
        $normalized = self::normalize($current);
        if (is_string($normalized)) {
            $text = $normalized;
        } else {
            $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $text = is_string($encoded) ? $encoded : '';
        }
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);

        return mb_substr($text, 0, 200);
    }

    private function emitSummary(string $line): void
    {
        $this->summary[] = $line;
        $this->log[] = $line;
        Log::info($line);
        $this->command?->info($line);
        fwrite(STDOUT, $line.PHP_EOL);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $path = database_path('seeders/data/encyclopedia-field-reapply/2026-10-22/'.$file);
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Encyclopedia field payload is not an object: '.$file);
        }

        return $decoded;
    }
}
