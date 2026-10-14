<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorSetting;
use App\Support\OgImageRevision;
use App\Support\ProductPageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_product_title_and_meta_are_neutral_and_within_length(): void
    {
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        $brand = Brand::create([
            'name' => 'Amino Club',
            'slug' => 'amino-club',
            'is_active' => true,
        ]);
        VendorSetting::create([
            'brand_id' => $brand->id,
            'coupon_code' => 'SAVE10',
            'coupon_discount_percent' => 10,
        ]);
        $product = Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'bpc-157-5mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 49.99,
            'discount_price' => 39.99,
            'status' => 'active',
            'hidden' => false,
        ]);

        $html = $this->get("/product/amino-club/bpc-157-5mg/{$product->id}")
            ->assertOk()
            ->getContent();

        $title = $this->headContent($html, '/<title>(.*?)<\/title>/s');
        $description = $this->headContent($html, '/<meta name="description" content="(.*?)"/s');
        $ogTitle = $this->headContent($html, '/property="og:title" content="(.*?)"/s');
        $ogDescription = $this->headContent($html, '/property="og:description" content="(.*?)"/s');
        $ogImage = $this->headContent($html, '/property="og:image" content="(.*?)"/s');

        $expectedTitle = ProductPageSeo::title('BPC-157 5mg', 'Amino Club');
        $expectedDescription = ProductPageSeo::description('BPC-157 5mg', 'Amino Club', 1);

        $this->assertSame($expectedTitle, $title);
        $this->assertSame($expectedTitle, $ogTitle);
        $this->assertLessThanOrEqual(60, mb_strlen($title), $title);
        $this->assertSame($expectedDescription, $description);
        $this->assertSame($expectedDescription, $ogDescription);
        $this->assertLessThanOrEqual(155, mb_strlen($description), $description);
        $this->assertStringContainsString('-'.OgImageRevision::COPY.'-'.OgImageRevision::PRODUCT, $ogImage);

        foreach ([$title, $description, $ogTitle, $ogDescription] as $copy) {
            $this->assertStringNotContainsString('$', $copy);
            $this->assertStringNotContainsString('Buy', $copy);
            $this->assertStringNotContainsString('% Off', $copy);
            $this->assertDoesNotMatchRegularExpression('/\bbuy\b/i', $copy);
            $this->assertStringNotContainsString('%', $copy);
        }
    }

    public function test_product_og_card_has_no_price_buy_or_percent_off(): void
    {
        $brand = Brand::create([
            'name' => 'Amino Club',
            'slug' => 'amino-club',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'bpc-157-5mg',
            'brand_id' => $brand->id,
            'price' => 49.99,
            'discount_price' => 39.99,
            'status' => 'active',
            'hidden' => false,
        ]);
        $product->setRelation('brand', $brand);

        $html = view('og.product', [
            'product' => $product,
            'productImage' => null,
            'vendorLogo' => null,
            'couponCode' => 'SAVE10',
            'vendorCount' => 4,
        ])->render();

        $this->assertStringContainsString('BPC-157 5mg', $html);
        $this->assertStringContainsString('Amino Club', $html);
        $this->assertStringContainsString('listing · RUO', $html);
        $this->assertStringContainsString('Vendor-listed code', $html);
        $this->assertStringNotContainsString('SAVE10', $html);
        $this->assertStringNotContainsString('$', $html);
        $this->assertDoesNotMatchRegularExpression('/\bBuy\b/', $html);
        $this->assertStringNotContainsString('% Off', $html);
        $this->assertStringNotContainsString('Save ', $html);
        $this->assertStringContainsString('Live prices', $html);
        $this->assertStringContainsString('PMAP coupons', $html);
    }

    private function headContent(string $html, string $pattern): string
    {
        $this->assertSame(1, preg_match($pattern, $html, $matches), $pattern);

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
