<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /products and /encyclopedia compound cards hotlinked vendor originals
 * (up to 4 MB each, some 403 from hotlink protection) although most
 * products already had a WebP mirror on our storage (24 Nov 2026).
 */
class CompoundCardImageTest extends TestCase
{
    use RefreshDatabase;

    private const MIRROR = '/storage/vendor-mirror/abc123.webp';

    public function test_compound_cards_use_our_mirrored_image(): void
    {
        $this->compoundWithProducts();

        $groups = collect($this->get('/products')->assertOk()->viewData('page')['props']['productGroups']);
        $this->assertSame(self::MIRROR, $groups->firstWhere('slug', 'zetapeptin')['image']);

        $peptides = collect($this->get('/encyclopedia')->assertOk()->viewData('page')['props']['peptides']);
        $this->assertSame(self::MIRROR, $peptides->firstWhere('name', 'Zetapeptin')['image']);
    }

    public function test_a_pinned_thumbnail_uses_its_mirror(): void
    {
        $pinned = $this->compoundWithProducts();
        $pinned->update(['is_peptide_thumb' => true, 'is_encyclopedia_thumb' => true, 'mirrored_image_url' => '/storage/vendor-mirror/pinned.webp']);

        $groups = collect($this->get('/products')->assertOk()->viewData('page')['props']['productGroups']);
        $this->assertSame('/storage/vendor-mirror/pinned.webp', $groups->firstWhere('slug', 'zetapeptin')['image']);

        $peptides = collect($this->get('/encyclopedia')->assertOk()->viewData('page')['props']['peptides']);
        $this->assertSame('/storage/vendor-mirror/pinned.webp', $peptides->firstWhere('name', 'Zetapeptin')['image']);
    }

    /**
     * An unmirrored product on a host outside the reliable list, created
     * first, and a mirrored one. Returns the unmirrored product.
     */
    private function compoundWithProducts(): Product
    {
        $brand = Brand::create(['name' => 'Mirror Labs', 'slug' => 'mirror-labs', 'is_active' => true]);
        $category = ProductCategory::create(['name' => 'Zetapeptin', 'slug' => 'zetapeptin', 'is_active' => true]);
        $base = [
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 40,
            'status' => 'active',
        ];

        $unmirrored = Product::create($base + [
            'name' => 'Zetapeptin 5mg',
            'slug' => 'zetapeptin-5mg-mirror-labs',
            'image_url' => 'https://vendor.example.com/wp-content/uploads/zetapeptin-5mg-scaled.png',
        ]);
        Product::create($base + [
            'name' => 'Zetapeptin 10mg',
            'slug' => 'zetapeptin-10mg-mirror-labs',
            'image_url' => 'https://vendor.example.com/wp-content/uploads/zetapeptin-10mg-scaled.png',
            'mirrored_image_url' => self::MIRROR,
        ]);

        return $unmirrored;
    }
}
