<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\CompoundDisplay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoP0Test extends TestCase
{
    use RefreshDatabase;

    public function test_compare_sitemap_locs_are_route_safe(): void
    {
        $bpc = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        $b12 = ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);
        $blend = ProductCategory::create([
            'name' => 'BPC blend',
            'slug' => 'BPC-157 / TB500 / Cartalax',
            'is_active' => true,
        ]);
        $reta = ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        $brand = Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
        foreach ([$bpc, $b12, $blend, $reta] as $category) {
            Product::create([
                'name' => $category->name.' listing',
                'slug' => 'listing-'.$category->id,
                'brand_id' => $brand->id,
                'product_category_id' => $category->id,
                'price' => 25,
                'status' => 'active',
                'hidden' => false,
            ]);
        }

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('https://peptidemap.com/compare/bpc-157</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/compare/vitamin-b12</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/compare/bpc-157-tb500-cartalax</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/compare/retatrutide</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/vitamin-b12</loc>', $xml);

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
            ->assertSee('<h1 class="ssr-seo-h1">Retatrutide</h1>', false)
            ->assertSee('<title>Cheapest Retatrutide — 0 Vendors Compared — Peptidemap</title>', false)
            ->assertDontSee('<h1 class="ssr-seo-h1">Cheapest Retatrutide</h1>', false)
            ->assertDontSee('<h1 class="ssr-seo-h1">GLP3-R</h1>', false)
            ->assertDontSee('<h1 class="ssr-seo-h1">Cheapest GLP3-R</h1>', false);
    }

    public function test_compare_faqs_use_primary_name_and_keep_alias_secondary(): void
    {
        $retatrutide = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $bpc = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        $glow = ProductCategory::create([
            'name' => 'GLOW',
            'slug' => 'glow',
            'is_active' => true,
        ]);
        $brand = Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Retatrutide 10mg',
            'slug' => 'retatrutide-10mg',
            'brand_id' => $brand->id,
            'product_category_id' => $retatrutide->id,
            'price' => 49.50,
            'status' => 'active',
            'hidden' => false,
        ]);
        Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'bpc-157-5mg',
            'brand_id' => $brand->id,
            'product_category_id' => $bpc->id,
            'price' => 30,
            'status' => 'active',
            'hidden' => false,
        ]);
        Product::create([
            'name' => 'GLOW blend',
            'slug' => 'glow-blend',
            'brand_id' => $brand->id,
            'product_category_id' => $glow->id,
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
        ]);

        $this->withoutVite();

        $aliasAnswer = 'GLP3-R is an alternate label PeptideMap uses for Retatrutide. PeptideMap lists Retatrutide as the primary name; GLP3-R appears as an alias for search and catalog matching. Listings are research use only (RUO).';
        $glowAlias = CompoundDisplay::label('GLOW');
        $glowAnswer = "{$glowAlias} is an alternate label PeptideMap uses for GLOW. PeptideMap lists GLOW as the primary name; {$glowAlias} appears as an alias for search and catalog matching. Listings are research use only (RUO).";

        $this->get('/compare/retatrutide')
            ->assertOk()
            ->assertSee('<h1 class="ssr-seo-h1">Retatrutide</h1>', false)
            ->assertSee('<title>Cheapest Retatrutide — 1 Vendors Compared — Peptidemap</title>', false)
            ->assertSee('property="og:title" content="Cheapest Retatrutide — 1 Vendors Compared"', false)
            ->assertSee('What is the cheapest Retatrutide?', false)
            ->assertSee('Is GLP3-R the same as Retatrutide?', false)
            ->assertSee($aliasAnswer, false)
            ->assertDontSee('research-vendor synonym', false)
            ->assertDontSee('Peptidemap lists Retatrutide', false)
            ->assertDontSee('What is the cheapest GLP3-R?', false)
            ->assertDontSee('How many vendors sell GLP3-R?', false)
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/CompareCompound')
                ->where('compound.name', 'Retatrutide')
                ->where('compound.alias', 'GLP3-R')
                ->where('seo.h1', 'Retatrutide')
                ->where('seo.title', 'Cheapest Retatrutide — 1 Vendors Compared')
                ->where('seo.og_title', 'Cheapest Retatrutide — 1 Vendors Compared')
                ->has('compound.faqs', 5)
                ->where('compound.faqs.0.q', 'What is the cheapest Retatrutide?')
                ->where('compound.faqs.0.a', 'The lowest Retatrutide price on Peptidemap is $49.50 from Example Research. Peptidemap tracks 1 Retatrutide listings across 1 vendors and updates prices daily.')
                ->where('compound.faqs.4.q', 'Is GLP3-R the same as Retatrutide?')
                ->where('compound.faqs.4.a', $aliasAnswer)
                ->where('seo.schema.3.mainEntity.0.name', 'What is the cheapest Retatrutide?')
                ->where('seo.schema.3.mainEntity.4.name', 'Is GLP3-R the same as Retatrutide?')
                ->where('seo.schema.3.mainEntity.4.acceptedAnswer.text', $aliasAnswer)
            );

        $this->get('/compare/bpc-157')
            ->assertOk()
            ->assertSee('<h1 class="ssr-seo-h1">BPC-157</h1>', false)
            ->assertSee('What is the cheapest BPC-157?', false)
            ->assertDontSee('Is BPC-157 the same as BPC-157?', false)
            ->assertInertia(fn ($page) => $page
                ->where('compound.name', 'BPC-157')
                ->where('compound.alias', null)
                ->has('compound.faqs', 4)
                ->has('seo.schema.3.mainEntity', 4)
            );

        $this->get('/compare/glow')
            ->assertOk()
            ->assertDontSee('research-vendor synonym', false)
            ->assertInertia(fn ($page) => $page
                ->where('compound.name', 'GLOW')
                ->where('compound.alias', $glowAlias)
                ->has('compound.faqs', 5)
                ->where('compound.faqs.4.q', "Is {$glowAlias} the same as GLOW?")
                ->where('compound.faqs.4.a', $glowAnswer)
                ->where('seo.schema.3.mainEntity.4.name', "Is {$glowAlias} the same as GLOW?")
                ->where('seo.schema.3.mainEntity.4.acceptedAnswer.text', $glowAnswer)
            );
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
