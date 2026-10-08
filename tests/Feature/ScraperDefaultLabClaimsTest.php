<?php

namespace Tests\Feature;

use App\Jobs\DiscoverProductsJob;
use App\Models\Brand;
use App\Models\Product;
use App\Support\ScraperDefaultLabClaims;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScraperDefaultLabClaimsTest extends TestCase
{
    use RefreshDatabase;

    public function test_discover_job_does_not_invent_purity_or_lab_tested(): void
    {
        Http::fake([
            'https://shop.example/wp-json/wc/store/v1/products*' => Http::response([
                [
                    'name' => 'BPC-157 5mg',
                    'permalink' => 'https://shop.example/product/bpc-157',
                    'prices' => ['price' => '4999'],
                    'images' => [['src' => 'https://shop.example/img.jpg']],
                    'short_description' => '<p>Research vial</p>',
                ],
            ]),
        ]);

        $brand = Brand::create([
            'name' => 'Shop Peptides',
            'slug' => 'shop-peptides',
            'is_active' => true,
        ]);

        (new DiscoverProductsJob($brand, 'https://shop.example'))->handle();

        $product = Product::where('product_url', 'https://shop.example/product/bpc-157')->first();

        $this->assertNotNull($product);
        $this->assertTrue((bool) $product->auto_scraped);
        $this->assertNull($product->purity);
        $this->assertFalse($product->lab_tested);
    }

    public function test_backfill_clears_only_the_untouched_scraper_default(): void
    {
        $scrapedDefault = $this->makeProduct('scraped-default', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
        ]);
        $adminOwned = $this->makeProduct('admin-owned', [
            'auto_scraped' => false,
            'purity' => 99.0,
            'lab_tested' => true,
            'verified' => true,
        ]);
        $otherPurity = $this->makeProduct('other-purity', [
            'auto_scraped' => true,
            'purity' => 98.5,
            'lab_tested' => true,
        ]);
        $labFlagCleared = $this->makeProduct('lab-flag-cleared', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => false,
        ]);
        $locked = $this->makeProduct('locked', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
        ]);
        $locked->manual_override = true;
        $locked->save();
        $adminVerified = $this->makeProduct('admin-verified', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
            'verified' => true,
        ]);
        $demo = $this->makeProduct('demo-catalog', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
            'is_demo' => true,
        ]);
        $alreadyClean = $this->makeProduct('already-clean', [
            'auto_scraped' => true,
            'purity' => null,
            'lab_tested' => false,
        ]);

        $cleared = ScraperDefaultLabClaims::clear();

        $this->assertSame(1, $cleared);
        $scrapedDefault->refresh();
        $this->assertNull($scrapedDefault->purity);
        $this->assertFalse($scrapedDefault->lab_tested);

        $adminOwned->refresh();
        $this->assertEquals(99.0, $adminOwned->purity);
        $this->assertTrue($adminOwned->lab_tested);

        $otherPurity->refresh();
        $this->assertEquals(98.5, $otherPurity->purity);
        $this->assertTrue($otherPurity->lab_tested);

        $labFlagCleared->refresh();
        $this->assertEquals(99.0, $labFlagCleared->purity);
        $this->assertFalse($labFlagCleared->lab_tested);

        $locked->refresh();
        $this->assertEquals(99.0, $locked->purity);
        $this->assertTrue($locked->lab_tested);

        $adminVerified->refresh();
        $this->assertEquals(99.0, $adminVerified->purity);
        $this->assertTrue($adminVerified->lab_tested);

        $demo->refresh();
        $this->assertEquals(99.0, $demo->purity);
        $this->assertTrue($demo->lab_tested);

        $alreadyClean->refresh();
        $this->assertNull($alreadyClean->purity);
        $this->assertFalse($alreadyClean->lab_tested);
    }

    public function test_backfill_is_idempotent(): void
    {
        $this->makeProduct('scraped-default', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
        ]);

        $this->assertSame(1, ScraperDefaultLabClaims::clear());
        $this->assertSame(0, ScraperDefaultLabClaims::clear());

        $product = Product::where('slug', 'scraped-default')->first();
        $this->assertNull($product->purity);
        $this->assertFalse($product->lab_tested);
    }

    private function makeProduct(string $slug, array $overrides): Product
    {
        return Product::create(array_merge([
            'name' => $slug,
            'slug' => $slug,
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
            'auto_scraped' => false,
            'lab_tested' => false,
            'verified' => false,
            'is_demo' => false,
            'manual_override' => false,
        ], $overrides));
    }
}
