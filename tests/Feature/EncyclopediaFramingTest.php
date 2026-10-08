<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaFraming;
use Database\Seeders\EncyclopediaFramingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncyclopediaFramingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_nonpeptide_stubs_drop_peptide_encyclopedia_copy(): void
    {
        $pages = [
            'slu-pp-332' => 'not a peptide',
            'orforglipron' => 'nonpeptide',
            'bacteriostatic-water' => 'diluent',
            'hCG' => 'glycoprotein',
            'methylene-blue' => 'phenothiazine',
            'NMN' => '/encyclopedia/nad',
            'acetic-acid' => 'carboxylic',
            'Ibutamoren' => 'ghrelin',
            'ACE-031' => 'ActRIIB',
            'tesofensine' => 'not a peptide',
            'L-Carnitine' => 'not a peptide',
            'bam-15' => 'not a peptide',
            '9-Me-BC' => 'not a peptide',
            'Bromantane' => 'not a peptide',
            'Salidroside' => 'not a peptide',
            'Spermidine' => 'not a peptide',
        ];

        foreach ($pages as $slug => $needle) {
            ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'is_active' => true,
            ]);
        }

        foreach ($pages as $slug => $needle) {
            $response = $this->get('/encyclopedia/'.$slug)->assertOk();
            $body = $response->getContent();
            $this->assertStringNotContainsString('Peptide Encyclopedia', $body, $slug);
            $this->assertStringNotContainsString('Comprehensive guide to', $body, $slug);
            $this->assertStringContainsString($needle, $body, $slug);
            $this->assertStringNotContainsString('products?category=', $body, $slug);
        }

        $nmn = $this->get('/encyclopedia/NMN')->assertOk()->getContent();
        $this->assertStringNotContainsString('/encyclopedia/NAD+', $nmn);
        $this->assertStringNotContainsString('/encyclopedia/nad-plus', $nmn);
        $this->assertStringNotContainsString('0.6%', $this->get('/encyclopedia/acetic-acid')->getContent());
        $this->assertStringNotContainsString('25 mg', $this->get('/encyclopedia/Ibutamoren')->getContent());
    }

    public function test_hyphen_canonicals_redirect_space_and_short_aliases(): void
    {
        ProductCategory::create(['name' => 'HGH 191AA', 'slug' => 'HGH 191AA', 'is_active' => true]);
        ProductCategory::create(['name' => 'Phosphate Buffered Saline', 'slug' => 'Phosphate Buffered Saline', 'is_active' => true]);
        ProductCategory::create(['name' => 'Sterile Water', 'slug' => 'Sterile Water', 'is_active' => true]);
        ProductCategory::create(['name' => 'Thymosin Beta-4 Fragment 1-4', 'slug' => 'Thymosin Beta-4 Fragment 1-4', 'is_active' => true]);
        ProductCategory::create(['name' => 'Alpha-Klotho LR', 'slug' => 'Alpha-Klotho LR', 'is_active' => true]);
        ProductCategory::create(['name' => 'N-Acetyl Larazotide', 'slug' => 'N-Acetyl Larazotide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);

        $this->get('/encyclopedia/'.rawurlencode('HGH 191AA'))
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/hgh-191aa');
        $this->get('/encyclopedia/PBS')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/phosphate-buffered-saline');
        $this->get('/encyclopedia/'.rawurlencode('Sterile Water'))
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/sterile-water');
        $this->get('/encyclopedia/'.rawurlencode('Thymosin Beta-4 Fragment 1-4'))
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/thymosin-beta-4-fragment-1-4');
        $this->get('/encyclopedia/'.rawurlencode('Alpha-Klotho LR'))
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/alpha-klotho-lr');
        $this->get('/encyclopedia/'.rawurlencode('N-Acetyl Larazotide'))
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/n-acetyl-larazotide');

        $this->get('/encyclopedia/hgh-191aa')->assertOk()->assertSee('not a peptide', false);
        $this->get('/encyclopedia/phosphate-buffered-saline')->assertOk()->assertSee('not a peptide', false);
        $this->get('/encyclopedia/sterile-water')->assertOk()->assertSee('diluent', false);
        $this->get('/encyclopedia/thymosin-beta-4-fragment-1-4')->assertOk()
            ->assertSee('Ac-SDKP', false)
            ->assertSee('TB-500', false)
            ->assertSee('404', false);
        $this->get('/encyclopedia/alpha-klotho-lr')->assertOk()
            ->assertSee('CAS field is blank', false)
            ->assertSee('not a peptide', false);
        $this->get('/encyclopedia/n-acetyl-larazotide')->assertOk()
            ->assertSee('larazotide acetate', false);
        $this->get('/encyclopedia/vitamin-b12')->assertOk()->assertSee('cobalamin', false);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('/encyclopedia/hgh-191aa', $xml);
        $this->assertStringContainsString('/encyclopedia/alpha-klotho-lr', $xml);
        $this->assertStringContainsString('/encyclopedia/n-acetyl-larazotide', $xml);
        $this->assertStringNotContainsString('HGH%20191AA', $xml);
        $this->assertStringNotContainsString('Alpha-Klotho%20LR', $xml);
    }

    public function test_identity_pages_keep_peptide_class_where_it_is_accurate(): void
    {
        foreach ([
            'oxytocin', 'gonadorelin', 'ghk-basic', 'Cerebrolysin', 'Adalank',
            'Eloralintide', 'Thymulin', 'Crystagen', 'Setmelanotide', 'Teduglutide',
            'HMG', 'Teriparatide', 'MIF-1', 'Vesilut', 'Vesugen', 'PNC-28', 'Cortexin',
        ] as $slug) {
            ProductCategory::create(['name' => $slug, 'slug' => $slug, 'is_active' => true]);
        }

        $this->get('/encyclopedia/oxytocin')->assertOk()->assertSee('Pitocin', false)->assertDontSee('Peptide Encyclopedia');
        $this->get('/encyclopedia/gonadorelin')->assertOk()->assertSee('Factrel', false);
        $this->get('/encyclopedia/ghk-basic')->assertOk()->assertSee('/encyclopedia/GHK-Cu', false);
        $this->get('/encyclopedia/Cerebrolysin')->assertOk()->assertSee('not one peptide', false);
        $this->get('/encyclopedia/Adalank')->assertOk()->assertSee('unsettled', false);
        $this->get('/encyclopedia/Eloralintide')->assertOk()->assertSee('amylin', false)->assertSee('not a GLP-1', false);
        $this->get('/encyclopedia/Thymulin')->assertOk()->assertSee('thymosin', false);
        $this->get('/encyclopedia/Crystagen')->assertOk()->assertSee('CAS field is blank', false);
        $this->get('/encyclopedia/Cortexin')->assertOk()->assertSee('CAS field is blank', false);
        $this->get('/encyclopedia/Setmelanotide')->assertOk()->assertSee('IMCIVREE', false)->assertSee('not melanotan', false)->assertDontSee('PCAC');
        $this->get('/encyclopedia/Teduglutide')->assertOk()->assertSee('GATTEX', false)->assertSee('GLP-2', false)->assertDontSee('PCAC');
        $this->get('/encyclopedia/HMG')->assertOk()->assertSee('menotropins', false)->assertSee('HMG-CoA', false)->assertSee('not hCG', false)->assertDontSee('PCAC');
        $this->get('/encyclopedia/Teriparatide')->assertOk()->assertSee('Forteo', false)->assertDontSee('PCAC')->assertDontSee('mcg');
        $this->get('/encyclopedia/MIF-1')->assertOk()->assertSee('Pro-Leu-Gly', false)->assertSee('not melanotan', false);
        $this->get('/encyclopedia/Vesilut')->assertOk()->assertSee('Glu-Asp', false)->assertSee('Lys-Glu-Asp', false);
        $this->get('/encyclopedia/Vesugen')->assertOk()->assertSee('Lys-Glu-Asp', false);
        $pnc = $this->get('/encyclopedia/PNC-28')->assertOk()->getContent();
        $this->assertStringContainsString('thin', $pnc);
        $this->assertStringNotContainsString('cures cancer', strtolower($pnc));
    }

    public function test_ordinary_peptide_stub_still_uses_peptide_encyclopedia_copy(): void
    {
        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);

        $this->get('/encyclopedia/BPC-157')
            ->assertOk()
            ->assertSee('Peptide Encyclopedia', false)
            ->assertDontSee('products?category=', false);
    }

    public function test_nad_phrases_are_patched_and_kpv_is_not_republished(): void
    {
        $nad = ProductCategory::create(['name' => 'NAD+', 'slug' => 'nad', 'is_active' => true]);
        EducationPost::create([
            'title' => 'NAD+',
            'slug' => 'nad',
            'product_category_id' => $nad->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'NAD+ is a coenzyme. While not technically a peptide, it is widely included in peptide therapy protocols due to its injectable format and complementary role in cellular repair pathways. The rest of the article stays.',
            'conclusion' => 'NAD+ occupies a unique position in the peptide and longevity research landscape as the molecule that connects cellular energy production to the fundamental biology of aging.',
            'faqs' => [[
                'question' => 'Is NAD+ a peptide?',
                'answer' => 'No. It is included in peptide therapy protocols due to its injectable format and complementary role in cellular repair and longevity pathways.',
            ]],
            'seo_page_title' => 'What is NAD+ - Peptide Encyclopedia - Peptidemap',
            'seo_description' => 'NAD+ (Nicotinamide Adenine Dinucleotide) is a coenzyme found in every living cell.',
        ]);

        $kpv = ProductCategory::create(['name' => 'KPV', 'slug' => 'KPV', 'is_active' => true]);
        EducationPost::create([
            'title' => 'KPV',
            'slug' => 'KPV',
            'product_category_id' => $kpv->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'KPV is a tripeptide kept as-is.',
            'seo_page_title' => 'Custom KPV title',
        ]);

        (new EncyclopediaFramingSeeder)->run();

        $nadPost = EducationPost::where('product_category_id', $nad->id)->first();
        $this->assertStringNotContainsString('peptide therapy protocols', $nadPost->overview);
        $this->assertStringContainsString('The rest of the article stays.', $nadPost->overview);
        $this->assertStringNotContainsString('peptide and longevity', $nadPost->conclusion);
        $this->assertSame('What is NAD+? Dinucleotide Coenzyme', $nadPost->seo_page_title);

        $kpvPost = EducationPost::where('product_category_id', $kpv->id)->first();
        $this->assertSame('KPV is a tripeptide kept as-is.', $kpvPost->overview);
        $this->assertSame('Custom KPV title', $kpvPost->seo_page_title);

        $this->get('/encyclopedia/nad')
            ->assertOk()
            ->assertSee('not a peptide', false)
            ->assertDontSee('Peptide Encyclopedia');
    }

    public function test_client_seo_fallback_does_not_invent_peptide_copy(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Frontend/EncyclopediaArticleDetail.vue'));
        $this->assertIsString($vue);
        $this->assertStringNotContainsString('framedAwayFromPeptide', $vue);
        $this->assertStringNotContainsString('Comprehensive guide to', $vue);
        $this->assertStringNotContainsString('this peptide', $vue);
    }

    public function test_protein_and_mixture_profiles_reject_peptide_identity(): void
    {
        $cases = [
            'Cerebrolysin' => ['Cerebrolysin hydrolysate is sold as a research peptide.', 'not one peptide'],
            'Alpha-Klotho LR' => ['Alpha-Klotho LR cas field is blank and vendors still call it a peptide.', 'not a peptide'],
            'Cortexin' => ['Cortexin cas field is blank and the listing calls the fraction a peptide.', 'not one peptide'],
        ];

        foreach ($cases as $slug => [$overview, $marker]) {
            $profile = EncyclopediaFraming::match($slug, $slug);
            $this->assertNotNull($profile, $slug);
            $this->assertTrue($profile->rejectsPeptideIdentity(), $slug);

            $category = ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'description' => $slug.' research peptide listing',
                'is_active' => true,
            ]);
            EducationPost::create([
                'title' => $slug,
                'slug' => $slug,
                'product_category_id' => $category->id,
                'status' => 'published',
                'show_in_encyclopedia' => true,
                'overview' => $overview,
                'seo_page_title' => 'What is '.$slug,
                'seo_description' => 'Laboratory notes for '.$slug,
            ]);
        }

        (new EncyclopediaFramingSeeder)->run();

        foreach ($cases as $slug => [$overview, $marker]) {
            $category = ProductCategory::where('slug', $slug)->first();
            $profile = EncyclopediaFraming::match($slug, $slug);
            $this->assertSame($profile->cardDescription(), $category->description, $slug);

            $post = EducationPost::where('product_category_id', $category->id)->first();
            $this->assertStringContainsString($marker, $post->overview, $slug);
            $this->assertNotSame($overview, $post->overview, $slug);
        }

        (new EncyclopediaFramingSeeder)->run();

        foreach ($cases as $slug => [$overview, $marker]) {
            $post = EducationPost::where('slug', $slug)->first();
            $this->assertStringContainsString($marker, $post->overview, $slug);
        }
    }
}
