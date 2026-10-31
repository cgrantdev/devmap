<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\SitemapController;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductListingRobotsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::forget(SitemapController::CACHE_KEY);
    }

    public function test_sitemap_and_robots_drop_uncategorized_and_broken_slugs_only(): void
    {
        ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'Uncategorized',
            'slug' => 'uncategorized',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'Vitamin B12',
            'slug' => 'Vitamin B12',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'BPC blend',
            'slug' => 'BPC-157 / TB500 / Cartalax',
            'is_active' => true,
        ]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertSame(1, substr_count($xml, 'https://peptidemap.com/product/bpc-157</loc>'));
        $this->assertStringNotContainsString('https://peptidemap.com/product/uncategorized', $xml);
        $this->assertStringNotContainsString('Vitamin B12', $xml);
        $this->assertStringNotContainsString('TB500', $xml);
        $this->assertStringNotContainsString('Cartalax', $xml);

        $bpc = $this->get('/product/bpc-157')->assertOk();
        $bpc->assertDontSee('noindex', false);
        $bpc->assertDontSee('<meta name="robots"', false);
        $bpc->assertHeaderMissing('X-Robots-Tag');
        $bpc->assertSee('<title>BPC-157: Research Compound Profile — Peptidemap</title>', false);

        $uncat = $this->get('/product/uncategorized')->assertOk();
        $uncat->assertSee('<meta name="robots" content="noindex, follow" />', false);
        $uncat->assertSee('<title>Uncategorized – Peptidemap</title>', false);
        $uncat->assertSee('Browse Uncategorized peptides. Compare products, prices, and vendors.', false);
        $uncat->assertHeaderMissing('X-Robots-Tag');

        $space = $this->get('/product/'.rawurlencode('Vitamin B12'))->assertOk();
        $space->assertSee('<meta name="robots" content="noindex, follow" />', false);
        $space->assertSee('<title>Vitamin B12 – Peptidemap</title>', false);
        $space->assertHeaderMissing('X-Robots-Tag');

        $this->get('/product/BPC-157%20/%20TB500%20/%20Cartalax')->assertNotFound();
    }
}
