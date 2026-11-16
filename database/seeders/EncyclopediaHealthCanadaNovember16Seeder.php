<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaPostMatch;
use App\Support\GuardedFieldWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Encyclopedia freshness, 16 November 2026 (2026_11_16_210000).
 *
 * Health Canada advisories RA-82930 (6 Nov 2026) and RA-82944 (12 Nov
 * 2026) on 8 entries (regulatory_subsections, references).
 *
 * Lookup: EncyclopediaPostMatch (one category by LOWER(TRIM(slug)), one
 * linked post). Any other status is a WARNING and the entry is skipped.
 *
 * Guard, all-or-nothing per entry (pattern of
 * EducationalFreshnessNovember16Seeder, 2026_11_16_200000):
 *   1. Each target column is classified with GuardedFieldWriter::comparable
 *      (normalised strings, object keys sorted at every level, so MySQL's
 *      JSON key order never decides a guard): "target" (already current),
 *      "old" (equals a payload old value, the live value read on 16 Nov
 *      2026; empty lists also accept null) or "drift".
 *   2. Any "drift" column: the whole entry is skipped with a
 *      "guard mismatch" WARNING per column. Nothing is written, so an entry
 *      never gets the subsection without its reference or vice versa.
 *   3. If at least one column would be written, the entry's sibling
 *      columns (payload "drift": human_use_intro, regulatory_important_note
 *      and whichever subsections column is not a target) must still equal
 *      the live 16 Nov 2026 values. Otherwise the entry is skipped with a
 *      "column drift: <column>" WARNING, so an admin edit made elsewhere in
 *      the entry since that read is never built on.
 *   4. Otherwise GuardedFieldWriter::apply() per column inside one
 *      DB::transaction, then saveQuietly() if anything was updated.
 * A second run logs "already current" and writes nothing.
 */
class EncyclopediaHealthCanadaNovember16Seeder extends Seeder
{
    public const MIGRATION = '2026_11_16_210000';

    public const LABEL = '2026_11_16_210000 health canada + eloralintide';

    public const PAYLOAD_PATH = 'seeders/data/encyclopedia-field-updates/2026-11-16a/health-canada-eloralintide.json';

    public const PAYLOAD_SHA256 = 'cb340c91c080e3ac5a342fac904fe890c5de918146d3102ce6630d9cf2a2690a';

    public const GUARD_MISMATCH = 'guard mismatch';

    public const DRIFT_PREFIX = 'column drift: ';

    public const HELD = 'entry held: another column failed its guard';

    /** @var array<string, list<string>> */
    public const COLUMNS = [
        'hcg' => ['regulatory_subsections', 'references'],
        'mots-c' => ['regulatory_subsections', 'references'],
        'retatrutide' => ['regulatory_subsections', 'references'],
        'tesamorelin' => ['regulatory_subsections', 'references'],
        'nad' => ['regulatory_subsections', 'references'],
        'tirzepatide' => ['regulatory_subsections', 'references'],
        'bpc-157' => ['regulatory_subsections', 'references'],
        'selank' => ['regulatory_subsections', 'references'],
    ];

    /** @var array<string, string> "<slug>.<field>" => status */
    public array $results = [];

    /** @var list<string> */
    public array $lines = [];

    /** @var list<string> slugs that reached a DB transaction */
    public array $transactions = [];

    public function run(): void
    {
        $this->results = [];
        $this->lines = [];
        $this->transactions = [];

        $posts = $this->payload()['posts'] ?? null;
        if (! is_array($posts) || array_keys($posts) !== array_keys(self::COLUMNS)) {
            throw new RuntimeException(self::LABEL.': payload posts do not match the seeder column list.');
        }

        $writer = new GuardedFieldWriter(self::MIGRATION, $this->command);

        foreach (self::COLUMNS as $slug => $fields) {
            $columns = $posts[$slug]['columns'] ?? null;
            $drift = $posts[$slug]['drift'] ?? null;
            if (! is_array($columns) || array_keys($columns) !== $fields || ! is_array($drift)) {
                throw new RuntimeException(self::LABEL.': payload spec for '.$slug.' does not match.');
            }
            foreach ($columns as $field => $spec) {
                if (! array_key_exists('target_value', $spec) || ! is_array($spec['old_values'] ?? null)) {
                    throw new RuntimeException(self::LABEL.': payload spec for '.$slug.'.'.$field.' is incomplete.');
                }
            }

            $match = EncyclopediaPostMatch::find($slug, $this->command, self::LABEL);
            if ($match->status !== 'ok' || ! $match->post instanceof EducationPost || ! $match->category instanceof ProductCategory) {
                foreach ($fields as $field) {
                    $writer->skip($slug, $field, 'post lookup '.$match->status);
                }

                continue;
            }

            $post = $match->post;
            $states = [];
            foreach ($columns as $field => $spec) {
                $states[$field] = $this->classify($post->getAttribute($field), $spec['target_value'], $spec['old_values']);
            }

            if (in_array('drift', $states, true)) {
                foreach ($states as $field => $state) {
                    $writer->skip($slug, $field, $state === 'drift' ? self::GUARD_MISMATCH : self::HELD);
                }

                continue;
            }

            if (in_array('old', $states, true)) {
                $drifted = $this->siblingDrift($post, $drift);
                if ($drifted !== null) {
                    foreach ($fields as $field) {
                        $writer->skip($slug, $field, self::DRIFT_PREFIX.$drifted);
                    }

                    continue;
                }
            }

            $this->transactions[] = $slug;
            DB::transaction(function () use ($writer, $slug, $columns, $post): void {
                $changed = false;
                foreach ($columns as $field => $spec) {
                    $status = $writer->apply($post, $slug, $field, $spec['target_value'], array_values($spec['old_values']));
                    $changed = $changed || $status === GuardedFieldWriter::UPDATED;
                }
                if ($changed) {
                    $post->saveQuietly();
                }
            });
        }

        $this->results = $writer->results;
        $this->lines = $writer->lines;
    }

    /**
     * @param  list<mixed>  $oldValues
     */
    private function classify(mixed $current, mixed $target, array $oldValues): string
    {
        $now = GuardedFieldWriter::comparable($current);
        if ($now === GuardedFieldWriter::comparable($target)) {
            return 'target';
        }
        foreach ($oldValues as $old) {
            if ($now === GuardedFieldWriter::comparable($old)) {
                return 'old';
            }
        }

        return 'drift';
    }

    /**
     * First sibling column whose value is no longer one of its expected
     * live values, or null.
     *
     * @param  array<string, array{expected: list<mixed>}>  $drift
     */
    private function siblingDrift(EducationPost $post, array $drift): ?string
    {
        foreach ($drift as $column => $spec) {
            $now = GuardedFieldWriter::comparable($post->getAttribute($column));
            $ok = false;
            foreach ($spec['expected'] ?? [] as $expected) {
                if ($now === GuardedFieldWriter::comparable($expected)) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok) {
                return $column;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $raw = (string) file_get_contents(database_path(self::PAYLOAD_PATH));
        if (hash('sha256', $raw) !== self::PAYLOAD_SHA256) {
            throw new RuntimeException(self::LABEL.': payload sha256 does not match the pinned value.');
        }

        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }
}
