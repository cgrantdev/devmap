<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\ApprovedMedicines;
use App\Support\CatalogCrossLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Advantage Collective report (Nov 12, 2026): Peptides tab not saving,
 * duplicate intros and FAQs on /compare/{slug}, intro link style, the
 * calculator chip label, and encyclopedia template text and FAQ schema.
 */
class AgencyCompoundSeoFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_compound_seo_editor_saves_the_peptides_block_and_the_compare_block(): void
    {
        $category = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        $admin = User::create([
            'name' => 'QA Admin',
            'email' => 'qa-seo-admin@example.test',
            'password' => bcrypt('x'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->put('/admin/seo/categories/'.$category->id, [
                'peptides' => [
                    'title' => 'BPC-157 Peptides From Vetted Vendors',
                    'meta' => 'Peptides meta.',
                    'h1' => 'Buy BPC-157 From Vetted Vendors',
                    'intro' => '<p>Peptides intro.</p>',
                    'faqs' => [['q' => 'Where can I buy BPC-157?', 'a' => 'From the vendors listed here.']],
                ],
                'compare' => [
                    'title' => 'BPC-157 Prices: Compare Vendor BPC-157 Pricing',
                    'meta' => 'Compare meta.',
                    'h1' => 'BPC-157 Price Comparison',
                    'intro' => '<p>Compare intro.</p>',
                    'faqs' => [['q' => 'How much does BPC-157 cost?', 'a' => 'It depends on the vendor.']],
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin/seo/categories/'.$category->id.'/edit');

        $category->refresh();
        $this->assertSame('BPC-157 Peptides From Vetted Vendors', $category->products_custom_title);
        $this->assertSame('Peptides meta.', $category->products_custom_meta_description);
        $this->assertSame('Buy BPC-157 From Vetted Vendors', $category->products_custom_h1);
        $this->assertSame('<p>Peptides intro.</p>', $category->products_custom_intro_html);
        $this->assertSame([['q' => 'Where can I buy BPC-157?', 'a' => 'From the vendors listed here.']], $category->products_custom_faqs);
        $this->assertSame('BPC-157 Price Comparison', $category->compare_custom_h1);
        $this->assertSame([['q' => 'How much does BPC-157 cost?', 'a' => 'It depends on the vendor.']], $category->compare_custom_faqs);

        $this->actingAs($admin)
            ->get('/admin/seo/categories/'.$category->id.'/edit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/SeoCategoryEdit')
                ->where('category.peptides.h1', 'Buy BPC-157 From Vetted Vendors')
                ->where('category.compare.h1', 'BPC-157 Price Comparison')
            );
    }

    public function test_agency_role_can_save_the_peptides_h1_alone(): void
    {
        $category = ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);
        $agency = User::create([
            'name' => 'Agency',
            'email' => 'qa-agency@example.test',
            'password' => bcrypt('x'),
            'role' => 'seo_agency',
        ]);
        $agency->markEmailAsVerified();

        $this->actingAs($agency)
            ->put('/admin/seo/categories/'.$category->id, [
                'peptides' => ['title' => '', 'meta' => '', 'h1' => 'Tirzepatide Vendors', 'intro' => '', 'faqs' => []],
                'compare' => ['title' => '', 'meta' => '', 'h1' => '', 'intro' => '', 'faqs' => []],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $category->refresh();
        $this->assertSame('Tirzepatide Vendors', $category->products_custom_h1);
        $this->assertNull($category->compare_custom_h1);
    }

    public function test_compare_page_agency_intro_and_faqs_replace_the_defaults(): void
    {
        $category = $this->listedCompound('BPC-157', 'bpc-157');

        $defaults = $this->props('/compare/bpc-157');
        $this->assertIsString($defaults['compound']['summary']);
        $this->assertNotSame('', $defaults['compound']['summary']);
        $this->assertSame('How do BPC-157 listings compare across vendors?', $defaults['compound']['faqs'][0]['q']);

        $intro = '<p>Compare prices, or see <a href="/product/bpc-157">BPC-157 for sale</a>.</p>';
        $category->forceFill([
            'compare_custom_intro_html' => $intro,
            'compare_custom_faqs' => [
                ['q' => 'How much does BPC-157 cost?', 'a' => 'It depends on the vendor, size and format.'],
                ['q' => 'Does Peptidemap sell BPC-157?', 'a' => 'No. Peptidemap lists vendors and sells nothing.'],
                ['q' => '   ', 'a' => 'Dropped: no question.'],
            ],
        ])->save();

        $response = $this->get('/compare/bpc-157');
        $response->assertOk();
        $response->assertDontSee('How do BPC-157 listings compare across vendors?', false);
        $props = $this->inertiaProps($response);

        $this->assertSame($intro, $props['compound']['custom_intro']);
        $this->assertNull($props['compound']['summary']);
        $this->assertNull($props['compound']['price_intro']);
        $this->assertSame([
            ['q' => 'How much does BPC-157 cost?', 'a' => 'It depends on the vendor, size and format.'],
            ['q' => 'Does Peptidemap sell BPC-157?', 'a' => 'No. Peptidemap lists vendors and sells nothing.'],
        ], $props['compound']['faqs']);

        $faqPage = collect($props['seo']['schema'])->firstWhere('@type', 'FAQPage');
        $this->assertIsArray($faqPage);
        $this->assertCount(2, $faqPage['mainEntity']);
        $this->assertSame('Does Peptidemap sell BPC-157?', $faqPage['mainEntity'][1]['name']);
        $this->assertSame(1, substr_count((string) $response->getContent(), '"name":"Does Peptidemap sell BPC-157?"'));
    }

    public function test_calculator_pill_reads_peptide_calculator(): void
    {
        $category = ProductCategory::create(['name' => 'Semaglutide', 'slug' => 'semaglutide', 'is_active' => true]);

        $groups = CatalogCrossLinks::forCategory($category, ['calculator'])['groups'];

        $this->assertNotEmpty($groups);
        $this->assertSame('Peptide calculator', $groups[0]['label']);
        $this->assertSame('Semaglutide peptide calculator', $groups[0]['links'][0]['label']);
        $this->assertStringNotContainsString('Dose calculator', json_encode($groups));
    }

    public function test_approved_medicine_notice_replaces_the_unapproved_line(): void
    {
        $this->assertSame(
            'Tirzepatide is FDA-approved as branded medicines. Research-grade tirzepatide listed by vendors is a different product and is for laboratory research use only. This article is for informational purposes and is not medical advice.',
            ApprovedMedicines::notice('Tirzepatide')
        );
        $this->assertStringContainsString('Research-grade methylene blue listed', ApprovedMedicines::notice('Methylene Blue'));
        $this->assertStringContainsString('Research-grade PT-141 listed', ApprovedMedicines::notice('PT-141'));
        $this->assertStringContainsString('Research-grade hCG listed', ApprovedMedicines::notice('hCG'));
        $this->assertTrue(ApprovedMedicines::has('Tesamorelin'));
        $this->assertFalse(ApprovedMedicines::has('bpc-157'));

        $tirzepatide = ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);
        $this->encyclopediaPost($tirzepatide, 'Tirzepatide');
        $bpc = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        $this->encyclopediaPost($bpc, 'BPC-157');

        $this->assertSame(ApprovedMedicines::notice('Tirzepatide'), $this->props('/encyclopedia/tirzepatide')['researchUseNotice']);
        $this->assertNull($this->props('/encyclopedia/BPC-157')['researchUseNotice']);
    }

    public function test_encyclopedia_footer_date_and_research_note_come_from_the_entry(): void
    {
        $category = ProductCategory::create(['name' => 'Kisspeptin', 'slug' => 'kisspeptin', 'is_active' => true]);
        $post = $this->encyclopediaPost($category, 'Kisspeptin', [
            'areas_of_research_note' => 'These findings come from human clinical studies and animal models.',
        ]);
        DB::table('education_posts')->where('id', $post->id)->update(['updated_at' => '2026-11-10 09:30:00']);

        $props = $this->props('/encyclopedia/kisspeptin');

        $this->assertSame(['iso' => '2026-11-10', 'label' => '10 November 2026'], $props['lastUpdated']);
        $this->assertSame('These findings come from human clinical studies and animal models.', $props['areasOfResearchNote']);

        $plain = ProductCategory::create(['name' => 'Selank', 'slug' => 'selank', 'is_active' => true]);
        $this->encyclopediaPost($plain, 'Selank');
        $this->assertSame('', $this->props('/encyclopedia/selank')['areasOfResearchNote']);
    }

    public function test_encyclopedia_sends_faq_schema_only_for_visible_faqs(): void
    {
        $visible = [
            ['question' => 'What is tirzepatide?', 'answer' => 'A dual GIP and GLP-1 receptor agonist.'],
        ];
        $tirzepatide = ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);
        $this->encyclopediaPost($tirzepatide, 'Tirzepatide', ['faqs' => $visible]);

        $props = $this->props('/encyclopedia/tirzepatide');
        $this->assertSame($visible, $props['faqs']);
        $faqPage = collect($props['seo']['schema'])->firstWhere('@type', 'FAQPage');
        $this->assertSame('What is tirzepatide?', $faqPage['mainEntity'][0]['name']);
        $this->assertSame('A dual GIP and GLP-1 receptor agonist.', $faqPage['mainEntity'][0]['acceptedAnswer']['text']);

        $semaglutide = ProductCategory::create(['name' => 'Semaglutide', 'slug' => 'semaglutide', 'is_active' => true]);
        $this->encyclopediaPost($semaglutide, 'Semaglutide', [
            'legacy_faqs' => [['question' => 'Hidden question?', 'answer' => 'Hidden answer.']],
        ]);

        $response = $this->get('/encyclopedia/semaglutide');
        $response->assertOk();
        $response->assertDontSee('Hidden question?', false);
        $response->assertDontSee('FAQPage', false);
        $this->assertSame([], $this->inertiaProps($response)['faqs']);
    }

    public function test_migration_archives_schema_only_faqs_and_keeps_allowlisted_ones(): void
    {
        $bpcFaqs = [['question' => 'What is BPC-157?', 'answer' => 'A research peptide.']];
        $tirzFaqs = [['question' => 'Why does dual agonism work better?', 'answer' => 'Old copy.']];

        $bpc = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        $bpcPost = $this->encyclopediaPost($bpc, 'BPC-157', ['faqs' => $bpcFaqs]);
        $tirzepatide = ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);
        $tirzPost = $this->encyclopediaPost($tirzepatide, 'Tirzepatide', ['faqs' => $tirzFaqs]);
        DB::table('education_posts')->where('id', $tirzPost->id)->update(['updated_at' => '2026-10-01 00:00:00']);

        $migration = require database_path('migrations/2026_11_14_100000_encyclopedia_visible_faqs_and_research_note.php');
        $migration->up();

        $this->assertSame($bpcFaqs, $bpcPost->fresh()->faqs);
        $this->assertNull($bpcPost->fresh()->legacy_faqs);

        $tirz = $tirzPost->fresh();
        $this->assertNull($tirz->faqs);
        $this->assertSame($tirzFaqs, $tirz->legacy_faqs);
        $this->assertSame('2026-10-01', $tirz->updated_at->toDateString());

        $props = $this->props('/encyclopedia/tirzepatide');
        $this->assertSame([], $props['faqs']);
        $this->assertNull(collect($props['seo']['schema'])->firstWhere('@type', 'FAQPage'));
    }

    public function test_encyclopedia_editor_saves_visible_faqs_and_the_research_note(): void
    {
        $category = ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);
        $post = $this->encyclopediaPost($category, 'Tirzepatide', [
            'legacy_faqs' => [['question' => 'Old?', 'answer' => 'Old.']],
        ]);
        $admin = User::create([
            'name' => 'QA Admin',
            'email' => 'qa-ency-admin@example.test',
            'password' => bcrypt('x'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/admin/encyclopedia-entries/'.$post->id.'/edit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('newFieldsReady', true)
                ->where('entry.faqs', [])
                ->where('entry.legacy_faqs.0.question', 'Old?')
            );

        $payload = [
            'title' => 'Tirzepatide',
            'education_tag' => 'Performance',
            'status' => 'published',
            'product_category_id' => $category->id,
            'areas_of_research_note' => '  Summarises phase 3 human trials.  ',
            'faqs' => [
                ['question' => ' Is tirzepatide FDA-approved? ', 'answer' => 'Yes, as Mounjaro and Zepbound.'],
                ['question' => '', 'answer' => ''],
            ],
        ];
        $this->actingAs($admin)
            ->post('/admin/encyclopedia-entries/'.$post->id, $payload)
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame([['question' => 'Is tirzepatide FDA-approved?', 'answer' => 'Yes, as Mounjaro and Zepbound.']], $post->faqs);
        $this->assertSame('Summarises phase 3 human trials.', $post->areas_of_research_note);
        $this->assertSame([['question' => 'Old?', 'answer' => 'Old.']], $post->legacy_faqs);

        $this->actingAs($admin)
            ->post('/admin/encyclopedia-entries/'.$post->id, array_merge($payload, [
                'faqs' => [['question' => 'Question without an answer?', 'answer' => '']],
            ]))
            ->assertSessionHasErrors(['faqs.0.answer']);

        $tooMany = array_fill(0, 13, ['question' => 'Q?', 'answer' => 'A.']);
        $this->actingAs($admin)
            ->post('/admin/encyclopedia-entries/'.$post->id, array_merge($payload, ['faqs' => $tooMany]))
            ->assertSessionHasErrors(['faqs']);
    }

    private function listedCompound(string $name, string $slug): ProductCategory
    {
        $category = ProductCategory::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
        $brand = Brand::create(['name' => 'Example Research', 'slug' => 'example-research', 'is_active' => true]);
        Product::create([
            'name' => $name.' 5mg',
            'slug' => $slug.'-5mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'size_mg' => '5mg',
            'price' => 30,
            'status' => 'active',
            'hidden' => false,
        ]);
        $this->encyclopediaPost($category, $name, [
            'overview' => $name.' is a synthetic peptide studied in tissue research. Research use only.',
        ]);

        return $category;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function encyclopediaPost(ProductCategory $category, string $title, array $attributes = []): EducationPost
    {
        // forceFill: legacy_faqs is not mass assignable.
        $post = new EducationPost;
        $post->forceFill(array_merge([
            'title' => $title,
            'slug' => strtolower($category->slug).'-entry',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => $title.' overview.',
        ], $attributes))->save();

        return $post;
    }

    /**
     * @return array<string, mixed>
     */
    private function props(string $url): array
    {
        $response = $this->get($url);
        $response->assertOk();

        return $this->inertiaProps($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function inertiaProps($response): array
    {
        $props = [];
        $response->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];

            return true;
        });

        return $props;
    }
}
