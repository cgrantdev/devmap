<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\CategoryAlias;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\CatalogCrossLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCrossLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_product_encyclopedia_and_compare_pages_link_to_each_other(): void
    {
        $brand = $this->brand();
        $bpc = $this->compound('BPC-157', 'BPC-157', published: true);
        $tb = $this->compound('TB-500', 'TB-500', published: true);
        $blend = $this->compound('BPC-157 / TB-500', 'bpc-157-tb-500', published: true);
        $draft = $this->compound('Draftone', 'draftone', published: false);
        $alpha = $this->compound('Zzz Alpha', 'zzz-alpha', published: true);
        $beta = $this->compound('Zzz Beta', 'zzz-beta', published: true);
        $hidden = $this->compound('Secret Pep', 'secret-pep', published: true, active: false);
        $ss31 = $this->compound('SS-31', 'ss-31', published: true);
        $elamipretide = $this->compound('Elamipretide', 'elamipretide', published: true);
        $fooBar = $this->compound('Foo Bar', 'foo-bar', published: true);
        $fooUnderscore = $this->compound('Foo Underscore', 'foo_bar', published: true);

        CategoryAlias::create([
            'product_category_id' => $bpc->id,
            'keyword' => 'BPC-157',
        ]);

        foreach ([$bpc, $tb, $blend, $draft, $alpha, $beta, $hidden, $ss31, $elamipretide, $fooBar, $fooUnderscore] as $category) {
            $this->listing($brand, $category);
        }

        $product = $this->get('/product/BPC-157');
        $product->assertOk();
        $product->assertSee('<a href="/encyclopedia/BPC-157" tabindex="-1">BPC-157 encyclopedia entry</a>', false);
        $product->assertSee('<a href="/compare/bpc-157" tabindex="-1">Compare BPC-157 listings</a>', false);
        $product->assertSee('<a href="/compare/bpc-157-vs-tb-500" tabindex="-1">Compare BPC-157 and TB-500 listings</a>', false);
        $this->assertCatalogLinks($product, [
            '/encyclopedia/BPC-157',
            '/compare/bpc-157',
            '/compare/bpc-157-vs-tb-500',
        ], [
            '/encyclopedia/draftone',
            '/compare/zzz-alpha-vs-zzz-beta',
            '/product/bpc-157-tb-500',
            '/encyclopedia/bpc-157-tb-500',
            '/product/secret-pep',
            '/encyclopedia/secret-pep',
            '/encyclopedia/elamipretide',
            '/compare/elamipretide',
            '/calculator',
        ]);

        $encyclopedia = $this->get('/encyclopedia/BPC-157');
        $encyclopedia->assertOk();
        $encyclopedia->assertSee('<a href="/product/BPC-157" tabindex="-1">Products listed for BPC-157</a>', false);
        $encyclopedia->assertSee('<a href="/compare/bpc-157" tabindex="-1">Compare BPC-157 listings</a>', false);
        $encyclopedia->assertSee('<a href="/compare/bpc-157-vs-tb-500" tabindex="-1">Compare BPC-157 and TB-500 listings</a>', false);
        $this->assertCatalogLinks($encyclopedia, [
            '/product/BPC-157',
            '/compare/bpc-157',
            '/compare/bpc-157-vs-tb-500',
        ], [
            '/encyclopedia/BPC-157',
            '/product/bpc-157-tb-500',
            '/encyclopedia/draftone',
            '/compare/zzz-alpha-vs-zzz-beta',
            '/calculator',
        ]);
        $encyclopedia->assertInertia(fn ($page) => $page
            ->where('relatedPages.compare.anchor', 'Compare BPC-157 prices across vendors')
            ->where('catalogLinks.groups.0.label', 'Products listed for BPC-157')
            ->where('catalogLinks.groups.1.label', 'Compare listings')
        );

        $compare = $this->get('/compare/bpc-157');
        $compare->assertOk();
        $compare->assertSee('<a href="/encyclopedia/BPC-157" tabindex="-1">BPC-157 encyclopedia entry</a>', false);
        $compare->assertSee('<a href="/product/BPC-157" tabindex="-1">Products listed for BPC-157</a>', false);
        $this->assertCatalogLinks($compare, [
            '/encyclopedia/BPC-157',
            '/product/BPC-157',
        ], [
            '/compare/bpc-157',
            '/product/bpc-157-tb-500',
            '/encyclopedia/draftone',
            '/calculator',
        ]);

        $vs = $this->get('/compare/bpc-157-vs-tb-500');
        $vs->assertOk();
        $vs->assertSee('<a href="/encyclopedia/BPC-157" tabindex="-1">BPC-157 encyclopedia entry</a>', false);
        $vs->assertSee('<a href="/encyclopedia/TB-500" tabindex="-1">TB-500 encyclopedia entry</a>', false);
        $vs->assertSee('<a href="/product/BPC-157" tabindex="-1">Products listed for BPC-157</a>', false);
        $vs->assertSee('<a href="/product/TB-500" tabindex="-1">Products listed for TB-500</a>', false);
        $this->assertCatalogLinks($vs, [
            '/encyclopedia/BPC-157',
            '/encyclopedia/TB-500',
            '/product/BPC-157',
            '/product/TB-500',
        ], [
            '/product/bpc-157-tb-500',
            '/encyclopedia/bpc-157-tb-500',
            '/encyclopedia/draftone',
            '/calculator',
        ]);

        $blendProduct = $this->get('/product/bpc-157-tb-500');
        $blendProduct->assertOk();
        $blendProduct->assertSee('<a href="/encyclopedia/bpc-157-tb-500" tabindex="-1">BPC-157 / TB-500 encyclopedia entry</a>', false);
        $blendProduct->assertSee('<a href="/compare/bpc-157-tb-500" tabindex="-1">Compare BPC-157 / TB-500 listings</a>', false);
        $this->assertCatalogLinks($blendProduct, [
            '/encyclopedia/bpc-157-tb-500',
            '/compare/bpc-157-tb-500',
        ], [
            '/product/BPC-157',
            '/encyclopedia/BPC-157',
            '/compare/bpc-157',
            '/compare/bpc-157-vs-tb-500',
            '/product/TB-500',
        ]);

        $blendEncyclopedia = $this->get('/encyclopedia/bpc-157-tb-500');
        $blendEncyclopedia->assertOk();
        $this->assertCatalogLinks($blendEncyclopedia, [
            '/product/bpc-157-tb-500',
            '/compare/bpc-157-tb-500',
        ], [
            '/product/BPC-157',
            '/product/TB-500',
            '/encyclopedia/BPC-157',
        ]);

        $draftPage = $this->get('/product/draftone');
        $draftPage->assertOk();
        $draftPage->assertSee('<a href="/compare/draftone" tabindex="-1">Compare Draftone listings</a>', false);
        $this->assertCatalogLinks($draftPage, [
            '/compare/draftone',
        ], [
            '/encyclopedia/draftone',
        ]);

        $thin = $this->get('/product/zzz-alpha');
        $thin->assertOk();
        $thin->assertSee('<a href="/compare/zzz-alpha" tabindex="-1">Compare Zzz Alpha listings</a>', false);
        $this->assertCatalogLinks($thin, [
            '/encyclopedia/zzz-alpha',
            '/compare/zzz-alpha',
        ], [
            '/compare/zzz-alpha-vs-zzz-beta',
        ]);
        $this->get('/compare/zzz-alpha-vs-zzz-beta')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow" />', false);

        $alias = $this->get('/product/ss-31');
        $alias->assertOk();
        $this->assertCatalogLinks($alias, [], [
            '/encyclopedia/elamipretide',
            '/compare/elamipretide',
            '/encyclopedia/ss-31',
            '/compare/ss-31',
        ]);

        $this->get('/product/foo_bar')
            ->assertOk()
            ->assertSee('<a href="/encyclopedia/foo_bar" tabindex="-1">Foo Underscore encyclopedia entry</a>', false);
        $this->assertCatalogLinks($this->get('/product/foo_bar'), [
            '/encyclopedia/foo_bar',
        ], [
            '/compare/foo-bar',
        ]);

        $notes = collect(CatalogCrossLinks::ambiguities());
        $this->assertTrue($notes->contains(fn ($note) => $note['slug'] === 'ss-31' && $note['kind'] === 'encyclopedia'));
        $this->assertTrue($notes->contains(fn ($note) => $note['slug'] === 'ss-31' && $note['kind'] === 'compare'));
        $this->assertTrue($notes->contains(fn ($note) => $note['slug'] === 'foo_bar' && $note['kind'] === 'compare'));
        $this->assertFalse($notes->contains(fn ($note) => $note['slug'] === 'BPC-157'));
        $this->assertFalse($notes->contains(fn ($note) => $note['slug'] === 'bpc-157-tb-500'));
    }

    public function test_compare_links_are_capped_and_ordered(): void
    {
        $brand = $this->brand();
        $subject = $this->compound('Cap Subject', 'cap-subject', published: true);
        $this->listing($brand, $subject);

        $extra = [];
        foreach (range('a', 'g') as $letter) {
            $slug = 'cap-'.$letter;
            $this->compound('Cap '.strtoupper($letter), $slug, published: false);
            $extra[] = $slug.'-vs-cap-subject';
        }
        config([
            'seo.vs_sitemap_allowlist' => array_values(array_unique(array_merge(
                config('seo.vs_sitemap_allowlist'),
                $extra
            ))),
        ]);

        $page = $this->get('/product/cap-subject')->assertOk();
        $captured = [];
        $page->assertInertia(function ($inertia) use (&$captured) {
            $groups = $inertia->toArray()['props']['catalogLinks']['groups'];
            $compare = collect($groups)->firstWhere('label', 'Compare listings');
            $captured = array_column($compare['links'], 'href');

            return true;
        });

        $this->assertSame([
            '/compare/cap-subject',
            '/compare/cap-a-vs-cap-subject',
            '/compare/cap-b-vs-cap-subject',
            '/compare/cap-c-vs-cap-subject',
            '/compare/cap-d-vs-cap-subject',
            '/compare/cap-e-vs-cap-subject',
        ], $captured);
        $this->assertCount(CatalogCrossLinks::CAP, $captured);
        $page->assertDontSee('/compare/cap-f-vs-cap-subject', false);
        $page->assertDontSee('/compare/cap-g-vs-cap-subject', false);
    }

    public function test_space_slug_encyclopedia_is_not_linked_and_vs_lookup_is_cached(): void
    {
        $brand = $this->brand();
        $space = $this->compound('AM Shred', 'AM Shred', published: true);
        $this->listing($brand, $space);
        $bpc = $this->compound('BPC-157', 'BPC-157', published: true);
        $tb = $this->compound('TB-500', 'TB-500', published: true);
        $this->listing($brand, $bpc);
        $this->listing($brand, $tb);

        $this->assertCatalogLinks($this->get('/compare/am-shred')->assertOk(), [], [
            '/encyclopedia/AM Shred',
            '/encyclopedia/AM%20Shred',
        ]);

        $this->get('/product/BPC-157')->assertOk();
        $count = 0;
        \Illuminate\Support\Facades\DB::listen(function () use (&$count) { $count++; });
        $this->get('/product/BPC-157')->assertOk()->assertSee('href="/compare/bpc-157-vs-tb-500"', false);
        $this->assertLessThan(60, $count, 'catalog links should not add hundreds of queries per page');
    }

    private function brand(): Brand
    {
        return Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
    }

    private function compound(string $name, string $slug, bool $published, bool $active = true): ProductCategory
    {
        $category = ProductCategory::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $active,
        ]);
        EducationPost::create([
            'title' => $name,
            'slug' => 'post-'.$slug,
            'product_category_id' => $category->id,
            'status' => $published ? 'published' : 'draft',
            'show_in_encyclopedia' => true,
            'overview' => $name.' overview.',
        ]);

        return $category;
    }

    private function listing(Brand $brand, ProductCategory $category): Product
    {
        return Product::create([
            'name' => $category->name.' 5mg',
            'slug' => 'listing-'.$category->id,
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
        ]);
    }

    /**
     * @param  list<string>  $present
     * @param  list<string>  $absent
     */
    private function assertCatalogLinks($response, array $present, array $absent): void
    {
        $html = $response->getContent();
        preg_match('#<nav class="ssr-catalog-links" aria-hidden="true">(.*?)</nav>#s', $html, $match);
        $nav = $match[1] ?? '';
        if ($present === []) {
            $this->assertSame('', $nav);
        } else {
            $this->assertNotSame('', $nav);
        }

        $labels = [];
        $hrefs = [];
        $response->assertInertia(function ($page) use ($present, $absent, &$labels, &$hrefs) {
            $groups = $page->toArray()['props']['catalogLinks']['groups'] ?? [];
            foreach ($groups as $group) {
                foreach ($group['links'] as $link) {
                    $hrefs[] = $link['href'];
                    $labels[] = $link['label'];
                }
            }
            foreach ($present as $href) {
                $this->assertContains($href, $hrefs, 'Missing catalog link '.$href);
            }
            foreach ($absent as $href) {
                $this->assertNotContains($href, $hrefs, 'Unexpected catalog link '.$href);
            }

            return true;
        });

        foreach ($present as $href) {
            $this->assertStringContainsString('href="'.$href.'"', $nav);
        }
        foreach ($absent as $href) {
            $this->assertStringNotContainsString('href="'.$href.'"', $nav);
        }
        foreach ($labels as $label) {
            $this->assertDoesNotMatchRegularExpression('/\b(buy|price|coupon|best|ranking)\b/i', $label);
        }
        $joined = implode(' ', $hrefs);
        $this->assertStringNotContainsString('/calculator', $joined);
        $this->assertStringNotContainsString('/calculator', $nav);
    }
}
