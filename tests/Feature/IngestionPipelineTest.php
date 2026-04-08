<?php

namespace Tests\Feature;

use App\Jobs\RunWooCommerceIngestJob;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use App\Services\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IngestionPipelineTest extends TestCase
{
    use RefreshDatabase;

    private function makeBrand(): Brand
    {
        return Brand::create(['name' => 'Acme Peptides', 'slug' => 'acme-peptides', 'is_active' => true]);
    }

    private function makeConfig(Brand $brand, array $overrides = []): ScrapingConfig
    {
        return ScrapingConfig::create(array_merge([
            'vendor_id' => $brand->id,
            'vendor_name' => $brand->name,
            'type' => ScrapingConfig::TYPE_WOO_API,
            'products_url' => 'https://acme.example.com',
            'store_url' => 'https://acme.example.com',
            'auth_credentials' => [
                'consumer_key' => 'ck_test',
                'consumer_secret' => 'cs_test',
            ],
            'frequency' => 'daily',
            'enabled' => true,
            'auto_promote' => false,
        ], $overrides));
    }

    public function test_upsert_staged_creates_pending_row(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $ingestion = app(IngestionService::class);

        $staged = $ingestion->upsertStaged($config, [
            'external_id' => '123',
            'name' => 'BPC-157',
            'price' => '49.99',
            'source_url' => 'https://acme.example.com/product/bpc-157',
        ]);

        $this->assertNotNull($staged);
        $this->assertEquals('pending', $staged->status);
        $this->assertEquals($brand->id, $staged->brand_id);
        $this->assertEquals('49.99', $staged->price);
        $this->assertNull($staged->product_id);
    }

    public function test_upsert_detects_price_change(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $ingestion = app(IngestionService::class);

        $ingestion->upsertStaged($config, [
            'external_id' => '1',
            'name' => 'TB-500',
            'price' => '59.99',
        ]);
        $updated = $ingestion->upsertStaged($config, [
            'external_id' => '1',
            'name' => 'TB-500',
            'price' => '54.99',
        ]);

        $this->assertEquals('54.99', $updated->price);
        $this->assertEquals('59.99', $updated->previous_price);
        $this->assertNotNull($updated->price_changed_at);
    }

    public function test_auto_promote_creates_live_product(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand, ['auto_promote' => true]);
        $ingestion = app(IngestionService::class);

        $staged = $ingestion->upsertStaged($config, [
            'external_id' => '1',
            'name' => 'Semaglutide',
            'price' => '99.99',
            'source_url' => 'https://acme.example.com/semag',
        ]);

        $this->assertEquals('approved', $staged->fresh()->status);
        $this->assertNotNull($staged->fresh()->product_id);
        $this->assertDatabaseHas('products', [
            'name' => 'Semaglutide',
            'brand_id' => $brand->id,
            'product_url' => 'https://acme.example.com/semag',
        ]);
    }

    public function test_manual_promote_from_admin_endpoint(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $ingestion = app(IngestionService::class);

        $staged = $ingestion->upsertStaged($config, [
            'external_id' => '1',
            'name' => 'Ipamorelin',
            'price' => '39.99',
        ]);

        $product = $ingestion->promote($staged->fresh());

        $this->assertEquals('approved', $staged->fresh()->status);
        $this->assertEquals('Ipamorelin', $product->name);
        $this->assertEquals($brand->id, $product->brand_id);
        $this->assertEquals($product->id, $staged->fresh()->product_id);
    }

    public function test_reject_marks_staged_product_rejected(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $ingestion = app(IngestionService::class);

        $staged = $ingestion->upsertStaged($config, [
            'external_id' => '1',
            'name' => 'Sketchy Peptide',
        ]);

        $ingestion->reject($staged, 'Invalid data');

        $this->assertEquals('rejected', $staged->fresh()->status);
        $this->assertDatabaseMissing('products', ['name' => 'Sketchy Peptide']);
    }

    public function test_woocommerce_ingest_job_stages_products(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);

        Http::fake([
            'acme.example.com/wp-json/wc/v3/products*' => Http::response([
                [
                    'id' => 101,
                    'name' => 'BPC-157 5mg',
                    'permalink' => 'https://acme.example.com/product/bpc-157-5mg',
                    'regular_price' => '49.99',
                    'sale_price' => '',
                    'stock_status' => 'instock',
                    'short_description' => '<p>5mg vial</p>',
                    'images' => [['src' => 'https://acme.example.com/img.jpg']],
                ],
                [
                    'id' => 102,
                    'name' => 'TB-500 2mg',
                    'permalink' => 'https://acme.example.com/product/tb-500-2mg',
                    'regular_price' => '59.99',
                    'sale_price' => '49.99',
                    'stock_status' => 'instock',
                    'short_description' => '',
                    'images' => [],
                ],
            ], 200, ['X-WP-TotalPages' => '1']),
        ]);

        (new RunWooCommerceIngestJob($config))->handle(app(IngestionService::class));

        $this->assertDatabaseCount('scraped_products', 2);
        $this->assertDatabaseHas('scraped_products', [
            'external_id' => '101',
            'name' => 'BPC-157 5mg',
            'source_type' => 'woo_api',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('scraped_products', [
            'external_id' => '102',
            'discount_price' => '49.99',
        ]);

        // Nothing should be live yet because auto_promote is off
        $this->assertDatabaseCount('products', 0);
    }

    public function test_woocommerce_ingest_job_records_auth_failure(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);

        Http::fake([
            'acme.example.com/wp-json/wc/v3/products*' => Http::response(['code' => 'woocommerce_rest_cannot_view'], 401),
        ]);

        (new RunWooCommerceIngestJob($config))->handle(app(IngestionService::class));

        $fresh = $config->fresh();
        $this->assertEquals(1, $fresh->error_count);
        $this->assertStringContainsString('Authentication failed', $fresh->last_error);
        $this->assertDatabaseCount('scraped_products', 0);
    }

    public function test_vendor_integration_form_validates_woo_store(): void
    {
        $brand = $this->makeBrand();
        $user = \App\Models\User::create([
            'name' => 'V',
            'email' => 'v@example.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
        ]);
        $brand->user_id = $user->id;
        $brand->save();

        Http::fake([
            'goodstore.example.com/wp-json/wc/v3/products*' => Http::response([], 200),
            'badstore.example.com/wp-json/wc/v3/products*' => Http::response([], 401),
        ]);

        // Bad credentials -> validation error
        $this->actingAs($user)
            ->post('/vendor/integrations/woo', [
                'store_url' => 'https://badstore.example.com',
                'consumer_key' => 'ck_bad',
                'consumer_secret' => 'cs_bad',
                'frequency' => 'daily',
            ])
            ->assertSessionHasErrors(['consumer_key']);

        $this->assertDatabaseCount('scraping_configs', 0);

        // Good credentials -> saved
        $this->actingAs($user)
            ->post('/vendor/integrations/woo', [
                'store_url' => 'https://goodstore.example.com',
                'consumer_key' => 'ck_good',
                'consumer_secret' => 'cs_good',
                'frequency' => 'hourly',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('scraping_configs', [
            'vendor_id' => $brand->id,
            'type' => ScrapingConfig::TYPE_WOO_API,
            'store_url' => 'https://goodstore.example.com',
            'frequency' => 'hourly',
        ]);
    }
}
