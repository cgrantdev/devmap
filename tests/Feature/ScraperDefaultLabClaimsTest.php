<?php

namespace Tests\Feature;

use App\Jobs\DiscoverProductsJob;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ScraperDefaultLabClaims;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

    public function test_null_protection_flags_are_treated_as_unset(): void
    {
        $product = $this->makeProduct('null-flags', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
        ]);

        $this->allowNullProtectionFlags();
        DB::table('products')->where('id', $product->id)->update([
            'manual_override' => null,
            'verified' => null,
            'is_demo' => null,
        ]);

        $this->assertSame(1, ScraperDefaultLabClaims::clear());

        $product->refresh();
        $this->assertNull($product->purity);
        $this->assertFalse($product->lab_tested);
    }

    public function test_backfill_logs_cleared_and_skipped_counts(): void
    {
        $this->makeProduct('scraped-default', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
        ]);
        $verified = $this->makeProduct('admin-verified', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
            'verified' => true,
        ]);
        $locked = $this->makeProduct('locked', [
            'auto_scraped' => true,
            'purity' => 99.0,
            'lab_tested' => true,
        ]);
        $locked->manual_override = true;
        $locked->save();

        $logged = null;
        Log::listen(function (MessageLogged $event) use (&$logged) {
            if ($event->message === 'Scraper default lab-claims backfill') {
                $logged = $event;
            }
        });

        $migration = require database_path('migrations/2026_10_08_130000_clear_scraper_default_lab_claims.php');
        $migration->up();

        $this->assertNotNull($logged);
        $this->assertSame('info', $logged->level);
        $this->assertSame(1, $logged->context['cleared']);
        $this->assertSame(2, $logged->context['skipped_verified_or_manual_override']);

        $verified->refresh();
        $locked->refresh();
        $this->assertEquals(99.0, $verified->purity);
        $this->assertTrue($verified->lab_tested);
        $this->assertEquals(99.0, $locked->purity);
        $this->assertTrue($locked->lab_tested);
    }

    public function test_rescrape_skips_a_manually_edited_existing_row(): void
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

        $existing = Product::create([
            'name' => 'BPC-157 edited by hand',
            'slug' => 'bpc-157-edited',
            'brand_id' => $brand->id,
            'price' => 55,
            'product_url' => 'https://shop.example/product/bpc-157',
            'status' => 'active',
            'hidden' => false,
            'purity' => 98.2,
            'lab_tested' => true,
            'auto_scraped' => true,
        ]);

        (new DiscoverProductsJob($brand, 'https://shop.example'))->handle();

        $this->assertSame(
            1,
            Product::where('brand_id', $brand->id)
                ->where('product_url', 'https://shop.example/product/bpc-157')
                ->count()
        );

        $existing->refresh();
        $this->assertSame('BPC-157 edited by hand', $existing->name);
        $this->assertEquals(98.2, $existing->purity);
        $this->assertTrue($existing->lab_tested);
    }

    public function test_product_card_listing_keeps_null_purity(): void
    {
        $this->withoutVite();

        $brand = Brand::create([
            'name' => 'Shop Peptides',
            'slug' => 'shop-peptides',
            'is_active' => true,
        ]);
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'bpc-157-5mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 49.99,
            'status' => 'active',
            'hidden' => false,
            'availability' => 'in_stock',
            'purity' => null,
            'lab_tested' => false,
            'auto_scraped' => true,
        ]);

        $this->get('/product/bpc-157')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/ProductListing')
                ->has('products.data', 1)
                ->where('products.data.0.id', $product->id)
                ->where('products.data.0.purity', null)
                ->where('products.data.0.lab_tested', false)
            );

        $this->get("/product/{$brand->slug}/{$product->slug}/{$product->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/ProductDetail')
                ->where('product.id', $product->id)
                ->missing('product.purity')
                ->missing('product.lab_tested')
            );
    }

    /**
     * The flag columns default to false and are NOT NULL. Null still means
     * unset in the backfill, so this test database allows the three flags
     * to be stored as null for that one row.
     */
    private function allowNullProtectionFlags(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('manual_override')->nullable()->default(false)->change();
            $table->boolean('verified')->nullable()->default(false)->change();
            $table->boolean('is_demo')->nullable()->default(false)->change();
        });
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
