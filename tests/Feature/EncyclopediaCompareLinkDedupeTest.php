<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #87 follow-up (QA S1) and #94 QA A1/A2: the encyclopedia page links its
 * own compare page once, through the cross-links chip, which the server
 * emits only for an owned, canonical, priced compare page. The hero
 * "Available from N vendors" is plain text. The pill reads "Compare
 * listings".
 */
class EncyclopediaCompareLinkDedupeTest extends TestCase
{
    use RefreshDatabase;

    private const VUE = 'js/Pages/Frontend/EncyclopediaArticleDetail.vue';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_catalog_links_carry_one_canonical_own_compare_link_and_no_repeated_href(): void
    {
        $brand = Brand::create(['name' => 'Example Research', 'slug' => 'example-research', 'is_active' => true]);
        $category = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        EducationPost::create([
            'title' => 'BPC-157', 'slug' => 'post-bpc-157', 'product_category_id' => $category->id,
            'status' => 'published', 'show_in_encyclopedia' => true, 'overview' => 'BPC-157 overview.',
        ]);
        Product::create([
            'name' => 'BPC-157 listing', 'slug' => 'listing-bpc', 'brand_id' => $brand->id,
            'product_category_id' => $category->id, 'price' => 40, 'status' => 'active', 'hidden' => false,
        ]);

        $response = $this->get('/encyclopedia/BPC-157');
        $response->assertOk();
        $response->assertDontSee('prices across vendors', false);

        $response->assertInertia(function ($page) {
            $props = $page->toArray()['props'];
            $this->assertGreaterThan(0, (int) $props['vendorCount']);

            $links = [];
            foreach ($props['catalogLinks']['groups'] ?? [] as $group) {
                foreach ($group['links'] ?? [] as $link) {
                    $links[] = $link;
                }
            }
            $hrefs = array_column($links, 'href');
            $this->assertSame(array_values(array_unique($hrefs)), $hrefs, 'an href repeats in catalogLinks');

            $own = array_values(array_filter($links, fn ($l) => ($l['kind'] ?? null) === 'compare'));
            $this->assertCount(1, $own);
            $this->assertSame('/compare/bpc-157', $own[0]['href']);
            $this->assertSame($own[0]['href'], strtolower($own[0]['href']));

            $this->assertSame('Compare BPC-157 listings across vendors', $props['relatedPages']['compare']['anchor']);
        });
    }

    public function test_vendor_count_without_a_listed_price_gets_no_compare_link(): void
    {
        // #94 QA A2: vendorCount (Product::visible) and the chip's priced check
        // (CatalogCrossLinks::listedCategoryIds) differ. A listing with price 40 but
        // discount_price 0 counts as a vendor yet is not a listed price, so the
        // old hero link pointed at a compare page the chip deliberately skips.
        $brand = Brand::create(['name' => 'Example Research', 'slug' => 'example-research', 'is_active' => true]);
        $category = ProductCategory::create(['name' => 'Unpricedpep', 'slug' => 'Unpricedpep', 'is_active' => true]);
        EducationPost::create([
            'title' => 'Unpricedpep', 'slug' => 'post-unpricedpep', 'product_category_id' => $category->id,
            'status' => 'published', 'show_in_encyclopedia' => true, 'overview' => 'Unpricedpep overview.',
        ]);
        Product::create([
            'name' => 'Unpricedpep listing', 'slug' => 'listing-unpriced', 'brand_id' => $brand->id,
            'product_category_id' => $category->id, 'price' => 40, 'discount_price' => 0, 'status' => 'active', 'hidden' => false,
        ]);

        $this->get('/encyclopedia/Unpricedpep')->assertOk()->assertInertia(function ($page) {
            $props = $page->toArray()['props'];
            $this->assertSame(1, (int) $props['vendorCount']);
            foreach ($props['catalogLinks']['groups'] ?? [] as $group) {
                foreach ($group['links'] ?? [] as $link) {
                    $this->assertNotSame('compare', $link['kind'] ?? null);
                    $this->assertStringNotContainsString('/compare/unpricedpep', strtolower((string) $link['href']));
                }
            }
        });
    }

    public function test_hero_vendor_count_is_plain_text_and_the_chip_is_the_only_compare_link(): void
    {
        $vue = (string) file_get_contents(resource_path(self::VUE));

        // #94 QA A2: no hero link that skips the server's ownership / priced check.
        $this->assertMatchesRegularExpression('/<p v-if="vendorCount > 0"[^>]*>\s*Available from \{\{ vendorCount \}\} vendors\s*<\/p>/', $vue);
        $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*>\s*Available from/', $vue);
        $this->assertStringNotContainsString(':href="`/compare/${', $vue);
        $this->assertStringContainsString('<CatalogCrossLinks :groups="catalogLinks?.groups || []" />', $vue);
    }

    public function test_compare_pill_says_listings_not_prices(): void
    {
        $vue = (string) file_get_contents(resource_path('js/components/CatalogCrossLinks.vue'));
        $this->assertStringContainsString("'Compare listings': 'Compare listings',", $vue);
        $this->assertStringNotContainsString('Compare prices', $vue);
    }

    public function test_compare_anchors_say_listings_not_prices(): void
    {
        $source = (string) file_get_contents(app_path('Http/Controllers/Frontend/EncyclopediaController.php'));
        $this->assertStringContainsString('"Compare {$category->name} listings across vendors"', $source);
        $this->assertStringNotContainsString('prices across vendors', $source);
    }
}
