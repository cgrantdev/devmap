<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\BrandOgImageController;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorSetting;
use App\Support\OgImageRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class BrandOgCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_one_product_card_is_singular_and_has_no_price_or_code(): void
    {
        $brand = Brand::create([
            'name' => 'Acme Research',
            'slug' => 'acme-research',
            'is_active' => true,
        ]);
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'bpc-157-5mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 30,
            'status' => 'active',
            'hidden' => false,
        ]);

        $count = (new ReflectionMethod(BrandOgImageController::class, 'productCount'))
            ->invoke(new BrandOgImageController, $brand);
        $this->assertSame(1, $count);

        $html = view('og.brand', [
            'brand' => $brand,
            'vendorLogo' => null,
            'tagline' => null,
            'productCount' => 1,
        ])->render();

        $this->assertStringContainsString('1</span>Product', $html);
        $this->assertStringNotContainsString('Products', $html);
        $this->assertStringNotContainsString('From', $html);
        $this->assertStringNotContainsString('Code', $html);
        $this->assertStringNotContainsString('$', $html);
        $this->assertStringContainsString('Catalog data', $html);
        $this->assertStringContainsString('RUO', $html);

        $plural = view('og.brand', [
            'brand' => $brand,
            'vendorLogo' => null,
            'tagline' => null,
            'productCount' => 2,
        ])->render();
        $this->assertStringContainsString('2</span>Products', $plural);

        $source = file_get_contents(app_path('Http/Controllers/Frontend/BrandOgImageController.php'));
        $this->assertStringContainsString("\$slug.'-'.OgImageRevision::COPY", $source);
        $this->assertStringNotContainsString('fromPrice', $source);
        $this->assertStringNotContainsString('couponCode', $source);
    }

    public function test_brand_page_og_image_url_includes_the_copy_revision(): void
    {
        $us = Location::create(['name' => 'United States']);
        $brand = Brand::create([
            'name' => 'Acme Research',
            'slug' => 'acme-research',
            'is_active' => true,
        ]);
        VendorSetting::create([
            'brand_id' => $brand->id,
            'location_id' => $us->id,
            'coupon_code' => 'SAVE10',
        ]);
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'bpc-157-5mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 30,
            'status' => 'active',
            'hidden' => false,
        ]);

        $html = $this->get('/brand/acme-research')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#/og/brand/acme-research\.png\?v=\d+-'.preg_quote(OgImageRevision::COPY, '#').'#',
            $html
        );
    }
}
