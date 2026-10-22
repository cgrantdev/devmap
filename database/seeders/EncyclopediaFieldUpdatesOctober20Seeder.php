<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\NadPlusProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Guarded whole-value replaces for NAD+ and Sermorelin (20 October 2026).
 *
 * /encyclopedia/{slug} does not render whichever education_posts row
 * happens to share that slug. EncyclopediaController::showArticle loads
 * the product category, then the category's educationPost (hasOne on
 * product_category_id). The post slug can differ from the category slug
 * because EducationPost's creating hook fills it from the title, and
 * legacy unlinked posts can occupy the category slug.
 *
 * Lookup is therefore product_categories where LOWER(TRIM(slug)) equals
 * the payload slug, then the education_posts rows with that
 * product_category_id. Exactly one category and exactly one linked post
 * are required. Any other count logs a warning that includes both counts
 * and writes nothing. It does not throw, so a later migration can still
 * run. Brief post ids are not used.
 *
 * CRLF, NBSP, and trailing whitespace are normalized only for the
 * old/new comparison. The stored value is the payload new_value.
 */
class EncyclopediaFieldUpdatesOctober20Seeder extends Seeder
{
    public const NAD_SLUG = 'nad';

    public const SERMORELIN_SLUG = 'sermorelin';

    /** @var array<string, string> */
    private const FILES = [
        self::NAD_SLUG => 'nad.json',
        self::SERMORELIN_SLUG => 'sermorelin.json',
    ];

    /** @var list<string> */
    private const NAD_RENDERED_TEXT = ['overview', 'background', 'conclusion'];

    /** @var array<string, string> */
    public array $report = [
        self::NAD_SLUG => 'missing',
        self::SERMORELIN_SLUG => 'missing',
    ];

    /** @var list<string> */
    public array $log = [];

    public function run(): void
    {
        $this->log = [];
        $this->report = [
            self::NAD_SLUG => 'missing',
            self::SERMORELIN_SLUG => 'missing',
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
        $match = strtolower((string) ($payload['slug_match'] ?? ''));
        if (! str_contains($match, "lower(slug) = '".$slug."'")) {
            throw new RuntimeException('Field payload slug_match does not target '.$slug.'.');
        }

        $columns = $payload['columns'] ?? null;
        if (! is_array($columns)) {
            throw new RuntimeException('Field payload is missing columns for '.$slug.'.');
        }

        $categories = ProductCategory::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', [$slug])
            ->orderBy('id')
            ->get();

        $categoryCount = $categories->count();
        $postCount = $categoryCount === 0
            ? 0
            : EducationPost::query()
                ->whereIn('product_category_id', $categories->pluck('id'))
                ->count();

        if ($categoryCount !== 1 || $postCount !== 1) {
            $this->refuse($slug, $categoryCount, $postCount);

            return;
        }

        /** @var ProductCategory $category */
        $category = $categories->first();
        $post = $category->educationPost;
        if (! $post instanceof EducationPost) {
            $this->refuse($slug, $categoryCount, 0);

            return;
        }

        $this->record(
            '20 Oct 2026 encyclopedia field update: '.$slug.' matched '.$categoryCount.' product_categories and '.$postCount.' education_posts.',
            'info'
        );

        $fields = array_keys($columns);
        $before = $this->snapshot($post, $fields);
        foreach ($columns as $column => $spec) {
            if (! is_string($column) || ! is_array($spec)) {
                continue;
            }
            $this->writeColumn($post, $slug, $column, $spec);
        }

        if ($this->snapshot($post, $fields) === $before) {
            $this->report[$slug] = 'skipped';

            return;
        }

        // saveQuietly skips the JSON-unwrapping hook, which would otherwise
        // mark every JSON column dirty and rewrite it.
        $post->saveQuietly();
        $this->report[$slug] = 'updated';
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function writeColumn(EducationPost $post, string $slug, string $column, array $spec): void
    {
        $current = $post->{$column};
        $old = $spec['old_value'] ?? null;
        $new = $spec['new_value'] ?? null;

        if ($column === 'research_url' && ($current === null || $current === '')) {
            $this->logColumn($slug, $column, 'skipped: empty');

            return;
        }

        if ($this->matchesNew($slug, $column, $current, $new)) {
            $this->logColumn($slug, $column, 'skipped: already applied');

            return;
        }

        if ($this->guardMatches($slug, $column, $current)) {
            $this->logColumn($slug, $column, 'skipped: already applied');

            return;
        }

        if (! $this->matchesOld($slug, $column, $current, $old)) {
            $this->logColumn($slug, $column, 'skipped: old value does not match');

            return;
        }

        $post->{$column} = $this->valueToWrite($slug, $column, $current, $new);
        $this->logColumn($slug, $column, 'wrote');
    }

    private function matchesNew(string $slug, string $column, mixed $current, mixed $new): bool
    {
        return $this->comparable($slug, $column, $current, true) === $this->comparable($slug, $column, $new, true);
    }

    private function matchesOld(string $slug, string $column, mixed $current, mixed $old): bool
    {
        // old_value is already the rendered text. Do not strtr it again.
        return $this->comparable($slug, $column, $current, true) === $this->comparable($slug, $column, $old, false);
    }

    private function comparable(string $slug, string $column, mixed $value, bool $renderStored): mixed
    {
        if ($column === 'faqs') {
            $render = $renderStored && $slug === self::NAD_SLUG;

            return $this->normalize($this->projectFaqs($value, $render));
        }

        if ($renderStored && $slug === self::NAD_SLUG && in_array($column, self::NAD_RENDERED_TEXT, true) && is_string($value)) {
            $value = strtr($value, NadPlusProfile::phraseReplacements());
        }

        return $this->normalize($value);
    }

    /**
     * FAQ comparisons use question and answer only. NAD answers are passed
     * through NadPlusProfile::phraseReplacements() when $render is true,
     * which is the same strtr the article page applies at render time.
     *
     * @return list<array{question: mixed, answer: mixed}>|mixed
     */
    private function projectFaqs(mixed $faqs, bool $render): mixed
    {
        if (! is_array($faqs)) {
            return $faqs;
        }

        $map = $render ? NadPlusProfile::phraseReplacements() : [];
        $projected = [];
        foreach ($faqs as $faq) {
            if (! is_array($faq)) {
                $projected[] = $faq;

                continue;
            }
            $answer = $faq['answer'] ?? null;
            if ($render && is_string($answer)) {
                $answer = strtr($answer, $map);
            }
            $projected[] = [
                'question' => $faq['question'] ?? null,
                'answer' => $answer,
            ];
        }

        return $projected;
    }

    private function guardMatches(string $slug, string $column, mixed $current): bool
    {
        if ($slug === self::NAD_SLUG) {
            return match ($column) {
                'overview' => $this->stringContains($current, 'Nature Metabolism'),
                'background' => $this->stringContains($current, 'mainly from rodent studies')
                    || $this->stringContains($current, 'William John Young')
                    || $this->stringContains($current, 'Walter Christian'),
                'conclusion' => $this->stringContains($current, 'Nat Metab 2026'),
                'regulatory_subsections' => $this->anyTitleStartsWith($current, 'Regulatory status (as of'),
                'references' => $this->anyCitationContains($current, 'PMID 41083806'),
                'human_use_subsections' => $this->anyTitleEquals($current, 'Intravenous NAD+'),
                'mechanism_subsections' => $this->anyItemDescriptionContains($current, 'Cell Metab 2016'),
                'preclinical_subsections' => $this->anyFindingContentContains($current, 'Cell 2013'),
                'half_life' => $current === '',
                'faqs' => $this->anyQuestionEquals($current, 'Do NAD+ levels fall with age in humans?'),
                'human_use_intro' => $this->stringContains($current, 'pilot study'),
                'potential_applications' => $this->anyTitleEquals($current, 'Aging research'),
                'areas_of_research' => $this->anyDescriptionContains($current, 'placebo-controlled trials are lacking'),
                default => false,
            };
        }

        return match ($column) {
            'faqs' => $this->anyQuestionEquals($current, 'What was GEREF Diagnostic used for?'),
            'overview', 'background' => $this->stringContains($current, 'NDA 019863'),
            'human_use_subsections' => $this->anyEntryContains($current, 'NDA 020443')
                || $this->anyEntryContains($current, 'Metabolism 1997'),
            'regulatory_subsections' => $this->anyTitleStartsWith($current, 'FDA history (as of'),
            'potential_applications' => $this->anyTitleEquals($current, 'Sleep')
                || $this->anyDescriptionContains($current, 'Metabolism 1997'),
            'conclusion' => $this->stringContains($current, 'not withdrawn for reasons of safety'),
            'references' => $this->anyCitationContains($current, '74 FR 23407'),
            'half_life' => $this->stringContains($current, 'J Clin Endocrinol Metab 1994'),
            'areas_of_research' => $this->anyNameEquals($current, 'GHRH and sleep'),
            default => false,
        };
    }

    /**
     * Sermorelin FAQ index 1 is unchanged. When the stored item's question
     * and answer match the new item, keep any extra keys on that item.
     */
    private function valueToWrite(string $slug, string $column, mixed $current, mixed $new): mixed
    {
        if ($slug !== self::SERMORELIN_SLUG || $column !== 'faqs' || ! is_array($current) || ! is_array($new)) {
            return $new;
        }

        $merged = $new;
        foreach ($new as $index => $item) {
            $stored = $current[$index] ?? null;
            if (! is_array($item) || ! is_array($stored)) {
                continue;
            }
            $storedPair = $this->normalize([
                'question' => $stored['question'] ?? null,
                'answer' => $stored['answer'] ?? null,
            ]);
            $newPair = $this->normalize([
                'question' => $item['question'] ?? null,
                'answer' => $item['answer'] ?? null,
            ]);
            if ($storedPair === $newPair) {
                $merged[$index] = array_merge($stored, $item);
            }
        }

        return $merged;
    }

    private function stringContains(mixed $value, string $needle): bool
    {
        return is_string($value) && str_contains($value, $needle);
    }

    private function anyTitleEquals(mixed $value, string $title): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['title'] ?? null) === $title || $this->anyTitleEquals($item, $title)) {
                return true;
            }
        }

        return false;
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
            if ((is_string($title) && str_starts_with($title, $prefix)) || $this->anyTitleStartsWith($item, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function anyNameEquals(mixed $value, string $name): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['name'] ?? null) === $name || $this->anyNameEquals($item, $name)) {
                return true;
            }
        }

        return false;
    }

    private function anyDescriptionContains(mixed $value, string $needle): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }
            $description = $item['description'] ?? null;
            if ((is_string($description) && str_contains($description, $needle)) || $this->anyDescriptionContains($item, $needle)) {
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
            if (! is_array($item)) {
                continue;
            }
            $citation = $item['citation'] ?? null;
            if ((is_string($citation) && str_contains($citation, $needle)) || $this->anyCitationContains($item, $needle)) {
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
            if (! is_array($item)) {
                continue;
            }
            if (($item['question'] ?? null) === $question || $this->anyQuestionEquals($item, $question)) {
                return true;
            }
        }

        return false;
    }

    private function anyItemDescriptionContains(mixed $value, string $needle): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $section) {
            if (! is_array($section)) {
                continue;
            }
            foreach ($section['items'] ?? [] as $item) {
                if (is_array($item) && is_string($item['description'] ?? null) && str_contains($item['description'], $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function anyFindingContentContains(mixed $value, string $needle): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $section) {
            if (! is_array($section)) {
                continue;
            }
            foreach ($section['findings'] ?? [] as $finding) {
                if (is_array($finding) && is_string($finding['content'] ?? null) && str_contains($finding['content'], $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function anyEntryContains(mixed $value, string $needle): bool
    {
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $section) {
            if (! is_array($section)) {
                continue;
            }
            foreach ($section['entries'] ?? [] as $entry) {
                $entryValue = is_array($entry) ? ($entry['value'] ?? null) : $entry;
                if (is_string($entryValue) && str_contains($entryValue, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = str_replace("\u{00A0}", ' ', $value);
            $value = str_replace(["\r\n", "\r"], "\n", $value);
            $lines = array_map(rtrim(...), explode("\n", $value));

            return rtrim(implode("\n", $lines));
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item);
            }

            return $normalized;
        }

        return $value;
    }

    /**
     * @param  list<string>  $fields
     */
    private function snapshot(EducationPost $post, array $fields): string
    {
        return json_encode($post->only($fields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private function refuse(string $slug, int $categoryCount, int $postCount): void
    {
        $this->report[$slug] = ($categoryCount > 1 || $postCount > 1) ? 'collision' : 'missing';
        $this->record(
            'WARNING 20 Oct 2026 encyclopedia field update: '.$slug.' matched '.$categoryCount.' product_categories and '.$postCount.' education_posts; nothing written',
            'warning'
        );
    }

    private function record(string $line, string $level): void
    {
        $this->log[] = $line;
        if ($level === 'warning') {
            Log::warning($line);
            $this->command?->warn($line);

            return;
        }

        Log::info($line);
        $this->command?->info($line);
    }

    private function logColumn(string $slug, string $column, string $message): void
    {
        $line = $slug.' '.$column.': '.$message;
        $this->log[] = $line;
        Log::info($line);
        $this->command?->line($line);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $path = database_path('seeders/data/encyclopedia-field-updates/2026-10-20/'.$file);
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Encyclopedia field payload is not an object: '.$file);
        }

        return $decoded;
    }
}
