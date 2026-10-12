<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorSetting;
use App\Support\ComparePageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Headline "from $X" and ItemList follow the same eligibility rules as
 * per-mg stats, and both quote the listed price. Meta and og descriptions
 * come from ComparePageSeo and contain no dollar amount. An unconfirmed
 * or far-below-median listing can stay in the vendor table.
 */
class ComparePriceClaimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_outlier_cannot_lead_headline_meta_or_item_list(): void
    {
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        $us = Location::create(['name' => 'United States']);
        $outlierBrand = Brand::create([
            'name' => 'Southern Aminos',
            'slug' => 'southern-aminos',
            'is_active' => true,
        ]);
        $listedBrand = Brand::create([
            'name' => 'Listed Research',
            'slug' => 'listed-research',
            'is_active' => true,
        ]);
        VendorSetting::create([
            'brand_id' => $outlierBrand->id,
            'location_id' => $us->id,
            'coupon_discount_percent' => 15,
        ]);
        VendorSetting::create([
            'brand_id' => $listedBrand->id,
            'location_id' => $us->id,
        ]);
        $couponBrand = Brand::create([
            'name' => 'Coupon Labs',
            'slug' => 'coupon-labs',
            'is_active' => true,
        ]);
        VendorSetting::create([
            'brand_id' => $couponBrand->id,
            'location_id' => $us->id,
            'coupon_discount_percent' => 50,
        ]);

        // $4.95 with a 15% code is $4.21. No mg figure in the name, so it
        // fails the same rule the per-mg table uses. Not a hard-coded id.
        // Coupon Labs lists $40 and charges $20 after code. The headline
        // follows the $27 listed price, not that after-code figure.
        $this->product($outlierBrand, $category, 'SA-3R', 'sa-3r', 4.95);
        $this->product($listedBrand, $category, 'Cheap — 10mg', 'cheap-10mg', 1.00);
        $this->product($listedBrand, $category, 'Anchor A — 10mg', 'anchor-a', 80);
        $this->product($listedBrand, $category, 'Anchor B — 10mg', 'anchor-b', 90);
        $this->product($listedBrand, $category, 'Anchor C — 10mg', 'anchor-c', 100);
        $this->product($listedBrand, $category, 'Listed — 10mg', 'listed-10mg', 27);
        $this->product($couponBrand, $category, 'Coupon — 10mg', 'coupon-10mg', 40);

        $response = $this->get('/compare/bpc-157');
        $response->assertOk();

        $props = null;
        $response->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];

            return $page;
        });

        $this->assertEquals(27.0, $props['compound']['cheapest_price']);
        $this->assertEquals(100.0, $props['compound']['priciest_price']);
        $description = ComparePageSeo::description('BPC-157', 3, false);
        $this->assertSame($description, $props['seo']['description']);
        $this->assertSame($description, $props['seo']['og_description']);
        $this->assertStringNotContainsString('$', $props['seo']['description']);
        $this->assertStringNotContainsString('$', $props['seo']['og_description']);

        $itemList = collect($props['seo']['schema'])->firstWhere('@type', 'ItemList');
        $this->assertSame(5, $itemList['numberOfItems']);
        $this->assertSame('Listed Research', $itemList['itemListElement'][0]['item']['brand']['name']);
        $this->assertSame('27.00', $itemList['itemListElement'][0]['item']['offers']['price']);
        $offerPrices = array_map(fn ($el) => $el['item']['offers']['price'], $itemList['itemListElement']);
        $this->assertNotContains('4.21', $offerPrices);
        $this->assertNotContains('1.00', $offerPrices);
        $this->assertNotContains('4.95', $offerPrices);
        $this->assertNotContains('20.00', $offerPrices);
        $this->assertContains('40.00', $offerPrices);

        $aggregate = collect($props['seo']['schema'])->firstWhere('@type', 'Product');
        $this->assertSame('27.00', $aggregate['offers']['lowPrice']);
        $this->assertSame('100.00', $aggregate['offers']['highPrice']);
        $this->assertSame(5, $aggregate['offers']['offerCount']);

        $tablePrices = array_map(fn ($row) => number_format((float) $row['final_price'], 2, '.', ''), $props['compound']['products']);
        $this->assertContains('4.21', $tablePrices);
        $this->assertContains('1.00', $tablePrices);
        $this->assertContains('20.00', $tablePrices);
    }

    public function test_item_list_is_omitted_when_no_listing_is_eligible(): void
    {
        $category = ProductCategory::create([
            'name' => 'CJC-1295',
            'slug' => 'cjc-1295',
            'is_active' => true,
        ]);
        $us = Location::create(['name' => 'United States']);
        $brand = Brand::create([
            'name' => 'Blend Co',
            'slug' => 'blend-co',
            'is_active' => true,
        ]);
        VendorSetting::create([
            'brand_id' => $brand->id,
            'location_id' => $us->id,
        ]);
        Product::create([
            'name' => 'CJC-1295 / Ipamorelin blend',
            'slug' => 'cjc-blend',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'price' => 50,
            'status' => 'active',
            'hidden' => false,
        ]);

        $response = $this->get('/compare/cjc-1295');
        $response->assertOk();

        $props = null;
        $response->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];

            return $page;
        });

        $this->assertNull($props['compound']['cheapest_price']);
        $this->assertNotEmpty($props['compound']['faqs']);
        $types = array_column($props['seo']['schema'], '@type');
        $this->assertNotContains('ItemList', $types);
        $this->assertNotContains('Product', $types);
    }

    private function product(Brand $brand, ProductCategory $category, string $name, string $slug, float $price): void
    {
        Product::create([
            'name' => $name,
            'slug' => $slug,
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'size_mg' => '10mg',
            'price' => $price,
            'status' => 'active',
            'hidden' => false,
        ]);
    }
}
