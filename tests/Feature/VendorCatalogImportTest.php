<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\VendorsController;
use App\Jobs\RunJsonLdIngestJob;
use App\Jobs\RunPageScraperJob;
use App\Jobs\RunPythonScraperJob;
use App\Models\Brand;
use App\Models\CategoryAlias;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ScrapingConfig;
use App\Models\VendorSetting;
use App\Services\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * Capital Precision Labs listed 3 of the 242 products in their sitemap
 * (24 Nov 2026): "Discover Products" had read their homepage once, and
 * the daily page scrape only refreshes products we already list. The
 * JSON-LD importer now reads sitemaps, and products named after their URL
 * take the vendor's own name.
 */
class VendorCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
    }

    public function test_a_sitemap_lists_the_whole_catalog(): void
    {
        $config = $this->jsonLdConfig('https://shop.example.com/sitemap.xml');
        Http::fake([
            'shop.example.com/sitemap.xml' => Http::response($this->sitemap([
                'https://shop.example.com/',
                'https://shop.example.com/shop',
                'https://shop.example.com/products/bpc-157-10mg',
                'https://shop.example.com/products/bpc-tb4-5-5',
                'https://shop.example.com/research/bpc-157-peptide-research',
            ])),
            'shop.example.com/products/bpc-157-10mg' => Http::response($this->productPage('BPC-157 10mg', 32, 244)),
            'shop.example.com/products/bpc-tb4-5-5' => Http::response($this->productPage('BPC-157 + TB-500 Blend 5mg + 5mg', 58, 443)),
            '*' => Http::response('', 404),
        ]);

        (new RunJsonLdIngestJob($config))->handle(app(IngestionService::class));

        // The sitemap and the two product pages; not the other pages.
        Http::assertSentCount(3);

        $single = Product::where('product_url', 'https://shop.example.com/products/bpc-157-10mg')->firstOrFail();
        $this->assertSame('BPC-157 10mg', $single->name);
        $this->assertEquals(32, (float) $single->price); // the single vial, not the kit of 10
        $this->assertSame('10mg', $single->size_mg);

        $blend = Product::where('product_url', 'https://shop.example.com/products/bpc-tb4-5-5')->firstOrFail();
        $this->assertSame('5mg/5mg', $blend->size_mg);

        $this->assertNull($config->fresh()->last_error);
    }

    public function test_a_store_root_reads_its_sitemap_index(): void
    {
        $config = $this->jsonLdConfig('https://shop.example.com');
        Http::fake([
            'shop.example.com/sitemap.xml' => Http::response(
                '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
                .'<sitemap><loc>https://shop.example.com/sitemap_pages_1.xml</loc></sitemap>'
                .'<sitemap><loc>https://shop.example.com/sitemap_products_1.xml</loc></sitemap>'
                .'</sitemapindex>'
            ),
            'shop.example.com/sitemap_products_1.xml' => Http::response($this->sitemap([
                'https://shop.example.com/products/semaglutide-10mg',
            ])),
            'shop.example.com/products/semaglutide-10mg' => Http::response($this->productPage('Semaglutide 10mg', 33, 255)),
            '*' => Http::response('', 404),
        ]);

        (new RunJsonLdIngestJob($config))->handle(app(IngestionService::class));

        // The index, the product sitemap and the product page.
        Http::assertSentCount(3);
        $this->assertDatabaseHas('products', ['name' => 'Semaglutide 10mg', 'brand_id' => $config->vendor_id]);
    }

    public function test_a_store_root_without_a_sitemap_reads_the_links_on_the_page(): void
    {
        $config = $this->jsonLdConfig('https://shop.example.com/');
        Http::fake([
            'shop.example.com/sitemap.xml' => Http::response('', 404),
            'shop.example.com/products/tb-500-10mg' => Http::response($this->productPage('TB-500 10mg', 40, 300)),
            'shop.example.com/' => Http::response('<a href="/products/tb-500-10mg">TB-500</a>'),
            '*' => Http::response('', 404),
        ]);

        (new RunJsonLdIngestJob($config))->handle(app(IngestionService::class));

        $this->assertDatabaseHas('products', ['name' => 'TB-500 10mg', 'brand_id' => $config->vendor_id]);
    }

    public function test_a_rate_limited_product_page_stops_the_run(): void
    {
        $config = $this->jsonLdConfig('https://shop.example.com/sitemap.xml');
        Http::fake([
            'shop.example.com/sitemap.xml' => Http::response($this->sitemap([
                'https://shop.example.com/products/a-10mg',
                'https://shop.example.com/products/b-10mg',
                'https://shop.example.com/products/c-10mg',
            ])),
            'shop.example.com/products/*' => Http::response('', 429),
            '*' => Http::response('', 404),
        ]);

        (new RunJsonLdIngestJob($config))->handle(app(IngestionService::class));

        // The sitemap and the first product page, not one call per product.
        Http::assertSentCount(2);
        $fresh = $config->fresh();
        $this->assertStringContainsString('Rate limited (429)', $fresh->last_error);
        $this->assertTrue($fresh->next_run_at->greaterThan(now()->addHours(5)));
    }

    public function test_the_size_comes_from_the_end_of_the_name(): void
    {
        $this->assertSame('10mg', RunJsonLdIngestJob::sizeFromName('Semaglutide 10mg'));
        $this->assertSame('10mg', RunJsonLdIngestJob::sizeFromName('Retatrutide 10 mg'));
        $this->assertSame('0.1mg', RunJsonLdIngestJob::sizeFromName('IGF-1 LR3 0.1mg'));
        $this->assertSame('500mg', RunJsonLdIngestJob::sizeFromName('NAD+ 500mg'));
        $this->assertSame('5mg/5mg', RunJsonLdIngestJob::sizeFromName('BPC-157 + TB-500 Blend 5mg + 5mg'));
        $this->assertSame('6mg/3mg/3mg', RunJsonLdIngestJob::sizeFromName('Tesa + Ipa + CJC Blend 6mg + 3mg + 3mg'));
        $this->assertNull(RunJsonLdIngestJob::sizeFromName('GHK-Cu Face Serum 1.5%'));
        $this->assertNull(RunJsonLdIngestJob::sizeFromName('BPC-157 10mg x 10'));
        $this->assertNull(RunJsonLdIngestJob::sizeFromName(null));
    }

    public function test_a_product_named_after_its_url_takes_the_vendors_name_and_compound(): void
    {
        $semaglutide = ProductCategory::firstOrCreate(['slug' => 'semaglutide'], ['name' => 'Semaglutide', 'is_active' => true]);
        CategoryAlias::updateOrCreate(['keyword' => 'semaglutide'], ['product_category_id' => $semaglutide->id]);
        $uncategorized = ProductCategory::firstOrCreate(['slug' => 'uncategorized'], ['name' => 'Uncategorized', 'is_active' => false]);

        $config = $this->jsonLdConfig('https://shop.example.com/sitemap.xml');
        // What "Discover Products" made from a homepage link.
        $product = Product::create([
            'name' => 'Cp1 Sg 10mg',
            'slug' => 'cp1-sg-10mg-acme-peptides',
            'brand_id' => $config->vendor_id,
            'product_category_id' => $uncategorized->id,
            'price' => 0,
            'product_url' => 'https://shop.example.com/products/cp1-sg-10mg',
            'status' => 'active',
            'hidden' => true,
            'auto_scraped' => true,
        ]);

        $this->stage($config, 'cp1-sg-10mg', 'Semaglutide 10mg', 33);

        $product->refresh();
        $this->assertSame('Semaglutide 10mg', $product->name);
        $this->assertEquals($semaglutide->id, $product->product_category_id);
        $this->assertFalse((bool) $product->hidden);
        $this->assertSame('10mg', $product->size_mg);
        $this->assertEquals(33, (float) $product->price);
    }

    public function test_a_name_a_person_chose_is_kept(): void
    {
        $config = $this->jsonLdConfig('https://shop.example.com/sitemap.xml');
        $product = Product::create([
            'name' => 'Semaglutide (10mg vial)',
            'slug' => 'semaglutide-10mg-vial-acme-peptides',
            'brand_id' => $config->vendor_id,
            'size_mg' => '10mg',
            'price' => 30,
            'product_url' => 'https://shop.example.com/products/cp1-sg-10mg',
            'status' => 'active',
            'hidden' => false,
        ]);

        $this->stage($config, 'cp1-sg-10mg', 'Semaglutide 10mg', 33);

        $product->refresh();
        $this->assertSame('Semaglutide (10mg vial)', $product->name);
        $this->assertEquals(33, (float) $product->price);
    }

    public function test_the_scheduler_runs_each_config_on_its_importer(): void
    {
        Queue::fake();
        $due = ['next_run_at' => now()->subMinute()];
        $jsonLd = $this->config('json-ld-vendor', ['type' => ScrapingConfig::TYPE_JSON_LD] + $due);
        $css = $this->config('css-vendor', [
            'type' => ScrapingConfig::TYPE_CSS_SCRAPE,
            'selectors' => ['product_container' => '.card', 'name' => 'h1', 'price' => '.price', 'image' => 'img', 'link' => 'a'],
        ] + $due);

        Artisan::call('scraping:run-scheduled');

        Queue::assertPushed(RunJsonLdIngestJob::class, fn ($job) => $job->config->id === $jsonLd->id);
        Queue::assertPushed(RunPythonScraperJob::class, fn ($job) => $job->config->id === $css->id);
        Queue::assertPushed(RunJsonLdIngestJob::class, 1);
        Queue::assertPushed(RunPythonScraperJob::class, 1);
    }

    public function test_saving_the_vendor_form_keeps_a_json_ld_import(): void
    {
        $config = $this->jsonLdConfig('https://shop.example.com/sitemap.xml');
        $brand = Brand::findOrFail($config->vendor_id);
        $settings = new VendorSetting(['api_platform' => 'page_scrape', 'shop_url' => 'https://shop.example.com']);

        $sync = new \ReflectionMethod(VendorsController::class, 'syncScrapingConfig');
        $sync->invoke(app(VendorsController::class), $brand, $settings);

        $fresh = $config->fresh();
        $this->assertSame(ScrapingConfig::TYPE_JSON_LD, $fresh->type);
        $this->assertSame('https://shop.example.com/sitemap.xml', $fresh->products_url);
        $this->assertSame(1, ScrapingConfig::where('vendor_id', $brand->id)->count());
    }

    public function test_the_page_scraper_stops_when_the_store_refuses(): void
    {
        $config = $this->config('page-vendor', ['type' => ScrapingConfig::TYPE_PAGE_SCRAPE]);
        foreach (['a-10mg', 'b-10mg'] as $slug) {
            Product::create([
                'name' => 'Product '.$slug,
                'slug' => $slug.'-page-vendor',
                'brand_id' => $config->vendor_id,
                'price' => 10,
                'product_url' => 'https://shop.example.com/products/'.$slug,
                'status' => 'active',
            ]);
        }
        Http::fake([
            'shop.example.com/products/*' => Http::response('', 403),
            '*' => Http::response('', 404),
        ]);

        (new RunPageScraperJob($config))->handle(app(IngestionService::class));

        Http::assertSentCount(1);
        $fresh = $config->fresh();
        $this->assertStringContainsString('Refused (403)', $fresh->last_error);
        $this->assertTrue($fresh->next_run_at->greaterThan(now()->addHours(23)));
    }

    public function test_the_migration_moves_capital_precision_labs_to_its_sitemap(): void
    {
        $brand = Brand::create(['name' => 'Capital Precision Labs', 'slug' => 'capital-precision-labs', 'is_active' => true]);
        $base = [
            'vendor_id' => $brand->id,
            'vendor_name' => $brand->name,
            'type' => ScrapingConfig::TYPE_PAGE_SCRAPE,
            'products_url' => 'https://capitalprecisionlabs.com',
            'store_url' => 'https://capitalprecisionlabs.com',
            'frequency' => 'daily',
            'enabled' => true,
            'auto_promote' => true,
            'next_run_at' => now()->addHours(5),
        ];
        $old = ScrapingConfig::create($base);
        $second = ScrapingConfig::create($base);

        $migration = require database_path('migrations/2026_11_24_100000_capital_precision_labs_sitemap_import.php');
        $migration->up();

        $fresh = $old->fresh();
        $this->assertSame(ScrapingConfig::TYPE_JSON_LD, $fresh->type);
        $this->assertSame('https://capitalprecisionlabs.com/sitemap.xml', $fresh->products_url);
        $this->assertSame(400, $fresh->selectors['max_products']);
        $this->assertSame('AUD', $fresh->auth_credentials['currency_code']);
        $this->assertTrue($fresh->enabled);
        $this->assertTrue($fresh->auto_promote);
        $this->assertFalse($fresh->next_run_at->isFuture());
        $this->assertFalse($second->fresh()->enabled);

        $migration->down();

        $this->assertSame(ScrapingConfig::TYPE_PAGE_SCRAPE, $old->fresh()->type);
        $this->assertSame('https://capitalprecisionlabs.com', $old->fresh()->products_url);
        $this->assertTrue($second->fresh()->enabled);
    }

    public function test_the_compound_migration_files_capital_precision_products(): void
    {
        $brand = Brand::create(['name' => 'Capital Precision Labs', 'slug' => 'capital-precision-labs', 'is_active' => true]);
        $category = function (string $slug, string $name, bool $active = true): ProductCategory {
            $row = ProductCategory::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => $active]);
            $row->update(['is_active' => $active]);

            return $row;
        };
        $semaglutide = $category('semaglutide', 'Semaglutide');
        $semax = $category('semax', 'Semax');
        $tb500 = $category('tb-500', 'TB-500');
        $klow = $category('klow-blend-ghk-cu-bpc-157-tb-500-kpv', 'KLOW');
        $uncategorized = $category('uncategorized', 'Uncategorized', false);
        $product = fn (string $name, ?int $categoryId, bool $hidden = false) => Product::create([
            'name' => $name,
            'slug' => strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name)).'-cpl',
            'brand_id' => $brand->id,
            'product_category_id' => $categoryId,
            'price' => 30,
            'status' => 'active',
            'hidden' => $hidden,
        ]);
        $semaxRow = $product('Semax 10mg', $semaglutide->id);
        $semaglutideRow = $product('Semaglutide 10mg', $semaglutide->id);
        $fragment = $product('TB-500 Fragment 10mg', $tb500->id);
        $klowHidden = $product('KLOW Blend 80mg', $uncategorized->id, true);
        $noPage = $product('Liraglutide 10mg', null);
        $aliasBefore = CategoryAlias::where('keyword', 'semax')->value('product_category_id');

        $migration = require database_path('migrations/2026_11_24_120000_capital_precision_labs_compounds.php');
        $migration->up();

        $this->assertEquals($semax->id, $semaxRow->fresh()->product_category_id);
        $this->assertEquals($semaglutide->id, $semaglutideRow->fresh()->product_category_id);
        $this->assertNull($fragment->fresh()->product_category_id);
        $this->assertEquals($klow->id, $klowHidden->fresh()->product_category_id);
        $this->assertFalse((bool) $klowHidden->fresh()->hidden);
        $this->assertNull($noPage->fresh()->product_category_id);
        $this->assertEquals($semax->id, CategoryAlias::where('keyword', 'semax')->value('product_category_id'));

        $migration->down();

        $this->assertEquals($semaglutide->id, $semaxRow->fresh()->product_category_id);
        $this->assertEquals($tb500->id, $fragment->fresh()->product_category_id);
        $this->assertEquals($uncategorized->id, $klowHidden->fresh()->product_category_id);
        $this->assertTrue((bool) $klowHidden->fresh()->hidden);
        $this->assertEquals($aliasBefore, CategoryAlias::where('keyword', 'semax')->value('product_category_id'));
    }

    private function stage(ScrapingConfig $config, string $slug, string $name, float $price): void
    {
        $url = 'https://shop.example.com/products/'.$slug;
        app(IngestionService::class)->upsertStaged($config, [
            'external_id' => $url,
            'source_url' => $url,
            'name' => $name,
            'price' => $price,
            'dosage' => RunJsonLdIngestJob::sizeFromName($name),
        ]);
    }

    private function jsonLdConfig(string $productsUrl): ScrapingConfig
    {
        return $this->config('acme-peptides', [
            'type' => ScrapingConfig::TYPE_JSON_LD,
            'products_url' => $productsUrl,
            'auth_credentials' => ['currency_code' => 'AUD'],
        ]);
    }

    private function config(string $brandSlug, array $overrides): ScrapingConfig
    {
        $brand = Brand::create(['name' => ucwords(str_replace('-', ' ', $brandSlug)), 'slug' => $brandSlug, 'is_active' => true]);

        return ScrapingConfig::create(array_merge([
            'vendor_id' => $brand->id,
            'vendor_name' => $brand->name,
            'products_url' => 'https://shop.example.com',
            'store_url' => 'https://shop.example.com',
            'frequency' => 'daily',
            'enabled' => true,
            'auto_promote' => true,
            'next_run_at' => now()->addDay(),
        ], $overrides));
    }

    private function sitemap(array $urls): string
    {
        $entries = array_map(fn (string $url) => '<url><loc>'.$url.'</loc></url>', $urls);

        return '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.implode('', $entries).'</urlset>';
    }

    private function productPage(string $name, float $single, float $kit): string
    {
        $offer = fn (string $label, float $price) => [
            '@type' => 'Offer',
            'name' => $label,
            'price' => $price,
            'priceCurrency' => 'AUD',
            'availability' => 'https://schema.org/InStock',
        ];
        $product = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'image' => 'https://shop.example.com/img.png',
            'offers' => [$offer('Single vial', $single), $offer('Kit of 10 vials', $kit)],
        ]);

        return '<html><head>'
            .'<script type="application/ld+json">{"@context":"https://schema.org","@type":"Organization","name":"Shop"}</script>'
            .'<script type="application/ld+json">'.$product.'</script>'
            .'</head><body><h1>'.$name.'</h1></body></html>';
    }
}
