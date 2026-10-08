<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorSetting;
use App\Support\RetatrutideCompareNarrative;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetatrutideComparePriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_faq_schema_matches_visible_text_and_the_page_drops_old_claims(): void
    {
        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $us = Location::create(['name' => 'United States']);
        $uk = Location::create(['name' => 'United Kingdom']);
        $usdBrand = Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
        $gbpBrand = Brand::create([
            'name' => 'UK Research',
            'slug' => 'uk-research',
            'is_active' => true,
        ]);
        VendorSetting::create(['brand_id' => $usdBrand->id, 'location_id' => $us->id]);
        VendorSetting::create(['brand_id' => $gbpBrand->id, 'location_id' => $uk->id]);

        Product::create([
            'name' => 'Example — 10mg',
            'slug' => 'example-10mg',
            'brand_id' => $usdBrand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'size_mg' => '10mg',
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
        ]);
        Product::create([
            'name' => 'SA-3R',
            'slug' => 'sa-3r',
            'brand_id' => $usdBrand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'size_mg' => '10mg',
            'price' => 4.95,
            'status' => 'active',
            'hidden' => false,
        ]);
        Product::create([
            'name' => 'UK — 10mg',
            'slug' => 'uk-10mg',
            'brand_id' => $gbpBrand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'size_mg' => '10mg',
            'price' => 30,
            'status' => 'active',
            'hidden' => false,
        ]);

        $response = $this->get('/compare/retatrutide');
        $response->assertOk();
        $response->assertDontSee('4.21', false);
        $response->assertDontSee('verified vendors', false);
        $response->assertDontSee('Best price', false);
        $response->assertDontSee('/blog/retatrutide-vs-tirzepatide', false);
        $response->assertDontSee('What is the cheapest Retatrutide?', false);

        $response->assertInertia(function ($page) {
            $props = $page->toArray()['props'];
            $faqs = $props['compound']['faqs'];
            $schema = collect($props['seo']['schema'])->firstWhere('@type', 'FAQPage');

            $this->assertNotNull($schema);
            $this->assertCount(count($faqs), $schema['mainEntity']);
            foreach ($faqs as $i => $faq) {
                $this->assertSame($faq['q'], $schema['mainEntity'][$i]['name']);
                $this->assertSame($faq['a'], $schema['mainEntity'][$i]['acceptedAnswer']['text']);
                $this->assertStringNotContainsString('$', $faq['q'].$faq['a']);
                $this->assertStringNotContainsString('4.21', $faq['a']);
            }

            $narrative = RetatrutideCompareNarrative::payload()['faqs'];
            $checked = $props['compound']['price_stats']['prices_updated_human'];
            $tokens = [
                '{listing_count}' => '3',
                '{vendor_count}' => '2',
                '{prices_updated_human}' => $checked,
            ];
            foreach ($narrative as $i => $faq) {
                $this->assertSame(strtr($faq['q'], $tokens), $faqs[$i]['q']);
                $this->assertSame(strtr($faq['a'], $tokens), $faqs[$i]['a']);
            }
            $this->assertSame('Is GLP3-R the same as Retatrutide?', $faqs[7]['q']);

            $this->assertSame(
                'No. Retatrutide (LY3437943) is an investigational compound from Eli Lilly and is not approved by the FDA or the EMA for any use. TRIUMPH-1 and TRIUMPH-2 results were published on 29 September 2026, and Lilly has said it plans a U.S. filing in Q1 2027. A filing plan is not approval. Research-use vials sold online are not Lilly products and are not clinical-trial material.',
                $faqs[4]['a']
            );
            $this->assertStringContainsString(
                'The cheapest retatrutide vial is not always the cheapest per mg',
                $props['compound']['price_intro']
            );

            $product = collect($props['seo']['schema'])->firstWhere('@type', 'Product');
            $this->assertSame('40.00', $product['offers']['lowPrice']);
            $this->assertSame('USD', $product['offers']['priceCurrency']);
            $this->assertSame($props['compound']['price_intro'], $product['description']);
            $this->assertStringNotContainsString('4.21', $product['description']);
            $this->assertStringNotContainsString('$', $product['description']);

            $ids = array_column($props['compound']['price_stats']['per_mg']['rows'], 'listing');
            $this->assertSame(['Example — 10mg'], $ids);
            $this->assertSame(1, $props['compound']['price_stats']['per_mg']['excluded_reasons']['non_usd']);
            $this->assertSame(1, $props['compound']['price_stats']['per_mg']['excluded_reasons']['no_mg_in_name']);

            $gbp = collect($props['compound']['products'])->firstWhere('brand_slug', 'uk-research');
            $this->assertSame('GBP', $gbp['currency_code']);
            $this->assertSame('£', $gbp['currency_symbol']);

            return $page;
        });
    }

    public function test_code_names_resolve_live_products_and_fetch_the_blend_by_id(): void
    {
        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $blendCategory = ProductCategory::create([
            'name' => 'Retatrutide / Tirzepatide Blend',
            'slug' => 'retatrutide-tirzepatide-blend',
            'is_active' => true,
        ]);
        $brands = [
            'southern-aminos-llc' => Brand::create(['name' => 'Southern Aminos LLC', 'slug' => 'southern-aminos-llc', 'is_active' => true]),
            'instant-peptides' => Brand::create(['name' => 'Instant Peptides', 'slug' => 'instant-peptides', 'is_active' => true]),
            'nura-systems-llc' => Brand::create(['name' => 'Nura Peptides', 'slug' => 'nura-systems-llc', 'is_active' => true]),
            'oasis-lab' => Brand::create(['name' => 'Oasis Lab', 'slug' => 'oasis-lab', 'is_active' => true]),
            'vertex-labs' => Brand::create(['name' => 'Vertex Labs', 'slug' => 'vertex-labs', 'is_active' => true]),
        ];

        $this->catalogProduct($brands['southern-aminos-llc'], $category, 1087, 'SA-3R — 30mg Single Vial', 'sa-3r-30mg-single-vial');
        $this->catalogProduct($brands['instant-peptides'], $category, 2861, 'GLP-3 RT — 10mg · Vial', 'glp-3-rt-10mg-vial-1');
        $this->catalogProduct($brands['nura-systems-llc'], $category, 4049, 'GLP-3R — 10MG', 'glp-3r-10mg-3');
        $this->catalogProduct($brands['oasis-lab'], $category, 1585, 'GLP3(R) — 10mg', 'glp3r-10mg');
        $this->catalogProduct($brands['vertex-labs'], $category, 5552, 'Reta GLP-3 — 10mg', 'reta-glp-3-10mg');
        $this->catalogProduct(
            $brands['southern-aminos-llc'],
            $blendCategory,
            1065,
            'Retatrutide / Tirzepatide Blend (35mg)',
            'sa-3r-sa-2t-blend-34mg',
            '35mg'
        );

        $this->get('/compare/retatrutide')->assertOk()->assertInertia(function ($page) {
            $codeNames = $page->toArray()['props']['compound']['code_names'];
            $labels = array_column($codeNames['rows'], 'link_label');
            $this->assertSame([
                'SA-3R — 30mg Single Vial',
                'GLP-3 RT — 10mg · Vial',
                'GLP-3R — 10MG',
                'GLP3(R) — 10mg',
                'Reta GLP-3 — 10mg',
            ], $labels);
            $this->assertSame('/product/southern-aminos-llc/sa-3r-30mg-single-vial/1087', $codeNames['rows'][0]['url']);
            $this->assertSame('/product/oasis-lab/glp3r-10mg/1585', $codeNames['rows'][3]['url']);
            $this->assertSame('SA-3R / SA-2T BLEND 34mg', $codeNames['blend']['link_label']);
            $this->assertSame('/product/southern-aminos-llc/sa-3r-sa-2t-blend-34mg/1065', $codeNames['blend']['url']);
            $this->assertStringNotContainsString('35mg', $codeNames['blend']['link_label']);
            $this->assertArrayNotHasKey('size_mg', $codeNames['blend']);

            $productIds = array_column($page->toArray()['props']['compound']['products'], 'id');
            $this->assertNotContains(1065, $productIds);
            $statIds = array_column($page->toArray()['props']['compound']['price_stats']['per_mg']['rows'], 'id');
            $this->assertNotContains(1065, $statIds);

            $hrefs = array_column($page->toArray()['props']['compound']['related_reading'], 'href');
            $this->assertContains('/encyclopedia/retatrutide', $hrefs);
            $this->assertContains('/compare/retatrutide-vs-tirzepatide', $hrefs);
            $this->assertNotContains('/encyclopedia/Retatrutide', $hrefs);
            $this->assertNotContains('/blog/retatrutide-vs-tirzepatide', $hrefs);

            return $page;
        });

        Product::where('id', 1585)->update(['hidden' => true]);

        $this->get('/compare/retatrutide')->assertOk()->assertInertia(function ($page) {
            $labels = array_column($page->toArray()['props']['compound']['code_names']['rows'], 'link_label');
            $this->assertNotContains('GLP3(R) — 10mg', $labels);
            $this->assertCount(4, $labels);

            return $page;
        });
    }

    public function test_testing_post_link_renders_only_when_the_blog_is_published(): void
    {
        ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);

        $testing = '/blog/retatrutide-testing-coa-limits';
        $coa = '/blog/how-to-verify-a-peptide-vendor-certificate-of-analysis';
        $prefix = 'Some vendors publish a certificate of analysis (CoA) from an independent lab. Others publish an in-house sheet or nothing. A CoA only tells you something if it matches the lot, names the lab, and can be checked. ';
        $short = $prefix.'Our guides cover how to verify a peptide CoA and which vendors use which testing labs.';
        $full = $prefix."Our guides cover how to verify a peptide CoA, what retatrutide testing can and can't show, and which vendors use which testing labs.";

        $live = $this->get('/compare/retatrutide');
        $live->assertOk();
        $this->assertTrue($this->hrefsInclude($live, $testing));
        $this->assertTrue($this->hrefsInclude($live, $coa));
        $this->assertTrue($this->hrefsInclude($live, '/testing-labs'));
        $this->assertTrue($this->hrefsInclude($live, '/compare/retatrutide-vs-tirzepatide'));
        $this->assertFalse($this->hrefsInclude($live, '/blog/retatrutide-vs-tirzepatide'));
        $this->assertSame($full, $this->testingBulletText($live));

        Blog::where('slug', 'retatrutide-testing-coa-limits')->update(['status' => 'draft']);

        $draft = $this->get('/compare/retatrutide');
        $draft->assertOk();
        $this->assertFalse($this->hrefsInclude($draft, $testing));
        $this->assertTrue($this->hrefsInclude($draft, $coa));
        $this->assertTrue($this->hrefsInclude($draft, '/testing-labs'));
        $this->assertTrue($this->hrefsInclude($draft, '/compare/retatrutide-vs-tirzepatide'));
        $this->assertFalse($this->hrefsInclude($draft, '/blog/retatrutide-vs-tirzepatide'));
        $this->assertSame($short, $this->testingBulletText($draft));

        Blog::where('slug', 'retatrutide-testing-coa-limits')->delete();

        $missing = $this->get('/compare/retatrutide');
        $missing->assertOk();
        $this->assertFalse($this->hrefsInclude($missing, $testing));
        $this->assertSame($short, $this->testingBulletText($missing));
    }

    private function catalogProduct(Brand $brand, ProductCategory $category, int $id, string $name, string $slug, string $size = '10mg'): void
    {
        $product = new Product([
            'name' => $name,
            'slug' => $slug,
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'size_mg' => $size,
            'price' => 50,
            'status' => 'active',
            'hidden' => false,
        ]);
        $product->id = $id;
        $product->save();
    }

    private function hrefsInclude(\Illuminate\Testing\TestResponse $response, string $href): bool
    {
        $props = null;
        $response->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];

            return $page;
        });

        $found = false;
        $walk = function ($value) use (&$walk, &$found, $href) {
            if ($found) {
                return;
            }
            if (is_string($value) && $value === $href) {
                $found = true;

                return;
            }
            if (is_array($value)) {
                foreach ($value as $child) {
                    $walk($child);
                }
            }
        };
        $walk($props['compound']['why_prices']);
        $walk($props['compound']['related_reading']);

        return $found;
    }

    private function testingBulletText(\Illuminate\Testing\TestResponse $response): string
    {
        $props = null;
        $response->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];

            return $page;
        });

        foreach ($props['compound']['why_prices']['bullets'] as $bullet) {
            if ($bullet['title'] !== 'Third-party testing and CoA availability.') {
                continue;
            }

            return implode('', array_column($bullet['parts'], 'text'));
        }

        $this->fail('Missing the third-party testing bullet.');
    }
}
