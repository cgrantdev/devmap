<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Guarded field updates for 22 October 2026.
 *
 * BPC-157 gets the 16 Oct + 19 Oct regulatory re-apply, then the Australia
 * subsection, plus the 19 Oct note and key point, the five Content QA FAQs,
 * and three references. GHK-Cu, TB-500, retatrutide, and CJC-1295 get the
 * Australia subsection and the same three references.
 *
 * GHK-Cu accepts either the live value or the value after the separate
 * 2026_10_22_130000 re-apply. Retatrutide accepts either the live value
 * or the value after the separate 2026_10_22_140000 Senate-letter update.
 * Australia is always appended at the end. A skipped column does not
 * block the others. Nothing is written for a slug unless exactly one
 * category and exactly one linked post match.
 */
class EncyclopediaFieldUpdatesOctober22Seeder extends Seeder
{
    public const LABEL = '22 Oct 2026 encyclopedia update';

    public const TIMESTAMP = '2026_10_22_120000';

    /** @var array<string, string> */
    private const FILES = [
        'bpc-157' => 'bpc-157.json',
        'ghk-cu' => 'ghk-cu.json',
        'tb-500' => 'tb-500.json',
        'retatrutide' => 'retatrutide.json',
        'cjc-1295' => 'cjc-1295.json',
    ];

    /** @var array<string, string> */
    public array $report = [];

    /** @var array<string, array{categories: int, posts: int, slug_matches: int}> */
    public array $report_counts = [];

    /** @var list<string> */
    public array $log = [];

    public static function directory(): string
    {
        return database_path('seeders/data/encyclopedia-field-updates/2026-10-22');
    }

    public function run(): void
    {
        $this->log = [];
        $this->report = [];
        $this->report_counts = [];
        $this->assertPayload();

        foreach (self::FILES as $slug => $file) {
            $this->report[$slug] = 'missing';
            $this->updateSlug($slug, $this->payload($file));
        }
    }

    /**
     * A payload that still contains the ship-date placeholder is a code bug.
     * Content values are compared separately and never throw.
     */
    public function assertPayload(?string $directory = null): void
    {
        $directory ??= self::directory();
        foreach (self::FILES as $slug => $file) {
            $path = $directory.'/'.$file;
            if (! is_file($path)) {
                throw new RuntimeException('Missing encyclopedia payload '.$path.'.');
            }
            $raw = file_get_contents($path);
            if (! is_string($raw) || str_contains($raw, '[SHIP DATE]')) {
                throw new RuntimeException('Payload still contains [SHIP DATE] in '.$file.'.');
            }
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new RuntimeException('Payload is not valid JSON: '.$file.'.', 0, $exception);
            }
            if (! is_array($decoded) || ($decoded['slug'] ?? null) !== $slug) {
                throw new RuntimeException('Payload slug does not match '.$file.'.');
            }
            $migration = $decoded['migration'] ?? null;
            if (! is_array($migration) || ($migration['seeder'] ?? null) !== self::class && ($migration['seeder'] ?? null) !== 'EncyclopediaFieldUpdatesOctober22Seeder') {
                throw new RuntimeException('Payload migration seeder does not match '.$file.'.');
            }
            if (($migration['timestamp'] ?? null) !== self::TIMESTAMP) {
                throw new RuntimeException('Payload migration timestamp does not match '.$file.'.');
            }
            if (! isset($decoded['as_of'], $decoded['normalize'], $decoded['columns']) || ! is_array($decoded['columns'])) {
                throw new RuntimeException('Payload is missing columns for '.$slug.'.');
            }
        }
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
        $this->report_counts[$slug] = $match->counts;
        foreach ($match->messages as $message) {
            $this->log[] = $message;
        }

        if ($match->outcome !== 'ok' || ! $match->post instanceof EducationPost) {
            $this->report[$slug] = $match->outcome === 'ok' ? 'missing' : $match->outcome;

            return;
        }

        $post = $match->post;
        $fields = array_keys($columns);
        $before = $this->snapshot($post, $fields);
        $this->applyColumns($slug, $post, $columns);
        if ($this->snapshot($post, $fields) === $before) {
            $this->report[$slug] = 'unchanged';
            $this->command?->info(self::LABEL.': '.$post->slug.' already current.');

            return;
        }

        // saveQuietly skips the model's JSON unwrapping hook, which would
        // otherwise mark every JSON column dirty and rewrite it.
        $post->saveQuietly();
        $this->report[$slug] = 'updated';
        $this->command?->info(self::LABEL.': updated '.$post->slug.'.');
    }

    /**
     * @param  array<string, mixed>  $columns
     */
    private function applyColumns(string $slug, EducationPost $post, array $columns): void
    {
        foreach ($columns as $column => $spec) {
            if (! is_array($spec)) {
                throw new RuntimeException('Field payload column '.$column.' for '.$slug.' is not an object.');
            }
            if (isset($spec['ops']) && is_array($spec['ops'])) {
                foreach ($spec['ops'] as $op) {
                    if (! is_array($op)) {
                        throw new RuntimeException('Field payload op for '.$slug.' '.$column.' is not an object.');
                    }
                    $this->applyOp($slug, $post, (string) $column, $op);
                }

                continue;
            }
            $this->applyOp($slug, $post, (string) $column, $spec);
        }
    }

    /**
     * @param  array<string, mixed>  $op
     */
    private function applyOp(string $slug, EducationPost $post, string $column, array $op): void
    {
        $current = $post->{$column};
        $reason = $this->skipReason($column, $op, $current);
        if ($reason !== null) {
            $this->record($slug, $column, 'skipped: '.$reason);

            return;
        }

        if (isset($op['au_subsection'])) {
            $olds = $this->oldValues($op);
            if (! $this->matchesAny($current, $olds, false)) {
                $this->record($slug, $column, 'skipped: old value does not match');

                return;
            }
            if (! is_array($current)) {
                $this->record($slug, $column, 'skipped: current value is not a list');

                return;
            }
            $next = $current;
            $next[] = $op['au_subsection'];
            $post->{$column} = $next;
            $this->record($slug, $column, 'appended au_subsection');

            return;
        }

        if (isset($op['new_items']) && is_array($op['new_items'])) {
            $olds = $this->oldValues($op);
            if (! $this->matchesAny($current, $olds, false)) {
                $this->record($slug, $column, 'skipped: old value does not match');

                return;
            }
            if (! is_array($current)) {
                $this->record($slug, $column, 'skipped: current value is not a list');

                return;
            }
            $next = $current;
            foreach ($op['new_items'] as $item) {
                $next[] = $item;
            }
            $post->{$column} = $next;
            $this->record($slug, $column, 'appended new_items');

            return;
        }

        if (array_key_exists('new_item', $op)) {
            $olds = $this->oldValues($op);
            if (! $this->matchesAny($current, $olds, false)) {
                $this->record($slug, $column, 'skipped: old value does not match');

                return;
            }
            if (! is_array($current)) {
                $this->record($slug, $column, 'skipped: current value is not a list');

                return;
            }
            $next = $current;
            $next[] = $op['new_item'];
            $post->{$column} = $next;
            $this->record($slug, $column, 'appended new_item');

            return;
        }

        if (array_key_exists('new_value', $op)) {
            if (! $this->matchesAny($current, [$op['old_value'] ?? null], $column === 'faqs')) {
                $this->record($slug, $column, 'skipped: old value does not match');

                return;
            }
            $post->{$column} = $op['new_value'];
            $this->record($slug, $column, 'wrote new_value');

            return;
        }

        $this->record($slug, $column, 'skipped: no operation');
    }

    /**
     * @param  array<string, mixed>  $op
     */
    private function skipReason(string $column, array $op, mixed $current): ?string
    {
        if ($column === 'regulatory_subsections' && isset($op['new_value']) && ! isset($op['au_subsection'])) {
            $title = $this->compoundingTitle($op);
            if ($title !== null && $this->anyTitleEquals($current, $title)) {
                return 'title equals '.$title;
            }
        }

        if (isset($op['au_subsection'])) {
            if ($this->anyTitleStartsWith($current, 'Australia (as of')) {
                return 'title starts with Australia (as of';
            }
            if ($this->anyEntryContains($current, 'NSW Health')) {
                return 'entry contains NSW Health';
            }
        }

        if ($column === 'references') {
            $url = $op['new_items'][0]['links'][0]['url'] ?? null;
            if (is_string($url) && $url !== '' && $this->anyLinkEquals($current, $url)) {
                return 'link url equals '.$url;
            }
        }

        if ($column === 'key_points' && $this->anyItemStartsWith($current, 'Regulatory status (as of ')) {
            return 'item starts with Regulatory status (as of ';
        }

        if ($column === 'regulatory_important_note' && is_string($current) && str_contains($current, 'Regulatory status (as of 19 October 2026)')) {
            return 'contains Regulatory status (as of 19 October 2026)';
        }

        if ($column === 'faqs' && $this->anyQuestionEquals($current, 'Has BPC-157 been tested in humans?')) {
            return 'question equals Has BPC-157 been tested in humans?';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $op
     */
    private function compoundingTitle(array $op): ?string
    {
        foreach ($op['new_value'] ?? [] as $section) {
            if (! is_array($section)) {
                continue;
            }
            $title = (string) ($section['title'] ?? '');
            if ($title !== '' && str_contains($title, 'compounding review (as of 19 October 2026)')) {
                return $title;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $op
     * @return list<mixed>
     */
    private function oldValues(array $op): array
    {
        if (isset($op['old_values']) && is_array($op['old_values'])) {
            return array_values($op['old_values']);
        }

        return [$op['old_value'] ?? null];
    }

    /**
     * @param  list<mixed>  $olds
     */
    private function matchesAny(mixed $current, array $olds, bool $faq): bool
    {
        $left = self::normalizeValue($faq ? $this->faqComparable($current) : $current);
        foreach ($olds as $old) {
            if ($left === self::normalizeValue($old)) {
                return true;
            }
        }

        return false;
    }

    private function faqComparable(mixed $current): mixed
    {
        if (! is_array($current)) {
            return $current;
        }
        $out = [];
        foreach ($current as $faq) {
            if (! is_array($faq)) {
                $out[] = $faq;

                continue;
            }
            $out[] = [
                'question' => trim((string) ($faq['question'] ?? '')),
                'answer' => trim(strip_tags((string) ($faq['answer'] ?? ''))),
            ];
        }

        return $out;
    }

    public static function normalizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = str_replace("\r\n", "\n", $value);
            $value = str_replace("\r", "\n", $value);
            $value = str_replace("\u{00A0}", ' ', $value);
            $lines = explode("\n", $value);
            $lines = array_map(static fn (string $line) => rtrim($line), $lines);

            return trim(implode("\n", $lines));
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = self::normalizeValue($item);
            }

            return $out;
        }

        return $value;
    }

    private function anyTitleEquals(mixed $sections, string $title): bool
    {
        if (! is_array($sections)) {
            return false;
        }
        foreach ($sections as $section) {
            if (is_array($section) && (string) ($section['title'] ?? '') === $title) {
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
            if (is_array($section) && str_starts_with((string) ($section['title'] ?? ''), $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function anyEntryContains(mixed $sections, string $needle): bool
    {
        if (! is_array($sections)) {
            return false;
        }
        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }
            if ($this->valueContains($section['entries'] ?? null, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function valueContains(mixed $value, string $needle): bool
    {
        if (is_string($value)) {
            return str_contains($value, $needle);
        }
        if (! is_array($value)) {
            return false;
        }
        foreach ($value as $item) {
            if ($this->valueContains($item, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function anyItemStartsWith(mixed $items, string $prefix): bool
    {
        if (! is_array($items)) {
            return false;
        }
        foreach ($items as $item) {
            if (is_string($item) && str_starts_with($item, $prefix)) {
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
            if (is_array($faq) && trim((string) ($faq['question'] ?? '')) === $question) {
                return true;
            }
        }

        return false;
    }

    private function anyLinkEquals(mixed $references, string $url): bool
    {
        if (! is_array($references)) {
            return false;
        }
        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }
            $links = $reference['links'] ?? [];
            if (! is_array($links)) {
                continue;
            }
            foreach ($links as $link) {
                $candidate = is_string($link) ? $link : (is_array($link) ? ($link['url'] ?? null) : null);
                if (is_string($candidate) && $candidate === $url) {
                    return true;
                }
            }
        }

        return false;
    }

    private function record(string $slug, string $column, string $message): void
    {
        $this->log[] = $slug.' '.$column.': '.$message;
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    private function snapshot(EducationPost $post, array $fields): array
    {
        $attributes = $post->getAttributes();
        $snapshot = [];
        foreach ($fields as $field) {
            $snapshot[$field] = $attributes[$field] ?? null;
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $decoded = json_decode((string) file_get_contents(self::directory().'/'.$file), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Payload is not an object: '.$file.'.');
        }

        return $decoded;
    }
}
