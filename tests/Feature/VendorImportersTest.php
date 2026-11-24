<?php

namespace Tests\Feature;

use App\Jobs\RunJsonLdIngestJob;
use App\Jobs\RunWooStoreIngestJob;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use App\Models\VendorSetting;
use App\Services\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * Importers for vendors that declined API keys (25 Nov 2026): the JSON-LD
 * importer's per-size offers, several products per page, URL sizes and
 * WooCommerce price specs; the WooCommerce Store API importer; and feed
 * rows whose URLs are not /product/ paths.
 */
class VendorImportersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
    }

    public function test_offers_named_by_size_become_one_listing_each(): void
    {
        // V8 Peptides: the product name has no size; each offer is a size.
        $config = $this->config('v8-test', ScrapingConfig::TYPE_JSON_LD, 'https://shop.example.com/sitemap.xml', 'USD');
        $this->fakeSite(['https://shop.example.com/products/bpc-157' => [[
            '@type' => 'Product',
            'name' => 'BPC-157',
            'sku' => 'bpc-157',
            'offers' => [
                ['@type' => 'Offer', 'name' => '5 mg', 'sku' => 'V8-BPC-5', 'price' => '49.00', 'priceCurrency' => 'USD', 'url' => 'https://shop.example.com/products/bpc-157'],
                ['@type' => 'Offer', 'name' => '10 mg', 'sku' => 'V8-BPC-10', 'price' => '69.00', 'priceCurrency' => 'USD', 'url' => 'https://shop.example.com/products/bpc-157'],
            ],
        ]]]);

        (new RunJsonLdIngestJob($config))->handle(app(IngestionService::class));

        $five = Product::where('brand_id', $config->vendor_id)->where('name', 'BPC-157 5 mg')->firstOrFail();
        $ten = Product::where('brand_id', $config->vendor_id)->where('name', 'BPC-157 10 mg')->firstOrFail();
        $this->assertSame('5mg', $five->size_mg);
        $this->assertEquals(69, (float) $ten->price);
        $this->assertSame('https://shop.example.com/products/bpc-157#5-mg', $five->product_url);
        $this->assertSame('https://shop.example.com/products/bpc-157#10-mg', $ten->product_url);
    }

    public function test_every_product_on_a_page_is_listed_with_its_pack(): void
    {
        // Vancouver Island Peptides: one Product per variant on a .html page,
        // each sold as a pack of 10 vials.
        $config = $this->config('vip-test', ScrapingConfig::TYPE_JSON_LD, 'https://shop.example.com/sitemap.xml', 'CAD',
            ['url_pattern' => '~\.html$~i']);
        $page = 'https://shop.example.com/bpc-157-canada.html';
        $variant = fn (int $n, string $name, string $price) => [
            '@type' => 'Product',
            '@id' => $page.'#variant-'.$n,
            'name' => $name,
            'sku' => 'VIP-'.$n,
            'url' => $page.'#variant-'.$n,
            'description' => $name.'. Pack: 10 vials. Vancouver, BC lab partner.',
            'offers' => ['@type' => 'Offer', 'price' => $price, 'priceCurrency' => 'CAD', 'availability' => 'https://schema.org/InStock'],
        ];
        $this->fakeSite([$page => [
            $variant(5, 'BPC-157 10 mg', '180.00'),
            $variant(18, 'BPC-157 + TB-500 Combo 10 mg + 10 mg', '330.00'),
        ]]);

        (new RunJsonLdIngestJob($config))->handle(app(IngestionService::class));

        $single = Product::where('product_url', $page.'#variant-5')->firstOrFail();
        $this->assertSame('BPC-157 10 mg — 10 vials', $single->name);
        $this->assertSame('10mg', $single->size_mg);
        $combo = Product::where('product_url', $page.'#variant-18')->firstOrFail();
        $this->assertSame('10mg/10mg', $combo->size_mg);
        $this->assertEquals(330, (float) $combo->price);
    }

    public function test_a_size_only_in_the_url_is_added_to_the_name(): void
    {
        // Southern Labs: "Adamax" at /product/adamax-5mg.
        $config = $this->config('southern-test', ScrapingConfig::TYPE_JSON_LD, 'https://shop.example.com/sitemap.xml', 'NZD');
        $this->fakeSite(['https://shop.example.com/product/adamax-5mg' => [[
            '@type' => 'Product',
            'name' => 'Adamax',
            'url' => 'https://shop.example.com/product/adamax-5mg',
            'offers' => ['@type' => 'Offer', 'price' => '90.00', 'priceCurrency' => 'NZD'],
        ]]]);

        (new RunJsonLdIngestJob($config))->handle(app(IngestionService::class));

        $product = Product::where('brand_id', $config->vendor_id)->firstOrFail();
        $this->assertSame('Adamax 5mg', $product->name);
        $this->assertSame('5mg', $product->size_mg);
    }

    public function test_a_price_inside_price_specification_is_read(): void
    {
        // WooCommerce's structured data (NextSelf Labs).
        $config = $this->config('woo-ld-test', ScrapingConfig::TYPE_JSON_LD, 'https://shop.example.com/sitemap.xml', 'USD');
        $this->fakeSite(['https://shop.example.com/product/tesamorelin-20mg' => [[
            '@type' => 'Product',
            'name' => 'Tesamorelin 20mg',
            'offers' => [[
                '@type' => 'Offer',
                'priceSpecification' => [['@type' => 'UnitPriceSpecification', 'price' => '109.99', 'priceCurrency' => 'USD']],
                'availability' => 'https://schema.org/InStock',
            ]],
        ]]]);

        (new RunJsonLdIngestJob($config))->handle(app(IngestionService::class));

        $this->assertEquals(109.99, (float) Product::where('brand_id', $config->vendor_id)->value('price'));
    }

    public function test_sizes_are_read_from_names_and_urls(): void
    {
        $this->assertSame('10mg', RunJsonLdIngestJob::sizeFromName('Semaglutide 10 mg/vial'));
        $this->assertSame('1mg', RunJsonLdIngestJob::sizeFromName('ACE-083 1 mg vial'));
        $this->assertSame('5mg', RunJsonLdIngestJob::sizeFromUrl('https://x.example/product/adamax-5mg'));
        $this->assertSame('1000mg', RunJsonLdIngestJob::sizeFromUrl('https://x.example/product/NAD-1000-mg'));
        $this->assertSame('10mg', RunJsonLdIngestJob::sizeFromUrl('https://x.example/product/SS-31-10-mg'));
        $this->assertNull(RunJsonLdIngestJob::sizeFromUrl('https://x.example/product/retatrutide'));
        $this->assertNull(RunJsonLdIngestJob::sizeFromUrl('https://x.example/bpc-157-canada.html'));
    }

    public function test_the_woo_store_api_lists_each_variation_and_skips_add_ons(): void
    {
        $config = $this->config('nextself-test', ScrapingConfig::TYPE_WOO_STORE, 'https://shop.example.com', null);
        $variation = fn (int $id, string $name, string $option, string $regular, string $sale, string $slug) => [
            'id' => $id,
            'name' => $name,
            'type' => 'variation',
            'permalink' => 'https://shop.example.com/product/'.$slug.'/?attribute_vial-size='.$option,
            'variation' => 'Vial Size: '.$option,
            'is_in_stock' => true,
            'prices' => ['price' => $sale, 'regular_price' => $regular, 'sale_price' => $sale, 'currency_code' => 'USD', 'currency_minor_unit' => 2],
            'images' => [['src' => 'https://shop.example.com/'.$slug.'.jpg']],
        ];
        Http::fake(function (Request $request) use ($variation) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            if (!str_contains($request->url(), '/wp-json/wc/store/v1/products')) {
                return Http::response('', 404);
            }

            return match ($query['type'] ?? null) {
                'simple' => Http::response([], 200),
                'variation' => Http::response([
                    $variation(10202, 'NS-3RT', '30mg', '17999', '17999', 'ns-3rt'),
                    $variation(124, 'BPC-157', '10mg', '5999', '4999', 'bpc-157'),
                    ['id' => 11618, 'name' => 'Chilled Shipping', 'variation' => 'Service: Cold Packs',
                        'permalink' => 'https://shop.example.com/product/chilled-shipping/?attribute_service=Cold+Packs',
                        'prices' => ['price' => '299', 'regular_price' => '299', 'sale_price' => '299', 'currency_minor_unit' => 2]],
                ], 200, ['X-WP-TotalPages' => '1']),
                default => Http::response('', 400),
            };
        });

        (new RunWooStoreIngestJob($config))->handle(app(IngestionService::class));

        Http::assertSentCount(2);
        $reta = Product::where('name', 'NS-3RT 30mg')->firstOrFail();
        $this->assertEquals(179.99, (float) $reta->price);
        $this->assertSame('30mg', $reta->size_mg);
        $this->assertSame('https://shop.example.com/product/ns-3rt/?attribute_vial-size=30mg', $reta->product_url);
        $bpc = Product::where('name', 'BPC-157 10mg')->firstOrFail();
        $this->assertEquals(59.99, (float) $bpc->price);
        $this->assertEquals(49.99, (float) $bpc->discount_price);
        $this->assertFalse(Product::where('name', 'like', 'Chilled Shipping%')->exists());
        $this->assertNull($config->fresh()->last_error);
    }

    public function test_feed_rows_with_html_urls_are_promoted(): void
    {
        // Vancouver Island Peptides' feed: product pages are /x-canada.html.
        $config = $this->config('feed-test', ScrapingConfig::TYPE_JSON_FEED, 'https://shop.example.com/api/feed.json', null);

        app(IngestionService::class)->upsertStaged($config, [
            'source_type' => ScrapedProduct::SOURCE_JSON_FEED,
            'external_id' => 'VIP-5',
            'source_url' => 'https://shop.example.com/bpc-157-canada.html',
            'name' => 'BPC-157 10 mg',
            'price' => 180,
        ]);

        $this->assertDatabaseHas('products', ['product_url' => 'https://shop.example.com/bpc-157-canada.html']);
    }

    public function test_the_report_lists_a_vendors_configs(): void
    {
        $config = $this->config('report-test', ScrapingConfig::TYPE_JSON_LD, 'https://shop.example.com/sitemap.xml', 'USD');
        $config->update(['last_error' => 'Refused (403) at https://shop.example.com/sitemap.xml']);

        Artisan::call('scraping:report', ['brands' => ['report-test', 'no-such-brand']]);
        $output = Artisan::output();

        $this->assertStringContainsString('json_ld', $output);
        $this->assertStringContainsString('Refused (403)', $output);
        $this->assertStringContainsString('credentials: set', $output);
        $this->assertStringNotContainsString('USD', $output);
        $this->assertStringContainsString('no-such-brand: no brand', $output);
    }

    public function test_the_migration_moves_the_vendors_and_fixes_the_southern_labs_host(): void
    {
        $rc = Brand::create(['name' => 'RC Fairtrade B.V.', 'slug' => 'rc-fairtrade-bv', 'is_active' => true]);
        $southern = Brand::create(['name' => 'Southern Labs', 'slug' => 'southern-labs', 'is_active' => true]);
        $settings = VendorSetting::create([
            'brand_id' => $southern->id,
            'shop_url' => 'https://www.southernlabs.co.nz',
            'referral_url' => 'https://www.southernlabs.co.nz/?ref=PMAP',
        ]);
        $old = ScrapingConfig::create([
            'vendor_id' => $rc->id,
            'vendor_name' => $rc->name,
            'type' => ScrapingConfig::TYPE_PAGE_SCRAPE,
            'products_url' => 'https://rcpeptides.com',
            'frequency' => 'daily',
            'enabled' => true,
            'auto_promote' => true,
        ]);

        $migration = require database_path('migrations/2026_11_25_100000_vendor_catalog_imports.php');
        $migration->up();

        $fresh = $old->fresh();
        $this->assertSame(ScrapingConfig::TYPE_JSON_LD, $fresh->type);
        $this->assertSame('https://rcpeptides.com/sitemap.xml', $fresh->products_url);
        $this->assertSame('EUR', $fresh->auth_credentials['currency_code']);
        $this->assertFalse($fresh->next_run_at->isFuture());
        $created = ScrapingConfig::where('vendor_id', $southern->id)->firstOrFail();
        $this->assertSame('https://southernlabs.co.nz/sitemap.xml', $created->products_url);
        $this->assertSame('https://southernlabs.co.nz', $settings->fresh()->shop_url);
        $this->assertSame('https://southernlabs.co.nz/?ref=PMAP', $settings->fresh()->referral_url);

        $migration->down();

        $this->assertSame(ScrapingConfig::TYPE_PAGE_SCRAPE, $old->fresh()->type);
        $this->assertFalse(ScrapingConfig::where('vendor_id', $southern->id)->exists());
        $this->assertSame('https://www.southernlabs.co.nz', $settings->fresh()->shop_url);
    }

    /**
     * Fake a store: /sitemap.xml lists the given pages, each page carries
     * its Product nodes as separate JSON-LD blocks.
     *
     * @param  array<string, list<array<string, mixed>>>  $pages
     */
    private function fakeSite(array $pages): void
    {
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .implode('', array_map(fn (string $url) => '<url><loc>'.$url.'</loc></url>', array_keys($pages)))
            .'</urlset>';
        $fakes = ['shop.example.com/sitemap.xml' => Http::response($sitemap)];
        foreach ($pages as $url => $products) {
            $blocks = implode('', array_map(
                fn (array $product) => '<script type="application/ld+json">'.json_encode(['@context' => 'https://schema.org'] + $product).'</script>',
                $products
            ));
            $fakes[(string) preg_replace('~^https?://~', '', $url)] = Http::response('<html><head>'.$blocks.'</head><body></body></html>');
        }
        $fakes['*'] = Http::response('', 404);
        Http::fake($fakes);
    }

    private function config(string $slug, string $type, string $productsUrl, ?string $currency, array $selectors = []): ScrapingConfig
    {
        $brand = Brand::create(['name' => ucwords(str_replace('-', ' ', $slug)), 'slug' => $slug, 'is_active' => true]);

        return ScrapingConfig::create([
            'vendor_id' => $brand->id,
            'vendor_name' => $brand->name,
            'type' => $type,
            'products_url' => $productsUrl,
            'store_url' => 'https://shop.example.com',
            'selectors' => $selectors + ['max_products' => 50],
            'auth_credentials' => $currency ? ['currency_code' => $currency] : null,
            'frequency' => 'daily',
            'enabled' => true,
            'auto_promote' => true,
        ]);
    }
}
