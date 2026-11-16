<?php

namespace Tests\Feature;

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Support\GuardedFieldWriter;
use Database\Seeders\EducationalFreshnessNovember16Seeder as Nov16;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 2026_11_16_200000 refresh. The fixture under tests/fixtures holds the
 * 1a9fb77 publisher renders used only as the guard's old values. It is
 * not page copy.
 */
class EducationalFreshnessNovember16Test extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_11_16_200000_refresh_guides_and_blogs_november_16.php';

    /** @var array<string, array{0: int, 1: string}> */
    private const EXPECTED = [
        'compounded-vs-ruo-peptides' => [2903, '13 Min Read'],
        'peptide-legality-fda-ruo-compounding' => [5748, '25 Min Read'],
        'bpc-157-vs-tb-500-evidence' => [4367, '19 Min Read'],
        'retatrutide-side-effects-trial-data' => [4720, '21 Min Read'],
        'retatrutide-vs-semaglutide' => [4319, '19 Min Read'],
        'eloralintide-tirzepatide-vs-retatrutide' => [1459, '7 Min Read'],
        'hhs-503a-peptide-enforcement-discretion-2026' => [910, '4 Min Read'],
        'bpc-157-vs-ghk-cu' => [2190, '10 Min Read'],
    ];

    /** @var list<array{level: string, message: string}> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e): void {
            $this->logged[] = ['level' => $e->level, 'message' => $e->message];
        });
    }

    public function test_migration_slot_and_old_render_hashes_are_pinned(): void
    {
        $files = glob(database_path('migrations/2026_11_16_*.php')) ?: [];
        sort($files);
        $this->assertSame([
            self::MIGRATION,
            '2026_11_16_210000_encyclopedia_health_canada_and_eloralintide_november_16.php',
            '2026_11_16_220000_encyclopedia_mots_c_503a_comment_period_closed.php',
        ], array_map('basename', $files));
        $source = (string) file_get_contents(database_path('migrations/'.self::MIGRATION));
        $this->assertStringContainsString('EducationalFreshnessNovember16Seeder', $source);
        $this->assertStringContainsString('Previous wording is not restored', $source);
        $this->assertSame(array_keys(self::EXPECTED), array_keys(Nov16::PAGES));

        $fixture = $this->fixture();
        foreach (Nov16::PAGES as $slug => $spec) {
            $this->assertSame($spec['old_sha256'], hash('sha256', $fixture[$slug]['html']), $slug);
            $this->assertSame($spec['old_read_time'], $fixture[$slug]['read_time'], $slug);
            $this->assertNotSame($spec['old_sha256'], hash('sha256', (new Nov16)->render($slug)), $slug);
        }
    }

    public function test_old_value_rows_are_updated_and_a_second_run_writes_nothing(): void
    {
        $this->plantOld();
        $ids = $this->ids();

        $seeder = new Nov16;
        $seeder->run();

        $this->assertSame([], $this->warnings());
        foreach (Nov16::PAGES as $slug => $spec) {
            $key = $this->key($slug);
            $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results[$key], $key);
            $this->assertTrue($this->hasLog('info', Nov16::MIGRATION.' '.$key.': updated'), $key);
            $row = $this->row($slug);
            $this->assertSame($ids[$slug], $row->id, $slug);
            $this->assertSame((new Nov16)->render($slug), $row->content, $slug);
            $this->assertSame(self::EXPECTED[$slug][1], $row->{$spec['read_column']}, $slug);
        }

        $after = $this->snapshot();
        $this->logged = [];
        $again = new Nov16;
        $again->run();
        $this->assertSame($after, $this->snapshot());
        $this->assertSame([], $this->warnings());
        foreach (array_keys(Nov16::PAGES) as $slug) {
            $key = $this->key($slug);
            $this->assertSame(GuardedFieldWriter::ALREADY, $again->results[$key], $key);
            $this->assertTrue($this->hasLog('info', Nov16::MIGRATION.' '.$key.': already current'), $key);
        }
    }

    public function test_a_drifted_row_is_skipped_and_left_byte_identical(): void
    {
        $slug = 'retatrutide-vs-semaglutide';
        $blog = Blog::where('slug', $slug)->firstOrFail();
        $blog->content = 'DRIFT-SENTINEL';
        $blog->saveQuietly();
        $before = $this->snapshot();

        $seeder = new Nov16;
        $seeder->run();

        $this->assertSame($before, $this->snapshot());
        $this->assertSame('DRIFT-SENTINEL', Blog::where('slug', $slug)->firstOrFail()->content);
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results[$this->key($slug)]);
        $this->assertTrue($this->hasLog('warning', Nov16::MIGRATION.' '.$this->key($slug).': '.GuardedFieldWriter::MISMATCH.' (content drift)'));
        foreach (array_keys(Nov16::PAGES) as $other) {
            if ($other === $slug) {
                continue;
            }
            $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results[$this->key($other)], $other);
        }
    }

    public function test_an_admin_edit_outside_content_skips_the_row(): void
    {
        $this->plantOld();
        $blog = $this->row('retatrutide-vs-semaglutide');
        $blog->forceFill(['seo_description' => 'ADMIN-SEO', 'status' => 'draft'])->saveQuietly();
        $guide = $this->row('compounded-vs-ruo-peptides');
        $guide->forceFill(['seo_page_title' => 'ADMIN-GUIDE-SEO'])->saveQuietly();
        $blogBefore = $blog->fresh()->getAttributes();
        $guideBefore = $guide->fresh()->getAttributes();

        $seeder = new Nov16;
        $seeder->run();

        $blogAfter = $blog->fresh()->getAttributes();
        $guideAfter = $guide->fresh()->getAttributes();
        unset($blogBefore['updated_at'], $blogAfter['updated_at'], $guideBefore['updated_at'], $guideAfter['updated_at']);
        $this->assertSame($blogBefore, $blogAfter);
        $this->assertSame($guideBefore, $guideAfter);
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results[$this->key('retatrutide-vs-semaglutide')]);
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results[$this->key('compounded-vs-ruo-peptides')]);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results[$this->key('retatrutide-side-effects-trial-data')]);
        $this->assertSame('21 Min Read', $this->row('retatrutide-side-effects-trial-data')->read_time);
    }

    public function test_missing_and_duplicate_slugs_are_warned_and_skipped(): void
    {
        EducationalGuide::where('slug', 'compounded-vs-ruo-peptides')->delete();
        $this->duplicate('educational_guides', 'peptide-legality-fda-ruo-compounding');
        $before = $this->snapshot();
        $dupes = EducationalGuide::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', ['peptide-legality-fda-ruo-compounding'])
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $dupes);
        $dupeBytes = $dupes->map(fn ($row) => $row->content)->all();

        $seeder = new Nov16;
        $seeder->run();

        $this->assertSame($before, $this->snapshot());
        $this->assertNull(EducationalGuide::where('slug', 'compounded-vs-ruo-peptides')->first());
        $afterDupes = EducationalGuide::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', ['peptide-legality-fda-ruo-compounding'])
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $afterDupes);
        $this->assertSame($dupeBytes, $afterDupes->map(fn ($row) => $row->content)->all());
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results['guide:compounded-vs-ruo-peptides.content']);
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results['guide:peptide-legality-fda-ruo-compounding.content']);
        $this->assertTrue($this->hasLog('warning', Nov16::MIGRATION.' guide:compounded-vs-ruo-peptides.content: '.GuardedFieldWriter::MISMATCH.' (missing row)'));
        $this->assertTrue($this->hasLog('warning', Nov16::MIGRATION.' guide:peptide-legality-fda-ruo-compounding.content: '.GuardedFieldWriter::MISMATCH.' (duplicate slugs)'));
    }

    public function test_unlisted_blogs_and_guides_are_untouched(): void
    {
        $blog = Blog::where('slug', 'tesamorelin-vs-sermorelin')->firstOrFail();
        $guide = EducationalGuide::where('slug', 'bacteriostatic-water-literacy')->firstOrFail();
        $blog->forceFill(['content' => 'SENTINEL-BLOG', 'read_time' => '99 Min Read'])->saveQuietly();
        $guide->forceFill(['content' => 'SENTINEL-GUIDE', 'reading_time' => '99 Min Read'])->saveQuietly();
        $listed = $this->snapshot();

        (new Nov16)->run();

        $this->assertSame('SENTINEL-BLOG', Blog::where('slug', 'tesamorelin-vs-sermorelin')->firstOrFail()->content);
        $this->assertSame('99 Min Read', Blog::where('slug', 'tesamorelin-vs-sermorelin')->firstOrFail()->read_time);
        $this->assertSame('SENTINEL-GUIDE', EducationalGuide::where('slug', 'bacteriostatic-water-literacy')->firstOrFail()->content);
        $this->assertSame('99 Min Read', EducationalGuide::where('slug', 'bacteriostatic-water-literacy')->firstOrFail()->reading_time);
        $this->assertSame($listed, $this->snapshot());
    }

    public function test_word_counts_and_read_times_match_the_16_nov_table(): void
    {
        $catalog = (new \ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null);
        foreach (self::EXPECTED as $slug => [$words, $readTime]) {
            $markdown = (string) file_get_contents(resource_path('content/educational/'.$slug.'.md'));
            $this->assertSame($words, $this->renderedWordCount($markdown), $slug);
            $this->assertSame($readTime, ((int) ceil($words / 230)).' Min Read', $slug);
            $entry = collect($catalog)->firstWhere('slug', $slug);
            $this->assertSame($readTime, $entry['read_time'], $slug);
            $row = $this->row($slug);
            $column = Nov16::PAGES[$slug]['read_column'];
            $this->assertSame($readTime, $row->{$column}, $slug);
        }
    }

    public function test_compounded_guide_faq_matches_the_publisher_and_json_ld(): void
    {
        $slug = 'compounded-vs-ruo-peptides';
        $answer = 'No. The Federal Register notice calls it a draft and says it is not binding. Its two-week comment window closed on 13 Nov 2026, and no final version had been published as of 16 Nov 2026 [2][10] [primary].';
        $markdown = (string) file_get_contents(resource_path('content/educational/'.$slug.'.md'));
        $this->assertStringContainsString("### Is the policy final?\n\n".$answer, $markdown);
        $this->assertStringContainsString("['Is the policy final?', '".$answer."'],", (string) file_get_contents(app_path('Content/EducationalContentPublisher.php')));

        $catalog = (new \ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null);
        $entry = collect($catalog)->firstWhere('slug', $slug);
        $entity = collect($entry['faq']['mainEntity'])->firstWhere('name', 'Is the policy final?');
        $this->assertSame($answer, $entity['acceptedAnswer']['text']);

        $guide = EducationalGuide::where('slug', $slug)->firstOrFail();
        $this->assertSame($entry['faq'], $guide->seo_schema[0] ?? null);
        $stored = collect($guide->seo_schema[0]['mainEntity'])->firstWhere('name', 'Is the policy final?');
        $this->assertSame($answer, $stored['acceptedAnswer']['text']);
    }

    public function test_rendered_pages_pass_the_content_gates(): void
    {
        $forbidden = [
            'Peptide Partners',
            'peptide-partners-llc',
            'best-performing',
            'HHS/FDA',
            'recalls-rappels.canada.ca/en/alert-recall/health-canada-warns-about-contaminated',
        ];
        foreach (array_keys(Nov16::PAGES) as $slug) {
            $path = (Nov16::PAGES[$slug]['table'] === 'educational_guides' ? '/guides/' : '/blog/').$slug;
            $html = html_entity_decode((string) $this->get($path)->assertOk()->getContent());
            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString($needle, $html, $slug.' '.$needle);
            }
        }

        $legality = html_entity_decode((string) $this->get('/guides/peptide-legality-fda-ruo-compounding')->assertOk()->getContent());
        $ghk = html_entity_decode((string) $this->get('/blog/bpc-157-vs-ghk-cu')->assertOk()->getContent());
        $this->assertStringContainsString('an online peptide seller', $legality);
        $this->assertStringContainsString('an online peptide seller', $ghk);
    }

    /**
     * @return array<string, array{html: string, read_time: string, sha256: string}>
     */
    private function fixture(): array
    {
        $decoded = json_decode((string) file_get_contents(base_path('tests/fixtures/nov16-main-renders.json')), true);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    private function plantOld(): void
    {
        $fixture = $this->fixture();
        foreach (Nov16::PAGES as $slug => $spec) {
            $row = $this->row($slug);
            $row->forceFill([
                'content' => $fixture[$slug]['html'],
                $spec['read_column'] => $spec['old_read_time'],
            ])->saveQuietly();
        }
    }

    private function row(string $slug): Blog|EducationalGuide
    {
        $model = Nov16::PAGES[$slug]['table'] === 'educational_guides' ? EducationalGuide::class : Blog::class;

        return $model::query()->where('slug', $slug)->firstOrFail();
    }

    private function key(string $slug): string
    {
        $prefix = Nov16::PAGES[$slug]['table'] === 'educational_guides' ? 'guide:' : 'blog:';

        return $prefix.$slug.'.content';
    }

    /**
     * @return array<string, int>
     */
    private function ids(): array
    {
        $ids = [];
        foreach (array_keys(Nov16::PAGES) as $slug) {
            $ids[$slug] = $this->row($slug)->id;
        }

        return $ids;
    }

    /**
     * @return array<string, string|null>
     */
    private function snapshot(): array
    {
        $out = [];
        foreach (Nov16::PAGES as $slug => $spec) {
            $model = $spec['table'] === 'educational_guides' ? EducationalGuide::class : Blog::class;
            $row = $model::query()->where('slug', $slug)->first();
            $out[$slug] = $row === null ? null : $row->content.'|'.$row->{$spec['read_column']};
        }

        return $out;
    }

    private function duplicate(string $table, string $slug): void
    {
        $row = (array) DB::table($table)->where('slug', $slug)->first();
        unset($row['id']);
        $row['slug'] = $slug.' ';
        DB::table($table)->insert($row);
    }

    private function renderedWordCount(string $markdown): int
    {
        $html = \App\Support\SimpleMarkdown::toHtml($markdown);
        $text = html_entity_decode(preg_replace('/<[^>]+>/', ' ', $html) ?? $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_match_all('/\S+/', $text);
    }

    private function hasLog(string $level, string $message): bool
    {
        foreach ($this->logged as $entry) {
            if ($entry['level'] === $level && $entry['message'] === $message) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function warnings(): array
    {
        return array_values(array_map(
            fn ($entry) => $entry['message'],
            array_filter($this->logged, fn ($entry) => $entry['level'] === 'warning')
        ));
    }
}
