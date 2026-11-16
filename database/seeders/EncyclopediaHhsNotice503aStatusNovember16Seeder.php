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
 * HHS 503A notice status on BPC-157, TB-500, epitalon and KPV,
 * 16 November 2026 (2026_11_16_240000).
 *
 * Same change and wording as 2026_11_16_220000 (MOTS-c): the notice
 * paragraph in regulatory_subsections moves from "is scheduled for
 * publication on 30 October 2026" to published on 30 October 2026
 * (FR Doc. 2026-22192, 91 FR 68526-68527, docket HHS-OASH-2026-0496),
 * comment period closed 11:59 pm ET on 13 November 2026, nothing final
 * as of 16 November 2026. The dated compounding heading of each entry
 * keeps its wording but its "(as of 29 October 2026)" becomes "(FDA
 * status as of 29 October 2026)", the 220000 convention (QA F1).
 * Nothing else in any entry changes.
 *
 * bpc-157 is also written by 2026_11_16_210000 (PR #93), so its payload
 * has two variants ("live-2026-11-16" and "after-2026_11_16_210000");
 * the other three have one. Each variant's target is its old value with
 * only those strings changed.
 *
 * Guard, per entry (pattern of 220000 / 210000):
 *   1. Lookup by EncyclopediaPostMatch. Any other status is a WARNING.
 *   2. The column is compared with GuardedFieldWriter::comparable (object
 *      key order never decides). Equal to a variant target: "already
 *      current". Equal to a variant old value: that variant is used.
 *      Anything else: "guard mismatch", nothing written.
 *   3. Before writing, the variant's sibling columns (human_use_intro,
 *      human_use_subsections, regulatory_important_note, references) must
 *      still equal their expected values. Otherwise "column drift: <col>",
 *      nothing written.
 *   4. GuardedFieldWriter::apply() inside one DB::transaction per entry,
 *      then saveQuietly(). A second run logs "already current".
 * One entry's skip never stops the others.
 */
class EncyclopediaHhsNotice503aStatusNovember16Seeder extends Seeder
{
    public const MIGRATION = '2026_11_16_240000';

    public const LABEL = '2026_11_16_240000 hhs notice 503a status';

    public const PAYLOAD_PATH = 'seeders/data/encyclopedia-field-updates/2026-11-16c/hhs-notice-503a-status.json';

    public const PAYLOAD_SHA256 = '3092f1ead8b7bfd130651331c36633391430af172c5212258a3d66f0cde13507';

    /** Payload keys, in order. */
    public const SLUGS = ['bpc-157', 'tb-500', 'epitalon', 'kpv'];

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
        if (! is_array($posts) || array_keys($posts) !== self::SLUGS) {
            throw new RuntimeException(self::LABEL.': payload posts do not match the seeder slug list.');
        }
        foreach ($posts as $slug => $spec) {
            if (($spec['field'] ?? null) !== self::FIELD || ! is_array($spec['variants'] ?? null) || $spec['variants'] === []) {
                throw new RuntimeException(self::LABEL.': payload spec for '.$slug.' does not match.');
            }
            foreach ($spec['variants'] as $variant) {
                if (! array_key_exists('old_value', $variant) || ! array_key_exists('target_value', $variant) || ! is_array($variant['drift'] ?? null)) {
                    throw new RuntimeException(self::LABEL.': payload variant for '.$slug.' is incomplete.');
                }
            }
        }

        $writer = new GuardedFieldWriter(self::MIGRATION, $this->command);
        foreach ($posts as $slug => $spec) {
            $this->runEntry($writer, $slug, $spec['variants']);
        }

        $this->results = $writer->results;
        $this->lines = $writer->lines;
    }

    /**
     * @param  list<array<string, mixed>>  $variants
     */
    private function runEntry(GuardedFieldWriter $writer, string $slug, array $variants): void
    {
        $match = EncyclopediaPostMatch::find($slug, $this->command, self::LABEL);
        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost || ! $match->category instanceof ProductCategory) {
            $writer->skip($slug, self::FIELD, 'post lookup '.$match->status);

            return;
        }

        $post = $match->post;
        $now = GuardedFieldWriter::comparable($post->getAttribute(self::FIELD));
        foreach ($variants as $variant) {
            if ($now === GuardedFieldWriter::comparable($variant['target_value'])) {
                // Already current: apply() records it and writes nothing.
                $writer->apply($post, $slug, self::FIELD, $variant['target_value'], []);

                return;
            }
        }

        $chosen = null;
        foreach ($variants as $variant) {
            if ($now === GuardedFieldWriter::comparable($variant['old_value'])) {
                $chosen = $variant;
                break;
            }
        }
        if ($chosen === null) {
            $writer->skip($slug, self::FIELD, self::GUARD_MISMATCH);

            return;
        }

        $drifted = $this->siblingDrift($post, $chosen['drift']);
        if ($drifted !== null) {
            $writer->skip($slug, self::FIELD, self::DRIFT_PREFIX.$drifted);

            return;
        }

        $this->transactions[] = $slug;
        DB::transaction(function () use ($writer, $post, $slug, $chosen): void {
            $status = $writer->apply($post, $slug, self::FIELD, $chosen['target_value'], [$chosen['old_value']]);
            if ($status === GuardedFieldWriter::UPDATED) {
                $post->saveQuietly();
            }
        });
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
