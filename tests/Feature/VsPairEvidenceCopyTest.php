<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Support\BpcTb500CompareCopy;
use App\Support\ElamipretideMotsCCompareFaqs;
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
    }

    public function test_retatrutide_vs_page_stays_neutral_with_no_trial_figures(): void
    {
        $copy = RetatrutideTirzepatideCompareCopy::forSlug('retatrutide-vs-tirzepatide');
        $blob = (string) json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // House rule #31: no per-arm results, estimands or mg figures in the
        // evidence block. Vendor product names live outside this copy.
        $this->assertNeutralTrialCopy($blob);
        $this->assertSame([], $copy['faqs']);
        $this->assertSame('/encyclopedia/retatrutide', $copy['link']['href']);

        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);

        $response = $this->get('/compare/retatrutide-vs-tirzepatide')->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringNotContainsStringIgnoringCase('efficacy estimand', $html);
        $this->assertStringNotContainsString('/blog/retatrutide-vs-tirzepatide', $html);

        // Scope % and mg checks to the evidence section payload. Listing
        // tables can name vials such as "10 mg" without being trial results.
        $rendered = $this->renderedEvidenceSection($response);
        $this->assertStringContainsString('TRIUMPH-2: retatrutide vs placebo', $rendered);
        $this->assertNeutralTrialCopy($rendered);

        $response->assertInertia(fn ($page) => $page
            ->component('Frontend/CompareCompoundVs')
            ->where('evidence.title', 'TRIUMPH-2: retatrutide vs placebo')
            ->where('evidenceLink.href', '/encyclopedia/retatrutide')
            ->has('faqs', 0)
            ->missing('seo.schema.1'));
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
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/CompareCompoundVs')
                ->where('evidence.title', 'Evidence at a glance')
                ->where('evidenceLink', null)
                ->has('faqs', 1)
                ->where('seo.schema.1.@type', 'FAQPage')
                ->where('seo.schema.1.mainEntity.0.name', $copy['faqs'][0]['q'])
                ->where('seo.schema.1.mainEntity.0.acceptedAnswer.text', $copy['faqs'][0]['a']));
    }

    private function assertNeutralTrialCopy(string $text): void
    {
        $this->assertDoesNotMatchRegularExpression('/[−-]\d+(\.\d)?%/u', $text);
        $this->assertStringNotContainsStringIgnoringCase('efficacy estimand', $text);
        $this->assertDoesNotMatchRegularExpression('/\d+ mg/i', $text);
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
