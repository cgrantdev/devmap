<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One encyclopedia entry, one URL. The canonical form is the stored slug.
 * Production stores those lowercase (retatrutide, 5-amino-1mq, slu-pp-332).
 * Forced families stay on their hyphen URL (vitamin-b12). Any other
 * letter case 301s there and must not publish its own canonical tag.
 */
class EncyclopediaSlugCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_case_variants_redirect_to_the_stored_slug(): void
    {
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'CJC-1295', 'slug' => 'cjc-1295', 'is_active' => true]);
        ProductCategory::create(['name' => '5-Amino-1MQ', 'slug' => '5-amino-1mq', 'is_active' => true]);
        ProductCategory::create(['name' => 'SLU-PP-332', 'slug' => 'slu-pp-332', 'is_active' => true]);
        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'bpc-157', 'is_active' => true]);

        $this->get('/encyclopedia/retatrutide')->assertOk();
        $this->get('/encyclopedia/RETATRUTIDE')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/retatrutide');
        $this->get('/encyclopedia/article/Retatrutide')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/retatrutide');

        $this->get('/encyclopedia/cjc-1295')->assertOk();
        $this->get('/encyclopedia/CJC-1295')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/cjc-1295');
        $this->get('/encyclopedia/Cjc-1295')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/cjc-1295');

        $this->get('/encyclopedia/5-amino-1mq')->assertOk();
        $this->get('/encyclopedia/5-Amino-1MQ')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/5-amino-1mq');
        $this->get('/encyclopedia/5-AMINO-1MQ')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/5-amino-1mq');

        $this->get('/encyclopedia/slu-pp-332')->assertOk();
        $this->get('/encyclopedia/SLU-PP-332')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/slu-pp-332');

        $this->get('/encyclopedia/BPC-157')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/bpc-157');
        $this->get('/encyclopedia/bpc-157')->assertOk();

        $this->get('/encyclopedia/NotARealCompound')->assertNotFound();
    }

    public function test_canonical_request_is_self_referencing_and_sitemap_and_index_agree(): void
    {
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'CJC-1295', 'slug' => 'cjc-1295', 'is_active' => true]);
        ProductCategory::create(['name' => '5-Amino-1MQ', 'slug' => '5-amino-1mq', 'is_active' => true]);
        ProductCategory::create(['name' => 'SLU-PP-332', 'slug' => 'slu-pp-332', 'is_active' => true]);
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);

        $page = $this->get('/encyclopedia/retatrutide');
        $page->assertOk();
        $page->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/retatrutide" />', false);
        $page->assertDontSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/Retatrutide" />', false);
        $page->assertInertia(fn ($view) => $view
            ->where('seo.canonical', url('/encyclopedia/retatrutide'))
            ->where('seo.url', url('/encyclopedia/retatrutide'))
            ->where('slug', 'retatrutide')
        );

        $this->get('/encyclopedia/cjc-1295')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/cjc-1295" />', false)
            ->assertInertia(fn ($view) => $view->where('slug', 'cjc-1295'));

        $this->get('/encyclopedia/5-amino-1mq')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/5-amino-1mq" />', false)
            ->assertInertia(fn ($view) => $view->where('slug', '5-amino-1mq'));

        $this->get('/encyclopedia/slu-pp-332')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/slu-pp-332" />', false)
            ->assertInertia(fn ($view) => $view->where('slug', 'slu-pp-332'));

        $this->get('/encyclopedia/vitamin-b12')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/vitamin-b12" />', false);

        $this->get('/encyclopedia')
            ->assertOk()
            ->assertInertia(fn ($view) => $view->where('peptides', function ($peptides) {
                $slugs = collect($peptides)->pluck('slug')->all();

                return in_array('retatrutide', $slugs, true)
                    && in_array('cjc-1295', $slugs, true)
                    && in_array('5-amino-1mq', $slugs, true)
                    && in_array('slu-pp-332', $slugs, true)
                    && in_array('vitamin-b12', $slugs, true)
                    && ! in_array('Retatrutide', $slugs, true)
                    && ! in_array('CJC-1295', $slugs, true)
                    && ! in_array('5-Amino-1MQ', $slugs, true)
                    && ! in_array('Vitamin B12', $slugs, true);
            }));

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>https://peptidemap.com/encyclopedia/([^<]+)</loc>#', $xml, $matches);
        $locs = $matches[1];

        $this->assertContains('retatrutide', $locs);
        $this->assertContains('cjc-1295', $locs);
        $this->assertContains('5-amino-1mq', $locs);
        $this->assertContains('slu-pp-332', $locs);
        $this->assertContains('vitamin-b12', $locs);
        $this->assertNotContains('Retatrutide', $locs);
        $this->assertNotContains('CJC-1295', $locs);
        $this->assertNotContains('5-Amino-1MQ', $locs);
        $this->assertNotContains('SLU-PP-332', $locs);
        $this->assertNotContains('Vitamin%20B12', $locs);
        $this->assertNotContains('Vitamin B12', $locs);
        $this->assertSame(1, count(array_filter($locs, fn ($slug) => strcasecmp($slug, 'retatrutide') === 0)));
        $this->assertSame(1, count(array_filter($locs, fn ($slug) => strcasecmp($slug, 'cjc-1295') === 0)));
        $this->assertSame(1, count(array_filter($locs, fn ($slug) => strcasecmp($slug, 'slu-pp-332') === 0)));
        $this->assertSame(1, count(array_filter($locs, fn ($slug) => strcasecmp($slug, 'vitamin-b12') === 0)));
    }

    public function test_forced_family_letter_case_redirects_to_the_hyphen_slug(): void
    {
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);
        ProductCategory::create(['name' => 'HGH 191AA', 'slug' => 'HGH 191AA', 'is_active' => true]);

        $this->get('/encyclopedia/VITAMIN-B12')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/vitamin-b12');
        $this->get('/encyclopedia/Vitamin-B12')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/vitamin-b12');
        $this->get('/encyclopedia/article/VITAMIN-B12')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/vitamin-b12');

        $this->get('/encyclopedia/HGH-191AA')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/hgh-191aa');
        $this->get('/encyclopedia/hgh-191aa')->assertOk();
    }

    public function test_inactive_case_variant_is_not_redirected(): void
    {
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => false]);

        $this->get('/encyclopedia/retatrutide')->assertNotFound();
        $this->get('/encyclopedia/Retatrutide')->assertNotFound();
    }

    public function test_elamipretide_trailing_slash_and_query_resolve_in_one_hop(): void
    {
        ProductCategory::create(['name' => 'Elamipretide', 'slug' => 'elamipretide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);

        $this->get('/encyclopedia/Elamipretide?utm=qa')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/elamipretide?utm=qa');
        $this->get('/encyclopedia/elamipretide?utm=qa')
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->get('/encyclopedia/article/Elamipretide?utm=qa')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/elamipretide?utm=qa');

        $this->requestWithRawUri('/encyclopedia/Elamipretide/?utm=qa')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/elamipretide?utm=qa');

        $this->requestWithRawUri('/encyclopedia/article/Elamipretide/?ref=1')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/elamipretide?ref=1');

        $this->requestWithRawUri('/encyclopedia/elamipretide/')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/elamipretide');

        $this->get('/encyclopedia/VITAMIN--B12')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/vitamin-b12');
        $this->get('/encyclopedia/vitamin-b12')
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->requestWithRawUri('/encyclopedia/VITAMIN--B12/?utm=qa')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/vitamin-b12?utm=qa');
    }

    private function requestWithRawUri(string $uri): \Illuminate\Testing\TestResponse
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $request = \Illuminate\Http\Request::create('http://localhost'.$uri, 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return \Illuminate\Testing\TestResponse::fromBaseResponse($response);
    }
}
