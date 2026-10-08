<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\ElamipretideMotsCCompareFaqs;
use App\Support\EncyclopediaShellParser;
use Database\Seeders\ElamipretideForzinitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElamipretideForzinityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_seeder_deepens_lowercase_elamipretide_and_is_idempotent(): void
    {
        $retatrutide = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $retaOverview = 'Original retatrutide encyclopedia overview that must survive.';
        $retaFaq = 'Not yet. It is in Phase 3 clinical trials with results expected in 2025-2026.';
        EducationPost::create([
            'title' => 'Retatrutide',
            'slug' => 'retatrutide',
            'product_category_id' => $retatrutide->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => $retaOverview,
            'faqs' => [[
                'question' => 'Is retatrutide FDA-approved?',
                'answer' => $retaFaq,
            ]],
        ]);

        $category = ProductCategory::create([
            'name' => 'Elamipretide',
            'slug' => 'elamipretide',
            'description' => 'Catalog description that is not an encyclopedia field.',
            'is_active' => true,
        ]);
        $keptFda = 'KEPT Forzinity accelerated approval answer for the original FAQ.';
        $keptAccelerated = 'KEPT accelerated approval is not traditional approval.';
        $keptVials = 'KEPT vendor vials are not Forzinity.';
        $overview = 'LOCKED overview that the Forzinity deepen must not rewrite.';
        $background = 'LOCKED background that the Forzinity deepen must not rewrite.';
        EducationPost::create([
            'title' => 'Elamipretide',
            'slug' => 'elamipretide',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => $overview,
            'background' => $background,
            'molecular_formula' => 'WRONG',
            'molecular_weight' => 'WRONG',
            'cas_registry_number' => '00000-00-0',
            'regulatory_important_note' => 'Regulatory literacy: Removal from FDA Category 2 (or a favorable Pharmacy Compounding Advisory Committee vote) is not placement on the final 503A Bulks List.',
            'faqs' => [
                ['question' => 'Is elamipretide FDA-approved?', 'answer' => $keptFda],
                ['question' => 'Is accelerated approval “full” approval?', 'answer' => $keptAccelerated],
                ['question' => 'Are Peptidemap vendor vials Forzinity?', 'answer' => $keptVials],
            ],
            'references' => [
                [
                    'title' => 'Junk reference that the replacement must drop',
                    'links' => [['url' => 'https://example.com/not-a-source', 'label' => 'Source']],
                ],
                [
                    'title' => 'Long-term efficacy and safety of elamipretide in Barth syndrome (TAZPOWER extension)',
                    'links' => [['url' => 'https://pubmed.ncbi.nlm.nih.gov/38584022/', 'label' => 'Source']],
                ],
            ],
            'human_use_subsections' => [[
                'title' => 'Old human use',
                'entries' => [['type' => 'content', 'value' => 'Old human-use sentence.']],
            ]],
        ]);

        $beforeCount = ProductCategory::query()->count();
        $seeder = new ElamipretideForzinitySeeder;
        $seeder->run();
        $this->assertTrue($seeder->report['updated']);
        $this->assertFalse($seeder->report['missing_category']);
        $this->assertFalse($seeder->report['missing_post']);
        $this->assertSame($beforeCount, ProductCategory::query()->count());
        $this->assertSame('elamipretide', $category->fresh()->slug);

        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame($overview, $post->overview);
        $this->assertSame($background, $post->background);
        $this->assertSame('C32H49N9O5', $post->molecular_formula);
        $this->assertSame('639.8 g/mol (free base; PubChem/USAN)', $post->molecular_weight);
        $this->assertSame('736992-21-5', $post->cas_registry_number);

        $blob = json_encode($post->only([
            'faqs', 'regulatory_important_note', 'regulatory_subsections',
            'human_use_subsections', 'references', 'molecular_formula',
            'molecular_weight', 'cas_registry_number',
        ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($blob);
        $this->assertStringContainsString('mitochondrial cardiolipin binder', $blob);
        $this->assertStringContainsString('19 September 2025', $blob);
        $this->assertStringContainsString('146bf34c-76f2-48db-ac07-fb29cce2cd75', $blob);
        $this->assertStringContainsString('at least 30 kg', $blob);
        $this->assertStringContainsString('Barth', $blob);
        $this->assertStringContainsString('not Forzinity', $blob);
        $this->assertStringContainsString('SPIBA-201', $blob);
        $this->assertStringNotContainsString('Category 2', $blob);
        $this->assertStringNotContainsString('PCAC', $blob);
        $this->assertStringNotContainsString('503A', $blob);
        $this->assertStringNotContainsString('Bulks List', $blob);
        $this->assertStringNotContainsString('Editor notes', $blob);
        $this->assertStringNotContainsString('CHANGES', $blob);
        $this->assertStringNotContainsString('which should you take', $blob);
        $this->assertStringNotContainsString('Colin', $blob);
        $this->assertDoesNotMatchRegularExpression('/\b\d+(?:\.\d+)?\s*mg\b/i', $blob);

        $faqs = $post->faqs;
        $this->assertCount(7, $faqs);
        $this->assertSame('Is elamipretide FDA-approved?', $faqs[0]['question']);
        $this->assertSame($keptFda, $faqs[0]['answer']);
        $this->assertSame($keptAccelerated, $faqs[1]['answer']);
        $this->assertSame($keptVials, $faqs[2]['answer']);
        $this->assertSame('Is SS-31 the same thing as elamipretide?', $faqs[3]['question']);
        $this->assertStringContainsString('not a second molecule', $faqs[3]['answer']);
        $questions = array_column($faqs, 'question');
        $this->assertContains('Does the Forzinity approval make research “elamipretide” or “SS-31” listings approved?', $questions);
        $this->assertContains('Does SS-31 need to come before MOTS-c?', $questions);
        $this->assertContains('Why is Forzinity’s approval called “accelerated”?', $questions);
        $sequencing = collect($faqs)->firstWhere('question', 'Does SS-31 need to come before MOTS-c?');
        $this->assertStringContainsString('no validated stacking or sequencing protocol', $sequencing['answer']);
        $this->assertStringContainsString('does not give dosing', $sequencing['answer']);

        $this->assertSame('Research-use (RUO) listings are not Forzinity', $post->regulatory_subsections[1]['title']);
        $this->assertSame('Barth syndrome program behind the Forzinity approval', $post->human_use_subsections[0]['title']);
        $this->assertNotContains('Old human use', array_column($post->human_use_subsections, 'title'));

        $urls = $this->referenceUrls($post->references);
        $this->assertCount(9, $post->references);
        $this->assertSame(
            'https://www.fda.gov/news-events/press-announcements/fda-grants-accelerated-approval-first-treatment-barth-syndrome',
            $urls[0]
        );
        $this->assertSame(
            'https://dailymed.nlm.nih.gov/dailymed/drugInfo.cfm?setid=146bf34c-76f2-48db-ac07-fb29cce2cd75',
            $post->references[1]['links'][1]['url']
        );
        $this->assertSame('https://pubmed.ncbi.nlm.nih.gov/38584022/', $urls[6]);
        $this->assertSame('https://www.nature.com/articles/s41436-020-01006-8', $urls[7]);
        $this->assertSame('https://pmc.ncbi.nlm.nih.gov/articles/PMC7334473/', $urls[8]);
        $this->assertNotContains('https://example.com/not-a-source', $urls);

        $reta = EducationPost::query()->where('product_category_id', $retatrutide->id)->firstOrFail();
        $this->assertSame($retaOverview, $reta->overview);
        $this->assertSame($retaFaq, $reta->faqs[0]['answer']);

        $post->updated_at = now()->subDay();
        $post->save();
        $stamp = $post->fresh()->updated_at;
        $again = new ElamipretideForzinitySeeder;
        $again->run();
        $this->assertFalse($again->report['updated']);
        $fresh = $post->fresh();
        $this->assertTrue($fresh->updated_at->equalTo($stamp));
        $this->assertCount(7, $fresh->faqs);
        $this->assertCount(9, $fresh->references);
        $this->assertSame($keptFda, $fresh->faqs[0]['answer']);

        $this->get('/encyclopedia/elamipretide')
            ->assertOk()
            ->assertSee('mitochondrial cardiolipin binder', false)
            ->assertSee('19 September 2025', false)
            ->assertSee('146bf34c-76f2-48db-ac07-fb29cce2cd75', false)
            ->assertSee('at least 30 kg', false)
            ->assertSee('Research-use-only listings sold as elamipretide or SS-31 are not Forzinity.', false)
            ->assertDontSee('Category 2', false)
            ->assertDontSee('not approved for human use by any regulatory agency', false)
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/EncyclopediaArticleDetail')
                ->where('molecularInfo.casNumber', '736992-21-5')
                ->where('molecularInfo.formula', 'C32H49N9O5')
                ->where('slug', 'elamipretide')
            );
    }

    public function test_seeder_skips_missing_category_missing_post_and_slug_collision(): void
    {
        $missing = new ElamipretideForzinitySeeder;
        $missing->run();
        $this->assertTrue($missing->report['missing_category']);
        $this->assertFalse($missing->report['updated']);
        // The pemvidutide migration seeds one category and post into every test database.
        $this->assertSame(0, ProductCategory::query()->whereRaw('LOWER(slug) != ?', ['pemvidutide'])->count());
        $this->assertSame(0, EducationPost::query()->whereRaw('LOWER(slug) != ?', ['pemvidutide'])->count());

        $category = ProductCategory::create([
            'name' => 'Elamipretide',
            'slug' => 'elamipretide',
            'is_active' => true,
        ]);
        $noPost = new ElamipretideForzinitySeeder;
        $noPost->run();
        $this->assertTrue($noPost->report['missing_post']);
        $this->assertFalse($noPost->report['updated']);
        $this->assertSame(0, EducationPost::query()->whereRaw('LOWER(slug) != ?', ['pemvidutide'])->count());

        ProductCategory::create([
            'name' => 'Elamipretide duplicate',
            'slug' => 'Elamipretide',
            'is_active' => true,
        ]);
        $note = 'Category 2 marker that a collision must leave in place.';
        EducationPost::create([
            'title' => 'Elamipretide',
            'slug' => 'elamipretide',
            'product_category_id' => $category->id,
            'status' => 'published',
            'regulatory_important_note' => $note,
        ]);
        $collision = new ElamipretideForzinitySeeder;
        $collision->run();
        $this->assertTrue($collision->report['skipped_slug_collision']);
        $this->assertFalse($collision->report['updated']);
        $this->assertSame(2, ProductCategory::query()->whereRaw('LOWER(slug) != ?', ['pemvidutide'])->count());
        $this->assertSame($note, EducationPost::query()->where('slug', 'elamipretide')->firstOrFail()->regulatory_important_note);
    }

    public function test_shell_parse_is_the_first_fill_source(): void
    {
        $root = database_path('seeders/data/encyclopedia-shells/Elamipretide');
        $body = (string) file_get_contents($root.'/body.md');
        $drug = $this->field($body, 'drugStatus', 'halfLife');
        $note = $this->field($body, 'regulatoryImportantNote', 'regulatorySubsections');
        $this->assertStringContainsString('mitochondrial cardiolipin binder', $drug);
        $this->assertStringContainsString('19 September 2025', $drug);
        $this->assertStringContainsString('at least 30 kg', $drug);
        $this->assertStringContainsString('not Forzinity', str_replace('*', '', $drug));
        $this->assertStringNotContainsString('Category 2', $note);
        $this->assertStringNotContainsString('PCAC', $note);
        $this->assertStringNotContainsString('503A', $body);
        $this->assertStringNotContainsString('CHANGES', $body);
        $this->assertStringNotContainsString('Unchanged from PR', $body);

        $parsed = (new EncyclopediaShellParser)->parse(
            'Elamipretide',
            $body,
            (string) file_get_contents($root.'/meta.md')
        );
        $blob = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($blob);
        $this->assertStringContainsString('146bf34c-76f2-48db-ac07-fb29cce2cd75', $blob);
        $this->assertStringContainsString('736992-21-5', $blob);
        $this->assertSame('C32H49N9O5', $parsed['molecular_formula']);
        $this->assertSame('639.8 g/mol (free base; PubChem/USAN)', $parsed['molecular_weight']);
        $this->assertCount(7, $parsed['faqs']);
        $this->assertSame('Is elamipretide FDA-approved?', $parsed['faqs'][0]['question']);
        $this->assertSame('Why is Forzinity’s approval called “accelerated”?', $parsed['faqs'][6]['question']);
        $this->assertCount(9, $parsed['references']);
        $this->assertStringNotContainsString('Category 2', $blob);
        $this->assertStringNotContainsString('Editor notes', $blob);
        $this->assertStringNotContainsString('Colin', $blob);
        $this->assertStringNotContainsString('do not publish', $blob);
    }

    public function test_compare_page_adds_elamipretide_mots_c_faqs_only(): void
    {
        $this->assertNull(ElamipretideMotsCCompareFaqs::forSlug('retatrutide-vs-tirzepatide'));
        $this->assertNull(ElamipretideMotsCCompareFaqs::forSlug('mots-c-vs-ss-31'));

        ProductCategory::create([
            'name' => 'Elamipretide',
            'slug' => 'elamipretide',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'MOTS-c',
            'slug' => 'mots-c',
            'is_active' => true,
        ]);

        $copy = ElamipretideMotsCCompareFaqs::forSlug('elamipretide-vs-mots-c');
        $this->assertNotNull($copy);
        $blob = json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertStringNotContainsString('which should you take', (string) $blob);
        $this->assertDoesNotMatchRegularExpression('/\b\d+(?:\.\d+)?\s*mg\b/i', (string) $blob);

        $this->get('/compare/mots-c-vs-elamipretide')
            ->assertRedirect('/compare/elamipretide-vs-mots-c');

        $this->get('/compare/elamipretide-vs-mots-c')
            ->assertOk()
            ->assertSee('Does SS-31 need to come before MOTS-c?', false)
            ->assertSee('listings on this page different compounds?', false)
            ->assertSee('19 September 2025', false)
            ->assertSee('not that product', false)
            ->assertSee('39940712', false)
            ->assertSee('no validated stacking or sequencing protocol', false)
            ->assertDontSee('which should you take', false)
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/CompareCompoundVs')
                ->has('faqs', 2)
                ->where('faqs.0.q', 'Does SS-31 need to come before MOTS-c?')
                ->where('faqs.1.q', 'Are “SS-31” and “elamipretide” listings on this page different compounds?')
                ->has('references', 2)
                ->where('references.0.url', 'https://www.fda.gov/news-events/press-announcements/fda-grants-accelerated-approval-first-treatment-barth-syndrome')
                ->where('references.1.url', 'https://pubmed.ncbi.nlm.nih.gov/39940712/')
                ->where('seo.schema.1.mainEntity.0.name', 'Does SS-31 need to come before MOTS-c?')
            );
    }

    /**
     * @param  list<mixed>  $references
     * @return list<string>
     */
    private function referenceUrls(array $references): array
    {
        $urls = [];
        foreach ($references as $reference) {
            if (! is_array($reference)) {
                $urls[] = '';

                continue;
            }
            $link = $reference['links'][0]['url'] ?? '';
            $urls[] = is_string($link) ? $link : '';
        }

        return $urls;
    }

    private function field(string $body, string $name, string $next): string
    {
        $start = strpos($body, "## Field: {$name}");
        $end = strpos($body, "## Field: {$next}");
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($body, $start, $end - $start);
    }
}
