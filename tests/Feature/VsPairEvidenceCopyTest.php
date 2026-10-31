<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Support\BpcTb500CompareCopy;
use App\Support\ElamipretideMotsCCompareFaqs;
use App\Support\EloralintideTirzepatideCompareCopy;
use App\Support\RetatrutideSemaglutideCompareCopy;
use App\Support\RetatrutideTirzepatideCompareCopy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VsPairEvidenceCopyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_copy_classes_only_match_their_own_slug(): void
    {
        $this->assertNotNull(RetatrutideTirzepatideCompareCopy::forSlug('retatrutide-vs-tirzepatide'));
        $this->assertNull(RetatrutideTirzepatideCompareCopy::forSlug('bpc-157-vs-tb-500'));
        $this->assertNotNull(BpcTb500CompareCopy::forSlug('bpc-157-vs-tb-500'));
        $this->assertNull(BpcTb500CompareCopy::forSlug('retatrutide-vs-tirzepatide'));
        $this->assertNull(ElamipretideMotsCCompareFaqs::forSlug('bpc-157-vs-tb-500'));
        $this->assertNotNull(EloralintideTirzepatideCompareCopy::forSlug('eloralintide-vs-tirzepatide'));
        $this->assertNull(EloralintideTirzepatideCompareCopy::forSlug('eloralintide-vs-petrelintide'));
        $this->assertNotNull(RetatrutideSemaglutideCompareCopy::forSlug('retatrutide-vs-semaglutide'));
        $this->assertNull(RetatrutideSemaglutideCompareCopy::forSlug('retatrutide-vs-tirzepatide'));
    }

    public function test_retatrutide_semaglutide_page_renders_the_transcend_note(): void
    {
        $copy = RetatrutideSemaglutideCompareCopy::forSlug('retatrutide-vs-semaglutide');
        $this->assertNotNull($copy);
        $this->assertSame(RetatrutideSemaglutideCompareCopy::HEADING, $copy['evidence']['title']);
        $this->assertSame([], $copy['evidence']['paragraphs']);
        $this->assertSame([RetatrutideSemaglutideCompareCopy::NOTE], $copy['evidence']['notes']);
        $this->assertSame([], $copy['faqs']);
        $this->assertNull($copy['link']);

        $note = $copy['evidence']['notes'][0];
        $visible = preg_replace('/\[([^\]]+)\]\((?:\/[^)\s]*|https?:\/\/[^)\s]+)\)/', '$1', $note);
        $this->assertIsString($visible);
        $words = preg_match_all('/\S+/', $visible);
        $this->assertGreaterThanOrEqual(60, $words);
        $this->assertLessThanOrEqual(90, $words);

        $this->assertStringContainsString('28 Oct 2026', $note);
        $this->assertStringContainsString('open-label Phase 3 trial versus once-weekly semaglutide (Ozempic)', $note);
        $this->assertStringContainsString('adults with type 2 diabetes on metformin', $note);
        $this->assertStringContainsString('met its primary endpoint (A1C noninferiority at 80 weeks) and all key secondary endpoints', $note);
        $this->assertStringContainsString('[company-reported]', $note);
        $this->assertStringContainsString('It is not peer-reviewed.', $note);
        $this->assertStringContainsString('Retatrutide is investigational and not approved.', $note);
        $this->assertStringContainsString('research-use-only peptide comparison publisher, not a clinic', $note);
        $this->assertStringContainsString('/blog/retatrutide-vs-semaglutide', $note);
        $this->assertStringContainsString(RetatrutideSemaglutideCompareCopy::LILLY_URL, $note);
        $this->assertSame(
            'Eli Lilly and Company (company-reported). TRANSCEND-T2D-2 topline press release, 28 October 2026.',
            $copy['evidence']['sources'][0]['title']
        );

        $prose = preg_replace('#https?://\S+#', '', $note.' '.$copy['evidence']['title'].' '.$copy['evidence']['sources'][0]['title']);
        $this->assertIsString($prose);
        $this->assertDoesNotMatchRegularExpression('/\bgreater\b|\bsuperior\b|\bwinner\b|outperformed|works better|more than double/i', $prose);
        $this->assertDoesNotMatchRegularExpression('/\d+(?:\.\d+)?\s*mg\b/i', $note);
        $this->assertDoesNotMatchRegularExpression('/\d+(?:\.\d+)?\s*%/', $note);
        $this->assertStringNotContainsString('Ozempic 2', $note);

        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Semaglutide', 'slug' => 'semaglutide', 'is_active' => true]);

        $this->get('/compare/retatrutide-vs-semaglutide')
            ->assertOk()
            ->assertSee('Head-to-head trial: TRANSCEND-T2D-2 (company-reported)', false)
            ->assertSee('open-label Phase 3 trial versus once-weekly semaglutide (Ozempic)', false)
            ->assertSee('It is not peer-reviewed.', false)
            ->assertSee('Retatrutide is investigational and not approved.', false)
            ->assertSee('blog\\/retatrutide-vs-semaglutide', false)
            ->assertSee('lillys-retatrutide-delivered-greater-a1c-reduction-and-more', false)
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/CompareCompoundVs')
                ->where('evidence.title', RetatrutideSemaglutideCompareCopy::HEADING)
                ->where('evidence.notes.0', RetatrutideSemaglutideCompareCopy::NOTE)
                ->has('faqs', 0));
    }

    public function test_eloralintide_tirzepatide_page_shows_the_dated_company_reported_note(): void
    {
        $copy = EloralintideTirzepatideCompareCopy::forSlug('eloralintide-vs-tirzepatide');
        $note = $copy['evidence']['title']."\n".implode("\n", $copy['evidence']['paragraphs']);

        $this->assertSame('As of 22 October 2026', $copy['evidence']['title']);
        $this->assertStringContainsString('company-reported', $note);
        $this->assertStringContainsString('sponsor press release', $note);
        $this->assertStringContainsString('no peer-reviewed paper', $note);
        $this->assertStringContainsString('not repeated on this compare page', $note);
        $this->assertDoesNotMatchRegularExpression('/\d+(\.\d+)?\s*%/u', $note);
        $this->assertDoesNotMatchRegularExpression('/\d+\s*mg\b/i', $note);
        $this->assertDoesNotMatchRegularExpression('/\bwinner\b/i', $note);
        $this->assertSame([], $copy['faqs']);

        $sourceTitle = $copy['evidence']['sources'][0]['title'];
        $this->assertStringContainsString('tirzepatide 15 mg', $sourceTitle);
        $this->assertSame(
            'https://investor.lilly.com/news-releases/news-release-details/lillys-eloratzp-combination-eloralintide-and-tirzepatide',
            $copy['evidence']['sources'][0]['url']
        );

        ProductCategory::create(['name' => 'Eloralintide', 'slug' => 'eloralintide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);

        $this->get('/compare/eloralintide-vs-tirzepatide')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/CompareCompoundVs')
                ->where('evidence.title', 'As of 22 October 2026')
                ->where('evidence.paragraphs.0', $copy['evidence']['paragraphs'][0])
                ->where('evidence.paragraphs.1', $copy['evidence']['paragraphs'][1])
                ->where('evidence.paragraphs.2', $copy['evidence']['paragraphs'][2])
                ->has('faqs', 0));
    }

    public function test_retatrutide_vs_page_stays_neutral_with_no_trial_figures(): void
    {
        $copy = RetatrutideTirzepatideCompareCopy::forSlug('retatrutide-vs-tirzepatide');
        $this->assertNotNull($copy);

        $triumph = $copy['evidence']['title']."\n"
            .implode("\n", $copy['evidence']['paragraphs'])."\n"
            .implode("\n", $copy['evidence']['notes']);
        $this->assertStringNotContainsStringIgnoringCase('efficacy estimand', $triumph);
        $this->assertNoMgOrPerArmPercent($triumph, 'triumph block');
        $this->assertSame('/encyclopedia/retatrutide', $copy['link']['href']);
        $this->assertSame(
            'Obstructive sleep apnea (OSA): what the trials report',
            $copy['evidence']['sections'][0]['title']
        );
        $this->assertCount(11, $copy['evidence']['sources']);
        $this->assertCount(2, $copy['faqs']);

        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);

        $response = $this->get('/compare/retatrutide-vs-tirzepatide')->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringNotContainsString('/blog/retatrutide-vs-tirzepatide', $html);

        $rendered = $this->renderedEvidenceSection($response);
        $this->assertStringContainsString('TRIUMPH-2: retatrutide vs placebo', $rendered);
        $this->assertStringContainsString('Obstructive sleep apnea (OSA): what the trials report', $rendered);
        $this->assertCompareEditorialOmitsDoseStrings($response, $copy);

        $response->assertInertia(fn ($page) => $page
            ->component('Frontend/CompareCompoundVs')
            ->where('evidence.title', 'TRIUMPH-2: retatrutide vs placebo')
            ->where('evidence.sections.0.title', 'Obstructive sleep apnea (OSA): what the trials report')
            ->where('evidenceLink.href', '/encyclopedia/retatrutide')
            ->has('faqs', 2)
            ->where('faqs.0.q', $copy['faqs'][0]['q'])
            ->where('faqs.0.a', $copy['faqs'][0]['a'])
            ->where('faqs.1.q', $copy['faqs'][1]['q'])
            ->where('faqs.1.a', $copy['faqs'][1]['a'])
            ->where('seo.schema.1.@type', 'FAQPage')
            ->where('seo.schema.1.mainEntity.0.name', $copy['faqs'][0]['q'])
            ->where('seo.schema.1.mainEntity.0.acceptedAnswer.text', $copy['faqs'][0]['a'])
            ->where('seo.schema.1.mainEntity.1.name', $copy['faqs'][1]['q'])
            ->where('seo.schema.1.mainEntity.1.acceptedAnswer.text', $copy['faqs'][1]['a']));
    }

    public function test_bpc_tb500_block_grades_evidence_and_has_no_dosing(): void
    {
        $copy = BpcTb500CompareCopy::forSlug('bpc-157-vs-tb-500');
        $blob = (string) json_encode($copy, JSON_UNESCAPED_UNICODE);

        $this->assertCount(4, $copy['evidence']['grades']);
        $this->assertStringContainsString('Ac-LKKTETQ', $blob);
        $this->assertStringContainsString('not FDA approval', $blob);
        $this->assertNull($copy['link']);
        $this->assertDoesNotMatchRegularExpression('/\d+(\.\d+)?\s?(mg|mcg|µg|iu)\b|verified|condor|per (day|week)/i', $blob);
        $this->assertSame('What is the “Wolverine stack”?', $copy['faqs'][0]['q']);
        $note = $copy['evidence']['notes'][2];
        $this->assertStringContainsString('Regulatory note (Oct 2026).', $note);
        $this->assertStringContainsString('/guides/compounded-vs-ruo-peptides', $note);
        $this->assertStringContainsString('/blog/hhs-503a-peptide-enforcement-discretion-2026', $note);
        $this->assertStringNotContainsString('HHS/FDA', $note);
        $this->assertDoesNotMatchRegularExpression('/\d+(?:\.\d+)?\s*mg\b/i', $note);
        $this->assertDoesNotMatchRegularExpression('/%/', $note);
    }

    public function test_bpc_tb500_vs_page_renders_evidence_and_matching_faq_schema(): void
    {
        $copy = BpcTb500CompareCopy::forSlug('bpc-157-vs-tb-500');

        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'bpc-157', 'is_active' => true]);
        ProductCategory::create(['name' => 'TB-500', 'slug' => 'tb-500', 'is_active' => true]);

        $this->get('/compare/bpc-157-vs-tb-500')
            ->assertOk()
            ->assertSee('Human RCTs: none', false)
            ->assertSee('Ac-LKKTETQ', false)
            ->assertSee('Regulatory note (Oct 2026).', false)
            ->assertSee('guides\\/compounded-vs-ruo-peptides', false)
            ->assertSee('blog\\/hhs-503a-peptide-enforcement-discretion-2026', false)
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/CompareCompoundVs')
                ->where('evidence.title', 'Evidence at a glance')
                ->where('evidenceLink', null)
                ->has('faqs', 1)
                ->where('seo.schema.1.@type', 'FAQPage')
                ->where('seo.schema.1.mainEntity.0.name', $copy['faqs'][0]['q'])
                ->where('seo.schema.1.mainEntity.0.acceptedAnswer.text', $copy['faqs'][0]['a']));
    }

    /**
     * Body above the numbered Sources, the FAQ, and the FAQPage JSON-LD
     * on /compare/retatrutide-vs-tirzepatide. Listing tables are outside
     * this check. Milligram dose strings and per-arm percentages are not.
     *
     * @param  array{evidence: array<string, mixed>, link: array<string, string>|null, faqs: list<array{q: string, a: string}>}  $copy
     */
    private function assertCompareEditorialOmitsDoseStrings(\Illuminate\Testing\TestResponse $response, array $copy): void
    {
        $props = $response->inertiaPage()['props'];
        $body = $this->evidenceBodyAboveSources(
            is_array($props['evidence'] ?? null) ? $props['evidence'] : [],
            is_array($props['evidenceLink'] ?? null) ? $props['evidenceLink'] : null
        );
        $this->assertStringContainsString('apnea-hypopnea index', $body);
        $this->assertStringContainsString('[4][5]', $body);
        $this->assertStringContainsString('[11]', $body);
        $this->assertStringNotContainsString('Grunstein RR', $body);
        $this->assertStringNotContainsString('NEJMoa2611477', $body);

        $faq = implode("\n", array_map(
            fn (array $row) => $row['q']."\n".$row['a'],
            $props['faqs'] ?? []
        ));
        $faqSchema = null;
        foreach ($props['seo']['schema'] ?? [] as $block) {
            if (is_array($block) && ($block['@type'] ?? null) === 'FAQPage') {
                $faqSchema = $block;
            }
        }
        $this->assertIsArray($faqSchema);
        $jsonLd = (string) json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertNoMgOrPerArmPercent($body, 'body above Sources');
        $this->assertNoMgOrPerArmPercent($faq, 'FAQ');
        $this->assertNoMgOrPerArmPercent($jsonLd, 'JSON-LD');

        foreach ($copy['faqs'] as $i => $row) {
            $this->assertSame($row['q'], $props['faqs'][$i]['q']);
            $this->assertSame($row['a'], $props['faqs'][$i]['a']);
            $this->assertSame($row['q'], $faqSchema['mainEntity'][$i]['name']);
            $this->assertSame($row['a'], $faqSchema['mainEntity'][$i]['acceptedAnswer']['text']);
        }

        $htmlFaq = $this->faqPageFromHtml((string) $response->getContent());
        $this->assertSame($faqSchema['mainEntity'], $htmlFaq['mainEntity']);
        $this->assertNoMgOrPerArmPercent(
            (string) json_encode($htmlFaq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'rendered JSON-LD'
        );
    }

    /**
     * @param  array<string, mixed>  $evidence
     * @param  array<string, string>|null  $link
     */
    private function evidenceBodyAboveSources(array $evidence, ?array $link): string
    {
        $chunks = [
            (string) ($evidence['title'] ?? ''),
            ...array_map('strval', $evidence['paragraphs'] ?? []),
            ...array_map('strval', $evidence['notes'] ?? []),
        ];
        foreach ($evidence['sections'] ?? [] as $section) {
            $chunks[] = (string) ($section['title'] ?? '');
            foreach ($section['paragraphs'] ?? [] as $paragraph) {
                if (is_string($paragraph)) {
                    $chunks[] = $paragraph;
                    continue;
                }
                $chunks[] = trim((string) ($paragraph['lead'] ?? '').' '.(string) ($paragraph['text'] ?? ''));
            }
        }
        if (is_array($link)) {
            $chunks[] = (string) ($link['before'] ?? '').(string) ($link['text'] ?? '').(string) ($link['after'] ?? '');
        }

        return implode("\n", $chunks);
    }

    private function assertNoMgOrPerArmPercent(string $text, string $label = ''): void
    {
        $this->assertDoesNotMatchRegularExpression('/\b\d+(?:\.\d+)?\s*mg\b/i', $text, $label);
        $this->assertDoesNotMatchRegularExpression('/\d+(?:\.\d+)?\s*%/u', $text, $label);
    }

    /**
     * @return array<string, mixed>
     */
    private function faqPageFromHtml(string $html): array
    {
        preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches);
        foreach ($matches[1] as $json) {
            $data = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (is_array($data) && ($data['@type'] ?? null) === 'FAQPage') {
                return $data;
            }
        }

        $this->fail('FAQPage JSON-LD was not rendered');
    }

    private function renderedEvidenceSection(\Illuminate\Testing\TestResponse $response): string
    {
        $props = $response->inertiaPage()['props'];

        return (string) json_encode([
            'evidence' => $props['evidence'] ?? null,
            'evidenceLink' => $props['evidenceLink'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
