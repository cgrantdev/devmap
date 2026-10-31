<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaPostMatch;
use App\Support\GuardedFieldWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Encyclopedia and guide freshness update (31 October 2026).
 *
 * Guides (educational_guides, exactly one row by LOWER(TRIM(slug))):
 *   peptide-legality-fda-ruo-compounding content, reading_time
 *   compounded-vs-ruo-peptides content, reading_time
 * The content targets are EducationalContentPublisher's rendering of the
 * updated resources/content/educational/*.md files: FR Doc. 2026-22192
 * published 30 October 2026 at 91 FR 68526, Regulations.gov comment
 * period closing 13 November 2026 (11:59 pm ET), the Partnership for Safe
 * Medicines citizen petition (FDA-2026-P-13366), AJMC's reporting on
 * states and the February PCAC review, and the stale "scheduled for
 * publication" and "still forthcoming" lines rewritten. No semax-specific
 * 503A text is added or changed.
 *
 * Blog (blogs, exactly one row by LOWER(TRIM(slug))):
 *   hhs-503a-peptide-enforcement-discretion-2026 content only
 * The target is the publisher rendering of the updated markdown (91 FR
 * 68526-68527 publication, Regulations.gov comment close 13 November
 * 2026, reference 7). read_time stays "4 Min Read"; FAQ, key_points and
 * SEO fields are not touched.
 *
 * Posts (EncyclopediaPostMatch, exactly one category and one linked post):
 *   orforglipron human_use_subsections (adds ACHIEVE-4 and the 10-Q type 2
 *   diabetes submission line), references (+3: Lancet, Lilly release, 10-Q)
 *   eloralintide human_use_subsections (adds the Phase 3 starts listed in
 *   Lilly's Q3 2026 Form 10-Q and the EloraTZP Phase 3 timing in the Q3
 *   earnings release), references (+10-Q, +8-K Exhibit 99.1)
 *
 * Guards: origin/main's value, which equalled production's live value
 * when read on 31 October 2026, so one guard per column. Eloralintide
 * references also accept null, which renders the same empty list as [].
 * Fallbacks carry the same targets (markdown, publisher read_time,
 * OrforglipronProfile human_use_subsections, CatalogFraming eloralintide),
 * so a fresh database reports "already current". OrforglipronProfile
 * references are left as the pre-chain seed value: later payloads
 * (2026_10_22, 2026_10_29_120000) still rewrite that column in order.
 *
 * One DB::transaction per slug; saveQuietly. Every column goes through
 * GuardedFieldWriter: "updated", "already current" or a "guard mismatch
 * — nothing written" WARNING. A second run writes nothing.
 */
class EncyclopediaFreshnessOctober31Seeder extends Seeder
{
    public const MIGRATION = '2026_10_31_100000';

    public const LABEL = '2026_10_31_100000 freshness';

    public const PAYLOAD_PATH = 'seeders/data/encyclopedia-field-updates/2026-10-31a/freshness.json';

    public const PAYLOAD_SHA256 = 'a809a4c386e3b66ca6d649ac5bec7026d8622d8acf0fb06f54b9c97b26232a5c';

    /** @var array<string, list<string>> */
    public const COLUMNS = [
        'orforglipron' => ['human_use_subsections', 'references'],
        'eloralintide' => ['human_use_subsections', 'references'],
    ];

    /** @var array<string, list<string>> educational_guides slug => columns */
    public const GUIDES = [
        'peptide-legality-fda-ruo-compounding' => ['content', 'reading_time'],
        'compounded-vs-ruo-peptides' => ['content', 'reading_time'],
    ];

    public const GUIDE_PREFIX = 'guide:';

    /** @var array<string, list<string>> blogs slug => columns */
    public const BLOGS = [
        'hhs-503a-peptide-enforcement-discretion-2026' => ['content'],
    ];

    public const BLOG_PREFIX = 'blog:';

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

        $guides = $this->payload()['guides'] ?? null;
        if (! is_array($guides) || array_keys($guides) !== array_keys(self::GUIDES)) {
            throw new RuntimeException(self::LABEL.': payload guides do not match the seeder guide list.');
        }
        foreach (self::GUIDES as $slug => $fields) {
            $columns = $guides[$slug]['columns'] ?? null;
            if (! is_array($columns) || array_keys($columns) !== $fields) {
                throw new RuntimeException(self::LABEL.': payload columns for guide '.$slug.' do not match.');
            }
            $this->reconcileGuide($writer, $slug, $columns);
        }

        $blogs = $this->payload()['blogs'] ?? null;
        if (! is_array($blogs) || array_keys($blogs) !== array_keys(self::BLOGS)) {
            throw new RuntimeException(self::LABEL.': payload blogs do not match the seeder blog list.');
        }
        foreach (self::BLOGS as $slug => $fields) {
            $columns = $blogs[$slug]['columns'] ?? null;
            if (! is_array($columns) || array_keys($columns) !== $fields) {
                throw new RuntimeException(self::LABEL.': payload columns for blog '.$slug.' do not match.');
            }
            $this->reconcileBlog($writer, $slug, $columns);
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

        DB::transaction(function () use ($writer, $slug, $columns, $post): void {
            $this->transactions[] = $slug;
            $postChanged = false;

            foreach ($columns as $field => $spec) {
                if (! array_key_exists('target_value', $spec) || ! is_array($spec['old_values'] ?? null)) {
                    throw new RuntimeException(self::LABEL.': payload spec for '.$slug.'.'.$field.' is incomplete.');
                }
                $target = $spec['target_value'];
                $olds = array_values($spec['old_values']);

                $status = $writer->apply($post, $slug, $field, $target, $olds);
                $postChanged = $postChanged || $status === GuardedFieldWriter::UPDATED;
            }

            if ($postChanged) {
                $post->saveQuietly();
            }
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $columns
     */
    private function reconcileGuide(GuardedFieldWriter $writer, string $slug, array $columns): void
    {
        $key = self::GUIDE_PREFIX.$slug;
        $rows = EducationalGuide::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', [strtolower(trim($slug))])
            ->orderBy('id')
            ->get();
        if ($rows->count() !== 1) {
            $status = $rows->count() === 0 ? 'missing' : 'collision';
            foreach (array_keys($columns) as $field) {
                $writer->skip($key, $field, 'guide lookup '.$status);
            }

            return;
        }

        /** @var EducationalGuide $guide */
        $guide = $rows->first();

        DB::transaction(function () use ($writer, $key, $columns, $guide): void {
            $this->transactions[] = $key;
            $changed = false;
            foreach ($columns as $field => $spec) {
                if (! array_key_exists('target_value', $spec) || ! is_array($spec['old_values'] ?? null)) {
                    throw new RuntimeException(self::LABEL.': payload spec for '.$key.'.'.$field.' is incomplete.');
                }
                $status = $writer->apply($guide, $key, $field, $spec['target_value'], array_values($spec['old_values']));
                $changed = $changed || $status === GuardedFieldWriter::UPDATED;
            }
            if ($changed) {
                $guide->saveQuietly();
            }
        });
    }

    /**
     * Same guard path as reconcileGuide(), on the blogs table.
     *
     * @param  array<string, array<string, mixed>>  $columns
     */
    private function reconcileBlog(GuardedFieldWriter $writer, string $slug, array $columns): void
    {
        $key = self::BLOG_PREFIX.$slug;
        $rows = Blog::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', [strtolower(trim($slug))])
            ->orderBy('id')
            ->get();
        if ($rows->count() !== 1) {
            $status = $rows->count() === 0 ? 'missing' : 'collision';
            foreach (array_keys($columns) as $field) {
                $writer->skip($key, $field, 'blog lookup '.$status);
            }

            return;
        }

        /** @var Blog $blog */
        $blog = $rows->first();

        DB::transaction(function () use ($writer, $key, $columns, $blog): void {
            $this->transactions[] = $key;
            $changed = false;
            foreach ($columns as $field => $spec) {
                if (! array_key_exists('target_value', $spec) || ! is_array($spec['old_values'] ?? null)) {
                    throw new RuntimeException(self::LABEL.': payload spec for '.$key.'.'.$field.' is incomplete.');
                }
                $status = $writer->apply($blog, $key, $field, $spec['target_value'], array_values($spec['old_values']));
                $changed = $changed || $status === GuardedFieldWriter::UPDATED;
            }
            if ($changed) {
                $blog->saveQuietly();
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
