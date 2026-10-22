<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\VendorSetting;
use App\Support\BrandPageSeo;
use App\Support\ProductPageSeo;
use App\Support\SiteCopy;
use App\Support\VsPageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoSpotCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_site_description_replaces_a_stored_verified_suppliers_line(): void
    {
        $stored = 'The definitive platform for research peptide vendors — compare verified suppliers, prices, and COAs.';
        Setting::create(['key' => 'site_description', 'value' => $stored]);

        $this->assertStringContainsString('verified suppliers', $stored);
        $this->assertSame(SiteCopy::DESCRIPTION, SiteCopy::description($stored));
        $this->assertDoesNotMatchRegularExpression('/\bverified\b/i', SiteCopy::DESCRIPTION);
        $this->assertLessThanOrEqual(155, mb_strlen(SiteCopy::DESCRIPTION));

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('verified suppliers', $home);
        $this->assertStringContainsString(SiteCopy::DESCRIPTION, $home);
    }

    public function test_home_title_is_60_or_fewer_and_keeps_the_coupon_meta(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $title = $this->headContent($html, '/<title>(.*?)<\/title>/s');
        $description = $this->headContent($html, '/<meta name="description" content="(.*?)"/s');

        $before = 'Peptidemap — Compare research-peptide vendors, prices and coupons';
        $this->assertSame(65, mb_strlen($before));
        $this->assertSame('Peptidemap — Compare Peptide Vendors, Prices & Coupons', $title);
        $this->assertSame(54, mb_strlen($title), $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title));
        $this->assertSame("Live prices and coupon codes. Check each vendor's site for COAs.", $description);
    }

    public function test_brand_titles_stay_within_60_and_drop_reviews_when_none_exist(): void
    {
        $short = BrandPageSeo::title('Certified Pep');
        $this->assertSame('Certified Pep: Coupon Codes, Reviews & Prices — Peptidemap', $short);
        $this->assertSame(58, mb_strlen($short));
        $this->assertStringNotContainsString(' - ', $short);

        $platinum = BrandPageSeo::title('Platinum Lion Peptides');
        $bio = BrandPageSeo::title('BioLongevity Labs');
        $this->assertLessThanOrEqual(60, mb_strlen($platinum), $platinum);
        $this->assertLessThanOrEqual(60, mb_strlen($bio), $bio);
        $this->assertStringContainsString(' — Peptidemap', $platinum);
        $this->assertStringNotContainsString(' - Peptidemap', $platinum);

        $brand = Brand::create([
            'name' => 'Platinum Lion Peptides',
            'slug' => 'platinum-lion-peptides',
            'is_active' => true,
        ]);
        VendorSetting::create([
            'brand_id' => $brand->id,
            'coupon_code' => 'PMAP',
        ]);

        $html = $this->get('/brand/platinum-lion-peptides')->assertOk()->getContent();
        $title = $this->headContent($html, '/<title>(.*?)<\/title>/s');
        $description = $this->headContent($html, '/<meta name="description" content="(.*?)"/s');

        $this->assertLessThanOrEqual(60, mb_strlen($title), $title);
        $this->assertStringContainsString(' — ', $title);
        $this->assertStringNotContainsString(' - Peptidemap', $title);
        $this->assertStringContainsString('Coupon code PMAP', $description);
        $this->assertStringNotContainsString('real customer reviews', $description);
    }

    public function test_product_meta_and_schema_drop_claims_verified_and_ellipsis(): void
    {
        $category = ProductCategory::create([
            'name' => 'GHK-Cu',
            'slug' => 'ghk-cu',
            'is_active' => true,
        ]);
        $brand = Brand::create([
            'name' => 'Honest Peptide',
            'slug' => 'honest-peptide',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'GHK-Cu 100mg',
            'slug' => 'ghk-cu-100mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 49.99,
            'status' => 'active',
            'hidden' => false,
            'vendor_description' => 'Honest Peptide offers 100mg of GHK-Cu, a tripeptide-copper complex studied for its role in wound healing and tissue remodeling pathways.',
            'description' => 'Supplied for laboratory research. COA-verified purity; certificates of analysis are published on this site.',
        ]);

        $html = $this->get("/product/honest-peptide/ghk-cu-100mg/{$product->id}")
            ->assertOk()
            ->getContent();

        $title = $this->headContent($html, '/<title>(.*?)<\/title>/s');
        $description = $this->headContent($html, '/<meta name="description" content="(.*?)"/s');

        $this->assertLessThanOrEqual(60, mb_strlen($title), $title);
        $this->assertLessThanOrEqual(155, mb_strlen($description), $description);
        $this->assertStringNotContainsString('…', $description);
        $this->assertFalse(ProductPageSeo::hasClaim($description), $description);
        $this->assertDoesNotMatchRegularExpression('/\bverified\b/i', $description);

        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $blocks);
        $schema = null;
        foreach ($blocks[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if (($decoded['@type'] ?? null) === 'Product') {
                $schema = $decoded;
                break;
            }
        }
        $this->assertIsArray($schema);
        $schemaDescription = (string) ($schema['description'] ?? '');
        $this->assertStringNotContainsString('verified', strtolower($schemaDescription));
        $this->assertStringNotContainsString('this site', strtolower($schemaDescription));
        $this->assertFalse(ProductPageSeo::hasClaim($schemaDescription), $schemaDescription);
        $this->assertStringContainsString('laboratory research', $schemaDescription);
    }

    public function test_related_vs_links_are_canonical_and_return_200(): void
    {
        foreach ([
            'semaglutide' => 'Semaglutide',
            'tirzepatide' => 'Tirzepatide',
            'bpc-157' => 'BPC-157',
            'tb-500' => 'TB-500',
            'cjc-1295' => 'CJC-1295',
            'ipamorelin' => 'Ipamorelin',
            'ghk-cu' => 'GHK-Cu',
            'mots-c' => 'MOTS-c',
            'nad' => 'NAD+',
            'tesamorelin' => 'Tesamorelin',
            'sermorelin' => 'Sermorelin',
            'retatrutide' => 'Retatrutide',
        ] as $slug => $name) {
            ProductCategory::create([
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
            ]);
        }

        $urls = [];
        $this->get('/compare/semaglutide-vs-tirzepatide')
            ->assertOk()
            ->assertInertia(function ($page) use (&$urls) {
                $related = $page->toArray()['props']['related'];
                $urls = array_column($related, 'url');
                $names = [];
                foreach ($related as $card) {
                    $names[] = $card['name_a'];
                    $names[] = $card['name_b'];
                    $this->assertStringNotContainsString('healing', strtolower($card['tagline']));
                    $this->assertStringNotContainsString('boost', strtolower($card['tagline']));
                    $this->assertStringNotContainsString('…', $card['title']);
                }
                $this->assertContains('GHK-Cu', $names);
                $this->assertNotContains('GHK-CU', $names);

                return true;
            });

        $this->assertContains('/compare/bpc-157-vs-ghk-cu', $urls);
        $this->assertContains('/compare/cjc-1295-vs-ipamorelin', $urls);
        $this->assertContains('/compare/ipamorelin-vs-sermorelin', $urls);
        $this->assertContains('/compare/ipamorelin-vs-tesamorelin', $urls);
        $this->assertNotContains('/compare/ghk-cu-vs-bpc-157', $urls);
        $this->assertNotContains('/compare/ipamorelin-vs-cjc-1295', $urls);
        $this->assertNotContains('/compare/sermorelin-vs-ipamorelin', $urls);
        $this->assertNotContains('/compare/tesamorelin-vs-ipamorelin', $urls);

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        $description = VsPageSeo::description('BPC-157', 'TB-500');
        $this->assertStringContainsString('research context', $description);
        $this->assertStringNotContainsString('use cases', $description);
        $this->assertLessThanOrEqual(155, mb_strlen($description));
        $this->assertGreaterThanOrEqual(140, mb_strlen($description));

        $glp = VsPageSeo::renderedTitle('GLP3-R', 'GLP2-T');
        $this->assertSame('GLP3-R vs GLP2-T — Vendor & Price Comparison — Peptidemap', $glp);
    }

    public function test_related_pair_exclusion_is_case_insensitive(): void
    {
        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        ProductCategory::create(['name' => 'TB-500', 'slug' => 'tb-500', 'is_active' => true]);
        ProductCategory::create(['name' => 'GHK-Cu', 'slug' => 'ghk-cu', 'is_active' => true]);
        ProductCategory::create(['name' => 'Ipamorelin', 'slug' => 'ipamorelin', 'is_active' => true]);
        ProductCategory::create(['name' => 'CJC-1295', 'slug' => 'cjc-1295', 'is_active' => true]);
        ProductCategory::create(['name' => 'Semaglutide', 'slug' => 'semaglutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);
        ProductCategory::create(['name' => 'MOTS-c', 'slug' => 'mots-c', 'is_active' => true]);
        ProductCategory::create(['name' => 'NAD+', 'slug' => 'nad', 'is_active' => true]);
        ProductCategory::create(['name' => 'Tesamorelin', 'slug' => 'tesamorelin', 'is_active' => true]);
        ProductCategory::create(['name' => 'Sermorelin', 'slug' => 'sermorelin', 'is_active' => true]);
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);

        $this->get('/compare/bpc-157-vs-tb-500')
            ->assertOk()
            ->assertInertia(function ($page) {
                $urls = array_column($page->toArray()['props']['related'], 'url');
                foreach ($urls as $url) {
                    $this->assertStringNotContainsString('bpc-157', strtolower($url));
                    $this->assertStringNotContainsString('tb-500', strtolower($url));
                }

                return true;
            });
    }

    public function test_summary_table_drops_the_one_sided_winner_class(): void
    {
        $vue = file_get_contents(resource_path('js/Pages/Frontend/CompareCompoundVs.vue'));
        $this->assertStringNotContainsString('betterClass', $vue);
        $this->assertStringNotContainsString('slug.toUpperCase()', $vue);
        $this->assertStringContainsString('Buy →', $vue);
    }

    private function headContent(string $html, string $pattern): string
    {
        $this->assertSame(1, preg_match($pattern, $html, $matches), $pattern);

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
