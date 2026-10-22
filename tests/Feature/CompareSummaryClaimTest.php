<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\CompoundOgImageController;
use App\Models\Brand;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ComparePageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class CompareSummaryClaimTest extends TestCase
{
    use RefreshDatabase;

    private const CAGRI_CLAIM = 'Cagrilintide is a long-acting amylin analog. The combination with semaglutide showed up to 25% body weight reduction.';

    private const CAGRI_LINE = 'Cagrilintide is an amylin analog studied in obesity research. Research use only.';

    private const IPAMORELIN_CLAIM = 'Ipamorelin is the most selective GHS, stimulating GH release without affecting cortisol.';

    private const IPAMORELIN_LINE = 'Ipamorelin is a growth hormone secretagogue studied in endocrine research. Research use only.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_compare_summaries_and_og_text_drop_outcome_claims(): void
    {
        $brand = Brand::create([
            'name' => 'Listed Research',
            'slug' => 'listed-research',
            'is_active' => true,
        ]);

        $cagrilintide = $this->compound($brand, 'Cagrilintide', 'cagrilintide', 22.00, self::CAGRI_CLAIM);
        $this->compound($brand, 'Semaglutide', 'semaglutide', 30.00, 'Semaglutide is a GLP-1 receptor agonist studied in metabolic research. Research use only.');
        $ipamorelin = $this->compound($brand, 'Ipamorelin', 'ipamorelin', 19.00, self::IPAMORELIN_CLAIM);

        $single = $this->get('/compare/cagrilintide');
        $single->assertOk();
        $single->assertDontSee('up to 25% body weight reduction', false);
        $single->assertDontSee('25%', false);
        $props = [];
        $single->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];

            return $page->component('Frontend/CompareCompound');
        });

        $this->assertSame('Cagrilintide', $props['compound']['name']);
        $this->assertSame(self::CAGRI_LINE, $props['compound']['summary']);
        $this->assertEquals(22.0, $props['compound']['products'][0]['price']);
        $seo = ComparePageSeo::forCompound('Cagrilintide', 1, false);
        $this->assertSame($seo['title'], $props['seo']['title']);
        $this->assertSame($seo['description'], $props['seo']['description']);

        $vs = $this->get('/compare/cagrilintide-vs-semaglutide');
        $vs->assertOk();
        $vs->assertDontSee('up to 25% body weight reduction', false);
        $vs->assertDontSee('25%', false);
        $vsProps = [];
        $vs->assertInertia(function ($page) use (&$vsProps) {
            $vsProps = $page->toArray()['props'];

            return $page->component('Frontend/CompareCompoundVs');
        });

        $this->assertSame('Cagrilintide', $vsProps['a']['name']);
        $this->assertSame(self::CAGRI_LINE, $vsProps['a']['summary']);
        $this->assertSame('GLP1-S', $vsProps['b']['name']);
        $this->assertEquals(22.0, $vsProps['a']['top_vendors'][0]['price']);

        $ipa = $this->get('/compare/ipamorelin');
        $ipa->assertOk();
        $ipa->assertDontSee('most selective', false);
        $ipaProps = [];
        $ipa->assertInertia(function ($page) use (&$ipaProps) {
            $ipaProps = $page->toArray()['props'];

            return $page->component('Frontend/CompareCompound');
        });

        $this->assertSame('Ipamorelin', $ipaProps['compound']['name']);
        $this->assertSame(self::IPAMORELIN_LINE, $ipaProps['compound']['summary']);
        $this->assertEquals(19.0, $ipaProps['compound']['products'][0]['price']);
        $ipaSeo = ComparePageSeo::forCompound('Ipamorelin', 1, false);
        $this->assertSame($ipaSeo['title'], $ipaProps['seo']['title']);
        $this->assertSame($ipaSeo['description'], $ipaProps['seo']['description']);

        $this->assertOgTagline($cagrilintide, self::CAGRI_LINE, 'up to 25% body weight reduction');
        $this->assertOgTagline($ipamorelin, self::IPAMORELIN_LINE, 'most selective');
    }

    private function assertOgTagline(ProductCategory $category, string $expected, string $forbidden): void
    {
        $category->load('educationPost');
        $tagline = (new ReflectionMethod(CompoundOgImageController::class, 'tagline'))
            ->invoke(new CompoundOgImageController, $category->educationPost, $category, null);

        $this->assertSame($expected, $tagline);
        $this->assertStringNotContainsString($forbidden, (string) $tagline);

        $html = view('og.compound', [
            'category' => $category,
            'fullName' => $category->educationPost?->peptide_full_name,
            'tagline' => $tagline,
            'eyebrow' => 'Peptide Compound Guide',
            'vendorCount' => 1,
        ])->render();

        $this->assertStringContainsString($expected, $html);
        $this->assertStringNotContainsString($forbidden, $html);
    }

    private function compound(Brand $brand, string $name, string $slug, float $price, string $copy): ProductCategory
    {
        $category = ProductCategory::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => $name,
            'slug' => $slug.'-article',
            'overview' => $copy,
            'description' => $copy,
            'status' => 'published',
            'product_category_id' => $category->id,
        ]);
        Product::create([
            'name' => $name.' — 10mg',
            'slug' => $slug.'-10mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'size_mg' => '10mg',
            'price' => $price,
            'status' => 'active',
            'hidden' => false,
        ]);

        return $category;
    }
}
