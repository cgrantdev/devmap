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
 * Reconcile production with origin/main's encyclopedia values (29 Oct 2026).
 *
 * Several 2026_10_22 content migrations ran on production but skipped
 * columns whose guards did not match the live value. This seeder writes
 * main's final value (the result of main's own migration chain through
 * 2026_10_29_110000) to each of those columns. It adds no new copy.
 *
 * One correction on top of main (Content QA, 29 Oct 2026): TRIUMPH-OSA
 * (NEJM, DOI 10.1056/NEJMoa2611477, PMID 42904878) was published online
 * 20 October 2026, not 21. The retatrutide human_use_subsections and
 * references targets carry 20 Oct in the OSA subsection title, its first
 * entry and the reference citation. Their guards accept 110000's merged
 * 21 Oct output, which is live on production. The 110000 payload carries
 * the same fix, so a fresh database matches.
 *
 * Columns: ghk-cu regulatory_subsections, references, human_use_subsections;
 * retatrutide references, human_use_subsections; cagrilintide overview,
 * regulatory_subsections, regulatory_important_note, conclusion,
 * description; orforglipron references; bpc-157 references.
 *
 * Not touched: bpc-157 regulatory_subsections (2026_10_29_100000),
 * retatrutide regulatory_subsections (2026_10_29_110000), every other
 * column and post, and legacy unlinked education_posts rows.
 *
 * Each post is resolved with EncyclopediaPostMatch (exactly one category
 * and one linked post). One DB::transaction per slug; saveQuietly.
 * Every column goes through GuardedFieldWriter, which prints "updated",
 * "already current" or a "guard mismatch — nothing written" WARNING.
 *
 * description is the /encyclopedia card text: education_posts.description
 * of the linked post when it is non-empty after normalize, otherwise
 * product_categories.description of the same category. Never both.
 */
class EncyclopediaProdDriftReconcileOctober29Seeder extends Seeder
{
    public const MIGRATION = '2026_10_29_120000';

    public const LABEL = '2026_10_29_120000 prod drift reconcile';

    public const PAYLOAD_PATH = 'seeders/data/encyclopedia-field-updates/2026-10-29c/prod-drift.json';

    public const PAYLOAD_SHA256 = 'c5c52c29c842fcf7989ecb46a7316e98df15521e202d38db2f2d1cd157aa960f';

    /** @var array<string, list<string>> */
    public const COLUMNS = [
        'ghk-cu' => ['regulatory_subsections', 'references', 'human_use_subsections'],
        'retatrutide' => ['references', 'human_use_subsections'],
        'cagrilintide' => ['overview', 'regulatory_subsections', 'regulatory_important_note', 'conclusion', 'description'],
        'orforglipron' => ['references'],
        'bpc-157' => ['references'],
    ];

    /** @var array<string, string> "<slug>.<field>" => status */
    public array $results = [];

    /** @var list<string> */
    public array $lines = [];

    /** @var list<string> */
    public array $transactions = [];

    public function run(): void
    {
        $this->results = [];
        $this->lines = [];
        $this->transactions = [];

        $posts = $this->payload()['posts'] ?? null;
        if (! is_array($posts)) {
            throw new RuntimeException(self::LABEL.': payload is missing posts.');
        }
        if (array_keys($posts) !== array_keys(self::COLUMNS)) {
            throw new RuntimeException(self::LABEL.': payload posts do not match the seeder column list.');
        }

        $writer = new GuardedFieldWriter(self::MIGRATION, $this->command);

        foreach (self::COLUMNS as $slug => $fields) {
            $columns = $posts[$slug]['columns'] ?? null;
            if (! is_array($columns) || array_keys($columns) !== $fields) {
                throw new RuntimeException(self::LABEL.': payload columns for '.$slug.' do not match.');
            }
            $this->reconcileSlug($writer, $slug, $columns);
        }

        $this->results = $writer->results;
        $this->lines = $writer->lines;
    }

    /**
     * @param  array<string, array<string, mixed>>  $columns
     */
    private function reconcileSlug(GuardedFieldWriter $writer, string $slug, array $columns): void
    {
        $match = EncyclopediaPostMatch::find($slug, $this->command, self::LABEL);
        if ($match->status !== 'ok' || ! $match->post instanceof EducationPost || ! $match->category instanceof ProductCategory) {
            foreach (array_keys($columns) as $field) {
                $writer->skip($slug, $field, 'post lookup '.$match->status);
            }

            return;
        }

        $post = $match->post;
        $category = $match->category;

        DB::transaction(function () use ($writer, $slug, $columns, $post, $category): void {
            $this->transactions[] = $slug;
            $postChanged = false;
            $categoryChanged = false;

            foreach ($columns as $field => $spec) {
                if (! array_key_exists('target_value', $spec) || ! is_array($spec['old_values'] ?? null)) {
                    throw new RuntimeException(self::LABEL.': payload spec for '.$slug.'.'.$field.' is incomplete.');
                }
                $target = $spec['target_value'];
                $olds = array_values($spec['old_values']);

                if ($field === 'description' && GuardedFieldWriter::normalize((string) $post->description) === '') {
                    $status = $writer->apply($category, $slug, $field, $target, $olds);
                    $categoryChanged = $categoryChanged || $status === GuardedFieldWriter::UPDATED;

                    continue;
                }

                $status = $writer->apply($post, $slug, $field, $target, $olds);
                $postChanged = $postChanged || $status === GuardedFieldWriter::UPDATED;
            }

            if ($postChanged) {
                $post->saveQuietly();
            }
            if ($categoryChanged) {
                $category->saveQuietly();
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $raw = (string) file_get_contents(database_path(self::PAYLOAD_PATH));
        if (hash('sha256', $raw) !== self::PAYLOAD_SHA256) {
            throw new RuntimeException(self::LABEL.': payload sha256 does not match the pinned value.');
        }
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException(self::LABEL.': payload is not an object.');
        }

        return $decoded;
    }
}
