<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoP0Test extends TestCase
{
    use RefreshDatabase;

    public function test_compare_sitemap_locs_are_route_safe(): void
    {
        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);
        ProductCategory::create([
            'name' => 'BPC blend',
            'slug' => 'BPC-157 / TB500 / Cartalax',
            'is_active' => true,
        ]);
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('https://peptidemap.com/compare/bpc-157</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/compare/vitamin-b12</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/compare/bpc-157-tb500-cartalax</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/compare/retatrutide</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/Vitamin%20B12</loc>', $xml);

        $this->assertStringNotContainsString('/compare/BPC-157', $xml);
        $this->assertStringNotContainsString('/compare/Vitamin', $xml);
        $this->assertStringNotContainsString('/compare/BPC-157 /', $xml);
        $this->assertStringNotContainsString(' ', $this->locs($xml));
    }

    public function test_mixed_case_and_spaced_compare_urls_resolve(): void
    {
        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);
        ProductCategory::create([
            'name' => 'BPC blend',
            'slug' => 'BPC-157 / TB500 / Cartalax',
            'is_active' => true,
        ]);
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'Retatrutide', 'is_active' => true]);

        $this->get('/compare/BPC-157')
            ->assertStatus(301)
            ->assertRedirect('/compare/bpc-157');

        $this->get('/compare/Vitamin%20B12')
            ->assertStatus(301)
            ->assertRedirect('/compare/vitamin-b12');

        $this->get('/compare/'.rawurlencode('BPC-157 / TB500 / Cartalax'))
            ->assertStatus(301)
            ->assertRedirect('/compare/bpc-157-tb500-cartalax');

        $this->get('/compare/Retatrutide')
            ->assertStatus(301)
            ->assertRedirect('/compare/retatrutide');

        $this->withoutVite();

        $this->get('/compare/bpc-157')->assertOk();
        $this->get('/compare/vitamin-b12')->assertOk();
        $this->get('/compare/bpc-157-tb500-cartalax')->assertOk();

        $this->get('/compare/retatrutide')
            ->assertOk()
            ->assertSee('<h1 class="ssr-seo-h1">Cheapest Retatrutide</h1>', false)
            ->assertDontSee('<h1 class="ssr-seo-h1">Cheapest GLP3-R</h1>', false);
    }

    public function test_vs_case_and_order_redirect_in_one_hop(): void
    {
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'Retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'Tirzepatide', 'is_active' => true]);

        $this->get('/compare/Tirzepatide-vs-Retatrutide')
            ->assertStatus(301)
            ->assertRedirect('/compare/retatrutide-vs-tirzepatide');

        $this->get('/compare/tirzepatide-vs-retatrutide')
            ->assertStatus(301)
            ->assertRedirect('/compare/retatrutide-vs-tirzepatide');

        $this->withoutVite();
        $this->get('/compare/retatrutide-vs-tirzepatide')->assertOk();
    }

    public function test_legacy_product_id_slug_redirects_to_canonical_url(): void
    {
        $brand = Brand::create([
            'name' => 'Southern Aminos LLC',
            'slug' => 'southern-aminos-llc',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'SA-3R — 10mg Single Vial',
            'slug' => 'sa-3r-10mg-single-vial',
            'brand_id' => $brand->id,
            'price' => 60,
            'status' => 'active',
            'hidden' => false,
        ]);

        $canonical = "/product/southern-aminos-llc/sa-3r-10mg-single-vial/{$product->id}";

        $this->get("/product/{$product->id}/not-the-real-slug")
            ->assertStatus(301)
            ->assertRedirect($canonical);

        $this->get("/product/{$product->id}/sa-3r-10mg-single-vial")
            ->assertStatus(301)
            ->assertRedirect($canonical);

        $this->get("/product/{$product->id}/foo")
            ->assertStatus(301)
            ->assertRedirect($canonical);
    }

    public function test_search_canonical_points_at_the_search_url_and_is_noindex(): void
    {
        $this->withoutVite();

        $this->get('/search?q=retatrutide&tab=products&utm_source=newsletter')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/search?q=retatrutide" />', false)
            ->assertSee('<meta name="robots" content="noindex, follow" />', false)
            ->assertDontSee('/search/retatrutide', false);

        $this->get('/search?tab=vendors')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/search" />', false)
            ->assertSee('<meta name="robots" content="noindex, follow" />', false);
    }

    private function locs(string $xml): string
    {
        preg_match_all('#<loc>(.*?)</loc>#', $xml, $matches);

        return implode("\n", $matches[1] ?? []);
    }
}
