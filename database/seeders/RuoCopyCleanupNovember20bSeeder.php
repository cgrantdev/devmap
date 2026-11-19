<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Strict RUO wording, pass 2 (20 Nov 2026).
 *
 * Colin: "There has to be a more RUO word than Dosing", "stack" and
 * "cycle" are "literal steroid terms", "protocol" (regimen sense)
 * "probably should not have that", and the legal sections in two blog
 * posts should go. data/ruo-cleanup/2026-11-20b/copy-rules.json holds:
 *
 * - rules: exact-phrase rewrites. Disclaimers such as "No dose is
 *   given." are deleted (a bullet that was only that sentence is
 *   removed); regimen uses of protocol/stack/cycle are reworded. Dose
 *   words that report what a published trial gave are not touched.
 * - groups: rules that run only on the named slugs and only while the
 *   "when" pattern matches. Used to drop the two court references from
 *   retatrutide-vs-semaglutide and renumber its citations once.
 * - cleanup: whitespace and empty-tag tidying, run only on values a rule
 *   changed.
 *
 * Every rule only matches the old wording, so a second run changes
 * nothing, and text an editor has since reworded is left alone. The same
 * rules were applied to the blog/guide source files.
 */
class RuoCopyCleanupNovember20bSeeder extends Seeder
{
    private const LABEL = 'ruo-copy-cleanup-2026-11-20b';

    private const RULES = __DIR__.'/data/ruo-cleanup/2026-11-20b/copy-rules.json';

    /** @var array<string, array{text: list<string>, json: list<string>}> */
    private const TABLES = [
        'education_posts' => [
            'text' => [
                'description', 'research_outline', 'peptide_full_name', 'half_life', 'overview', 'background',
                'areas_of_research_intro', 'areas_of_research_note', 'mechanism_of_action_intro', 'preclinical_intro',
                'preclinical_disclaimer', 'regulatory_important_note', 'potential_applications_intro',
                'potential_applications_important_context', 'conclusion',
                // Added for 2026_11_20_140000: encyclopedia meta descriptions.
                'seo_description', 'seo_og_description',
            ],
            'json' => [
                'key_points', 'key_effects', 'areas_of_research', 'mechanism_subsections', 'preclinical_subsections',
                'regulatory_subsections', 'potential_applications', 'references', 'faqs',
            ],
        ],
        'blogs' => [
            'text' => [
                'title', 'description', 'outline', 'introduction', 'detailed_analysis', 'conclusion', 'content',
                'seo_page_title', 'seo_description', 'seo_og_title', 'seo_og_description',
            ],
            'json' => ['key_points', 'tags', 'seo_schema', 'faqs'],
        ],
        'educational_guides' => [
            'text' => [
                'title', 'description', 'outline', 'introduction', 'conclusion', 'content',
                'seo_page_title', 'seo_description', 'seo_og_title', 'seo_og_description',
            ],
            'json' => ['seo_schema'],
        ],
        // Added for 2026_11_20_140000. Category descriptions show on
        // product pages and as the encyclopedia subtitle fallback;
        // seo_pages holds the page-level metas (e.g. /products).
        'product_categories' => [
            'text' => ['description'],
            'json' => [],
        ],
        'seo_pages' => [
            'text' => ['title', 'description', 'og_title', 'og_description'],
            'json' => [],
        ],
    ];

    /** @var array<string, int> */
    public array $counts = ['education_posts' => 0, 'blogs' => 0, 'educational_guides' => 0, 'product_categories' => 0, 'seo_pages' => 0];

    /** @var list<array{0: string, 1: string}> */
    private array $rules = [];

    /** @var list<array{when: string, slugs: list<string>, rules: list<array{0: string, 1: string}>}> */
    private array $groups = [];

    /** @var list<array{0: string, 1: string}> */
    private array $cleanup = [];

    public function run(): void
    {
        $this->load();
        foreach (self::TABLES as $table => $columns) {
            $this->counts[$table] = $this->rewriteTable($table, $columns['text'], $columns['json']);
        }

        Log::info(self::LABEL.' done', $this->counts);
        $this->command?->info(self::LABEL.': '.json_encode($this->counts));
    }

    private function load(): void
    {
        $spec = json_decode((string) file_get_contents(self::RULES), true);
        if (! is_array($spec) || ! isset($spec['rules'], $spec['groups'], $spec['cleanup'])) {
            throw new RuntimeException(self::LABEL.': copy-rules.json is unreadable.');
        }

        // Patterns are shared with the source-file pass; "~" never appears in them.
        $compile = fn (array $items) => array_map(fn ($r) => ['~'.$r['find'].'~u', (string) $r['replace']], $items);
        $this->rules = $compile($spec['rules']);
        $this->cleanup = $compile($spec['cleanup']);
        $this->groups = array_map(fn ($g) => [
            'when' => '~'.$g['when'].'~u',
            'slugs' => array_map('strval', $g['slugs']),
            'rules' => $compile($g['rules']),
        ], $spec['groups']);
    }

    /**
     * @param  list<string>  $textColumns
     * @param  list<string>  $jsonColumns
     */
    private function rewriteTable(string $table, array $textColumns, array $jsonColumns): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }
        $text = array_values(array_filter($textColumns, fn ($c) => Schema::hasColumn($table, $c)));
        $json = array_values(array_filter($jsonColumns, fn ($c) => Schema::hasColumn($table, $c)));
        $hasSlug = Schema::hasColumn($table, 'slug');
        $select = array_merge(['id'], $hasSlug ? ['slug'] : [], $text, $json);
        $changedRows = 0;

        DB::table($table)->select($select)->chunkById(100, function ($rows) use ($table, $text, $json, $hasSlug, &$changedRows) {
            foreach ($rows as $row) {
                $slug = $hasSlug ? strtolower(trim((string) $row->slug)) : '';
                $updates = [];
                foreach ($text as $column) {
                    $value = $row->{$column};
                    if (is_string($value) && $value !== '') {
                        $new = $this->rewrite($value, $slug);
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
                    $new = $this->rewriteJson($decoded, $slug);
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

    private function rewrite(string $value, string $slug): string
    {
        $out = $this->apply($value, $this->rules);
        foreach ($this->groups as $group) {
            if (in_array($slug, $group['slugs'], true) && preg_match($group['when'], $out) === 1) {
                $out = $this->apply($out, $group['rules']);
            }
        }
        if ($out !== $value) {
            $out = $this->apply($out, $this->cleanup);
        }

        return $out;
    }

    /** @param list<array{0: string, 1: string}> $rules */
    private function apply(string $value, array $rules): string
    {
        foreach ($rules as [$pattern, $replacement]) {
            $next = preg_replace($pattern, $replacement, $value);
            if (is_string($next)) {
                $value = $next;
            }
        }

        return $value;
    }

    private function rewriteJson(mixed $value, string $slug): mixed
    {
        if (is_string($value)) {
            return $value === '' ? $value : $this->rewrite($value, $slug);
        }
        if (! is_array($value)) {
            return $value;
        }
        $isList = array_is_list($value);
        $out = [];
        foreach ($value as $key => $item) {
            $new = $this->rewriteJson($item, $slug);
            // A bullet that was only a removed sentence is dropped.
            if ($isList && is_string($item) && $item !== '' && $new === '') {
                continue;
            }
            $out[$key] = $new;
        }

        return $isList ? array_values($out) : $out;
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
