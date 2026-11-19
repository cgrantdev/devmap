<?php

namespace Database\Seeders;

use App\Support\EncyclopediaPostMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Strict research-use-only (RUO) language, 20 Nov 2026.
 *
 * Feedback to Colin: encyclopedia Regulatory Status sections "go into
 * court cases", "gray market" wording is unacceptable, and copy must
 * stay strictly RUO. Two parts:
 *
 * 1. Encyclopedia (data/ruo-cleanup/2026-11-20/encyclopedia.json): for 30
 *    entries, regulatory sections keep plain approval and anti-doping
 *    status only. Removed: court cases and lawsuits, enforcement and
 *    seizures, Health Canada advisories, the Senate letter, NSW emergency
 *    figures, expanded-access routes, compounding-policy detail, and
 *    grey/black-market wording anywhere in the entry, plus the
 *    references that only supported those passages. Each field is
 *    written only when its stored value still equals the value read from
 *    the live page on 20 Nov 2026 ("old"); anything edited since is
 *    skipped and logged, never overwritten. A field that already holds
 *    the new value is counted and left alone.
 *
 * 2. Blogs and guides (copy-rules.json): ordered phrase rewrites that
 *    remove "grey-area" / "grey-market" / "black-market" wording,
 *    including the stock line "Peptidemap is an RUO / grey-area peptide
 *    comparison publisher", and drop the "Grey market" blog tag. Each
 *    rule only matches the old wording, so running it again changes
 *    nothing. The same rules were applied to the blog source files.
 */
class RuoCopyCleanupNovember20Seeder extends Seeder
{
    private const LABEL = 'ruo-copy-cleanup-2026-11-20';

    private const DATA_DIR = __DIR__.'/data/ruo-cleanup/2026-11-20';

    private const BLOG_TEXT = ['title', 'description', 'content', 'seo_page_title', 'seo_description', 'seo_og_title', 'seo_og_description'];

    private const BLOG_JSON = ['key_points', 'tags', 'seo_schema', 'faqs'];

    private const GUIDE_TEXT = ['title', 'description', 'content', 'seo_page_title', 'seo_description', 'seo_og_title', 'seo_og_description'];

    private const GUIDE_JSON = ['seo_schema'];

    /** @var array<string, int> */
    public array $counts = ['fields_written' => 0, 'fields_already' => 0, 'fields_skipped' => 0, 'blog_rows' => 0, 'guide_rows' => 0];

    public function run(): void
    {
        $this->cleanEncyclopedia();
        $rules = $this->rules();
        $this->counts['blog_rows'] = $this->rewriteTable('blogs', self::BLOG_TEXT, self::BLOG_JSON, $rules, true);
        $this->counts['guide_rows'] = $this->rewriteTable('educational_guides', self::GUIDE_TEXT, self::GUIDE_JSON, $rules, false);

        Log::info(self::LABEL.' done', $this->counts);
        $this->command?->info(self::LABEL.': '.json_encode($this->counts));
    }

    private function cleanEncyclopedia(): void
    {
        $spec = json_decode((string) file_get_contents(self::DATA_DIR.'/encyclopedia.json'), true);
        if (! is_array($spec)) {
            throw new RuntimeException(self::LABEL.': encyclopedia.json is unreadable.');
        }

        foreach ($spec as $slug => $fields) {
            $match = EncyclopediaPostMatch::find((string) $slug, $this->command, self::LABEL);
            if ($match->status !== 'ok' || ! $match->post) {
                $this->counts['fields_skipped'] += count($fields);
                Log::warning(self::LABEL.' post lookup '.$match->status, ['slug' => $slug]);

                continue;
            }

            $row = (array) DB::table('education_posts')->where('id', $match->post->id)->first();
            $updates = [];
            foreach ($fields as $column => $change) {
                if (! array_key_exists($column, $row)) {
                    $this->counts['fields_skipped']++;
                    Log::warning(self::LABEL.' missing column', ['slug' => $slug, 'column' => $column]);

                    continue;
                }
                $current = $change['json'] ? $this->decode($row[$column]) : (string) ($row[$column] ?? '');
                if ($current == $change['new']) {
                    $this->counts['fields_already']++;

                    continue;
                }
                if ($current != $change['old']) {
                    $this->counts['fields_skipped']++;
                    Log::warning(self::LABEL.' changed since 20 Nov, skipped', ['slug' => $slug, 'column' => $column]);

                    continue;
                }
                $updates[$column] = $change['json']
                    ? json_encode($change['new'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $change['new'];
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('education_posts')->where('id', $match->post->id)->update($updates);
                $this->counts['fields_written'] += count($updates) - 1;
            }
        }
    }

    /**
     * @param  list<string>  $textColumns
     * @param  list<string>  $jsonColumns
     * @param  list<array{0: string, 1: string}>  $rules
     */
    private function rewriteTable(string $table, array $textColumns, array $jsonColumns, array $rules, bool $dropGreyTag): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }
        $text = array_values(array_filter($textColumns, fn ($c) => Schema::hasColumn($table, $c)));
        $json = array_values(array_filter($jsonColumns, fn ($c) => Schema::hasColumn($table, $c)));
        $changedRows = 0;

        DB::table($table)->orderBy('id')->select(array_merge(['id'], $text, $json))->chunkById(100, function ($rows) use ($table, $text, $json, $rules, $dropGreyTag, &$changedRows) {
            foreach ($rows as $row) {
                $updates = [];
                foreach ($text as $column) {
                    $value = $row->{$column};
                    if (is_string($value) && $value !== '') {
                        $new = $this->rewrite($value, $rules);
                        if ($new !== $value) {
                            $updates[$column] = $new;
                        }
                    }
                }
                foreach ($json as $column) {
                    $decoded = $this->decode($row->{$column});
                    if (! is_array($decoded)) {
                        continue;
                    }
                    $new = $this->rewriteJson($decoded, $rules);
                    if ($dropGreyTag && $column === 'tags') {
                        $new = array_values(array_filter($new, fn ($tag) => ! (is_string($tag) && strcasecmp(trim($tag), 'Grey market') === 0)));
                    }
                    if ($new !== $decoded) {
                        $updates[$column] = json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
                if ($updates !== []) {
                    $updates['updated_at'] = now();
                    DB::table($table)->where('id', $row->id)->update($updates);
                    $changedRows++;
                }
            }
        });

        return $changedRows;
    }

    /** @return list<array{0: string, 1: string}> */
    private function rules(): array
    {
        $rules = json_decode((string) file_get_contents(self::DATA_DIR.'/copy-rules.json'), true);
        if (! is_array($rules)) {
            throw new RuntimeException(self::LABEL.': copy-rules.json is unreadable.');
        }

        // Patterns are shared with the source-file pass; "~" never appears in them.
        return array_map(fn ($r) => ['~'.$r[0].'~u', $r[1]], $rules);
    }

    /** @param list<array{0: string, 1: string}> $rules */
    private function rewrite(string $value, array $rules): string
    {
        foreach ($rules as [$pattern, $replacement]) {
            $next = preg_replace($pattern, $replacement, $value);
            if (is_string($next)) {
                $value = $next;
            }
        }

        return $value;
    }

    /** @param list<array{0: string, 1: string}> $rules */
    private function rewriteJson(mixed $value, array $rules): mixed
    {
        if (is_string($value)) {
            return $this->rewrite($value, $rules);
        }
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->rewriteJson($item, $rules);
            }
        }

        return $value;
    }

    private function decode(mixed $raw): mixed
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (! is_string($raw) || $raw === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        // Some older rows hold JSON encoded twice.
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return $decoded;
    }
}
