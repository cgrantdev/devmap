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
use Illuminate\Support\Facades\Log;
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
            'source_url' => 'https://acme.example.com/products/semag',
        ]);

        $this->assertEquals('approved', $staged->fresh()->status);
        $this->assertNotNull($staged->fresh()->product_id);
        $this->assertDatabaseHas('products', [
            'name' => 'Semaglutide',
            'brand_id' => $brand->id,
            'product_url' => 'https://acme.example.com/products/semag',
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
        // email_verified_at is not fillable; vendor routes require a verified email.
        $user->forceFill(['email_verified_at' => now()])->save();
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

    public function test_promote_updates_existing_root_slug_product_instead_of_rejecting(): void
    {
        $brand = Brand::create([
            'name' => 'Instant Peptides',
            'slug' => 'instant-peptides',
            'is_active' => true,
        ]);
        $config = $this->makeConfig($brand);
        $product = Product::create([
            'name' => 'BPC-157',
            'slug' => 'instant-bpc157',
            'brand_id' => $brand->id,
            'price' => 49.99,
            'product_url' => 'https://instantpeptides.com/bpc157',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'bpc157',
            'name' => 'BPC-157 10mg',
            'price' => '59.50',
            'source_url' => 'https://instantpeptides.com/bpc157',
        ]);

        Log::spy();
        $updated = $ingestion->promote($staged->fresh());

        $this->assertNotNull($updated);
        $this->assertSame($product->id, $updated->id);
        $this->assertEqualsWithDelta(59.50, (float) $updated->fresh()->price, 0.001);
        $this->assertSame('https://instantpeptides.com/bpc157', $updated->fresh()->product_url);
        $this->assertSame('approved', $staged->fresh()->status);
        $this->assertSame(1, Product::where('brand_id', $brand->id)->count());
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($staged, $product) {
            return $message === 'promote: url-pattern bypass'
                && ($context['staged_id'] ?? null) === $staged->id
                && ($context['product_id'] ?? null) === $product->id
                && ($context['match_type'] ?? null) === 'url'
                && ($context['url'] ?? null) === 'https://instantpeptides.com/bpc157';
        })->once();
    }

    public function test_promote_matches_double_slash_url_through_normalization(): void
    {
        $brand = Brand::create([
            'name' => 'Limitless Biotech',
            'slug' => 'limitless-biotech',
            'is_active' => true,
        ]);
        $config = $this->makeConfig($brand);
        $product = Product::create([
            'name' => 'BPC-157 / TB-500 blend spray',
            'slug' => 'limitless-blend-spray',
            'brand_id' => $brand->id,
            'price' => 40,
            'product_url' => 'https://limitlesslifenootropics.com/bpc-157-acetate-tb-500-fragment-17-23-blend-spray',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'blend-spray',
            'name' => 'Staged blend spray',
            'price' => '44.00',
            'source_url' => 'https://LimitlessLifeNootropics.com//bpc-157-acetate-tb-500-fragment-17-23-blend-spray/?utm_source=pmap#reviews',
        ]);

        $updated = $ingestion->promote($staged->fresh());

        $this->assertNotNull($updated);
        $this->assertSame($product->id, $updated->id);
        $this->assertEqualsWithDelta(44.00, (float) $updated->fresh()->price, 0.001);
        $this->assertSame(
            'https://limitlesslifenootropics.com/bpc-157-acetate-tb-500-fragment-17-23-blend-spray',
            $updated->fresh()->product_url
        );
        $this->assertSame('approved', $staged->fresh()->status);
        $this->assertSame(1, Product::where('brand_id', $brand->id)->count());
    }

    public function test_promote_rejects_a_new_product_whose_url_fails_the_pattern(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $ingestion = app(IngestionService::class);

        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'new-root',
            'name' => 'Brand New Root Slug',
            'price' => '19.00',
            'source_url' => 'https://newvendor.example.com/brand-new-root-slug',
        ]);

        $this->assertNull($ingestion->promote($staged->fresh()));
        $this->assertSame('rejected', $staged->fresh()->status);
        $this->assertDatabaseMissing('products', ['name' => 'Brand New Root Slug']);
    }

    public function test_promote_does_not_match_the_same_url_from_another_vendor(): void
    {
        $owner = Brand::create([
            'name' => 'Instant Peptides',
            'slug' => 'instant-peptides',
            'is_active' => true,
        ]);
        $other = Brand::create([
            'name' => 'Other Vendor',
            'slug' => 'other-vendor',
            'is_active' => true,
        ]);
        $existing = Product::create([
            'name' => 'BPC-157',
            'slug' => 'owner-bpc157',
            'brand_id' => $owner->id,
            'external_id' => 'bpc157',
            'price' => 49.99,
            'product_url' => 'https://instantpeptides.com/bpc157',
            'status' => 'active',
        ]);
        $config = $this->makeConfig($other);
        $ingestion = app(IngestionService::class);

        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'bpc157',
            'name' => 'BPC-157',
            'price' => '10.00',
            'source_url' => 'https://instantpeptides.com/bpc157/',
        ]);

        $this->assertNull($ingestion->promote($staged->fresh()));
        $this->assertSame('rejected', $staged->fresh()->status);
        $this->assertEqualsWithDelta(49.99, (float) $existing->fresh()->price, 0.001);
        $this->assertSame(1, Product::count());
    }

    public function test_promote_matches_existing_product_by_external_id_when_url_shape_is_bad(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $product = Product::create([
            'name' => 'Ipamorelin',
            'slug' => 'acme-ipamorelin',
            'brand_id' => $brand->id,
            'external_id' => 'sku-ipa',
            'price' => 30,
            'product_url' => 'https://acme.example.com/product/ipamorelin',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'sku-ipa',
            'name' => 'Staged ipamorelin',
            'price' => '33.00',
            'source_url' => 'https://acme.example.com/ipamorelin',
        ]);

        $updated = $ingestion->promote($staged->fresh());

        $this->assertNotNull($updated);
        $this->assertSame($product->id, $updated->id);
        $this->assertEqualsWithDelta(33.00, (float) $updated->fresh()->price, 0.001);
        $this->assertSame('https://acme.example.com/product/ipamorelin', $updated->fresh()->product_url);
        $this->assertSame('approved', $staged->fresh()->status);
        $this->assertSame(1, Product::where('brand_id', $brand->id)->count());
    }

    public function test_promote_updates_only_the_matching_woo_variant_query(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $five = Product::create([
            'name' => 'BPC-157',
            'slug' => 'woo-bpc-5mg',
            'brand_id' => $brand->id,
            'price' => 30,
            'product_url' => 'https://elite.example.com/product/bpc-157?attribute_pa_size=5mg',
            'status' => 'active',
        ]);
        $ten = Product::create([
            'name' => 'BPC-157',
            'slug' => 'woo-bpc-10mg',
            'brand_id' => $brand->id,
            'price' => 50,
            'product_url' => 'https://elite.example.com/product/bpc-157?attribute_pa_size=10mg',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'woo-10mg',
            'name' => 'BPC-157',
            'price' => '55.00',
            'source_url' => 'https://elite.example.com/product/bpc-157?attribute_pa_size=10mg&utm_source=pmap#dose',
        ]);

        $updated = $ingestion->promote($staged->fresh());

        $this->assertNotNull($updated);
        $this->assertSame($ten->id, $updated->id);
        $this->assertEqualsWithDelta(55.00, (float) $ten->fresh()->price, 0.001);
        $this->assertEqualsWithDelta(30.00, (float) $five->fresh()->price, 0.001);
        $this->assertSame(
            'https://elite.example.com/product/bpc-157?attribute_pa_size=10mg',
            $ten->fresh()->product_url
        );
        $this->assertSame(
            'https://elite.example.com/product/bpc-157?attribute_pa_size=5mg',
            $five->fresh()->product_url
        );
        $this->assertSame('approved', $staged->fresh()->status);
        $this->assertSame(2, Product::where('brand_id', $brand->id)->count());
    }

    public function test_promote_leaves_an_ambiguous_shared_url_pending(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $shared = 'https://medusa.example.com/products/bpc-157';
        $first = Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'medusa-bpc-5',
            'brand_id' => $brand->id,
            'price' => 30,
            'product_url' => $shared,
            'status' => 'active',
        ]);
        $second = Product::create([
            'name' => 'BPC-157 10mg',
            'slug' => 'medusa-bpc-10',
            'brand_id' => $brand->id,
            'price' => 50,
            'product_url' => $shared,
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'medusa-shared',
            'name' => 'Staged shared parent',
            'price' => '55.00',
            'source_url' => $shared.'?utm_source=pmap#reviews',
        ]);

        Log::spy();
        $this->assertNull($ingestion->promote($staged->fresh()));

        $this->assertSame('pending', $staged->fresh()->status);
        $this->assertEqualsWithDelta(30.00, (float) $first->fresh()->price, 0.001);
        $this->assertEqualsWithDelta(50.00, (float) $second->fresh()->price, 0.001);
        $this->assertSame($shared, $first->fresh()->product_url);
        $this->assertSame($shared, $second->fresh()->product_url);
        $this->assertSame(2, Product::where('brand_id', $brand->id)->count());
        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) use ($staged, $first, $second) {
            $ids = $context['candidate_ids'] ?? [];

            return $message === 'promote: ambiguous product url match'
                && ($context['staged_id'] ?? null) === $staged->id
                && in_array($first->id, $ids, true)
                && in_array($second->id, $ids, true);
        })->once();
    }

    public function test_promote_leaves_pending_when_a_sibling_differs_only_by_query(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $parent = Product::create([
            'name' => 'BPC-157',
            'slug' => 'parent-bpc',
            'brand_id' => $brand->id,
            'price' => 30,
            'product_url' => 'https://shop.example.com/products/bpc-157',
            'status' => 'active',
        ]);
        $variant = Product::create([
            'name' => 'BPC-157',
            'slug' => 'variant-bpc',
            'brand_id' => $brand->id,
            'price' => 50,
            'product_url' => 'https://shop.example.com/products/bpc-157?variation_id=99',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'variation-99',
            'name' => 'BPC-157',
            'price' => '55.00',
            'source_url' => 'https://shop.example.com/products/bpc-157?variation_id=99&utm_campaign=spring',
        ]);

        Log::spy();
        $this->assertNull($ingestion->promote($staged->fresh()));

        $this->assertSame('pending', $staged->fresh()->status);
        $this->assertEqualsWithDelta(30.00, (float) $parent->fresh()->price, 0.001);
        $this->assertEqualsWithDelta(50.00, (float) $variant->fresh()->price, 0.001);
        $this->assertSame('https://shop.example.com/products/bpc-157', $parent->fresh()->product_url);
        $this->assertSame(
            'https://shop.example.com/products/bpc-157?variation_id=99',
            $variant->fresh()->product_url
        );
        Log::shouldHaveReceived('warning')->withArgs(function ($message) {
            return $message === 'promote: ambiguous product url match';
        })->once();
    }

    public function test_promote_does_not_update_a_linked_product_from_another_vendor(): void
    {
        $owner = Brand::create([
            'name' => 'Owner Vendor',
            'slug' => 'owner-vendor',
            'is_active' => true,
        ]);
        $other = $this->makeBrand();
        $linked = Product::create([
            'name' => 'Owner BPC',
            'slug' => 'owner-bpc',
            'brand_id' => $owner->id,
            'price' => 40,
            'product_url' => 'https://owner.example.com/product/bpc-157',
            'status' => 'active',
        ]);
        $config = $this->makeConfig($other);
        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'cross-vendor',
            'name' => 'Staged cross vendor',
            'price' => '99.00',
            'source_url' => 'https://other.example.com/not-a-product',
        ]);
        $staged->product_id = $linked->id;
        $staged->save();

        Log::spy();
        $this->assertNull($ingestion->promote($staged->fresh()));

        $this->assertSame('rejected', $staged->fresh()->status);
        $this->assertEqualsWithDelta(40.00, (float) $linked->fresh()->price, 0.001);
        $this->assertSame('https://owner.example.com/product/bpc-157', $linked->fresh()->product_url);
        $this->assertSame(1, Product::count());
        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) use ($staged, $linked, $owner, $other) {
            return $message === 'promote: linked product brand mismatch'
                && ($context['staged_id'] ?? null) === $staged->id
                && ($context['product_id'] ?? null) === $linked->id
                && (int) ($context['linked_brand_id'] ?? 0) === $owner->id
                && (int) ($context['staged_brand_id'] ?? 0) === $other->id;
        })->once();
    }

    public function test_promote_does_not_match_a_blank_external_id(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $product = Product::create([
            'name' => 'Real Product',
            'slug' => 'real-product',
            'brand_id' => $brand->id,
            'external_id' => '   ',
            'price' => 25,
            'product_url' => 'https://acme.example.com/product/real',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => '   ',
            'name' => 'Whitespace sku',
            'price' => '80.00',
            'source_url' => 'https://acme.example.com/whitespace-root',
        ]);

        $this->assertNull($ingestion->promote($staged->fresh()));
        $this->assertSame('rejected', $staged->fresh()->status);
        $this->assertEqualsWithDelta(25.00, (float) $product->fresh()->price, 0.001);
        $this->assertSame('https://acme.example.com/product/real', $product->fresh()->product_url);
        $this->assertSame(1, Product::where('brand_id', $brand->id)->count());
    }

    public function test_promote_strips_tracking_params_and_fragments_from_a_new_product_url(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'semag-10',
            'name' => 'Semaglutide 10mg',
            'price' => '99.00',
            'source_url' => 'https://acme.example.com/products/semag?attribute_pa_size=10mg&utm_source=pmap&gclid=abc&fbclid=def&ref=home#reviews',
        ]);

        $product = $ingestion->promote($staged->fresh());

        $this->assertNotNull($product);
        $this->assertSame(
            'https://acme.example.com/products/semag?attribute_pa_size=10mg',
            $product->fresh()->product_url
        );
    }

    public function test_promote_matches_an_http_variant_url_to_the_https_row(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $five = Product::create([
            'name' => 'BPC-157',
            'slug' => 'http-bpc-5mg',
            'brand_id' => $brand->id,
            'price' => 30,
            'product_url' => 'https://elite.example.com/product/bpc-157?attribute_pa_size=5mg',
            'status' => 'active',
        ]);
        $ten = Product::create([
            'name' => 'BPC-157',
            'slug' => 'http-bpc-10mg',
            'brand_id' => $brand->id,
            'price' => 50,
            'product_url' => 'https://elite.example.com/product/bpc-157?attribute_pa_size=10mg',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'http-10mg',
            'name' => 'BPC-157',
            'price' => '55.00',
            'source_url' => 'http://elite.example.com/product/bpc-157?attribute_pa_size=10mg',
        ]);

        $updated = $ingestion->promote($staged->fresh());

        $this->assertNotNull($updated);
        $this->assertSame($ten->id, $updated->id);
        $this->assertEqualsWithDelta(55.00, (float) $ten->fresh()->price, 0.001);
        $this->assertEqualsWithDelta(30.00, (float) $five->fresh()->price, 0.001);
        $this->assertSame(
            'https://elite.example.com/product/bpc-157?attribute_pa_size=10mg',
            $ten->fresh()->product_url
        );
        $this->assertSame(
            'https://elite.example.com/product/bpc-157?attribute_pa_size=5mg',
            $five->fresh()->product_url
        );
        $this->assertSame(2, Product::where('brand_id', $brand->id)->count());
    }

    public function test_promote_matches_a_www_variant_url_to_the_non_www_row(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $five = Product::create([
            'name' => 'BPC-157',
            'slug' => 'www-bpc-5mg',
            'brand_id' => $brand->id,
            'price' => 30,
            'product_url' => 'https://elite.example.com/product/bpc-157?attribute_pa_size=5mg',
            'status' => 'active',
        ]);
        $ten = Product::create([
            'name' => 'BPC-157',
            'slug' => 'www-bpc-10mg',
            'brand_id' => $brand->id,
            'price' => 50,
            'product_url' => 'https://elite.example.com/product/bpc-157?attribute_pa_size=10mg',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'www-10mg',
            'name' => 'BPC-157',
            'price' => '55.00',
            'source_url' => 'https://www.elite.example.com/product/bpc-157?attribute_pa_size=10mg',
        ]);

        $updated = $ingestion->promote($staged->fresh());

        $this->assertNotNull($updated);
        $this->assertSame($ten->id, $updated->id);
        $this->assertEqualsWithDelta(55.00, (float) $ten->fresh()->price, 0.001);
        $this->assertEqualsWithDelta(30.00, (float) $five->fresh()->price, 0.001);
        $this->assertSame(
            'https://elite.example.com/product/bpc-157?attribute_pa_size=10mg',
            $ten->fresh()->product_url
        );
        $this->assertSame(
            'https://elite.example.com/product/bpc-157?attribute_pa_size=5mg',
            $five->fresh()->product_url
        );
        $this->assertSame(2, Product::where('brand_id', $brand->id)->count());
    }

    public function test_promote_leaves_pending_when_the_name_matches_more_than_one_row(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $five = Product::create([
            'name' => 'BPC-157',
            'slug' => 'name-bpc-5mg',
            'brand_id' => $brand->id,
            'price' => 30,
            'product_url' => 'https://shop.example.com/product/bpc-157-5mg',
            'status' => 'active',
        ]);
        $ten = Product::create([
            'name' => 'BPC-157',
            'slug' => 'name-bpc-10mg',
            'brand_id' => $brand->id,
            'price' => 50,
            'product_url' => 'https://shop.example.com/product/bpc-157-10mg',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'name-ambiguous',
            'name' => 'BPC-157',
            'price' => '55.00',
            'source_url' => 'https://shop.example.com/product/bpc-157-new',
        ]);

        Log::spy();
        $this->assertNull($ingestion->promote($staged->fresh()));

        $this->assertSame('pending', $staged->fresh()->status);
        $this->assertEqualsWithDelta(30.00, (float) $five->fresh()->price, 0.001);
        $this->assertEqualsWithDelta(50.00, (float) $ten->fresh()->price, 0.001);
        $this->assertSame('https://shop.example.com/product/bpc-157-5mg', $five->fresh()->product_url);
        $this->assertSame('https://shop.example.com/product/bpc-157-10mg', $ten->fresh()->product_url);
        $this->assertSame(2, Product::where('brand_id', $brand->id)->count());
        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context) use ($staged, $five, $ten) {
            $ids = $context['candidate_ids'] ?? [];

            return $message === 'promote: ambiguous product name match'
                && ($context['staged_id'] ?? null) === $staged->id
                && (int) ($context['brand_id'] ?? 0) === (int) $five->brand_id
                && in_array($five->id, $ids, true)
                && in_array($ten->id, $ids, true);
        })->once();
    }

    public function test_promote_matches_a_mid_path_double_slash_without_creating_a_duplicate(): void
    {
        $brand = $this->makeBrand();
        $config = $this->makeConfig($brand);
        $product = Product::create([
            'name' => 'BPC-157',
            'slug' => 'slash-bpc',
            'brand_id' => $brand->id,
            'price' => 40,
            'product_url' => 'https://acme.example.com/products/bpc-157',
            'status' => 'active',
        ]);

        $ingestion = app(IngestionService::class);
        $staged = $ingestion->upsertStaged($config, [
            'external_id' => 'slash-bpc',
            'name' => 'Staged double slash',
            'price' => '45.00',
            'source_url' => 'https://acme.example.com/products//bpc-157',
        ]);

        $updated = $ingestion->promote($staged->fresh());

        $this->assertNotNull($updated);
        $this->assertSame($product->id, $updated->id);
        $this->assertEqualsWithDelta(45.00, (float) $product->fresh()->price, 0.001);
        $this->assertSame('https://acme.example.com/products/bpc-157', $product->fresh()->product_url);
        $this->assertSame('approved', $staged->fresh()->status);
        $this->assertSame(1, Product::where('brand_id', $brand->id)->count());
    }
}
