<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Support\EncyclopediaPostMatch;
use App\Support\GuardedFieldWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Retatrutide OSA trials + 2026 seller lawsuits (29 October 2026).
 *
 * Adds one human-use subsection (TRIUMPH-1-OSA / TRIUMPH-2-OSA, NEJM
 * 20 Oct 2026), one regulatory subsection (Lilly's 2026 suits against
 * sellers, inserted after the classification subsection) and five
 * references. Nothing existing is edited or removed.
 *
 * One post, one transaction. Comparison uses
 * GuardedFieldWriter::comparable(): the 160000 seeder's normalize() rules,
 * with JSON object key order ignored, because production MySQL returns
 * keys shortest-first. A column is written only when its current value
 * equals one of the payload's old_values; the result must equal the paired
 * target_values entry or the transaction throws and rolls back. A column
 * already at a target is skipped. Anything else logs a WARNING and that
 * column is left unchanged.
 */
class EncyclopediaRetatrutideOsaLitigationSeeder extends Seeder
{
    public const LABEL = 'Retatrutide OSA + litigation update';

    public const SLUG = 'retatrutide';

    public const PAYLOAD_PATH = 'seeders/data/encyclopedia-field-updates/2026-10-29b/retatrutide.json';

    public const PAYLOAD_SHA256 = 'd5d05de947fa83a70332bb8087b3b12f949a3b38ac5072cdaa72b9af8809a742';

    public const UPDATED = 'updated';

    public const ALREADY = 'already current';

    public const MISMATCH = 'skipped (old mismatch)';

    /** @var list<string> */
    public const ORDER = [
        'human_use_subsections',
        'regulatory_subsections',
        'references',
    ];

    /** @var array<string, string> */
    public array $columnReport = [];

    /** @var list<string> */
    public array $log = [];

    /** @var list<string> */
    public array $transactions = [];

    public string $report = 'missing';

    public function run(): void
    {
        $this->columnReport = [];
        $this->log = [];
        $this->transactions = [];
        $this->report = 'missing';

        $columns = $this->payload()['columns'] ?? null;
        if (! is_array($columns)) {
            throw new RuntimeException('Retatrutide OSA + litigation payload is missing columns.');
        }

        $match = EncyclopediaPostMatch::find(self::SLUG, $this->command, self::LABEL);
        foreach ($match->lines as $line) {
            $this->log[] = $line;
        }
        if (is_string($match->warning)) {
            $this->log[] = $match->warning;
        }

        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost) {
            $this->report = $match->status === 'ok' ? 'missing' : $match->status;
            $this->emit(self::LABEL.' '.self::SLUG.': '.$this->report.'; wrote nothing', true);

            return;
        }

        $post = $match->post;

        $statuses = DB::transaction(function () use ($post, $columns): array {
            $this->transactions[] = self::SLUG;
            $statuses = [];
            $changed = false;

            foreach (self::ORDER as $column) {
                $spec = $columns[$column] ?? null;
                if (! is_array($spec)) {
                    throw new RuntimeException('Retatrutide OSA + litigation payload is missing '.$column.'.');
                }
                $status = $this->applyColumn($post, $column, $spec);
                $statuses[$column] = $status;
                if ($status === self::UPDATED) {
                    $changed = true;
                }
            }

            if ($changed) {
                $post->saveQuietly();
            }

            $written = array_keys(array_filter($statuses, fn (string $s) => $s === self::UPDATED));
            $skipped = array_keys(array_filter($statuses, fn (string $s) => $s !== self::UPDATED));
            $this->emit(
                self::LABEL.' '.self::SLUG.': wrote '.($written === [] ? 'nothing' : implode(', ', $written))
                .'; skipped '.($skipped === [] ? 'nothing' : implode(', ', $skipped))
            );

            return $statuses;
        });

        $this->columnReport = $statuses;
        $this->report = in_array(self::UPDATED, $statuses, true) ? self::UPDATED : self::ALREADY;
        if (! in_array(self::UPDATED, $statuses, true) && in_array(self::MISMATCH, $statuses, true)) {
            $this->report = self::MISMATCH;
        }
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyColumn(EducationPost $post, string $column, array $spec): string
    {
        $current = $post->{$column};

        foreach ($this->list($spec, 'target_values') as $target) {
            if (GuardedFieldWriter::comparable($current) === GuardedFieldWriter::comparable($target)) {
                $this->emit(self::SLUG.'.'.$column.': '.self::ALREADY);

                return self::ALREADY;
            }
        }

        $matched = null;
        foreach ($this->list($spec, 'old_values') as $index => $old) {
            if (GuardedFieldWriter::comparable($current) === GuardedFieldWriter::comparable($old)) {
                $matched = $index;
                break;
            }
        }

        if ($matched === null) {
            $this->warnMismatch($column, $current);

            return self::MISMATCH;
        }

        $result = $this->transform($spec, $current);
        if ($result === null) {
            $this->warnMismatch($column, $current);

            return self::MISMATCH;
        }

        $expected = $this->list($spec, 'target_values')[$matched] ?? null;
        if ($expected === null || GuardedFieldWriter::comparable($result) !== GuardedFieldWriter::comparable($expected)) {
            throw new RuntimeException($column.' post-edit assertion failed');
        }

        $post->{$column} = $result;
        $this->emit(self::SLUG.'.'.$column.': '.self::UPDATED);

        return self::UPDATED;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>|null
     */
    private function transform(array $spec, mixed $current): ?array
    {
        if (! is_array($current) || ! array_is_list($current)) {
            return null;
        }

        return match ($spec['op'] ?? null) {
            'insert_after_title' => $this->insertAfterTitle($current, $spec),
            'append' => is_array($spec['append'] ?? null) && array_is_list($spec['append'])
                ? array_merge($current, $spec['append'])
                : null,
            default => null,
        };
    }

    /**
     * @param  list<mixed>  $sections
     * @param  array<string, mixed>  $spec
     * @return list<mixed>|null
     */
    private function insertAfterTitle(array $sections, array $spec): ?array
    {
        $after = $spec['after_title'] ?? null;
        $insert = $spec['insert'] ?? null;
        if (! is_string($after) || ! is_array($insert) || ! is_string($insert['title'] ?? null)) {
            return null;
        }

        $hits = [];
        foreach ($sections as $index => $section) {
            if (! is_array($section)) {
                continue;
            }
            $title = self::normalize((string) ($section['title'] ?? ''));
            if ($title === self::normalize($insert['title'])) {
                return null;
            }
            if ($title === self::normalize($after)) {
                $hits[] = $index;
            }
        }
        if (count($hits) !== 1) {
            return null;
        }

        array_splice($sections, $hits[0] + 1, 0, [$insert]);

        return $sections;
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

    /**
     * @param  array<string, mixed>  $spec
     * @return list<mixed>
     */
    private function list(array $spec, string $key): array
    {
        return is_array($spec[$key] ?? null) ? array_values($spec[$key]) : [];
    }

    private function warnMismatch(string $column, mixed $current): void
    {
        $this->emit(
            self::LABEL.' '.self::SLUG.'.'.$column.': '.self::MISMATCH.' excerpt='.$this->excerpt($current),
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
            self::stream('stderr', $line);

            return;
        }

        Log::info($line);
        $this->command?->info($line);
        self::stream('stdout', $line);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $path = database_path(self::PAYLOAD_PATH);
        $raw = (string) file_get_contents($path);
        if (hash('sha256', $raw) !== self::PAYLOAD_SHA256) {
            throw new RuntimeException('Retatrutide OSA + litigation payload sha256 does not match the pinned value.');
        }
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Retatrutide OSA + litigation payload is not an object.');
        }

        return $decoded;
    }

    /**
     * STDOUT/STDERR are undefined outside the CLI (e.g. Artisan::call from
     * the /admin dev console), so only write when the constant exists.
     */
    private static function stream(string $stream, string $line): void
    {
        $handle = match ($stream) {
            'stderr' => defined('STDERR') ? STDERR : null,
            default => defined('STDOUT') ? STDOUT : null,
        };
        if (is_resource($handle)) {
            @fwrite($handle, $line.PHP_EOL);
        }
    }
}
