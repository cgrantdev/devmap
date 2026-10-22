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
 * Neutral research-use-only card descriptions for the /encyclopedia hub
 * (and the /compare index, which passes it through CompareHubBlurb).
 *
 * The hub card reads education_posts.description of the category-linked
 * post, then product_categories.description, then the framing profile.
 * This seeder writes exactly one of the first two: the post column when it
 * is non-empty after normalize(), otherwise the category column. Profile
 * text is code and is not touched.
 *
 * Per slug: EncyclopediaPostMatch must return exactly 1 category and 1
 * linked post, otherwise a WARNING with the counts is logged and nothing is
 * written. Legacy unlinked rows are never touched. One DB::transaction per
 * slug. A column is written only when normalize(current) equals one of the
 * guard values; it is skipped when it already equals new_value.
 *
 * Source payload sha256 121f7c878f6e6c4bc78a77538c80a5979cac0559aee4979cda74566b10ae3c8e.
 */
class EncyclopediaHubCardsOctober22Seeder extends Seeder
{
    public const SOURCE_PAYLOAD_SHA256 = '121f7c878f6e6c4bc78a77538c80a5979cac0559aee4979cda74566b10ae3c8e';

    public const PAYLOAD = 'seeders/data/encyclopedia-field-updates/2026-10-22g/hub-cards.json';

    public const UPDATED = 'updated';

    public const CURRENT = 'skipped (already current)';

    public const MISMATCH = 'skipped (old mismatch)';

    public const NO_MATCH = 'skipped (post match not ok)';

    private const LABEL = 'hub cards 2026-10-22';

    /** @var array<string, string> slug => overall status */
    public array $report = [];

    /** @var array<string, array<string, string>> slug => [table.column => status] */
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
            $this->updateSlug((string) $slug, $spec);
        }
    }

    /**
     * Comparison normalizer: CRLF/CR to LF, NBSP to space, rtrim each line,
     * trim. Writes stay verbatim.
     */
    public static function normalize(mixed $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", (string) ($value ?? ''));
        $value = str_replace("\u{00A0}", ' ', $value);
        $lines = array_map(rtrim(...), explode("\n", $value));

        return trim(implode("\n", $lines));
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        $path = database_path(self::PAYLOAD);
        $raw = (string) file_get_contents($path);
        if (hash('sha256', $raw) !== self::SOURCE_PAYLOAD_SHA256) {
            throw new RuntimeException('Hub-card payload sha256 mismatch: '.$path);
        }
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! is_array($decoded['posts'] ?? null)) {
            throw new RuntimeException('Hub-card payload has no posts.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function updateSlug(string $slug, array $spec): void
    {
        $match = EncyclopediaPostMatch::find($slug, $this->command, self::LABEL);
        foreach ($match->lines as $line) {
            $this->log[] = $line;
        }

        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost || ! $match->category instanceof ProductCategory) {
            $warning = sprintf(
                '%s %s: %s, nothing written (categories %d, linked posts %d, slug-matching posts %d)',
                self::LABEL,
                $slug,
                $match->status,
                $match->categoryCount,
                $match->linkedPostCount,
                $match->slugPostCount,
            );
            $this->warn($warning);
            $this->report[$slug] = self::NO_MATCH;

            return;
        }

        $post = $match->post;
        $category = $match->category;
        $columns = is_array($spec['columns'] ?? null) ? $spec['columns'] : [];

        DB::transaction(function () use ($slug, $post, $category, $columns): void {
            foreach ($columns as $column => $colSpec) {
                if (isset($colSpec['append_item'])) {
                    $this->appendItem($slug, $post, (string) $column, $colSpec);
                } elseif ($column === 'description') {
                    $this->applyDescription($slug, $post, $category, $colSpec);
                } else {
                    $this->applyPostColumn($slug, $post, (string) $column, $colSpec);
                }
            }
            if ($post->isDirty()) {
                $post->saveQuietly();
            }
            if ($category->isDirty()) {
                $category->saveQuietly();
            }
        });

        $overall = self::CURRENT;
        foreach ($this->columns[$slug] ?? [] as $status) {
            if ($status === self::UPDATED) {
                $overall = self::UPDATED;
            } elseif ($status === self::MISMATCH && $overall !== self::UPDATED) {
                $overall = self::MISMATCH;
            }
        }
        $this->report[$slug] = $overall;
        $this->info($slug.': '.$overall);
    }

    /**
     * Card description: the post column when non-empty, else the category
     * fallback the controller would render. Never both.
     *
     * @param  array<string, mixed>  $spec
     */
    private function applyDescription(string $slug, EducationPost $post, ProductCategory $category, array $spec): void
    {
        if (self::normalize($post->description) !== '') {
            $this->guardedWrite($slug, $post, 'education_posts', 'description', $spec);

            return;
        }

        $this->guardedWrite($slug, $category, 'product_categories', 'description', $spec);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function applyPostColumn(string $slug, EducationPost $post, string $column, array $spec): void
    {
        $this->guardedWrite($slug, $post, 'education_posts', $column, $spec);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function guardedWrite(string $slug, EducationPost|ProductCategory $model, string $table, string $column, array $spec): void
    {
        $key = $table.'.'.$column;
        $new = (string) ($spec['new_value'] ?? '');
        $olds = array_map(fn ($v) => self::normalize($v), (array) ($spec['old_values'] ?? []));
        if ($new === '' || $olds === []) {
            throw new RuntimeException("Hub-card payload for {$slug}.{$column} lacks new_value/old_values.");
        }

        $current = self::normalize($model->{$column});
        if ($current === self::normalize($new)) {
            $this->columns[$slug][$key] = self::CURRENT;
            $this->info("{$slug} {$key}: ".self::CURRENT);

            return;
        }

        if (in_array($current, $olds, true)) {
            $model->{$column} = $new;
            $this->columns[$slug][$key] = self::UPDATED;
            $this->info("{$slug} {$key}: ".self::UPDATED);

            return;
        }

        $this->columns[$slug][$key] = self::MISMATCH;
        $this->warn(sprintf(
            '%s %s %s: old mismatch, column left unchanged. Current (200 chars): %s',
            self::LABEL,
            $slug,
            $key,
            mb_substr($current, 0, 200),
        ));
    }

    /**
     * Guarded append to a JSON list column (references). Already linked →
     * "already current"; stored list equal to a guard → guard + item;
     * otherwise WARNING old mismatch and nothing is written.
     *
     * @param  array<string, mixed>  $spec
     */
    private function appendItem(string $slug, EducationPost $post, string $column, array $spec): void
    {
        $key = 'education_posts.'.$column;
        $marker = (string) ($spec['marker_url'] ?? '');
        $olds = (array) ($spec['old_values'] ?? []);
        if ($marker === '' || $olds === [] || ! is_array($spec['append_item'] ?? null)) {
            throw new RuntimeException("Hub-card payload for {$slug}.{$column} lacks marker_url/old_values/append_item.");
        }

        $stored = $post->{$column};
        $stored = is_array($stored) ? $stored : (json_decode((string) $stored, true) ?: []);
        foreach ($stored as $item) {
            foreach ((array) ($item['links'] ?? []) as $link) {
                if (self::normalize($link['url'] ?? '') === $marker) {
                    $this->columns[$slug][$key] = self::CURRENT;
                    $this->info("{$slug} {$key}: ".self::CURRENT);

                    return;
                }
            }
        }

        $current = self::normalizeDeep($stored);
        foreach ($olds as $old) {
            if ($current === self::normalizeDeep($old)) {
                $stored[] = $spec['append_item'];
                $post->{$column} = array_values($stored);
                $this->columns[$slug][$key] = self::UPDATED;
                $this->info("{$slug} {$key}: ".self::UPDATED);

                return;
            }
        }

        $this->columns[$slug][$key] = self::MISMATCH;
        $this->warn(sprintf(
            '%s %s %s: old mismatch, column left unchanged. Current (200 chars): %s',
            self::LABEL,
            $slug,
            $key,
            mb_substr((string) json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 200),
        ));
    }

    public static function normalizeDeep(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => self::normalizeDeep($v), $value);
        }

        return is_string($value) ? self::normalize($value) : $value;
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
