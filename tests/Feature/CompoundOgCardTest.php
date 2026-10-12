<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\CompoundOgImageController;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class CompoundOgCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_vendor_card_is_singular_and_ignores_unpriced_rows(): void
    {
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        $priced = Brand::create([
            'name' => 'Priced Research',
            'slug' => 'priced-research',
            'is_active' => true,
        ]);
        $unpriced = Brand::create([
            'name' => 'Zero Research',
            'slug' => 'zero-research',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'bpc-157-5mg',
            'brand_id' => $priced->id,
            'product_category_id' => $category->id,
            'price' => 30,
            'status' => 'active',
            'hidden' => false,
        ]);
        Product::create([
            'name' => 'BPC-157 placeholder',
            'slug' => 'bpc-157-placeholder',
            'brand_id' => $unpriced->id,
            'product_category_id' => $category->id,
            'price' => 10,
            'discount_price' => 0,
            'status' => 'active',
            'hidden' => false,
        ]);

        $count = (new ReflectionMethod(CompoundOgImageController::class, 'vendorCount'))
            ->invoke(new CompoundOgImageController, $category);
        $this->assertSame(1, $count);

        $html = view('og.compound', [
            'category' => $category,
            'fullName' => null,
            'tagline' => null,
            'eyebrow' => 'Peptide Compound Guide',
            'vendorCount' => 1,
        ])->render();

        $this->assertStringContainsString('1</span>Vendor', $html);
        $this->assertStringNotContainsString('Vendors', $html);
        $this->assertStringNotContainsString('From', $html);
        $this->assertStringContainsString('Catalog data', $html);
        $this->assertStringContainsString('RUO', $html);

        $plural = view('og.compound', [
            'category' => $category,
            'fullName' => null,
            'tagline' => null,
            'eyebrow' => 'Peptide Compound Guide',
            'vendorCount' => 2,
        ])->render();
        $this->assertStringContainsString('2</span>Vendors', $plural);
    }
}
