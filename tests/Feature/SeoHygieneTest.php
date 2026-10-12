<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorSetting;
use App\Support\ComparePageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoHygieneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_encyclopedia_self_canonicals_and_omits_invented_research(): void
    {
        $blend = ProductCategory::create([
            'name' => 'BPC-157 / TB-500',
            'slug' => 'BPC-157-TB-500',
            'is_active' => true,
        ]);
        $reta = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $brand = Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
        foreach ([$blend, $reta] as $category) {
            Product::create([
                'name' => $category->name.' 5mg',
                'slug' => $category->id.'-5mg',
                'brand_id' => $brand->id,
                'product_category_id' => $category->id,
                'price' => 40,
                'status' => 'active',
                'hidden' => false,
            ]);
        }

        $blendPage = $this->get('/encyclopedia/BPC-157-TB-500');
        $blendPage->assertOk();
        $blendPage->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/BPC-157-TB-500" />', false);
        $blendPage->assertDontSee('University of Zagreb', false);
        $blendPage->assertInertia(fn ($page) => $page
            ->where('seo.canonical', url('/encyclopedia/BPC-157-TB-500'))
            ->where('seo.url', url('/encyclopedia/BPC-157-TB-500'))
            ->where('primaryResearch', null)
            ->where('relatedPages.compare.url', url('/compare/bpc-157-tb-500'))
        );

        $this->get('/encyclopedia/retatrutide')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/retatrutide" />', false)
            ->assertDontSee('University of Zagreb', false)
            ->assertInertia(fn ($page) => $page
                ->where('seo.canonical', url('/encyclopedia/retatrutide'))
                ->where('primaryResearch', null)
            );

        $this->get('/compare/bpc-157-tb-500')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/compare/bpc-157-tb-500" />', false);
    }

    public function test_filtered_compare_title_stays_on_the_unfiltered_catalog(): void
    {
        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $us = Location::create(['name' => 'United States']);
        $brand = Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
        VendorSetting::create([
            'brand_id' => $brand->id,
            'location_id' => $us->id,
        ]);
        Product::create([
            'name' => 'Retatrutide 10mg',
            'slug' => 'retatrutide-10mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 49.50,
            'status' => 'active',
            'hidden' => false,
        ]);

        $title = '<title>Retatrutide Price per mg: 1 Vendor Compared — Peptidemap</title>';

        $this->get('/compare/retatrutide')
            ->assertOk()
            ->assertSee($title, false)
            ->assertSee('<h1 class="ssr-seo-h1">Retatrutide Price Comparison</h1>', false)
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/compare/retatrutide" />', false);

        $filtered = $this->get('/compare/retatrutide?location=Canada');
        $filtered->assertOk();
        $filtered->assertSee($title, false);
        $filtered->assertDontSee('0 Vendors Compared', false);
        $filtered->assertSee('<h1 class="ssr-seo-h1">Retatrutide Price Comparison</h1>', false);
        $filtered->assertSee('<link rel="canonical" href="https://peptidemap.com/compare/retatrutide" />', false);
        $filtered->assertInertia(fn ($page) => $page
            ->where('seo.title', 'Retatrutide Price per mg: 1 Vendor Compared')
            ->where('seo.h1', 'Retatrutide Price Comparison')
            ->where('compound.products', [])
            ->where('compound.price_stats.listing_count', 1)
            ->where('compound.price_stats.vendor_count', 1)
        );

        $this->get('/compare/retatrutide?verified=cgmp')
            ->assertOk()
            ->assertSee($title, false)
            ->assertDontSee('0 Vendors Compared', false);

        $this->get('/compare/retatrutide?verified=')
            ->assertOk()
            ->assertSee($title, false)
            ->assertInertia(fn ($page) => $page->has('compound.products', 1));

        $this->get('/compare/retatrutide?location=')
            ->assertOk()
            ->assertSee($title, false);
    }

    public function test_compare_titles_and_metas_are_neutral_and_within_length(): void
    {
        $alpha = Brand::create([
            'name' => 'Alpha Research',
            'slug' => 'alpha-research',
            'is_active' => true,
        ]);
        $beta = Brand::create([
            'name' => 'Beta Research',
            'slug' => 'beta-research',
            'is_active' => true,
        ]);

        $pages = [
            ['name' => 'GHK-Cu', 'slug' => 'ghk-cu', 'vendors' => 1, 'per_mg' => false],
            ['name' => 'BPC-157', 'slug' => 'bpc-157', 'vendors' => 2, 'per_mg' => false],
            ['name' => 'Retatrutide', 'slug' => 'retatrutide', 'vendors' => 1, 'per_mg' => true],
            ['name' => 'Thymosin Beta-4 Fragment 1-4', 'slug' => 'thymosin-beta-4-fragment-1-4', 'vendors' => 2, 'per_mg' => false],
        ];

        foreach ($pages as $index => $page) {
            $category = ProductCategory::create([
                'name' => $page['name'],
                'slug' => $page['slug'],
                'is_active' => true,
            ]);
            for ($vendor = 0; $vendor < $page['vendors']; $vendor++) {
                Product::create([
                    'name' => $page['name'].' '.$vendor,
                    'slug' => 'listing-'.$index.'-'.$vendor,
                    'brand_id' => $vendor === 0 ? $alpha->id : $beta->id,
                    'product_category_id' => $category->id,
                    'price' => 25 + $vendor,
                    'status' => 'active',
                    'hidden' => false,
                ]);
            }
        }

        $tb = ProductCategory::create([
            'name' => 'TB-500',
            'slug' => 'tb-500',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'TB-500 5mg',
            'slug' => 'tb-500-5mg',
            'brand_id' => $alpha->id,
            'product_category_id' => $tb->id,
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
        ]);

        foreach ($pages as $page) {
            $html = $this->get('/compare/'.$page['slug'])->assertOk()->getContent();
            $title = $this->headContent($html, '/<title>(.*?)<\/title>/s');
            $description = $this->headContent($html, '/<meta name="description" content="(.*?)"/s');
            $ogTitle = $this->headContent($html, '/property="og:title" content="(.*?)"/s');
            $ogDescription = $this->headContent($html, '/property="og:description" content="(.*?)"/s');
            $twitterTitle = $this->headContent($html, '/name="twitter:title" content="(.*?)"/s');
            $twitterDescription = $this->headContent($html, '/name="twitter:description" content="(.*?)"/s');

            $this->assertLessThanOrEqual(60, mb_strlen($title), $title);
            $this->assertStringNotContainsString('Cheapest', $title);
            $this->assertStringNotContainsString('$', $title);
            $this->assertLessThanOrEqual(155, mb_strlen($description), $description);
            $this->assertDoesNotMatchRegularExpression('/verified/i', $description);
            $this->assertStringNotContainsString('$', $description);

            $perMg = $page['per_mg'];
            $this->assertSame(ComparePageSeo::renderedTitle($page['name'], $page['vendors'], $perMg), $title);
            $this->assertSame(ComparePageSeo::description($page['name'], $page['vendors'], $perMg), $description);
            $this->assertSame(ComparePageSeo::title($page['name'], $page['vendors'], $perMg), $ogTitle);
            if (! $perMg) {
                $this->assertStringNotContainsString('per mg', $title);
                $this->assertStringNotContainsString('per mg', $description);
            }
            $this->assertSame($description, $ogDescription);
            $this->assertSame($ogTitle, $twitterTitle);
            $this->assertSame($description, $twitterDescription);
            $this->assertStringEndsWith(' — Peptidemap', $title);
            $this->assertStringNotContainsString(' — Peptidemap', $ogTitle);
        }

        $vs = $this->get('/compare/bpc-157-vs-tb-500')->assertOk()->getContent();
        $vsTitle = $this->headContent($vs, '/<title>(.*?)<\/title>/s');
        $vsDescription = $this->headContent($vs, '/<meta name="description" content="(.*?)"/s');
        $this->assertStringNotContainsString('Cheapest', $vsTitle);
        $this->assertStringNotContainsString('$', $vsTitle);
        $this->assertStringNotContainsString('Cheapest', $vsDescription);
        $this->assertStringNotContainsString('$', $vsDescription);
    }

    public function test_compare_trailing_slash_redirects_to_the_bare_path(): void
    {
        // MakesHttpRequests::prepareUrlForRequest trims trailing slashes
        // before the kernel sees the URI. Production nginx does not, so
        // these requests are built with Request::create.
        $this->requestWithRawUri('/compare/')
            ->assertStatus(301)
            ->assertRedirect('/compare');

        $this->requestWithRawUri('/compare/retatrutide/')
            ->assertStatus(301)
            ->assertRedirect('/compare/retatrutide');

        $this->requestWithRawUri('/compare/retatrutide/?location=Canada')
            ->assertStatus(301)
            ->assertRedirect('/compare/retatrutide?location=Canada');
    }

    private function headContent(string $html, string $pattern): string
    {
        $this->assertSame(1, preg_match($pattern, $html, $matches), $pattern);

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function requestWithRawUri(string $uri): \Illuminate\Testing\TestResponse
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $request = \Illuminate\Http\Request::create('http://localhost'.$uri, 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return \Illuminate\Testing\TestResponse::fromBaseResponse($response);
    }

    public function test_bare_slash_blends_redirect_to_compare(): void
    {
        ProductCategory::create([
            'name' => 'Selank/Semax',
            'slug' => 'Selank/Semax',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'BPC blend',
            'slug' => 'BPC-157 / TB500 / Cartalax',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'GHK blend',
            'slug' => 'GHK-CU/BPC 157/KPV',
            'is_active' => true,
        ]);

        $this->get('/Selank/Semax')
            ->assertStatus(301)
            ->assertRedirect('/compare/selanksemax');

        $this->get('/encyclopedia/Selank/Semax')
            ->assertStatus(301)
            ->assertRedirect('/compare/selanksemax');

        $this->get('/BPC-157%20/%20TB500%20/%20Cartalax')
            ->assertStatus(301)
            ->assertRedirect('/compare/bpc-157-tb500-cartalax');

        $this->get('/GHK-CU/BPC%20157/KPV')
            ->assertStatus(301)
            ->assertRedirect('/compare/ghk-cubpc-157kpv');

        $this->get('/Nope/Thing')->assertNotFound();

        $this->get('/blog/bpc-157-vs-tb-500-evidence')->assertOk();
    }

    public function test_editorial_cards_do_not_hotlink_stock_cdns(): void
    {
        Blog::create([
            'title' => 'Legacy unsplash post',
            'slug' => 'legacy-unsplash',
            'status' => 'published',
            'published_at' => now()->subYear(),
            'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f',
            'seo_og_image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f',
        ]);

        $home = $this->get('/');
        $home->assertOk();
        $home->assertDontSee('picsum.photos', false);
        $home->assertDontSee('unsplash.com', false);
        $home->assertSee('bpc-157-vs-ghk-cu.png', false);

        $this->get('/blogs')
            ->assertOk()
            ->assertDontSee('picsum.photos', false)
            ->assertDontSee('unsplash.com', false)
            ->assertSee('bpc-157-vs-tb-500-evidence.png', false);

        $this->get('/blog/legacy-unsplash')
            ->assertOk()
            ->assertDontSee('unsplash.com', false)
            ->assertDontSee('picsum.photos', false);

        $this->get('/guides')
            ->assertOk()
            ->assertDontSee('picsum.photos', false)
            ->assertDontSee('unsplash.com', false);
    }

    public function test_compare_index_omits_unused_product_image_urls(): void
    {
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        $brand = Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'BPC-157 5mg',
            'slug' => 'bpc-157-5mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 30,
            'status' => 'active',
            'hidden' => false,
            'image_url' => 'https://vendor.example/vial.jpg',
            'product_url' => 'https://vendor.example/products/bpc-157?utm=long',
        ]);

        $this->get('/compare')
            ->assertOk()
            ->assertDontSee('vendor.example/vial.jpg', false)
            ->assertDontSee('vendor.example/products/bpc-157', false)
            ->assertInertia(fn ($page) => $page
                ->has('compounds.0.products.0.go_url')
                ->missing('compounds.0.products.0.image_url')
                ->missing('compounds.0.products.0.product_url')
            );
    }
}
