<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\Cjc1295DacIdentitySeeder;
use Database\Seeders\FiveAmino1mqNotAPeptideSeeder;
use Database\Seeders\RetatrutideTimelineLiteracySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncyclopediaIdentityLiteracyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_three_deepens_match_lowercase_slugs_and_are_idempotent(): void
    {
        $beforeCategories = ProductCategory::query()->count();

        $blend = $this->category('CJC-1295 / Ipamorelin', 'CJC-1295-Ipamorelin');
        $blendOverview = null;
        $blendPost = $this->education($blend, [
            'overview' => $blendOverview,
            'description' => 'Vendor listings only.',
            'background' => null,
            'key_points' => null,
        ]);

        $formula = 'C₁₅₂H₂₅₂N₄₄O₄₂';
        $weight = '3,367.97 g/mol';
        $cas = '446036-97-1';
        $keptBackground = 'Later background paragraph about WADA that must survive the first-paragraph tighten.';
        $cjc = $this->category('CJC-1295', 'CJC-1295');
        $cjcPost = $this->education($cjc, [
            'peptide_full_name' => 'CJC-1295 without DAC (Mod GRF 1-29)',
            'description' => 'Old short overview that equates CJC-1295 with Modified GRF 1-29.',
            'overview' => 'CJC-1295 (Modified GRF 1-29) has a ~30-minute half-life, while the with-DAC form has a 6-8 day half-life.',
            'background' => "Old opening that treats the names as one molecule.\n\n".$keptBackground,
            'human_use_intro' => 'The DAC form raises GH for many days.',
            'human_use_subsections' => [
                [
                    'title' => 'Clinical half-life',
                    'entries' => [
                        ['type' => 'content', 'value' => 'Teichman reported a 6-8 day half-life for this page’s compound.'],
                    ],
                ],
                [
                    'title' => 'Sport context',
                    'entries' => [
                        ['type' => 'content', 'value' => 'KEEP-WADA framing stays on the page.'],
                    ],
                ],
            ],
            'key_points' => ['Old point: ~30-minute half-life without DAC'],
            'conclusion' => 'Without-DAC is the standard and the most robust amplification.',
            'faqs' => [
                ['question' => 'Is it legal?', 'answer' => 'KEEP-LEGAL research-use framing only.'],
            ],
            'references' => [
                ['title' => 'Existing review', 'citation' => 'Keep this citation.', 'links' => []],
            ],
            'molecular_formula' => $formula,
            'molecular_weight' => $weight,
            'cas_registry_number' => $cas,
            'amino_acid_stability' => 'High',
        ]);

        $triumph2Body = 'LOCKED-TRIUMPH-2 efficacy estimand up to −20.8% and treatment-regimen −18.8%. DOI 10.1016/S0140-6736(26)01861-1.';
        $triumph1Point = 'TRIUMPH-1 labeled treatment-regimen −25.0% vs efficacy −28.3% kept separate.';
        $lockedOverview = 'LOCKED-OVERVIEW TRIUMPH-2 efficacy estimand up to −20.8% and treatment-regimen −18.8%.';
        $lockedShort = 'LOCKED-SHORT TRIUMPH-2 summary stays in front of the timeline sentence.';
        $lockedConclusion = 'LOCKED-CONCLUSION TRIUMPH-2 remains the diabetes trial.';
        $phase2 = 'LOCKED-PHASE-2 narrative that is not a TRIUMPH subsection.';
        $context = 'LOCKED-CONTEXT TRIUMPH-2 was not a head-to-head ranking.';
        $keptFda = 'KEEP-FDA still not FDA-approved. Planned U.S. BLA in Q1 2027 is a filing plan, not approval.';
        $reta = $this->category('Retatrutide', 'retatrutide');
        $retaPost = $this->education($reta, [
            'description' => $lockedShort,
            'overview' => $lockedOverview,
            'conclusion' => $lockedConclusion,
            'regulatory_important_note' => 'OLD NOTE investigational, not a pharmacy.',
            'key_points' => [
                'Investigational triple agonist',
                $triumph1Point,
                'TRIUMPH-2 Phase 3 locked bullet',
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
                        ['type' => 'content', 'value' => $triumph2Body],
                    ],
                ],
                [
                    'title' => 'TRIUMPH-1 Phase 3 (NEJM, 29 Sep 2026) — different trial from TRIUMPH-2',
                    'entries' => [
                        ['type' => 'content', 'value' => 'LOCKED-TRIUMPH-1 treatment-regimen −25.0% vs efficacy −28.3%.'],
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
                    'title' => 'TRIUMPH-2 publication note',
                    'entries' => [
                        ['type' => 'content', 'value' => $triumph2Body],
                    ],
                ],
            ],
            'faqs' => [
                ['question' => 'Is retatrutide FDA-approved?', 'answer' => $keptFda],
            ],
            'references' => [
                [
                    'title' => 'Retatrutide in adults with obesity and type 2 diabetes (TRIUMPH-2)',
                    'citation' => 'DOI 10.1016/S0140-6736(26)01861-1',
                    'description' => 'Treatment-regimen −18.8% at 12 mg.',
                    'links' => [],
                ],
            ],
        ]);

        $otherOverview = 'Other page: retatrutide TRIUMPH-2 −20.8% and −18.8% must not be edited from here.';
        $other = $this->category('Tirzepatide', 'tirzepatide');
        $otherPost = $this->education($other, [
            'overview' => $otherOverview,
            'description' => 'Tirzepatide card copy.',
            'faqs' => [
                ['question' => 'Is retatrutide approved?', 'answer' => 'LEAVE this other-page answer alone.'],
            ],
        ]);

        $aminoFormula = 'C₁₀H₁₁N₂O⁺';
        $aminoWeight = '175.21 g/mol';
        $aminoBackground = 'Unlike many peptide-based research compounds, 5-Amino-1MQ is a small molecule. Preclinical DIO models reported reduced adipocyte size.';
        $aminoFaq = 'KEEP-FAQ about NAD+ pathway literacy, not a dose.';
        $aminoConclusion = 'KEEP-CONCLUSION preclinical tool language.';
        $amino = $this->category('5-Amino-1MQ', '5-Amino-1MQ');
        $aminoPost = $this->education($amino, [
            'peptide_full_name' => '5-Amino-1-Methylquinolinium',
            'description' => 'Old short copy that never says not a peptide.',
            'overview' => '5-Amino-1MQ is a cell-permeable NNMT inhibitor.',
            'key_points' => [
                '5-Amino-1MQ is a small-molecule inhibitor of nicotinamide N-methyltransferase (NNMT)',
                'Preclinical studies show reduced adipocyte size and body fat in DIO models',
            ],
            'background' => $aminoBackground,
            'conclusion' => $aminoConclusion,
            'faqs' => [
                ['question' => 'What pathway does it touch?', 'answer' => $aminoFaq],
            ],
            'molecular_formula' => $aminoFormula,
            'molecular_weight' => $aminoWeight,
            'cas_registry_number' => null,
            'references' => [
                ['title' => 'Kraus preclinical NNMT paper', 'citation' => 'Keep.', 'links' => []],
            ],
        ]);

        $categoryCount = ProductCategory::query()->count();
        $this->assertSame($beforeCategories + 5, $categoryCount);

        $cjcSeeder = new Cjc1295DacIdentitySeeder;
        $cjcSeeder->run();
        $retaSeeder = new RetatrutideTimelineLiteracySeeder;
        $retaSeeder->run();
        $aminoSeeder = new FiveAmino1mqNotAPeptideSeeder;
        $aminoSeeder->run();

        $this->assertTrue($cjcSeeder->report['updated']);
        $this->assertTrue($retaSeeder->report['updated']);
        $this->assertTrue($aminoSeeder->report['updated']);
        $this->assertSame($categoryCount, ProductCategory::query()->count());
        $this->assertNull(ProductCategory::query()->whereRaw('LOWER(slug) = ?', ['cjc-1295-with-dac'])->first());

        $cjcPost->refresh();
        $this->assertSame(Cjc1295DacIdentitySeeder::SUBTITLE, $cjcPost->peptide_full_name);
        $this->assertSame(Cjc1295DacIdentitySeeder::OVERVIEW_SHORT, $cjcPost->description);
        $this->assertStringStartsWith(Cjc1295DacIdentitySeeder::OVERVIEW_SHORT, $cjcPost->overview);
        $this->assertStringContainsString('PMID 15817669', $cjcPost->overview);
        $this->assertStringContainsString('PMID 16352683', $cjcPost->overview);
        $this->assertStringContainsString('DAC construct', $cjcPost->overview);
        $this->assertStringContainsString('no-DAC', $cjcPost->overview);
        $this->assertStringContainsString('5.8–8.1 day', $cjcPost->overview);
        $this->assertStringContainsString('/encyclopedia/CJC-1295-Ipamorelin', $cjcPost->overview);
        $this->assertStringContainsString('Not dosing or reconstitution', $cjcPost->overview);
        $this->assertSame($formula, $cjcPost->molecular_formula);
        $this->assertSame($weight, $cjcPost->molecular_weight);
        $this->assertSame($cas, $cjcPost->cas_registry_number);
        $this->assertSame(Cjc1295DacIdentitySeeder::MOLECULAR_NOTE, $cjcPost->amino_acid_stability);
        $this->assertStringContainsString($keptBackground, $cjcPost->background);
        $this->assertStringContainsString('different design strategies', $cjcPost->background);
        $this->assertSame(Cjc1295DacIdentitySeeder::KEY_POINTS, $cjcPost->key_points);
        $this->assertSame('KEEP-LEGAL research-use framing only.', $cjcPost->faqs[0]['answer']);
        $this->assertSame('Sport context', $cjcPost->human_use_subsections[2]['title']);
        $this->assertSame(Cjc1295DacIdentitySeeder::TEICHMAN_TITLE, $cjcPost->human_use_subsections[0]['title']);
        $this->assertStringContainsString('DAC construct', $cjcPost->human_use_subsections[0]['entries'][0]['value']);
        $this->assertStringContainsString('5.8–8.1 days', $cjcPost->human_use_subsections[0]['entries'][0]['value']);
        $this->assertStringContainsString('Do not quote the 5.8–8.1 day half-life', $cjcPost->human_use_subsections[0]['entries'][1]['value']);
        $this->assertStringContainsString('DAC construct only', $cjcPost->key_points[2]);
        $this->assertNotContains('Clinical half-life', array_column($cjcPost->human_use_subsections, 'title'));
        $cjcBlob = json_encode($cjcPost->only([
            'peptide_full_name', 'description', 'overview', 'background', 'human_use_intro',
            'human_use_subsections', 'key_points', 'faqs', 'conclusion', 'references', 'amino_acid_stability',
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($cjcBlob);
        $this->assertStringNotContainsString('Editor notes', $cjcBlob);
        $this->assertStringNotContainsString('CHANGES', $cjcBlob);
        $this->assertStringNotContainsString('/workspace/drafts', $cjcBlob);
        $this->assertStringNotContainsString('which should you take', $cjcBlob);
        $this->assertStringNotContainsString('Condor', $cjcBlob);
        $this->assertStringNotContainsString('Do not publish', $cjcBlob);
        $this->assertStringContainsString('15817669', $cjcBlob);
        $this->assertStringContainsString('16352683', $cjcBlob);

        $blendPost->refresh();
        $this->assertNull($blendPost->overview);
        $this->assertSame('Vendor listings only.', $blendPost->description);
        $this->assertNull($blendPost->background);

        $retaPost->refresh();
        $this->assertStringStartsWith($lockedOverview, $retaPost->overview);
        $this->assertStringContainsString('Timeline literacy (early Oct 2026)', $retaPost->overview);
        $this->assertStringContainsString('Q1 2027', $retaPost->overview);
        $this->assertStringContainsString('filing plan', $retaPost->overview);
        $this->assertStringStartsWith($lockedShort, $retaPost->description);
        $this->assertStringContainsString('Seventh Circuit', $retaPost->description);
        $this->assertStringContainsString('no ruling was located', $retaPost->description);
        $this->assertStringStartsWith($lockedConclusion, $retaPost->conclusion);
        $this->assertStringContainsString('planned BLA filing window', $retaPost->conclusion);
        $this->assertContains($triumph1Point, $retaPost->key_points);
        $this->assertContains('TRIUMPH-2 Phase 3 locked bullet', $retaPost->key_points);
        $this->assertContains('Q1 2027 = Lilly’s stated BLA submission window while finishing CMC — not an approval date', $retaPost->key_points);
        $sections = $retaPost->human_use_subsections;
        $this->assertSame('Phase 2 Results', $sections[0]['title']);
        $this->assertSame($phase2, $sections[0]['entries'][0]['value']);
        $this->assertSame('TRIUMPH-2 Phase 3 (EASD / The Lancet, Sep 2026)', $sections[1]['title']);
        $this->assertSame($triumph2Body, $sections[1]['entries'][0]['value']);
        $this->assertSame('LOCKED-TRIUMPH-1 treatment-regimen −25.0% vs efficacy −28.3%.', $sections[2]['entries'][0]['value']);
        $this->assertSame(RetatrutideTimelineLiteracySeeder::MAP_TITLE, $sections[3]['title']);
        $this->assertSame($context, $sections[4]['entries'][0]['value']);
        $this->assertSame($triumph2Body, $retaPost->regulatory_subsections[0]['entries'][0]['value']);
        $this->assertSame('Filing literacy — planned BLA in Q1 2027 (≠ approval)', $retaPost->regulatory_subsections[2]['title']);
        $this->assertSame(RetatrutideTimelineLiteracySeeder::REGULATORY_NOTE, $retaPost->regulatory_important_note);
        $this->assertSame($keptFda, $retaPost->faqs[0]['answer']);
        $seventh = collect($retaPost->faqs)->first(fn ($faq) => str_contains((string) $faq['question'], 'Seventh Circuit'));
        $this->assertNotNull($seventh);
        $this->assertStringContainsString('No published ruling', $seventh['answer']);
        $this->assertStringContainsString('26-1301', json_encode($retaPost->references));
        $retaBlob = json_encode($retaPost->only([
            'description', 'overview', 'conclusion', 'key_points', 'human_use_subsections',
            'regulatory_subsections', 'regulatory_important_note', 'faqs', 'references',
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($retaBlob);
        $this->assertStringContainsString('−20.8%', $retaBlob);
        $this->assertStringContainsString('−18.8%', $retaBlob);
        $this->assertStringContainsString('−25.0%', $retaBlob);
        $this->assertStringContainsString('−28.3%', $retaBlob);
        $this->assertStringContainsString('Seventh Circuit', $retaBlob);
        $this->assertStringContainsString('Q1 2027', $retaBlob);
        $this->assertStringNotContainsString('Editor notes', $retaBlob);
        $this->assertStringNotContainsString('CHANGES', $retaBlob);
        $this->assertStringNotContainsString('Condor', $retaBlob);
        $this->assertStringNotContainsString('which should you take', $retaBlob);

        $otherPost->refresh();
        $this->assertSame($otherOverview, $otherPost->overview);
        $this->assertSame('LEAVE this other-page answer alone.', $otherPost->faqs[0]['answer']);

        $aminoPost->refresh();
        $this->assertSame(FiveAmino1mqNotAPeptideSeeder::SUBTITLE, $aminoPost->peptide_full_name);
        $this->assertSame(FiveAmino1mqNotAPeptideSeeder::OVERVIEW_SHORT, $aminoPost->description);
        $this->assertStringContainsString('not a peptide', $aminoPost->overview);
        $this->assertStringContainsString('NNMT', $aminoPost->overview);
        $this->assertStringContainsString('/encyclopedia/slu-pp-332', $aminoPost->overview);
        $this->assertStringContainsString('5-Amino-1-Methylquinolinium', $aminoPost->overview);
        $this->assertSame(FiveAmino1mqNotAPeptideSeeder::KEY_POINTS, $aminoPost->key_points);
        $this->assertNotContains('Preclinical studies show reduced adipocyte size and body fat in DIO models', $aminoPost->key_points);
        $this->assertSame($aminoFormula, $aminoPost->molecular_formula);
        $this->assertSame($aminoWeight, $aminoPost->molecular_weight);
        $this->assertNull($aminoPost->cas_registry_number);
        $this->assertSame($aminoBackground, $aminoPost->background);
        $this->assertSame($aminoConclusion, $aminoPost->conclusion);
        $this->assertSame($aminoFaq, $aminoPost->faqs[0]['answer']);
        $this->assertSame('Kraus preclinical NNMT paper', $aminoPost->references[0]['title']);
        $aminoBlob = json_encode($aminoPost->only([
            'peptide_full_name', 'description', 'overview', 'key_points',
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($aminoBlob);
        $this->assertStringContainsString('not a peptide', $aminoBlob);
        $this->assertStringContainsString('NNMT', $aminoBlob);
        $this->assertStringNotContainsString('Editor notes', $aminoBlob);
        $this->assertStringNotContainsString('CHANGES', $aminoBlob);
        $this->assertStringNotContainsString('which should you take', $aminoBlob);
        $this->assertDoesNotMatchRegularExpression('/\b\d+\s*mg\b/', $aminoBlob);

        $cjcPost->updated_at = now()->subDay();
        $cjcPost->save();
        $retaPost->updated_at = now()->subDay();
        $retaPost->save();
        $aminoPost->updated_at = now()->subDay();
        $aminoPost->save();
        $stamps = [
            $cjcPost->fresh()->updated_at,
            $retaPost->fresh()->updated_at,
            $aminoPost->fresh()->updated_at,
        ];

        $againCjc = new Cjc1295DacIdentitySeeder;
        $againCjc->run();
        $againReta = new RetatrutideTimelineLiteracySeeder;
        $againReta->run();
        $againAmino = new FiveAmino1mqNotAPeptideSeeder;
        $againAmino->run();
        $this->assertFalse($againCjc->report['updated']);
        $this->assertFalse($againReta->report['updated']);
        $this->assertFalse($againAmino->report['updated']);
        $this->assertTrue($cjcPost->fresh()->updated_at->equalTo($stamps[0]));
        $this->assertTrue($retaPost->fresh()->updated_at->equalTo($stamps[1]));
        $this->assertTrue($aminoPost->fresh()->updated_at->equalTo($stamps[2]));
        $this->assertCount(5, $cjcPost->fresh()->faqs);
        $this->assertCount(5, $retaPost->fresh()->human_use_subsections);
        $this->assertSame($triumph2Body, $retaPost->fresh()->human_use_subsections[1]['entries'][0]['value']);
        $this->assertSame($aminoFormula, $aminoPost->fresh()->molecular_formula);

        $this->get('/encyclopedia/CJC-1295')
            ->assertOk()
            ->assertSee('DAC construct', false)
            ->assertSee('no-DAC', false)
            ->assertSee('Teichman', false)
            ->assertDontSee('Editor notes', false)
            ->assertDontSee('/workspace/drafts', false)
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/EncyclopediaArticleDetail')
                ->where('subtitle', Cjc1295DacIdentitySeeder::SUBTITLE)
                ->where('molecularInfo.formula', $formula)
                ->where('molecularInfo.molecularWeight', $weight)
                ->where('molecularInfo.casNumber', $cas)
                ->where('aminoAcidSequence.properties.stability', Cjc1295DacIdentitySeeder::MOLECULAR_NOTE)
                ->where('keyPoints.2', Cjc1295DacIdentitySeeder::KEY_POINTS[2])
            );

        $this->get('/encyclopedia/CJC-1295-Ipamorelin')
            ->assertOk()
            ->assertDontSee('DAC construct', false)
            ->assertDontSee('PMID 15817669', false)
            ->assertInertia(fn ($page) => $page
                ->where('overview', '')
                ->where('background', '')
                ->where('keyPoints', [])
            );

        $this->get('/encyclopedia/retatrutide')
            ->assertOk()
            ->assertSee('Q1 2027', false)
            ->assertSee('Seventh Circuit', false)
            ->assertSee('BLA', false)
            ->assertDontSee('Condor', false)
            ->assertInertia(fn ($page) => $page
                ->where('humanUseSubsections.1.entries.0.value', $triumph2Body)
                ->where('humanUseSubsections.2.entries.0.value', 'LOCKED-TRIUMPH-1 treatment-regimen −25.0% vs efficacy −28.3%.')
                ->where('humanUseSubsections.3.title', RetatrutideTimelineLiteracySeeder::MAP_TITLE)
                ->where('overview', fn ($overview) => is_string($overview)
                    && str_contains($overview, '−20.8%')
                    && str_contains($overview, '−18.8%')
                    && str_contains($overview, 'Q1 2027'))
            );

        $this->get('/encyclopedia/5-Amino-1MQ')
            ->assertOk()
            ->assertSee('not a peptide', false)
            ->assertSee('NNMT', false)
            ->assertDontSee('reduced adipocyte size and body fat in DIO models', false)
            ->assertInertia(fn ($page) => $page
                ->where('subtitle', FiveAmino1mqNotAPeptideSeeder::SUBTITLE)
                ->where('molecularInfo.formula', $aminoFormula)
                ->where('molecularInfo.molecularWeight', $aminoWeight)
                ->where('molecularInfo.casNumber', '')
                ->where('keyPoints', FiveAmino1mqNotAPeptideSeeder::KEY_POINTS)
                ->where('overview', fn ($overview) => is_string($overview)
                    && str_contains($overview, 'not a peptide')
                    && str_contains($overview, 'NNMT')
                    && str_contains($overview, '/encyclopedia/slu-pp-332'))
            );

        $this->get('/encyclopedia/tirzepatide')
            ->assertOk()
            ->assertSee($otherOverview, false);
    }

    public function test_seeders_skip_missing_categories_missing_posts_and_slug_collisions(): void
    {
        foreach ([
            new Cjc1295DacIdentitySeeder,
            new RetatrutideTimelineLiteracySeeder,
            new FiveAmino1mqNotAPeptideSeeder,
        ] as $seeder) {
            $seeder->run();
            $this->assertTrue($seeder->report['missing_category']);
            $this->assertFalse($seeder->report['updated']);
        }
        $this->assertSame(0, ProductCategory::query()->count());
        $this->assertSame(0, EducationPost::query()->count());

        $cjc = $this->category('CJC-1295', 'cjc-1295');
        $noPost = new Cjc1295DacIdentitySeeder;
        $noPost->run();
        $this->assertTrue($noPost->report['missing_post']);
        $this->assertFalse($noPost->report['updated']);
        $this->assertSame(0, EducationPost::query()->count());

        $this->category('CJC-1295 duplicate', 'CJC-1295');
        $marker = 'Collision marker that must survive.';
        $post = $this->education($cjc, ['overview' => $marker, 'molecular_formula' => 'KEEP']);
        $collision = new Cjc1295DacIdentitySeeder;
        $collision->run();
        $this->assertTrue($collision->report['skipped_slug_collision']);
        $this->assertFalse($collision->report['updated']);
        $this->assertSame($marker, $post->fresh()->overview);
        $this->assertSame('KEEP', $post->fresh()->molecular_formula);
        $this->assertSame(2, ProductCategory::query()->count());

        $lower = $this->category('Retatrutide', 'retatrutide');
        $this->category('Retatrutide duplicate', 'Retatrutide');
        $retaPost = $this->education($lower, ['overview' => 'LOCKED-OVERVIEW −20.8% −18.8%']);
        $reta = new RetatrutideTimelineLiteracySeeder;
        $reta->run();
        $this->assertTrue($reta->report['skipped_slug_collision']);
        $this->assertSame('LOCKED-OVERVIEW −20.8% −18.8%', $retaPost->fresh()->overview);

        $amino = $this->category('5-Amino-1MQ', '5-amino-1mq');
        $this->category('5-Amino-1MQ duplicate', '5-Amino-1MQ');
        $aminoPost = $this->education($amino, [
            'overview' => 'Original amino overview.',
            'molecular_formula' => 'C₁₀H₁₁N₂O⁺',
            'molecular_weight' => '175.21 g/mol',
        ]);
        $aminoSeeder = new FiveAmino1mqNotAPeptideSeeder;
        $aminoSeeder->run();
        $this->assertTrue($aminoSeeder->report['skipped_slug_collision']);
        $this->assertSame('Original amino overview.', $aminoPost->fresh()->overview);
        $this->assertSame('C₁₀H₁₁N₂O⁺', $aminoPost->fresh()->molecular_formula);
        $this->assertSame('175.21 g/mol', $aminoPost->fresh()->molecular_weight);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function category(string $name, string $slug): ProductCategory
    {
        return ProductCategory::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function education(ProductCategory $category, array $attributes): EducationPost
    {
        return EducationPost::create(array_merge([
            'title' => $category->name,
            'slug' => $category->slug.'-post',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
        ], $attributes));
    }
}
