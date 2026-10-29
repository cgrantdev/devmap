<?php

namespace Database\Seeders;

use App\Models\EducationalGuide;
use App\Models\EducationPost;
use App\Support\EncyclopediaPostMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * HHS draft interim 503A peptide enforcement-discretion policy (as of
 * 29 October 2026): BPC-157 (regulatory subsections incl. the Australia /
 * TGA subsection from 2026_10_22_120000, important note, key point, FAQ),
 * TB-500, KPV, epitalon, MOTS-c, semax, the BPC-157 / TB-500, GLOW and
 * KLOW blends (single-API condition), and the peptide-legality guide.
 *
 * Posts: App\Support\EncyclopediaPostMatch must return exactly 1 category
 * and 1 linked post, else nothing is written for that slug. Guides:
 * exactly 1 educational_guides row by LOWER(TRIM(slug)). One
 * DB::transaction per slug. Per column: normalize(current) equal to a
 * target -> "already current"; equal to old_values[i] -> write
 * target_values[i]; otherwise WARNING "old mismatch" + 200-char excerpt,
 * column untouched. Old values list both the live production value and
 * the value main produces, where they differ. saveQuietly() once per row.
 *
 * Source payload sha256 2fb41cac2695e5ed40ad9e54478aff6f85aeb9d0b2cb0bc148bc46dc7a001ea9.
 */
class EncyclopediaCompoundingPolicyOctober29Seeder extends Seeder
{
    public const SOURCE_PAYLOAD_SHA256 = '2fb41cac2695e5ed40ad9e54478aff6f85aeb9d0b2cb0bc148bc46dc7a001ea9';

    public const PAYLOAD = 'seeders/data/encyclopedia-field-updates/2026-10-29/compounding-policy.json';

    public const LABEL = 'Compounding policy 29 Oct 2026';

    public const UPDATED = 'updated';

    public const CURRENT = 'skipped (already current)';

    public const MISMATCH = 'skipped (old mismatch)';

    public const NO_MATCH = 'skipped (post match not ok)';

    public const GUIDE_PREFIX = 'guide:';

    /** @var array<string, string> */
    public array $report = [];

    /** @var array<string, array<string, string>> */
    public array $columns = [];

    /** @var list<string> */
    public array $warnings = [];

    /** @var list<string> */
    public array $log = [];

    public function run(): void
    {
        $this->report = [];
        $this->columns = [];
        $this->warnings = [];
        $this->log = [];
        $payload = self::payload();

        foreach ($payload['posts'] as $slug => $spec) {
            $this->updatePost((string) $slug, $spec);
        }
        foreach ((array) ($payload['guides'] ?? []) as $slug => $spec) {
            $this->updateGuide((string) $slug, $spec);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        $path = database_path(self::PAYLOAD);
        $raw = (string) file_get_contents($path);
        if (hash('sha256', $raw) !== self::SOURCE_PAYLOAD_SHA256) {
            throw new RuntimeException('Compounding policy payload sha256 mismatch: '.$path);
        }
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! is_array($decoded['posts'] ?? null)) {
            throw new RuntimeException('Compounding policy payload has no posts.');
        }

        return $decoded;
    }

    /**
     * Comparison normalizer (writes stay verbatim). Strings: CRLF/CR -> LF,
     * NBSP -> space, rtrim each line, trim. Arrays: same keys and order,
     * values normalized recursively.
     */
    public static function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::normalize($v);
            }

            return $out;
        }
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }
        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);
        $value = str_replace("\u{00A0}", ' ', $value);

        return trim(implode("\n", array_map(rtrim(...), explode("\n", $value))));
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function updatePost(string $slug, array $spec): void
    {
        $match = EncyclopediaPostMatch::find((string) ($spec['lookup'] ?? $slug), $this->command, self::LABEL);
        foreach ($match->lines as $line) {
            $this->log[] = $line;
        }
        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost) {
            $this->warn(sprintf(
                '%s %s: %s, nothing written (categories %d, linked posts %d, slug-matching posts %d)',
                self::LABEL,
                $slug,
                $match->status,
                $match->categoryCount,
                $match->linkedPostCount,
                $match->slugPostCount,
            ));
            $this->report[$slug] = self::NO_MATCH;

            return;
        }

        $this->applyRow($slug, $match->post, $spec);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function updateGuide(string $slug, array $spec): void
    {
        $key = self::GUIDE_PREFIX.$slug;
        $rows = EducationalGuide::query()->whereRaw('LOWER(TRIM(slug)) = ?', [strtolower(trim($slug))])->orderBy('id')->get();
        $this->info(sprintf('matched-count %s %s: guides=%d', self::LABEL, $key, $rows->count()));
        if ($rows->count() !== 1) {
            $this->warn(sprintf('%s %s: %d rows, nothing written', self::LABEL, $key, $rows->count()));
            $this->report[$key] = self::NO_MATCH;

            return;
        }

        $this->applyRow($key, $rows->first(), $spec);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyRow(string $key, Model $model, array $spec): void
    {
        DB::transaction(function () use ($key, $model, $spec): void {
            foreach ((array) ($spec['columns'] ?? []) as $column => $colSpec) {
                $this->applyColumn($key, $model, (string) $column, $colSpec);
            }
            if ($model->isDirty()) {
                $model->saveQuietly();
            }
        });

        $overall = self::CURRENT;
        foreach ($this->columns[$key] ?? [] as $status) {
            if ($status === self::UPDATED) {
                $overall = self::UPDATED;
            } elseif ($status === self::MISMATCH && $overall !== self::UPDATED) {
                $overall = self::MISMATCH;
            }
        }
        $this->report[$key] = $overall;
        $this->info($key.': '.$overall);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyColumn(string $key, Model $model, string $column, array $spec): void
    {
        $stored = $model->{$column};
        $current = self::normalize($stored);
        $olds = array_values((array) ($spec['old_values'] ?? []));
        $targets = array_values((array) ($spec['target_values'] ?? []));
        if ($olds === [] || count($olds) !== count($targets)) {
            throw new RuntimeException("Compounding policy payload for {$key}.{$column} needs paired old/target values.");
        }

        foreach ($targets as $target) {
            if ($current === self::normalize($target)) {
                $this->status($key, $column, self::CURRENT);

                return;
            }
        }

        foreach ($olds as $i => $old) {
            if ($current === self::normalize($old)) {
                $model->{$column} = $targets[$i];
                $this->status($key, $column, self::UPDATED);

                return;
            }
        }

        $this->status($key, $column, self::MISMATCH);
        $excerpt = is_array($stored) ? json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $stored;
        $this->warn(sprintf(
            '%s %s %s: old mismatch, column left unchanged. Current (200 chars): %s',
            self::LABEL,
            $key,
            $column,
            mb_substr((string) $excerpt, 0, 200),
        ));
    }

    private function status(string $key, string $column, string $status): void
    {
        $this->columns[$key][$column] = $status;
        $this->info("{$key} {$column}: {$status}");
    }

    private function info(string $line): void
    {
        $this->log[] = $line;
        Log::info(self::LABEL.' '.$line);
        $this->command?->info($line);
    }

    private function warn(string $line): void
    {
        $this->warnings[] = $line;
        $this->log[] = $line;
        Log::warning($line);
        $this->command?->warn($line);
    }
}
