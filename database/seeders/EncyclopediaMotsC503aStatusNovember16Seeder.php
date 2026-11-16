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
 * MOTS-c 503A status, 16 November 2026 (2026_11_16_220000).
 *
 * One column, mots-c regulatory_subsections. The 503A notice paragraph
 * still said the HHS notice "is scheduled for publication on 30 October
 * 2026". It now says the notice was published on 30 October 2026
 * (FR Doc. 2026-22192, 91 FR 68526-68527, docket HHS-OASH-2026-0496),
 * that the comment period closed at 11:59 pm ET on 13 November 2026, and
 * that nothing final has been published as of 16 November 2026. Nothing
 * else in the entry changes.
 *
 * The payload has two variants because 2026_11_16_210000 (PR #93) also
 * writes this column and may or may not have applied when this runs:
 *   "live-2026-11-16"          old = live value read 16 Nov 2026 10:31 ICT
 *   "after-2026_11_16_210000"  old = the 210000 target
 * Each variant's target is its old value with only that paragraph changed.
 *
 * Guard (pattern of 2026_11_16_200000 / 210000):
 *   1. Lookup by EncyclopediaPostMatch. Any other status is a WARNING.
 *   2. The column is compared with GuardedFieldWriter::comparable (object
 *      key order never decides). Equal to a variant target: "already
 *      current". Equal to a variant old value: that variant is used.
 *      Anything else: "guard mismatch", nothing written.
 *   3. Before writing, the variant's sibling columns (human_use_intro,
 *      human_use_subsections, regulatory_important_note, references) must
 *      still equal their expected values. Otherwise "column drift: <col>",
 *      nothing written.
 *   4. GuardedFieldWriter::apply() inside a DB::transaction, then
 *      saveQuietly(). A second run logs "already current".
 */
class EncyclopediaMotsC503aStatusNovember16Seeder extends Seeder
{
    public const MIGRATION = '2026_11_16_220000';

    public const LABEL = '2026_11_16_220000 mots-c 503a status';

    public const PAYLOAD_PATH = 'seeders/data/encyclopedia-field-updates/2026-11-16b/mots-c-503a-status.json';

    public const PAYLOAD_SHA256 = '161980f2b3204a08730529085919190c482c0fb4d7a5252074ea4b56d517102d';

    public const SLUG = 'mots-c';

    public const FIELD = 'regulatory_subsections';

    public const GUARD_MISMATCH = 'guard mismatch';

    public const DRIFT_PREFIX = 'column drift: ';

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
        $spec = is_array($posts) ? ($posts[self::SLUG] ?? null) : null;
        if (! is_array($posts) || array_keys($posts) !== [self::SLUG] || ! is_array($spec)
            || ($spec['field'] ?? null) !== self::FIELD || ! is_array($spec['variants'] ?? null) || $spec['variants'] === []) {
            throw new RuntimeException(self::LABEL.': payload does not match the seeder.');
        }
        foreach ($spec['variants'] as $variant) {
            if (! array_key_exists('old_value', $variant) || ! array_key_exists('target_value', $variant) || ! is_array($variant['drift'] ?? null)) {
                throw new RuntimeException(self::LABEL.': payload variant is incomplete.');
            }
        }

        $writer = new GuardedFieldWriter(self::MIGRATION, $this->command);
        $match = EncyclopediaPostMatch::find(self::SLUG, $this->command, self::LABEL);
        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost || ! $match->category instanceof ProductCategory) {
            $writer->skip(self::SLUG, self::FIELD, 'post lookup '.$match->status);
            $this->finish($writer);

            return;
        }

        $post = $match->post;
        $now = GuardedFieldWriter::comparable($post->getAttribute(self::FIELD));
        $chosen = null;
        foreach ($spec['variants'] as $variant) {
            if ($now === GuardedFieldWriter::comparable($variant['target_value'])) {
                // Already current: apply() records it and writes nothing.
                $writer->apply($post, self::SLUG, self::FIELD, $variant['target_value'], []);
                $this->finish($writer);

                return;
            }
        }
        foreach ($spec['variants'] as $variant) {
            if ($now === GuardedFieldWriter::comparable($variant['old_value'])) {
                $chosen = $variant;
                break;
            }
        }

        if ($chosen === null) {
            $writer->skip(self::SLUG, self::FIELD, self::GUARD_MISMATCH);
            $this->finish($writer);

            return;
        }

        $drifted = $this->siblingDrift($post, $chosen['drift']);
        if ($drifted !== null) {
            $writer->skip(self::SLUG, self::FIELD, self::DRIFT_PREFIX.$drifted);
            $this->finish($writer);

            return;
        }

        $this->transactions[] = self::SLUG;
        DB::transaction(function () use ($writer, $post, $chosen): void {
            $status = $writer->apply($post, self::SLUG, self::FIELD, $chosen['target_value'], [$chosen['old_value']]);
            if ($status === GuardedFieldWriter::UPDATED) {
                $post->saveQuietly();
            }
        });

        $this->finish($writer);
    }

    private function finish(GuardedFieldWriter $writer): void
    {
        $this->results = $writer->results;
        $this->lines = $writer->lines;
    }

    /**
     * First sibling column whose value is no longer one of its expected
     * values, or null. Empty expected values also accept null.
     *
     * @param  array<string, array{expected: list<mixed>}>  $drift
     */
    private function siblingDrift(EducationPost $post, array $drift): ?string
    {
        foreach ($drift as $column => $spec) {
            $now = GuardedFieldWriter::comparable($post->getAttribute($column));
            $ok = false;
            foreach ($spec['expected'] ?? [] as $expected) {
                $want = GuardedFieldWriter::comparable($expected);
                if ($now === $want || ($now === null && ($want === '' || $want === []))) {
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
