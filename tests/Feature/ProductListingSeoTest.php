<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ProductListingSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListingSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_listing_title_and_meta_follow_the_vendor_count(): void
    {
        $none = $this->category('Tesamorelin', 'tesamorelin');
        $one = $this->category('Ipamorelin', 'ipamorelin');
        $brand = $this->brand('One Lab', 'one-lab');
        $this->listing($one, $brand, 'ipamorelin-5mg', 22.00);
        $this->listing($one, $brand, 'ipamorelin-10mg', 30.00);
        $this->listing($one, $this->brand('Unpriced Lab', 'unpriced-lab'), 'ipamorelin-0', 0.00);

        $many = $this->category('BPC-157', 'bpc-157');
        $this->listing($many, $this->brand('Alpha Lab', 'alpha-lab'), 'bpc-alpha', 40.00);
        $this->listing($many, $this->brand('Beta Lab', 'beta-lab'), 'bpc-beta', 42.00);

        $glow = $this->category('GLOW', 'glow');
        $reta = $this->category('Retatrutide', 'retatrutide');
        $this->listing($reta, $this->brand('Reta Lab', 'reta-lab'), 'reta-10', 50.00);
        $this->listing($reta, $this->brand('Reta Two', 'reta-two'), 'reta-20', 55.00);

        $this->assertListing($none, 0, 'Tesamorelin');
        $this->assertListing($one, 1, 'Ipamorelin');
        $this->assertListing($many, 2, 'BPC-157');
        $this->assertListing($glow, 0, ProductListingSeo::compoundName('GLOW'));
        $this->assertSame(
            'GLOW — GHK-Cu/BPC-157/TB-500',
            ProductListingSeo::compoundName('GLOW')
        );
        $this->assertStringContainsString('GHK-Cu/BPC-157/TB-500', ProductListingSeo::title('GLOW — GHK-Cu/BPC-157/TB-500', 0));

        $retaPage = $this->get('/product/retatrutide')->assertOk();
        $retaHtml = $retaPage->getContent();
        $retaTitle = $this->headContent($retaHtml, '/<title>(.*?)<\/title>/s');
        $this->assertSame(ProductListingSeo::title('Retatrutide', 2), $retaTitle);
        $this->assertStringStartsWith('Retatrutide: Compare 2 Research Vendors', $retaTitle);
        $this->assertStringNotContainsString('GLP3-R', $retaTitle);
        $retaPage->assertInertia(fn ($page) => $page
            ->component('Frontend/ProductListing')
            ->where('productName', 'GLP3-R')
            ->where('seo.title', $retaTitle)
            ->where('seo.og_title', $retaTitle)
            ->where('seo.h1', 'Retatrutide')
        );

        $filtered = $this->headContent(
            $this->get('/product/bpc-157?search=no-such-listing')->assertOk()->getContent(),
            '/<title>(.*?)<\/title>/s'
        );
        $this->assertSame(ProductListingSeo::title('BPC-157', 2), $filtered);
    }

    public function test_long_names_fall_back_without_cutting_a_word_and_metas_stay_within_155(): void
    {
        $medium = 'Bremelanotide Analog';
        $mediumTitle = ProductListingSeo::title($medium, 12);
        $this->assertLessThanOrEqual(60, mb_strlen($mediumTitle));
        $this->assertSame('Bremelanotide Analog: 12 Research Vendors — Peptidemap', $mediumTitle);
        $this->assertGreaterThan(60, mb_strlen($medium.': Compare 12 Research Vendors — Peptidemap'));

        $long = trim(str_repeat('Longname ', 8));
        $longTitle = ProductListingSeo::title($long, 4);
        $this->assertLessThanOrEqual(60, mb_strlen($longTitle));
        $this->assertSame('Longname Longname Longname Longname Longname Longname', $longTitle);
        $this->assertDoesNotMatchRegularExpression('/\bLongn\b|\bLongna\b/', $longTitle);

        $meta = ProductListingSeo::description($long, 0);
        $raw = $long.' research-use-only profile. No vendors currently list it on Peptidemap; compared listings appear here when they do. We sell nothing.';
        $this->assertGreaterThan(155, mb_strlen($raw));
        $this->assertLessThanOrEqual(155, mb_strlen($meta));
        $this->assertStringStartsWith($meta, $raw);
        $this->assertSame(' ', mb_substr($raw, mb_strlen($meta), 1));

        foreach ([$mediumTitle, $longTitle, $meta, ProductListingSeo::description($medium, 12)] as $copy) {
            $this->assertClean($copy);
        }
    }

    public function test_broken_slug_category_keeps_the_previous_title_and_meta(): void
    {
        $category = ProductCategory::create([
            'name' => 'Vitamin B12',
            'slug' => 'Vitamin B12',
            'description' => 'Stored blend description that must stay on this row.',
            'is_active' => true,
        ]);
        $this->listing($category, $this->brand('Blend Lab', 'blend-lab'), 'blend-a', 18.00);
        $this->listing($category, $this->brand('Blend Two', 'blend-two'), 'blend-b', 19.00);

        $this->assertFalse(ProductListingSeo::slugIsRouteSafe($category->slug));
        $this->assertFalse(ProductListingSeo::slugIsRouteSafe('BPC-157 / TB500 / Cartalax'));

        $page = $this->get('/product/'.rawurlencode($category->slug))->assertOk();
        $html = $page->getContent();
        $title = $this->headContent($html, '/<title>(.*?)<\/title>/s');
        $description = $this->headContent($html, '/<meta name="description" content="(.*?)"/s');

        $this->assertSame('Vitamin B12 – Peptidemap', $title);
        $this->assertSame('Stored blend description that must stay on this row.', $description);
        $this->assertStringNotContainsString('Research Vendors', $title);
        $this->assertStringNotContainsString('Research use only', $description);
    }

    private function assertListing(ProductCategory $category, int $count, string $name): void
    {
        $expectedTitle = ProductListingSeo::title($name, $count);
        $expectedDescription = ProductListingSeo::description($name, $count);

        $this->assertLessThanOrEqual(60, mb_strlen($expectedTitle), $expectedTitle);
        $this->assertLessThanOrEqual(155, mb_strlen($expectedDescription), $expectedDescription);
        $this->assertClean($expectedTitle);
        $this->assertClean($expectedDescription);

        $html = $this->get('/product/'.$category->slug)->assertOk()->getContent();
        $title = $this->headContent($html, '/<title>(.*?)<\/title>/s');
        $description = $this->headContent($html, '/<meta name="description" content="(.*?)"/s');
        $ogTitle = $this->headContent($html, '/property="og:title" content="(.*?)"/s');
        $ogDescription = $this->headContent($html, '/property="og:description" content="(.*?)"/s');
        $twitterTitle = $this->headContent($html, '/name="twitter:title" content="(.*?)"/s');
        $twitterDescription = $this->headContent($html, '/name="twitter:description" content="(.*?)"/s');

        $this->assertSame($expectedTitle, $title);
        $this->assertSame($expectedTitle, $ogTitle);
        $this->assertSame($expectedTitle, $twitterTitle);
        $this->assertSame($expectedDescription, $description);
        $this->assertSame($expectedDescription, $ogDescription);
        $this->assertSame($expectedDescription, $twitterDescription);
        $this->assertStringContainsString('role="heading" aria-level="1">'.$category->name.'</div>', $html);

        if ($count >= 2 && mb_strlen($name.': Compare '.$count.' Research Vendors — Peptidemap') <= 60) {
            $this->assertStringContainsString('Compare '.$count.' Research Vendors', $title);
        } elseif ($count === 1 && mb_strlen($name.': 1 Research Vendor Listed — Peptidemap') <= 60) {
            $this->assertStringContainsString('1 Research Vendor Listed', $title);
        } elseif ($count === 0 && mb_strlen($name.': Research Compound Profile — Peptidemap') <= 60) {
            $this->assertStringContainsString('Research Compound Profile', $title);
        }
    }

    private function assertClean(string $copy): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/\$|%|\b(?:price|coupon|cheapest|best|verified|buy)\b/i',
            $copy,
            $copy
        );
    }

    private function category(string $name, string $slug): ProductCategory
    {
        return ProductCategory::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function brand(string $name, string $slug): Brand
    {
        return Brand::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function listing(ProductCategory $category, Brand $brand, string $slug, float $price): void
    {
        Product::create([
            'name' => $category->name.' '.$slug,
            'slug' => $slug,
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => $price,
            'status' => 'active',
            'hidden' => false,
        ]);
    }

    private function headContent(string $html, string $pattern): string
    {
        $this->assertSame(1, preg_match($pattern, $html, $matches), $pattern);

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
