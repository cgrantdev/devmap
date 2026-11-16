<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * #87 follow-up (QA S1): the encyclopedia page links its own compare page
 * once. The cross-links chip "Compare X listings" carries the canonical
 * href; the hero "Available from N vendors" is plain text when that chip
 * is present, and otherwise links the lowercase /compare URL (no 301).
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

    public function test_hero_vendor_count_is_not_a_second_link_when_the_chip_is_present(): void
    {
        $vue = (string) file_get_contents(resource_path(self::VUE));

        $this->assertStringContainsString('const hasOwnCompareChip = computed(', $vue);
        $this->assertStringContainsString("link.kind === 'compare'", $vue);
        $this->assertMatchesRegularExpression('/<p v-if="vendorCount > 0 && hasOwnCompareChip"[^>]*>\s*Available from \{\{ vendorCount \}\} vendors\s*<\/p>/', $vue);
        $this->assertStringContainsString('<a v-else-if="vendorCount > 0" :href="`/compare/${(slug || slugify(categoryName || name)).toLowerCase()}`"', $vue);
        $this->assertStringNotContainsString('<a v-if="vendorCount > 0"', $vue);
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
