<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\RetatrutideCompareNarrative;
use Database\Seeders\RetatrutideEncyclopediaFaqSeeder;
use Database\Seeders\RetatrutideTimelineLiteracySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetatrutideEncyclopediaFaqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_seeder_refreshes_retatrutide_faq_once_and_leaves_triumph_2_body_intact(): void
    {
        $categoryCount = ProductCategory::query()->count();
        $missing = new RetatrutideEncyclopediaFaqSeeder;
        $missing->run();
        $this->assertTrue($missing->report['missing_category']);
        $this->assertFalse($missing->report['updated']);
        $this->assertSame($categoryCount, ProductCategory::query()->count());

        $lower = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'description' => 'Lowercase slug must stay untouched.',
            'is_active' => true,
        ]);
        $lowerPost = EducationPost::create([
            'title' => 'Retatrutide lowercase',
            'slug' => 'retatrutide',
            'product_category_id' => $lower->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'Lowercase overview that must survive.',
            'faqs' => [[
                'question' => 'Is retatrutide FDA-approved?',
                'answer' => 'Not yet. It is in Phase 3 clinical trials with results expected in 2025-2026.',
            ]],
        ]);

        $lowerSeeder = new RetatrutideEncyclopediaFaqSeeder;
        $lowerSeeder->run();
        $this->assertTrue($lowerSeeder->report['updated']);
        $this->assertFalse($lowerSeeder->report['skipped_slug_collision']);
        $lower->refresh();
        $lowerPost->refresh();
        $this->assertSame('retatrutide', $lower->slug);
        $this->assertSame('Lowercase slug must stay untouched.', $lower->description);
        $this->assertSame('Lowercase overview that must survive.', $lowerPost->overview);
        $this->assertStringNotContainsString('expected in 2025-2026', $lowerPost->faqs[0]['answer']);
        $this->assertStringContainsString('Q1 2027', $lowerPost->faqs[0]['answer']);
        $this->assertNotNull(collect($lowerPost->faqs)->first(
            fn ($faq) => is_array($faq) && str_contains((string) ($faq['question'] ?? ''), 'TRIUMPH-1')
        ));
        $this->get('/encyclopedia/retatrutide')
            ->assertOk()
            ->assertSee('Q1 2027', false)
            ->assertSee('TRIUMPH-1', false)
            ->assertDontSee('expected in 2025-2026', false)
            ->assertDontSee('expected in 2025', false);
        $this->get('/encyclopedia/Retatrutide')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/retatrutide');

        $lowerAgain = new RetatrutideEncyclopediaFaqSeeder;
        $lowerAgain->run();
        $this->assertFalse($lowerAgain->report['updated']);

        ProductCategory::create([
            'name' => 'Retatrutide duplicate',
            'slug' => 'Retatrutide',
            'is_active' => true,
        ]);
        $collision = new RetatrutideEncyclopediaFaqSeeder;
        $collision->run();
        $this->assertTrue($collision->report['skipped_slug_collision']);
        $this->assertFalse($collision->report['updated']);
        $lowerPost->refresh();
        $this->assertStringContainsString('Q1 2027', $lowerPost->faqs[0]['answer']);

        ProductCategory::query()->where('slug', 'Retatrutide')->delete();
        $lowerPost->delete();
        $lower->delete();

        $other = ProductCategory::create([
            'name' => 'Semaglutide',
            'slug' => 'Semaglutide',
            'is_active' => true,
        ]);
        $otherFaq = 'Not yet. It is in Phase 3 clinical trials with results expected in 2025-2026.';
        $otherPost = EducationPost::create([
            'title' => 'Semaglutide',
            'slug' => 'Semaglutide',
            'product_category_id' => $other->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'faqs' => [[
                'question' => 'Is semaglutide FDA-approved?',
                'answer' => $otherFaq,
            ]],
            'overview' => 'Leave this other encyclopedia overview alone.',
        ]);

        $triumph2 = 'LOCKED-TRIUMPH-2 efficacy up to −20.8% and treatment-regimen −18.8% versus placebo. Do not rewrite this sentence.';
        $context = 'LOCKED-CONTEXT TRIUMPH-2 compared retatrutide with placebo, not with semaglutide or tirzepatide.';
        $overview = 'LOCKED-OVERVIEW TRIUMPH-2 efficacy estimand up to −20.8% and treatment-regimen −18.8%. Planned U.S. BLA in Q1 2027 (filing plan ≠ approval).';
        $note = 'LOCKED-NOTE investigational and not approved. PeptideMap is not a pharmacy or clinic.';
        $tirzepatideFaq = 'Retatrutide adds glucagon receptor agonism on top of GLP-1 and GIP. The glucagon component increases energy expenditure and fat oxidation beyond what dual agonists achieve.';
        $glucagonFaq = "The GLP-1 component offsets glucagon's glycemic effect, while glucagon's metabolic benefits (increased energy expenditure, hepatic fat reduction) add to the overall effect.";
        $keyPoints = [
            'Investigational GIP + GLP-1 + glucagon (triple) agonist; not FDA-approved',
            'TRIUMPH-2 Phase 3: T2D with obesity/overweight, 80 weeks (NCT05929079)',
            'Planned U.S. BLA stated for Q1 2027 (company plan, not approval)',
        ];
        $existingReferences = [[
            'title' => 'Retatrutide in adults with obesity and type 2 diabetes (TRIUMPH-2)',
            'citation' => 'DOI 10.1016/S0140-6736(26)01861-1',
            'description' => 'Treatment-regimen −18.8% at 12 mg.',
        ]];

        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'Retatrutide',
            'description' => 'Catalog description that is not an encyclopedia field.',
            'is_active' => true,
        ]);
        $phase2 = "The Phase 2 trial showed up to 24.2% body weight reduction at 48 weeks.\n\n".RetatrutideEncyclopediaFaqSeeder::PHASE2_UNDERWAY;
        EducationPost::create([
            'title' => 'Retatrutide',
            'slug' => 'Retatrutide',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => $overview,
            'description' => 'A stored blurb still says results expected in 2025-2026.',
            'background' => 'Background still says results expected in 2025–2026.',
            'conclusion' => 'LOCKED-CONCLUSION TRIUMPH-2 remains the diabetes trial. Q1 2027 is a filing plan.',
            'human_use_intro' => 'LOCKED-INTRO TRIUMPH-2 now adds a detailed Phase 3 readout.',
            'regulatory_important_note' => $note,
            'key_points' => $keyPoints,
            'references' => $existingReferences,
            'faqs' => [
                [
                    'question' => 'How does retatrutide differ from tirzepatide?',
                    'answer' => $tirzepatideFaq,
                ],
                [
                    'question' => 'Is retatrutide FDA-approved?',
                    'answer' => 'Not yet. It is in Phase 3 clinical trials with results expected in 2025-2026.',
                ],
                [
                    'question' => 'Why add glucagon if it raises blood sugar?',
                    'answer' => $glucagonFaq,
                ],
            ],
            'human_use_subsections' => [
                [
                    'title' => 'Phase 2 Results',
                    'entries' => [
                        ['type' => 'content', 'value' => $phase2],
                    ],
                ],
                [
                    'title' => 'TRIUMPH-2 Phase 3 (EASD / The Lancet, Sep 2026)',
                    'entries' => [
                        ['type' => 'content', 'value' => $triumph2],
                    ],
                ],
                [
                    'title' => 'Context vs semaglutide / tirzepatide (not head-to-head)',
                    'entries' => [
                        ['type' => 'content', 'value' => $context],
                    ],
                ],
            ],
            'regulatory_subsections' => [
                [
                    'title' => 'Development Status',
                    'entries' => [
                        ['type' => 'content', 'value' => 'Retatrutide is not yet FDA-approved. It is in Phase 3 clinical trials (Eli Lilly). Research-grade retatrutide is available through peptide vendors for research purposes only.'],
                    ],
                ],
            ],
        ]);

        $beforeCount = ProductCategory::query()->count();
        $seeder = new RetatrutideEncyclopediaFaqSeeder;
        $seeder->run();
        $this->assertTrue($seeder->report['updated']);
        $this->assertSame($beforeCount, ProductCategory::query()->count());

        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $blob = json_encode($post->only([
            'faqs', 'overview', 'description', 'background', 'conclusion', 'human_use_intro',
            'human_use_subsections', 'regulatory_subsections', 'regulatory_important_note',
            'key_points', 'references',
        ]), JSON_UNESCAPED_UNICODE);

        $this->assertIsString($blob);
        $this->assertStringNotContainsString('2025-2026', $blob);
        $this->assertStringNotContainsString('2025–2026', $blob);
        $this->assertStringNotContainsString('expected in 2025', $blob);
        $this->assertStringNotContainsString('Phase 3 trials are underway', $blob);

        $faqs = $post->faqs;
        $this->assertSame('How does retatrutide differ from tirzepatide?', $faqs[0]['question']);
        $this->assertSame($tirzepatideFaq, $faqs[0]['answer']);
        $this->assertSame('Is retatrutide FDA-approved?', $faqs[1]['question']);
        $this->assertSame(RetatrutideEncyclopediaFaqSeeder::FDA_ANSWER, $faqs[1]['answer']);
        $this->assertStringContainsString('still not FDA-approved', $faqs[1]['answer']);
        $this->assertStringContainsString('Q1 2027', $faqs[1]['answer']);
        $this->assertStringContainsString('filing plan is not FDA approval', $faqs[1]['answer']);
        $this->assertStringContainsString('published', $faqs[1]['answer']);
        $this->assertSame($glucagonFaq, $faqs[2]['answer']);

        $triumph1 = collect($faqs)->firstWhere('question', RetatrutideEncyclopediaFaqSeeder::TRIUMPH1_QUESTION);
        $this->assertNotNull($triumph1);
        $this->assertStringContainsString('treatment-regimen', $triumph1['answer']);
        $this->assertStringContainsString('−25.0%', $triumph1['answer']);
        $this->assertStringContainsString('−3.9%', $triumph1['answer']);
        $this->assertStringContainsString('−28.3%', $triumph1['answer']);
        $this->assertStringContainsString('efficacy estimand', $triumph1['answer']);
        $this->assertStringContainsString('not the abstract primary', $triumph1['answer']);
        $this->assertStringContainsString('−2.2%', $triumph1['answer']);
        $this->assertStringContainsString('TRIUMPH-2', $triumph1['answer']);
        $this->assertStringContainsString('10.1056/NEJMoa2604169', $triumph1['answer']);
        $this->assertStringContainsString('42814954', $triumph1['answer']);

        $triumph3 = collect($faqs)->firstWhere('question', RetatrutideEncyclopediaFaqSeeder::TRIUMPH3_QUESTION);
        $this->assertNotNull($triumph3);
        $this->assertStringContainsString('does not establish a reduction in cardiovascular events', $triumph3['answer']);
        $this->assertStringContainsString('Both intervals include 1', $triumph3['answer']);
        $this->assertStringContainsString('0.82', $triumph3['answer']);
        $this->assertStringContainsString('1.12', $triumph3['answer']);
        $this->assertStringContainsString('−21.6%', $triumph3['answer']);
        $this->assertStringContainsString('−22.6%', $triumph3['answer']);
        $this->assertStringNotContainsString('cardiovascular benefit', $blob);
        $this->assertStringNotContainsString('−23.3%', $blob);
        $this->assertStringNotContainsString('3 of 12', $blob);

        $sections = $post->human_use_subsections;
        $this->assertSame('Phase 2 Results', $sections[0]['title']);
        $this->assertStringContainsString('24.2%', $sections[0]['entries'][0]['value']);
        $this->assertStringContainsString(RetatrutideEncyclopediaFaqSeeder::PHASE2_PUBLISHED, $sections[0]['entries'][0]['value']);
        $this->assertSame('TRIUMPH-2 Phase 3 (EASD / The Lancet, Sep 2026)', $sections[1]['title']);
        $this->assertSame($triumph2, $sections[1]['entries'][0]['value']);
        $this->assertSame(RetatrutideEncyclopediaFaqSeeder::TRIUMPH1_TITLE, $sections[2]['title']);
        $this->assertSame(RetatrutideEncyclopediaFaqSeeder::TRIUMPH1_BODY, $sections[2]['entries'][0]['value']);
        $this->assertSame(RetatrutideEncyclopediaFaqSeeder::TRIUMPH3_TITLE, $sections[3]['title']);
        $this->assertSame($context, $sections[4]['entries'][0]['value']);

        $this->assertSame($overview, $post->overview);
        $this->assertSame('LOCKED-CONCLUSION TRIUMPH-2 remains the diabetes trial. Q1 2027 is a filing plan.', $post->conclusion);
        $this->assertSame('LOCKED-INTRO TRIUMPH-2 now adds a detailed Phase 3 readout.', $post->human_use_intro);
        $this->assertSame($note, $post->regulatory_important_note);
        $this->assertSame($keyPoints, array_slice($post->key_points, 0, count($keyPoints)));
        $this->assertSame(RetatrutideEncyclopediaFaqSeeder::TRIUMPH1_KEY_POINT, $post->key_points[count($keyPoints)]);
        $this->assertSame(RetatrutideEncyclopediaFaqSeeder::APPROVAL_STATUS, $post->regulatory_subsections[0]['entries'][0]['value']);
        $this->assertSame($existingReferences[0], $post->references[0]);
        $referenceBlob = json_encode($post->references, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('10.1056/NEJMoa2604169', (string) $referenceBlob);
        $this->assertStringContainsString('medical.lilly.com', (string) $referenceBlob);
        $this->assertStringNotContainsString('condorresearch.com', $blob);
        $this->assertSame($overview, $post->overview);
        $category->refresh();
        $this->assertSame('Catalog description that is not an encyclopedia field.', $category->description);

        $otherPost->refresh();
        $this->assertSame($otherFaq, $otherPost->faqs[0]['answer']);
        $this->assertSame('Leave this other encyclopedia overview alone.', $otherPost->overview);

        $compareLead = RetatrutideCompareNarrative::payload()['lead'];
        $this->assertStringContainsString('−25.0%', $compareLead);
        $this->assertStringContainsString('−28.3%', $compareLead);

        EducationPost::query()->where('id', $post->id)->update(['updated_at' => '2020-01-01 00:00:00']);
        $frozen = json_encode($post->fresh()->only(['faqs', 'human_use_subsections', 'key_points', 'references', 'regulatory_subsections']));

        $again = new RetatrutideEncyclopediaFaqSeeder;
        $again->run();
        $this->assertFalse($again->report['updated']);
        $post->refresh();
        $this->assertSame('2020-01-01 00:00:00', $post->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame($frozen, json_encode($post->only(['faqs', 'human_use_subsections', 'key_points', 'references', 'regulatory_subsections'])));
        $this->assertSame(1, EducationPost::query()->where('product_category_id', $category->id)->count());

        $this->get('/encyclopedia/Retatrutide')
            ->assertOk()
            ->assertSee('Is retatrutide FDA-approved?', false)
            ->assertSee('still not FDA-approved', false)
            ->assertSee('Q1 2027', false)
            ->assertSee('filing plan is not FDA approval', false)
            ->assertSee('treatment-regimen estimand', false)
            ->assertSee('\u221225.0%', false)
            ->assertSee('efficacy estimand', false)
            ->assertSee('\u221228.3%', false)
            ->assertSee('not the abstract primary', false)
            ->assertSee('does not establish a reduction in cardiovascular events', false)
            ->assertSee('LOCKED-TRIUMPH-2', false)
            ->assertSee('Planned U.S. BLA stated for Q1 2027 (company plan, not approval)', false)
            ->assertDontSee('2025-2026', false)
            ->assertDontSee('expected in 2025', false)
            ->assertDontSee('cardiovascular benefit', false)
            ->assertDontSee('condorresearch.com', false);
    }

    public function test_faq_and_timeline_seeders_converge_in_either_production_order(): void
    {
        $this->assertFaqAndTimelineConverge(true);
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $this->assertFaqAndTimelineConverge(false);
    }

    private function assertFaqAndTimelineConverge(bool $faqFirst): void
    {
        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $post = EducationPost::create([
            'title' => 'Retatrutide',
            'slug' => 'retatrutide',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'faqs' => [
                ['question' => 'How does retatrutide differ from tirzepatide?', 'answer' => 'Adds glucagon receptor agonism.'],
                ['question' => 'Is retatrutide FDA-approved?', 'answer' => 'Not yet. Results expected in 2025-2026.'],
                ['question' => 'Why add glucagon if it raises blood sugar?', 'answer' => 'The GLP-1 component offsets it.'],
            ],
            'key_points' => ['Point 1', 'Point 2', 'Point 3', 'Point 4', 'Point 5', 'Point 6'],
            'regulatory_subsections' => [[
                'title' => 'Development Status',
                'entries' => [[
                    'type' => 'content',
                    'value' => 'Phase 3 clinical trials with results expected in 2025-2026.',
                ]],
            ]],
            'references' => [
                ['title' => 'TRIUMPH-2', 'citation' => 'DOI 10.1016/S0140-6736(26)01861-1', 'links' => [['url' => 'https://doi.org/10.1016/S0140-6736(26)01861-1']]],
                ['title' => 'Ref 2', 'links' => [['url' => 'https://example.test/ref-2']]],
                ['title' => 'Ref 3', 'links' => [['url' => 'https://example.test/ref-3']]],
                ['title' => 'Ref 4', 'links' => [['url' => 'https://example.test/ref-4']]],
                ['title' => 'Ref 5', 'links' => [['url' => 'https://example.test/ref-5']]],
                ['title' => 'Ref 6', 'links' => [['url' => 'https://example.test/ref-6']]],
            ],
        ]);

        $first = $faqFirst ? new RetatrutideEncyclopediaFaqSeeder : new RetatrutideTimelineLiteracySeeder;
        $second = $faqFirst ? new RetatrutideTimelineLiteracySeeder : new RetatrutideEncyclopediaFaqSeeder;
        $third = $faqFirst ? new RetatrutideEncyclopediaFaqSeeder : new RetatrutideTimelineLiteracySeeder;
        $first->run();
        $second->run();

        $post->refresh();
        $blob = json_encode($post->only(['faqs', 'key_points', 'regulatory_subsections', 'references']), JSON_UNESCAPED_UNICODE);
        $this->assertCount(9, $post->faqs);
        $this->assertCount(12, $post->key_points);
        $this->assertCount(5, $post->regulatory_subsections);
        $this->assertCount(11, $post->references);
        $this->assertStringNotContainsString('expected in 2025', (string) $blob);
        $this->assertSame('retatrutide', $category->fresh()->slug);

        EducationPost::query()->where('id', $post->id)->update(['updated_at' => '2020-01-01 00:00:00']);
        $third->run();
        $this->assertFalse($third->report['updated']);
        $this->assertSame('2020-01-01 00:00:00', $post->fresh()->updated_at->format('Y-m-d H:i:s'));
    }
}
