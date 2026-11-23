<?php

namespace Tests\Feature;

use App\Content\EducationalContentPublisher;
use App\Models\EducationalGuide;
use App\Support\GuardedFieldWriter;
use Database\Seeders\EducationalFreshnessNovember16Seeder as Nov16;
use Database\Seeders\EducationalStateEnforcementNovember23Seeder as Nov23;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 2026_11_23_200000 state-enforcement update of two guides. The fixture
 * under tests/fixtures holds the 6fd0905a publisher renders (equal to the
 * live content read on 23 Nov 2026), used only as the guard's old values.
 */
class EducationalStateEnforcementNovember23Test extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_11_23_200000_guides_state_enforcement_november_23.php';

    private const LEGALITY = 'peptide-legality-fda-ruo-compounding';

    private const COMPOUNDED = 'compounded-vs-ruo-peptides';

    /** @var array<string, array{0: int, 1: string}> */
    private const EXPECTED = [
        self::LEGALITY => [7040, '31 Min Read'],
        self::COMPOUNDED => [3379, '15 Min Read'],
    ];

    private const FAQ_ANSWER = 'No. The Federal Register notice calls it a draft and says it is not binding. Its two-week comment window closed on 13 Nov 2026, and no final version had been published as of 23 Nov 2026 [2][10] [primary].';

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
        // 200000 is this update's slot; 210000 is reserved for the CBL-514
        // blog draft and 1xxxxx for Encyclopedia Author.
        $files = glob(database_path('migrations/2026_11_23_20*.php')) ?: [];
        $this->assertSame([self::MIGRATION], array_map('basename', $files));
        $this->assertFileExists(database_path('migrations/'.self::MIGRATION));
        $source = (string) file_get_contents(database_path('migrations/'.self::MIGRATION));
        $this->assertStringContainsString('EducationalStateEnforcementNovember23Seeder', $source);
        $this->assertStringContainsString('Previous wording is not restored', $source);
        $this->assertSame('2026_11_23_200000', Nov23::MIGRATION);
        $this->assertSame(array_keys(self::EXPECTED), array_keys(Nov23::PAGES));

        $fixture = $this->fixture();
        foreach (Nov23::PAGES as $slug => $spec) {
            $this->assertSame('educational_guides', $spec['table'], $slug);
            $this->assertSame($spec['old_sha256'], hash('sha256', $fixture[$slug]['html']), $slug);
            $this->assertSame($spec['old_sha256'], $fixture[$slug]['sha256'], $slug);
            $this->assertSame($spec['old_read_time'], $fixture[$slug]['read_time'], $slug);
            $this->assertNotSame($spec['old_sha256'], hash('sha256', (new Nov23)->render($slug)), $slug);
        }
    }

    public function test_old_value_rows_are_updated_and_a_second_run_writes_nothing(): void
    {
        $this->plantOld();
        $ids = $this->ids();

        $seeder = new Nov23;
        $seeder->run();

        $this->assertSame([], $this->warnings());
        $catalog = $this->catalog();
        foreach (array_keys(Nov23::PAGES) as $slug) {
            $key = $this->key($slug);
            $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results[$key], $key);
            $this->assertTrue($this->hasLog('info', Nov23::MIGRATION.' '.$key.': updated'), $key);
            $row = $this->row($slug);
            $this->assertSame($ids[$slug], $row->id, $slug);
            $this->assertSame((new Nov23)->render($slug), $row->content, $slug);
            $this->assertSame(self::EXPECTED[$slug][1], $row->reading_time, $slug);
            $this->assertSame(collect($catalog)->firstWhere('slug', $slug)['faq'], $row->seo_schema[0] ?? null, $slug);
        }

        $after = $this->snapshot();
        $this->logged = [];
        $again = new Nov23;
        $again->run();
        $this->assertSame($after, $this->snapshot());
        $this->assertSame([], $this->warnings());
        foreach (array_keys(Nov23::PAGES) as $slug) {
            $key = $this->key($slug);
            $this->assertSame(GuardedFieldWriter::ALREADY, $again->results[$key], $key);
            $this->assertTrue($this->hasLog('info', Nov23::MIGRATION.' '.$key.': already current'), $key);
        }
    }

    public function test_after_the_16_nov_migration_the_23_nov_migration_is_a_no_op(): void
    {
        // A database that has not yet run 2026_11_16_200000: the 16 Nov
        // seeder publishes the current sources, so 23 Nov finds them current.
        $nov16 = json_decode((string) file_get_contents(base_path('tests/fixtures/nov16-main-renders.json')), true);
        foreach (array_keys(Nov23::PAGES) as $slug) {
            $this->row($slug)->forceFill([
                'content' => $nov16[$slug]['html'],
                'reading_time' => Nov16::PAGES[$slug]['old_read_time'],
            ])->saveQuietly();
        }
        (new Nov16)->run();
        $before = $this->snapshot();

        $seeder = new Nov23;
        $seeder->run();

        $this->assertSame($before, $this->snapshot());
        foreach (array_keys(Nov23::PAGES) as $slug) {
            $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results[$this->key($slug)], $slug);
            $this->assertSame(self::EXPECTED[$slug][1], $this->row($slug)->reading_time, $slug);
        }
    }

    public function test_a_drifted_row_is_skipped_and_left_byte_identical(): void
    {
        $this->plantOld();
        $guide = $this->row(self::LEGALITY);
        $guide->forceFill(['content' => $guide->content.'<p>ADMIN-EDIT</p>'])->saveQuietly();
        $before = $this->row(self::LEGALITY)->getAttributes();

        $seeder = new Nov23;
        $seeder->run();

        $after = $this->row(self::LEGALITY)->getAttributes();
        unset($before['updated_at'], $after['updated_at']);
        $this->assertSame($before, $after);
        $this->assertSame('25 Min Read', $after['reading_time']);
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results[$this->key(self::LEGALITY)]);
        $this->assertTrue($this->hasLog('warning', Nov23::MIGRATION.' '.$this->key(self::LEGALITY).': '.GuardedFieldWriter::MISMATCH.' (content drift)'));
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results[$this->key(self::COMPOUNDED)]);
    }

    public function test_an_admin_edit_outside_content_skips_the_row(): void
    {
        $this->plantOld();
        $this->row(self::LEGALITY)->forceFill(['seo_page_title' => 'ADMIN-SEO'])->saveQuietly();
        $this->row(self::COMPOUNDED)->forceFill(['is_featured' => true])->saveQuietly();
        $legalBefore = $this->row(self::LEGALITY)->getAttributes();
        $compBefore = $this->row(self::COMPOUNDED)->getAttributes();

        $seeder = new Nov23;
        $seeder->run();

        $legalAfter = $this->row(self::LEGALITY)->getAttributes();
        $compAfter = $this->row(self::COMPOUNDED)->getAttributes();
        unset($legalBefore['updated_at'], $legalAfter['updated_at'], $compBefore['updated_at'], $compAfter['updated_at']);
        $this->assertSame($legalBefore, $legalAfter);
        $this->assertSame($compBefore, $compAfter);
        $this->assertTrue($this->hasLog('warning', Nov23::MIGRATION.' '.$this->key(self::LEGALITY).': '.GuardedFieldWriter::MISMATCH.' (column drift: seo_page_title)'));
        $this->assertTrue($this->hasLog('warning', Nov23::MIGRATION.' '.$this->key(self::COMPOUNDED).': '.GuardedFieldWriter::MISMATCH.' (column drift: is_featured)'));
    }

    public function test_missing_and_duplicate_slugs_are_warned_and_skipped(): void
    {
        $this->plantOld();
        EducationalGuide::where('slug', self::COMPOUNDED)->delete();
        $this->duplicate('educational_guides', self::LEGALITY);
        $before = DB::table('educational_guides')->orderBy('id')->get(['id', 'slug', 'content', 'reading_time'])->toArray();

        $seeder = new Nov23;
        $seeder->run();

        $this->assertEquals($before, DB::table('educational_guides')->orderBy('id')->get(['id', 'slug', 'content', 'reading_time'])->toArray());
        $this->assertTrue($this->hasLog('warning', Nov23::MIGRATION.' guide:'.self::COMPOUNDED.'.content: '.GuardedFieldWriter::MISMATCH.' (missing row)'));
        $this->assertTrue($this->hasLog('warning', Nov23::MIGRATION.' guide:'.self::LEGALITY.'.content: '.GuardedFieldWriter::MISMATCH.' (duplicate slugs)'));
    }

    public function test_unlisted_rows_and_encyclopedia_entries_are_untouched(): void
    {
        $this->plantOld();
        $guides = DB::table('educational_guides')->whereNotIn('slug', array_keys(Nov23::PAGES))->orderBy('id')->get()->toArray();
        $blogs = DB::table('blogs')->orderBy('id')->get()->toArray();
        $posts = DB::table('education_posts')->orderBy('id')->get()->toArray();

        (new Nov23)->run();

        $this->assertEquals($guides, DB::table('educational_guides')->whereNotIn('slug', array_keys(Nov23::PAGES))->orderBy('id')->get()->toArray());
        $this->assertEquals($blogs, DB::table('blogs')->orderBy('id')->get()->toArray());
        $this->assertEquals($posts, DB::table('education_posts')->orderBy('id')->get()->toArray());
        $seederSource = (string) file_get_contents(database_path('seeders/EducationalStateEnforcementNovember23Seeder.php'));
        $this->assertStringNotContainsString('education_posts', $seederSource);
    }

    public function test_word_counts_and_read_times_match_the_23_nov_table(): void
    {
        $catalog = $this->catalog();
        foreach (self::EXPECTED as $slug => [$words, $readTime]) {
            $markdown = (string) file_get_contents(resource_path('content/educational/'.$slug.'.md'));
            $this->assertSame($words, $this->renderedWordCount($markdown), $slug);
            $this->assertSame($readTime, ((int) ceil($words / 230)).' Min Read', $slug);
            $this->assertSame($readTime, collect($catalog)->firstWhere('slug', $slug)['read_time'], $slug);
            $this->assertSame($readTime, $this->row($slug)->reading_time, $slug);
        }
    }

    public function test_faqs_match_the_publisher_and_json_ld(): void
    {
        $catalog = $this->catalog();
        foreach (array_keys(Nov23::PAGES) as $slug) {
            $markdown = (string) file_get_contents(resource_path('content/educational/'.$slug.'.md'));
            $entry = collect($catalog)->firstWhere('slug', $slug);
            $faq = substr($markdown, strpos($markdown, "\n## FAQ"));
            $faq = substr($faq, 0, strpos($faq, "\n## Sources"));
            preg_match_all('/^### (.+)\n\n(.+)$/m', $faq, $m, PREG_SET_ORDER);
            $pairs = array_map(fn ($x) => [$x[1], $x[2]], $m);
            $schema = array_map(fn ($q) => [$q['name'], $q['acceptedAnswer']['text']], $entry['faq']['mainEntity']);
            $this->assertSame($schema, $pairs, $slug);
            $this->assertSame($entry['faq'], $this->row($slug)->seo_schema[0] ?? null, $slug);
        }
        $markdown = (string) file_get_contents(resource_path('content/educational/'.self::COMPOUNDED.'.md'));
        $this->assertStringContainsString("### Is the policy final?\n\n".self::FAQ_ANSWER, $markdown);
        $this->assertStringContainsString("['Is the policy final?', '".self::FAQ_ANSWER."'],", (string) file_get_contents(app_path('Content/EducationalContentPublisher.php')));
        $this->assertStringNotContainsString('as of 16 Nov 2026', $markdown);
    }

    public function test_legality_guide_state_and_australia_sections(): void
    {
        $markdown = (string) file_get_contents(resource_path('content/educational/'.self::LEGALITY.'.md'));
        $html = html_entity_decode((string) $this->get('/guides/'.self::LEGALITY)->assertOk()->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertSame(1, substr_count($markdown, "\n## State actions on the draft HHS policy (as of 23 November 2026)\n"));
        $this->assertSame(1, substr_count($markdown, "\n## Outside the U.S.: Australia (as of 23 November 2026)\n"));
        // Section order: state actions sit before the October reports; Australia follows Health Canada.
        $this->assertLessThan(strpos($markdown, '## Enforcement-related reports'), strpos($markdown, '## State actions on the draft HHS policy'));
        $this->assertLessThan(strpos($markdown, '## Outside the U.S.: Australia'), strpos($markdown, '## Outside the U.S.: Health Canada'));
        $this->assertLessThan(strpos($markdown, '## WADA / sport'), strpos($markdown, '## Outside the U.S.: Australia'));

        foreach ([
            '“voted to enforce 201 KAR 2:076 as written and to decline to grant waivers for compounding with the peptide bulk drug substances covered by the federal HHS enforcement discretion policy.”',
            '“the federal enforcement policy does not supersede Kentucky law and does not authorize compounding these substances in or into Kentucky.”',
            '“includes preparations compounded by nonresident pharmacies permitted by the Board.”',
            '“at this time, the Board will not consider or grant requests to waive”',
            'The Federal Register notice itself describes it as a draft.',
            '“will exercise similar limited enforcement discretion in Utah, in strict accordance with the scope of this HHS interim policy,”',
            'These predate the HHS notice and do not mention it.',
            'Disclaimers that a product is for ‘research use only’ or ‘not for human use’ do not change whether a product is regulated as a therapeutic good.',
            '“1,180 presentations to NSW emergency departments associated with peptide use between 1 July 2025 and 30 September 2026,”',
            'As of **23 November 2026**, no final or revised version had been published',
            '### Pending vs in force (as of 29 October 2026; HHS notice row updated 23 November 2026)',
            'Not finalized as of 23 November 2026.',
            '8. **State boards answer separately.**',
        ] as $needle) {
            $this->assertSame(1, substr_count($markdown, $needle), $needle);
        }
        $this->assertStringNotContainsString('as of 16 November 2026', $markdown);
        $this->assertStringNotContainsString('Not finalized as of 16 November 2026', $markdown);

        foreach (['7n.', '7o.', '7p.', '7q.', '7r.', '7s.', '7t.', '7u.', '7v.'] as $ref) {
            $this->assertSame(1, preg_match_all('/^'.preg_quote($ref, '/').' /m', $markdown), $ref);
        }
        foreach ([
            'https://pharmacy.ky.gov/Documents/Frankfort%2C%20KY.%2011.19.26%20Compliance%20Alert',
            'https://commerce.utah.gov/2026/10/30/peptide-enforcement-discretion-policy/',
            'https://www.mbp.ms.gov/sites/default/files/inline-images/1_Joint_Statement_SIGNED_Accessible.pdf',
            'https://www.tga.gov.au/products/medicines/monitoring-and-compliance/how-we-regulate-therapeutic-peptide-products',
            'https://www.nsw.gov.au/ministerial-releases/nsw-health-warns-of-surge-hospital-presentations-linked-to-peptide-use',
            'https://www.health.vic.gov.au/health-alerts/toxicity-linked-to-unapproved-peptide-retatrutide',
        ] as $url) {
            $this->assertStringContainsString(htmlspecialchars($url, ENT_QUOTES), (string) $this->row(self::LEGALITY)->content, $url);
        }
        $this->assertStringContainsString('State actions on the draft HHS policy (as of 23 November 2026)', $html);
        $this->assertStringContainsString('31 Min Read', $html);
    }

    public function test_compounded_guide_kentucky_and_stakeholder_lines(): void
    {
        $markdown = (string) file_get_contents(resource_path('content/educational/'.self::COMPOUNDED.'.md'));
        $html = html_entity_decode((string) $this->get('/guides/'.self::COMPOUNDED)->assertOk()->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $stateNote = $this->section($markdown, '## State-law note');
        $this->assertStringContainsString('On 19 Nov 2026 the Kentucky Board of Pharmacy issued a compliance alert recording a board vote of 18 Nov 2026', $stateNote);
        $this->assertStringContainsString('"Never use a substance labeled \'research use only\' or \'not for human use,\'"', $stateNote);
        $this->assertStringContainsString('[19] [primary, state board]', $stateNote);
        $this->assertStringContainsString('[20] [primary, state agency]', $stateNote);

        $stakeholders = $this->section($markdown, '## Stakeholder statements after publication');
        $this->assertStringContainsString('On 18 Nov 2026 ASHP said that it and the American College of Clinical Pharmacy (ACCP) sent a letter to HHS', $stakeholders);
        $this->assertStringContainsString('ASHP\'s release calls it "FDA\'s interim policy". The Federal Register notice was issued by the HHS Office of the Secretary', $stakeholders);
        $this->assertStringContainsString('These are Public Citizen\'s findings and arguments, not FDA or HHS findings.', $stakeholders);
        $this->assertStringContainsString('[18] [advocacy report]', $stakeholders);
        $this->assertLessThan(strpos($stakeholders, 'ASHP'), strpos($stakeholders, 'American Medical Association'));

        // Sources run 1..20 with no gaps, and 17-20 are each cited in the body.
        $sources = $this->section($markdown, '## Sources');
        preg_match_all('/^(\d+)\. /m', $sources, $m);
        $this->assertSame(range(1, 20), array_map('intval', $m[1]));
        $body = substr($markdown, 0, strpos($markdown, "\n## Sources"));
        foreach ([17, 18, 19, 20] as $n) {
            $this->assertStringContainsString('['.$n.']', $body, (string) $n);
        }
        $stored = (string) $this->row(self::COMPOUNDED)->content;
        $this->assertStringContainsString('https://news.ashp.org/news/ashp-news/2026/11/18/ashp-and-accp-call-for-enforcement-of-hhs-interim-policy-on-peptides', $stored);
        // The report links seller sites and product pages, so it is cited unlinked.
        $this->assertStringNotContainsString('citizen.org', $stored);
        $this->assertStringContainsString('19 Nov 2026 (not linked). [advocacy report]', $stored);
        $this->assertStringContainsString('Kentucky Board of Pharmacy', $html);
        $this->assertStringContainsString('15 Min Read', $html);
    }

    public function test_rendered_pages_pass_the_content_gates(): void
    {
        $forbidden = [
            // Issuer wording and Nov 20 strict-RUO wording.
            'HHS/FDA', 'grey market', 'gray market', 'grey-market', 'gray-market',
            // Firms, people and testers named in the Public Citizen report.
            'Tailor Made', 'Paradigm Peptides', 'pinnedaminos', 'GenoGenix', 'Nuscience', 'Pink Pony', 'Mile High',
            'Prime Sciences', 'USAPeptide', 'Olympia', 'OPS International', 'Ways2Well', 'Buhler', 'Brecka',
            'Finnrick', 'Janoshik', 'PeptideMeter', 'TRM Labs',
            // Claims not confirmed from a primary source on 23 Nov 2026.
            'parliamentary inquiry', 'Mark Butler', '4 Nov 2026', '4 November 2026',
            // Secondary sources kept out of the copy.
            'abc.net.au', 'natlawreview', 'a4pc.org', 'peptidebureau', 'vialaudit',
            // Comparative and publisher-voice gates.
            'superior', 'best-performing', '/workspace/',
        ];
        foreach (array_keys(Nov23::PAGES) as $slug) {
            $this->get('/guides/'.$slug)->assertOk();
            $html = html_entity_decode((string) $this->row($slug)->content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $this->assertStringContainsString('As of 23 ', $html, $slug);
            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsStringIgnoringCase($needle, $html, $slug.' '.$needle);
            }
            // Product codes from the Victorian alert.
            $this->assertDoesNotMatchRegularExpression('/\bR-(10|20)\b/', $html, $slug);
            $this->assertDoesNotMatchRegularExpression('/\d\s?mg\b/i', strip_tags((string) $this->row($slug)->content), $slug);
        }
    }

    /**
     * @return array<string, array{html: string, read_time: string, sha256: string}>
     */
    private function fixture(): array
    {
        $decoded = json_decode((string) file_get_contents(base_path('tests/fixtures/nov23-main-renders.json')), true);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    private function plantOld(): void
    {
        $fixture = $this->fixture();
        foreach (Nov23::PAGES as $slug => $spec) {
            $this->row($slug)->forceFill([
                'content' => $fixture[$slug]['html'],
                'reading_time' => $spec['old_read_time'],
            ])->saveQuietly();
        }
    }

    private function section(string $markdown, string $heading): string
    {
        $start = strpos($markdown, "\n".$heading."\n");
        $this->assertNotFalse($start, $heading);
        $rest = substr($markdown, $start + strlen($heading) + 2);
        $end = preg_match('/^## /m', $rest, $m, PREG_OFFSET_CAPTURE) ? $m[0][1] : strlen($rest);

        return substr($rest, 0, $end);
    }

    /** @return list<array<string, mixed>> */
    private function catalog(): array
    {
        return (new \ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null);
    }

    private function row(string $slug): EducationalGuide
    {
        return EducationalGuide::query()->where('slug', $slug)->firstOrFail();
    }

    private function key(string $slug): string
    {
        return 'guide:'.$slug.'.content';
    }

    /** @return array<string, int> */
    private function ids(): array
    {
        $ids = [];
        foreach (array_keys(Nov23::PAGES) as $slug) {
            $ids[$slug] = $this->row($slug)->id;
        }

        return $ids;
    }

    /** @return array<string, string|null> */
    private function snapshot(): array
    {
        $out = [];
        foreach (array_keys(Nov23::PAGES) as $slug) {
            $row = EducationalGuide::query()->where('slug', $slug)->first();
            $out[$slug] = $row === null ? null : $row->content.'|'.$row->reading_time.'|'.json_encode($row->seo_schema);
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

    /** @return list<string> */
    private function warnings(): array
    {
        return array_values(array_map(
            fn ($entry) => $entry['message'],
            array_filter($this->logged, fn ($entry) => $entry['level'] === 'warning')
        ));
    }
}
