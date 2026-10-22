<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\CompareController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\ProductCategory;
use App\Support\VsPairIndexing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VsSitemapIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_allowlist_is_alphabetical_and_includes_starters_editorial_and_featured(): void
    {
        $allow = config('seo.vs_sitemap_allowlist');
        $sorted = $allow;
        sort($sorted, SORT_STRING);

        $this->assertIsArray($allow);
        $this->assertSame($sorted, $allow);
        $this->assertTrue(config('seo.vs_noindex_thin'));

        foreach ([
            'elamipretide-vs-mots-c',
            'mazdutide-vs-tirzepatide',
            'retatrutide-vs-survodutide',
            'selank-vs-semax',
            'cagrilintide-vs-eloralintide',
            'orforglipron-vs-tirzepatide',
            'sermorelin-vs-tesamorelin',
        ] as $slug) {
            $this->assertContains($slug, $allow);
        }

        foreach (CompareController::FEATURED_VS_PAIRS as $pair) {
            $this->assertContains($pair['a'].'-vs-'.$pair['b'], $allow);
        }

        $this->assertNotContains('glow-vs-klow', $allow);
        $this->assertNotContains('eloralintide-vs-retatrutide', $allow);
        $this->assertNotContains('cjc-1295-dac-vs-no-dac', $allow);
    }

    public function test_allowlisted_pairs_return_200_are_in_the_sitemap_and_are_indexable(): void
    {
        $this->activatePairs(config('seo.vs_sitemap_allowlist'));

        $xml = $this->sitemap();

        foreach (config('seo.vs_sitemap_allowlist') as $slug) {
            $response = $this->get('/compare/'.$slug);
            $response->assertOk();
            $response->assertHeaderMissing('Location');
            $response->assertSee(
                '<link rel="canonical" href="https://peptidemap.com/compare/'.$slug.'" />',
                false
            );
            $response->assertDontSee('noindex', false);
            $response->assertDontSee('<meta name="robots"', false);
            $this->assertSame(
                1,
                substr_count($xml, 'https://peptidemap.com/compare/'.$slug.'</loc>'),
                $slug
            );
        }

        $this->get('/compare/semax-vs-selank')
            ->assertRedirect('/compare/selank-vs-semax');
    }

    public function test_mixed_case_category_slugs_still_list_the_canonical_pair(): void
    {
        ProductCategory::create(['name' => 'Selank', 'slug' => 'Selank', 'is_active' => true]);
        ProductCategory::create(['name' => 'Semax', 'slug' => 'Semax', 'is_active' => true]);
        ProductCategory::create(['name' => 'Survodutide', 'slug' => 'Survodutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'Retatrutide', 'is_active' => true]);

        $xml = $this->sitemap();

        $this->assertStringContainsString('https://peptidemap.com/compare/selank-vs-semax</loc>', $xml);
        $this->assertStringContainsString('https://peptidemap.com/compare/retatrutide-vs-survodutide</loc>', $xml);
    }

    public function test_thin_pair_is_noindex_follow_and_absent_from_the_sitemap(): void
    {
        $this->activatePair('epitalon', 'kpv');

        $this->assertFalse(VsPairIndexing::isAllowlisted('epitalon-vs-kpv'));
        $this->assertFalse(VsPairIndexing::isFeatured('epitalon-vs-kpv'));
        $this->assertFalse(VsPairIndexing::hasEditorialContent('epitalon-vs-kpv'));

        $this->get('/compare/epitalon-vs-kpv')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertSee('<meta name="robots" content="noindex,follow" />', false)
            ->assertSee(
                '<link rel="canonical" href="https://peptidemap.com/compare/epitalon-vs-kpv" />',
                false
            );

        $this->assertStringNotContainsString(
            'https://peptidemap.com/compare/epitalon-vs-kpv',
            $this->sitemap()
        );
    }

    public function test_featured_pairs_stay_indexable_and_in_the_sitemap(): void
    {
        config(['seo.vs_sitemap_allowlist' => array_values(array_filter(
            config('seo.vs_sitemap_allowlist'),
            fn ($slug) => ! in_array($slug, ['semaglutide-vs-tirzepatide', 'retatrutide-vs-tirzepatide'], true)
        ))]);

        ProductCategory::create(['name' => 'Semaglutide', 'slug' => 'semaglutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'Tirzepatide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'Retatrutide', 'is_active' => true]);

        $this->assertFalse(VsPairIndexing::isAllowlisted('retatrutide-vs-tirzepatide'));
        $this->assertTrue(VsPairIndexing::isFeatured('retatrutide-vs-tirzepatide'));

        $this->get('/compare/retatrutide-vs-tirzepatide')
            ->assertOk()
            ->assertDontSee('noindex', false)
            ->assertSee('<h1 class="ssr-seo-h1">Retatrutide vs Tirzepatide</h1>', false)
            ->assertDontSee('<h1 class="ssr-seo-h1">GLP3-R vs GLP2-T</h1>', false)
            ->assertSee('<title>Retatrutide vs Tirzepatide — Price Comparison — Peptidemap</title>', false)
            ->assertSee('"name":"Retatrutide vs Tirzepatide"', false)
            ->assertInertia(fn ($page) => $page
                ->where('a.name', 'GLP3-R')
                ->where('b.name', 'GLP2-T'))
            ->assertSee(
                '<link rel="canonical" href="https://peptidemap.com/compare/retatrutide-vs-tirzepatide" />',
                false
            );

        $this->get('/compare/semaglutide-vs-tirzepatide')
            ->assertOk()
            ->assertDontSee('noindex', false);

        $xml = $this->sitemap();
        $this->assertSame(1, substr_count($xml, 'https://peptidemap.com/compare/retatrutide-vs-tirzepatide</loc>'));
        $this->assertSame(1, substr_count($xml, 'https://peptidemap.com/compare/semaglutide-vs-tirzepatide</loc>'));
    }

    public function test_kill_switch_restores_indexing_and_leaves_the_thin_pair_out_of_the_sitemap(): void
    {
        $this->activatePair('epitalon', 'kpv');
        $this->activatePair('selank', 'semax');
        $this->activatePair('bpc-157', 'tb-500');

        $this->get('/compare/epitalon-vs-kpv')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow" />', false);

        $this->get('/compare/selank-vs-semax')->assertOk()->assertDontSee('noindex', false);
        $this->get('/compare/bpc-157-vs-tb-500')->assertOk()->assertDontSee('noindex', false);

        // Boolean false and the env string "false" both turn the switch off.
        foreach ([false, 'false'] as $off) {
            config(['seo.vs_noindex_thin' => $off]);

            $this->assertTrue(VsPairIndexing::isIndexable('epitalon-vs-kpv'));
            $this->assertTrue(VsPairIndexing::isIndexable('selank-vs-semax'));
            $this->assertTrue(VsPairIndexing::isIndexable('bpc-157-vs-tb-500'));

            $this->get('/compare/epitalon-vs-kpv')
                ->assertOk()
                ->assertDontSee('noindex', false)
                ->assertDontSee('<meta name="robots"', false)
                ->assertSee(
                    '<link rel="canonical" href="https://peptidemap.com/compare/epitalon-vs-kpv" />',
                    false
                );

            $this->get('/compare/selank-vs-semax')
                ->assertOk()
                ->assertDontSee('noindex', false)
                ->assertSee(
                    '<link rel="canonical" href="https://peptidemap.com/compare/selank-vs-semax" />',
                    false
                );

            $this->get('/compare/bpc-157-vs-tb-500')
                ->assertOk()
                ->assertDontSee('noindex', false)
                ->assertSee(
                    '<link rel="canonical" href="https://peptidemap.com/compare/bpc-157-vs-tb-500" />',
                    false
                );
        }

        $this->assertStringNotContainsString(
            'https://peptidemap.com/compare/epitalon-vs-kpv',
            $this->sitemap()
        );
    }

    public function test_published_head_to_head_keeps_a_pair_indexable_but_out_of_the_sitemap(): void
    {
        $this->activatePair('epitalon', 'kpv');

        Blog::create([
            'title' => 'Draft epitalon vs KPV',
            'slug' => 'kpv-vs-epitalon',
            'status' => 'draft',
            'content' => '<p>Educational head-to-head.</p>',
        ]);

        $this->get('/compare/epitalon-vs-kpv')
            ->assertSee('<meta name="robots" content="noindex,follow" />', false);

        Blog::query()->where('slug', 'kpv-vs-epitalon')->update([
            'status' => 'published',
            'content' => '   ',
        ]);

        $this->get('/compare/epitalon-vs-kpv')
            ->assertSee('<meta name="robots" content="noindex,follow" />', false);

        Blog::query()->where('slug', 'kpv-vs-epitalon')->update([
            'content' => '<p>Educational head-to-head of epitalon and KPV.</p>',
        ]);

        $this->assertTrue(VsPairIndexing::hasEditorialContent('epitalon-vs-kpv'));

        $this->get('/compare/epitalon-vs-kpv')
            ->assertOk()
            ->assertDontSee('noindex', false)
            ->assertSee(
                '<link rel="canonical" href="https://peptidemap.com/compare/epitalon-vs-kpv" />',
                false
            );

        $this->assertStringNotContainsString(
            'https://peptidemap.com/compare/epitalon-vs-kpv',
            $this->sitemap()
        );
    }

    public function test_published_guide_with_an_evidence_suffix_counts_as_editorial(): void
    {
        $this->activatePair('adamax', 'dihexa');

        EducationalGuide::create([
            'title' => 'Adamax vs Dihexa',
            'slug' => 'adamax-vs-dihexa-evidence',
            'status' => 'published',
            'content' => '<p>Educational head-to-head.</p>',
        ]);

        $this->assertTrue(VsPairIndexing::hasEditorialContent('dihexa-vs-adamax'));

        $this->get('/compare/adamax-vs-dihexa')
            ->assertOk()
            ->assertDontSee('noindex', false);

        $this->assertStringNotContainsString(
            'https://peptidemap.com/compare/adamax-vs-dihexa',
            $this->sitemap()
        );
    }

    public function test_on_page_pair_faqs_stay_indexable_when_dropped_from_the_allowlist(): void
    {
        config(['seo.vs_sitemap_allowlist' => array_values(array_filter(
            config('seo.vs_sitemap_allowlist'),
            fn ($slug) => $slug !== 'elamipretide-vs-mots-c'
        ))]);
        Cache::forget(SitemapController::CACHE_KEY);

        $this->activatePair('elamipretide', 'mots-c');

        $this->assertFalse(VsPairIndexing::isAllowlisted('elamipretide-vs-mots-c'));
        $this->assertFalse(VsPairIndexing::isFeatured('elamipretide-vs-mots-c'));
        $this->assertTrue(VsPairIndexing::hasEditorialContent('elamipretide-vs-mots-c'));

        $this->get('/compare/elamipretide-vs-mots-c')
            ->assertOk()
            ->assertDontSee('noindex', false)
            ->assertSee('Does SS-31 need to come before MOTS-c?', false);

        $this->assertStringNotContainsString(
            'https://peptidemap.com/compare/elamipretide-vs-mots-c',
            $this->sitemap()
        );
    }

    public function test_allowlisted_pair_with_a_missing_side_stays_out_of_the_sitemap(): void
    {
        ProductCategory::create(['name' => 'Selank', 'slug' => 'Selank', 'is_active' => true]);

        $sitemap = $this->sitemap();
        // This post's /blog loc contains the pair slug. Drop only that loc;
        // the original check still rejects the compare URL and any other hit.
        $withoutThisPost = str_replace('https://peptidemap.com/blog/selank-vs-semax', '', $sitemap);
        $this->assertNotSame($sitemap, $withoutThisPost);
        $this->assertStringNotContainsString('selank-vs-semax', $withoutThisPost);
        $this->get('/compare/selank-vs-semax')->assertNotFound();
    }

    /**
     * @param  list<string>  $pairSlugs
     */
    private function activatePairs(array $pairSlugs): void
    {
        $sides = [];
        foreach ($pairSlugs as $slug) {
            $pos = strpos($slug, '-vs-');
            $sides[substr($slug, 0, $pos)] = true;
            $sides[substr($slug, $pos + 4)] = true;
        }

        foreach (array_keys($sides) as $side) {
            ProductCategory::create([
                'name' => $side,
                'slug' => $side,
                'is_active' => true,
            ]);
        }
    }

    private function activatePair(string $a, string $b): void
    {
        foreach ([$a, $b] as $side) {
            ProductCategory::query()->firstOrCreate(
                ['slug' => $side],
                ['name' => $side, 'is_active' => true]
            );
        }
    }

    private function sitemap(): string
    {
        return $this->get('/sitemap.xml')->assertOk()->getContent();
    }
}
