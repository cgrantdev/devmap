<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Support\EncyclopediaPostMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orforglipron EU CHMP opinion and retatrutide Senate letter / section 505
 * (22 October 2026).
 *
 * Lookup is App\Support\EncyclopediaPostMatch, the same helper the
 * 2026_10_22_130000 re-apply uses: one product category by
 * LOWER(TRIM(slug)), then that category's education post. A legacy
 * unlinked row is logged and never written. Missing and collision
 * counts are warnings; they do not throw.
 *
 * Each slug runs in its own transaction. saveQuietly skips the JSON
 * unwrapping hook. CRLF, NBSP, and surrounding whitespace are normalized
 * only for comparison. Stored text is the payload value, or, for the
 * retatrutide note, the stored string after one sentence replacement.
 */
class EncyclopediaOrfoRetaOctober22Seeder extends Seeder
{
    public const LABEL = 'orfo-reta 2026-10-22';

    public const SOURCE_PAYLOAD_SHA256 = 'c0699d6ee204e91c93a37dee8137454009d988d0caceeb024d64116458cbba3f';

    public const PAYLOAD_BASE = 'origin/main b4eb1ffcca7d853bbe4517c1a62f8273fb617c11 (current main, 22 Oct 2026 08:57 UTC+7)';

    public const UPDATED = 'updated';

    public const MARKER = 'skipped (marker present)';

    public const AT_TARGET = 'skipped (already at target)';

    public const MISMATCH = 'skipped (old mismatch)';

    /** @var array<string, string> */
    private const FILES = [
        'orforglipron' => 'orforglipron.json',
        'retatrutide' => 'retatrutide.json',
    ];

    /** @var array<string, string> */
    public array $report = [];

    /** @var array<string, array<string, string>> */
    public array $columnReport = [];

    /** @var list<string> */
    public array $log = [];

    /** @var list<string> */
    public array $transactions = [];

    public function run(): void
    {
        $this->log = [];
        $this->transactions = [];
        $this->columnReport = [];
        $this->report = [];
        foreach (array_keys(self::FILES) as $slug) {
            $this->report[$slug] = 'missing';
        }

        foreach (self::FILES as $slug => $file) {
            $this->updateSlug($slug, $this->payload($file));
        }
    }

    /**
     * Seam a test can override to fail one column write. The failure
     * rolls back that slug only.
     */
    protected function beforeColumn(string $slug, string $column, int $index): void
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateSlug(string $slug, array $payload): void
    {
        $columns = $payload['columns'] ?? null;
        if (! is_array($columns)) {
            throw new \RuntimeException('Field payload is missing columns for '.$slug.'.');
        }

        $match = EncyclopediaPostMatch::find($slug, $this->command, self::LABEL);
        foreach ($match->lines as $line) {
            $this->log[] = $line;
        }
        if (is_string($match->warning)) {
            $this->log[] = $match->warning;
        }

        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost) {
            $this->report[$slug] = $match->status === 'ok' ? 'missing' : $match->status;
            $this->emit($slug.': '.$this->report[$slug]);

            return;
        }

        $post = $match->post;

        try {
            $statuses = DB::transaction(function () use ($slug, $post, $columns): array {
                $this->transactions[] = $slug;
                $statuses = [];
                $index = 0;
                foreach ($columns as $column => $spec) {
                    if (! is_string($column) || ! is_array($spec)) {
                        continue;
                    }
                    $index++;
                    $this->beforeColumn($slug, $column, $index);
                    $status = $this->applyColumn($post, $slug, $column, $spec);
                    $statuses[$column] = $status;
                    if ($status === self::UPDATED) {
                        $post->saveQuietly();
                    }
                }

                return $statuses;
            });
        } catch (Throwable $e) {
            $this->report[$slug] = 'failed';
            $this->emit(
                self::LABEL.' '.$slug.': exception rolled back the slug ('.$e->getMessage().')',
                true
            );

            return;
        }

        $this->columnReport[$slug] = $statuses;
        $this->report[$slug] = $this->rollup($statuses);
        foreach ($statuses as $column => $status) {
            $this->emit($slug.'.'.$column.': '.$status);
        }
        $this->emit($slug.': '.$this->report[$slug]);
    }

    /**
     * @param  array<string, string>  $statuses
     */
    private function rollup(array $statuses): string
    {
        if (in_array(self::UPDATED, $statuses, true)) {
            return self::UPDATED;
        }

        $unique = array_values(array_unique($statuses));
        if (count($unique) === 1 && is_string($unique[0])) {
            return $unique[0];
        }
        if (in_array(self::MARKER, $unique, true)) {
            return self::MARKER;
        }
        if (in_array(self::AT_TARGET, $unique, true)) {
            return self::AT_TARGET;
        }

        return self::MISMATCH;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyColumn(EducationPost $post, string $slug, string $column, array $spec): string
    {
        $current = $post->{$column};

        if ($this->markerPresent($slug, $column, $current, $spec)) {
            return self::MARKER;
        }

        foreach ($this->targets($spec) as $target) {
            if ($this->same($current, $target)) {
                return self::AT_TARGET;
            }
        }

        $matched = null;
        foreach ($this->olds($spec) as $index => $old) {
            if ($this->same($current, $old)) {
                $matched = $index;
                break;
            }
        }
        if ($matched === null) {
            $this->warnMismatch($slug, $column, $current);

            return self::MISMATCH;
        }

        $next = $this->transform($post, $slug, $column, $spec, $current, $matched);
        if ($next === null) {
            return self::MISMATCH;
        }

        $post->{$column} = $next;

        return self::UPDATED;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function transform(EducationPost $post, string $slug, string $column, array $spec, mixed $current, int $matched): mixed
    {
        if (isset($spec['old_sentence'], $spec['new_sentence']) && is_string($spec['old_sentence']) && is_string($spec['new_sentence'])) {
            return $this->replaceSentence($slug, $column, $spec, $current, $matched);
        }

        if (isset($spec['index'], $spec['old_item']) && array_key_exists('new_item', $spec)) {
            return $this->replaceAtIndex($slug, $column, $spec, $current);
        }

        if (isset($spec['insert_at'])) {
            return $this->insertItems($slug, $column, $spec, $current);
        }

        return $this->appendItems($slug, $column, $spec, $current);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function replaceSentence(string $slug, string $column, array $spec, mixed $current, int $matched): ?string
    {
        if (! is_string($current)) {
            $this->warnMismatch($slug, $column, $current);

            return null;
        }

        $oldSentence = $spec['old_sentence'];
        $count = substr_count($current, $oldSentence);
        if ($count !== 1) {
            $this->warnMismatch($slug, $column, $current, 'old sentence count '.$count);

            return null;
        }

        $result = str_replace($oldSentence, $spec['new_sentence'], $current);
        $targets = $this->targets($spec);
        $expected = $targets[$matched] ?? null;
        if (! $this->same($result, $expected)) {
            $this->warnMismatch($slug, $column, $result, 'replacement did not match target');

            return null;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function replaceAtIndex(string $slug, string $column, array $spec, mixed $current): ?array
    {
        $index = (int) $spec['index'];
        if (! is_array($current) || ! array_is_list($current) || ($current[$index] ?? null) !== $spec['old_item']) {
            $this->warnMismatch($slug, $column, $current, 'index '.$index.' does not equal old_item');

            return null;
        }

        $current[$index] = $spec['new_item'];

        return $current;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<int, mixed>|null
     */
    private function insertItems(string $slug, string $column, array $spec, mixed $current): ?array
    {
        $items = $this->incomingItems($spec);
        if (! is_array($current) || ! array_is_list($current) || $items === null) {
            $this->warnMismatch($slug, $column, $current);

            return null;
        }

        $at = (int) $spec['insert_at'];

        return array_merge(array_slice($current, 0, $at), $items, array_slice($current, $at));
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<int, mixed>|null
     */
    private function appendItems(string $slug, string $column, array $spec, mixed $current): ?array
    {
        $items = $this->incomingItems($spec);
        if (! is_array($current) || ! array_is_list($current) || $items === null) {
            $this->warnMismatch($slug, $column, $current);

            return null;
        }

        return array_merge($current, $items);
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>|null
     */
    private function incomingItems(array $spec): ?array
    {
        if (isset($spec['new_subsection'])) {
            return [$spec['new_subsection']];
        }
        if (array_key_exists('new_item', $spec)) {
            return [$spec['new_item']];
        }
        if (isset($spec['new_items']) && is_array($spec['new_items'])) {
            return array_values($spec['new_items']);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function markerPresent(string $slug, string $column, mixed $current, array $spec): bool
    {
        if ($column === 'regulatory_subsections') {
            return $this->anyTitleStartsWith($current, $this->titleMarker($spec));
        }
        if ($column === 'key_points' && $slug === 'orforglipron') {
            return $this->anyStringStartsWith($current, 'EU (15 Oct 2026):');
        }
        if ($column === 'key_points' && $slug === 'retatrutide') {
            return $this->anyStringStartsWith($current, 'U.S. pathway (as of ');
        }
        if ($column === 'regulatory_important_note') {
            return is_string($current) && str_contains($current, 'section 505 of the Federal Food, Drug, and Cosmetic Act');
        }
        if ($column === 'references') {
            $url = $this->firstNewUrl($spec);

            return $url !== null && $this->containsUrl($current, $url);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function titleMarker(array $spec): string
    {
        $title = (string) ($spec['new_subsection']['title'] ?? '');
        $trimmed = preg_replace('/\s*\([^)]*\)\s*$/', '', $title);

        return is_string($trimmed) && $trimmed !== '' ? $trimmed : $title;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function firstNewUrl(array $spec): ?string
    {
        $items = $spec['new_items'] ?? null;
        if (! is_array($items)) {
            return null;
        }
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            foreach ($item['links'] ?? [] as $link) {
                if (is_array($link) && is_string($link['url'] ?? null) && $link['url'] !== '') {
                    return $link['url'];
                }
            }
        }

        return null;
    }

    private function anyTitleStartsWith(mixed $value, string $prefix): bool
    {
        if (! is_array($value) || $prefix === '') {
            return false;
        }
        foreach ($value as $item) {
            if (is_array($item) && is_string($item['title'] ?? null) && str_starts_with($item['title'], $prefix)) {
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

    private function containsUrl(mixed $value, string $url): bool
    {
        if (! is_array($value)) {
            return false;
        }
        if (($value['url'] ?? null) === $url) {
            return true;
        }
        foreach ($value as $child) {
            if ($this->containsUrl($child, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>
     */
    private function targets(array $spec): array
    {
        if (array_key_exists('target_values', $spec) && is_array($spec['target_values'])) {
            return array_values($spec['target_values']);
        }
        if (array_key_exists('target_value', $spec)) {
            return [$spec['target_value']];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>
     */
    private function olds(array $spec): array
    {
        if (array_key_exists('old_values', $spec) && is_array($spec['old_values'])) {
            return array_values($spec['old_values']);
        }
        if (array_key_exists('old_value', $spec)) {
            return [$spec['old_value']];
        }

        return [];
    }

    private function same(mixed $left, mixed $right): bool
    {
        return self::normalize($left) === self::normalize($right);
    }

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

    private function warnMismatch(string $slug, string $column, mixed $current, string $detail = ''): void
    {
        $suffix = $detail !== '' ? ' '.$detail : '';
        $this->emit(
            self::LABEL.' '.$slug.'.'.$column.': skipped (old mismatch) excerpt='.$this->excerpt($current).$suffix,
            true
        );
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

        return mb_substr(str_replace(["\r\n", "\r", "\n"], ' ', $text), 0, 200);
    }

    private function emit(string $line, bool $warning = false): void
    {
        $this->log[] = $line;
        if ($warning) {
            Log::warning($line);
            $this->command?->error($line);
            fwrite(STDERR, $line.PHP_EOL);

            return;
        }

        Log::info($line);
        $this->command?->info($line);
        fwrite(STDOUT, $line.PHP_EOL);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $path = database_path('seeders/data/encyclopedia-field-updates/2026-10-22c/'.$file);
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Encyclopedia field payload is not an object: '.$file);
        }

        return $decoded;
    }
}
