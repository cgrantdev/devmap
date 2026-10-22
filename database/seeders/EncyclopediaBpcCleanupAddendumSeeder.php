<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\EducationPost;
use App\Support\EncyclopediaPostMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * BPC-157 cleanup addendum (2026_10_22_170000), Content QA M1–M10 applied.
 *
 * BPC-157: human use (no doses, registry-accurate trial entry, corrected
 * "Current Status"), regulatory ("Animal Safety Data" → "Animal Toxicology
 * Data"; sourced "Theoretical Concerns"), preclinical "Tendon and Ligament
 * Repair", background, conclusion, half-life, references and the card
 * description. TB-500: formula, molecular weight, half-life and the
 * sourcing sentence in the conclusion. One legacy blog description.
 *
 * Per slug: App\Support\EncyclopediaPostMatch must return exactly 1
 * category and 1 linked post, else nothing is written for that slug.
 * Legacy unlinked rows are never selected. One DB::transaction per slug.
 * Per column: normalize(current) equal to a target → "already current";
 * equal to an old value and the op preconditions hold → apply the op to
 * the stored value, assert the normalized result equals the target, set;
 * otherwise WARNING "old mismatch" + 200-char excerpt, column untouched.
 * saveQuietly() once per slug.
 *
 * Source payload sha256 73c4a16b71d373b5af73bddd2d48fba36c0fc2c2e17a0df0772f47c3f0019c45.
 */
class EncyclopediaBpcCleanupAddendumSeeder extends Seeder
{
    public const SOURCE_PAYLOAD_SHA256 = '73c4a16b71d373b5af73bddd2d48fba36c0fc2c2e17a0df0772f47c3f0019c45';

    public const PAYLOAD = 'seeders/data/encyclopedia-field-updates/2026-10-22f/bpc-addendum.json';

    public const LABEL = 'BPC-157 cleanup addendum';

    public const UPDATED = 'updated';

    public const CURRENT = 'skipped (already current)';

    public const MISMATCH = 'skipped (old mismatch)';

    public const EMPTY = 'skipped (empty)';

    public const NO_MATCH = 'skipped (post match not ok)';

    private const BLOG_PREFIX = 'blog:';

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

        foreach (self::payload()['posts'] as $key => $spec) {
            $key = (string) $key;
            if (str_starts_with($key, self::BLOG_PREFIX)) {
                $this->updateBlog(substr($key, strlen(self::BLOG_PREFIX)), $spec);
            } else {
                $this->updateSlug($key, $spec);
            }
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
            throw new RuntimeException('BPC cleanup addendum payload sha256 mismatch: '.$path);
        }
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! is_array($decoded['posts'] ?? null)) {
            throw new RuntimeException('BPC cleanup addendum payload has no posts.');
        }

        return $decoded;
    }

    /**
     * Comparison normalizer (writes stay verbatim). Strings: CRLF/CR → LF,
     * NBSP → space, rtrim each line, trim. Arrays: same keys and order,
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
    private function updateSlug(string $slug, array $spec): void
    {
        $match = EncyclopediaPostMatch::find($slug, $this->command, self::LABEL);
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

        $post = $match->post;
        DB::transaction(function () use ($slug, $post, $spec): void {
            foreach ((array) ($spec['columns'] ?? []) as $column => $colSpec) {
                $this->applyColumn($slug, $post, (string) $column, $colSpec);
            }
            if ($post->isDirty()) {
                $post->saveQuietly();
            }
        });
        $this->finish($slug);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function updateBlog(string $blogSlug, array $spec): void
    {
        $key = self::BLOG_PREFIX.$blogSlug;
        $rows = Blog::query()->whereRaw('LOWER(TRIM(slug)) = ?', [strtolower(trim($blogSlug))])->orderBy('id')->get();
        $this->info(sprintf('matched-count %s %s: blogs=%d', self::LABEL, $key, $rows->count()));
        if ($rows->count() !== 1) {
            $this->warn(sprintf('%s %s: %d rows, nothing written', self::LABEL, $key, $rows->count()));
            $this->report[$key] = self::NO_MATCH;

            return;
        }

        /** @var Blog $blog */
        $blog = $rows->first();
        DB::transaction(function () use ($key, $blog, $spec): void {
            foreach ((array) ($spec['columns'] ?? []) as $column => $colSpec) {
                $this->applyColumn($key, $blog, (string) $column, $colSpec);
            }
            if ($blog->isDirty()) {
                $blog->saveQuietly();
            }
        });
        $this->finish($key);
    }

    private function finish(string $key): void
    {
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
        $targets = array_values(array_key_exists('target_values', $spec) ? (array) $spec['target_values'] : [$spec['new_value'] ?? null]);
        if ($olds === [] || $targets === [] || in_array(null, $targets, true)) {
            throw new RuntimeException("BPC cleanup addendum payload for {$key}.{$column} lacks old/target values.");
        }

        foreach ($targets as $target) {
            if ($current === self::normalize($target)) {
                $this->status($key, $column, self::CURRENT);

                return;
            }
        }

        if ($column === 'description' && $model instanceof EducationPost && $current === '') {
            // The card falls back to product_categories.description; that table is not touched.
            $this->status($key, $column, self::EMPTY);

            return;
        }

        $index = null;
        foreach ($olds as $i => $old) {
            if ($current === self::normalize($old)) {
                $index = $i;
                break;
            }
        }

        $result = $index === null ? null : $this->applyOp($column, $stored, $spec);
        $expected = $index !== null && count($targets) === count($olds) ? [$targets[$index]] : $targets;
        $ok = $result !== null;
        if ($ok) {
            $ok = false;
            foreach ($expected as $target) {
                if (self::normalize($result) === self::normalize($target)) {
                    $ok = true;
                    break;
                }
            }
        }

        if (! $ok) {
            $this->status($key, $column, self::MISMATCH);
            $excerpt = is_array($stored) ? json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $stored;
            $this->warn(sprintf(
                '%s %s %s: old mismatch, column left unchanged. Current (200 chars): %s',
                self::LABEL,
                $key,
                $column,
                mb_substr((string) $excerpt, 0, 200),
            ));

            return;
        }

        $model->{$column} = $result;
        $this->status($key, $column, self::UPDATED);
    }

    /**
     * Apply the column's op to the STORED value. Returns null when a
     * precondition fails.
     *
     * @param  array<string, mixed>  $spec
     */
    private function applyOp(string $column, mixed $stored, array $spec): mixed
    {
        if (isset($spec['edits'])) {
            return $this->applyEdits($stored, (array) $spec['edits']);
        }
        if (isset($spec['old_subsection'])) {
            return $this->replaceSubsections($stored, $spec);
        }
        if (isset($spec['old_finding'])) {
            return $this->replaceFinding($stored, $spec);
        }
        if (isset($spec['replace'], $spec['new_items'])) {
            return $this->replaceReferences($stored, $spec);
        }

        return $spec['new_value'];
    }

    /**
     * @param  list<array{old: string, new: string}>  $edits
     */
    private function applyEdits(mixed $stored, array $edits): ?string
    {
        if (! is_string($stored)) {
            return null;
        }
        foreach ($edits as $edit) {
            if (substr_count($stored, (string) $edit['old']) !== 1) {
                return null;
            }
            $stored = str_replace((string) $edit['old'], (string) $edit['new'], $stored);
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<int, mixed>|null
     */
    private function replaceSubsections(mixed $stored, array $spec): ?array
    {
        if (! is_array($stored)) {
            return null;
        }
        $pairs = [[$spec['old_subsection'], $spec['new_subsection']]];
        if (isset($spec['old_theoretical_concerns'])) {
            $pairs[] = [$spec['old_theoretical_concerns'], $spec['new_theoretical_concerns']];
        }
        foreach ($pairs as [$old, $new]) {
            $hits = array_keys(array_filter($stored, fn ($s) => is_array($s) && ($s['title'] ?? null) === $old['title']));
            if (count($hits) !== 1 || self::normalize($stored[$hits[0]]) !== self::normalize($old)) {
                return null;
            }
            $stored[$hits[0]] = $new;
        }

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<int, mixed>|null
     */
    private function replaceFinding(mixed $stored, array $spec): ?array
    {
        if (! is_array($stored)) {
            return null;
        }
        $old = $spec['old_finding'];
        $hits = [];
        foreach ($stored as $i => $subsection) {
            foreach ((array) ($subsection['findings'] ?? []) as $j => $finding) {
                if (is_array($finding) && ($finding['title'] ?? null) === $old['title']) {
                    $hits[] = [$i, $j];
                }
            }
        }
        if (count($hits) !== 1) {
            return null;
        }
        [$i, $j] = $hits[0];
        if (self::normalize($stored[$i]['findings'][$j]['content'] ?? '') !== self::normalize($old['content'])) {
            return null;
        }
        $stored[$i]['findings'][$j]['content'] = $spec['new_finding']['content'];

        return $stored;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<int, mixed>|null
     */
    private function replaceReferences(mixed $stored, array $spec): ?array
    {
        if (! is_array($stored) || count($stored) < 8) {
            return null;
        }
        $stored = array_values($stored);
        $url = fn (int $i): string => (string) ($stored[$i]['links'][0]['url'] ?? '');
        if (! str_starts_with((string) ($stored[5]['title'] ?? ''), 'USADA')
            || ! str_contains($url(6), 'orthoandwellness.com')
            || ! str_contains($url(7), 'en.wikipedia.org')) {
            return null;
        }
        foreach ((array) $spec['replace'] as $i => $item) {
            $stored[(int) $i] = $item;
        }
        foreach ((array) $spec['new_items'] as $item) {
            $stored[] = $item;
        }

        return $stored;
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
