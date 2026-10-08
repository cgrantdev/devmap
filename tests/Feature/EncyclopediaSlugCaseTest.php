<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One encyclopedia entry, one URL. The canonical form is the stored slug
 * (CJC-1295, 5-Amino-1MQ), which is what the sitemap already lists.
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
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'Retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'CJC-1295', 'slug' => 'CJC-1295', 'is_active' => true]);
        ProductCategory::create(['name' => '5-Amino-1MQ', 'slug' => '5-Amino-1MQ', 'is_active' => true]);
        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'bpc-157', 'is_active' => true]);

        $this->get('/encyclopedia/retatrutide')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/Retatrutide');
        $this->get('/encyclopedia/RETATRUTIDE')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/Retatrutide');
        $this->get('/encyclopedia/article/retatrutide')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/Retatrutide');

        $this->get('/encyclopedia/cjc-1295')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/CJC-1295');
        $this->get('/encyclopedia/Cjc-1295')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/CJC-1295');

        $this->get('/encyclopedia/5-amino-1mq')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/5-Amino-1MQ');
        $this->get('/encyclopedia/5-AMINO-1MQ')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/5-Amino-1MQ');

        // Stored lowercase stays lowercase. Case variants fold to the row, not to title case.
        $this->get('/encyclopedia/BPC-157')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/bpc-157');
        $this->get('/encyclopedia/bpc-157')->assertOk();

        $this->get('/encyclopedia/NotARealCompound')->assertNotFound();
    }

    public function test_canonical_request_is_self_referencing_and_sitemap_and_index_agree(): void
    {
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'Retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'CJC-1295', 'slug' => 'CJC-1295', 'is_active' => true]);
        ProductCategory::create(['name' => '5-Amino-1MQ', 'slug' => '5-Amino-1MQ', 'is_active' => true]);
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);

        $page = $this->get('/encyclopedia/Retatrutide');
        $page->assertOk();
        $page->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/Retatrutide" />', false);
        $page->assertDontSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/retatrutide" />', false);
        $page->assertInertia(fn ($view) => $view
            ->where('seo.canonical', url('/encyclopedia/Retatrutide'))
            ->where('seo.url', url('/encyclopedia/Retatrutide'))
            ->where('slug', 'Retatrutide')
        );

        $this->get('/encyclopedia/CJC-1295')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/CJC-1295" />', false)
            ->assertInertia(fn ($view) => $view->where('slug', 'CJC-1295'));

        $this->get('/encyclopedia/5-Amino-1MQ')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/5-Amino-1MQ" />', false)
            ->assertInertia(fn ($view) => $view->where('slug', '5-Amino-1MQ'));

        $this->get('/encyclopedia/vitamin-b12')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://peptidemap.com/encyclopedia/vitamin-b12" />', false);

        $this->get('/encyclopedia')
            ->assertOk()
            ->assertInertia(fn ($view) => $view->where('peptides', function ($peptides) {
                $slugs = collect($peptides)->pluck('slug')->all();

                return in_array('Retatrutide', $slugs, true)
                    && in_array('CJC-1295', $slugs, true)
                    && in_array('5-Amino-1MQ', $slugs, true)
                    && in_array('vitamin-b12', $slugs, true)
                    && ! in_array('retatrutide', $slugs, true)
                    && ! in_array('Vitamin B12', $slugs, true);
            }));

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('#<loc>https://peptidemap.com/encyclopedia/([^<]+)</loc>#', $xml, $matches);
        $locs = $matches[1];

        $this->assertContains('Retatrutide', $locs);
        $this->assertContains('CJC-1295', $locs);
        $this->assertContains('5-Amino-1MQ', $locs);
        $this->assertContains('vitamin-b12', $locs);
        $this->assertNotContains('retatrutide', $locs);
        $this->assertNotContains('cjc-1295', $locs);
        $this->assertNotContains('5-amino-1mq', $locs);
        $this->assertNotContains('Vitamin%20B12', $locs);
        $this->assertNotContains('Vitamin B12', $locs);
        $this->assertSame(1, count(array_filter($locs, fn ($slug) => strcasecmp($slug, 'Retatrutide') === 0)));
        $this->assertSame(1, count(array_filter($locs, fn ($slug) => strcasecmp($slug, 'CJC-1295') === 0)));
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
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'Retatrutide', 'is_active' => false]);

        $this->get('/encyclopedia/retatrutide')->assertNotFound();
        $this->get('/encyclopedia/Retatrutide')->assertNotFound();
    }
}
