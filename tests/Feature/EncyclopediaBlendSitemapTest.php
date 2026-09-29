<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncyclopediaBlendSitemapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The eight blend slugs Gap Hunter found in the sitemap. Each 404s
     * because "/" splits /encyclopedia/{slug}. The live page for the same
     * category is the compare URL.
     */
    private const BLENDS = [
        'Adalank /  Adamax' => '/compare/adalank-adamax',
        'BPC-157 / TB500 / Cartalax' => '/compare/bpc-157-tb500-cartalax',
        'GHK-CU/BPC 157/KPV' => '/compare/ghk-cubpc-157kpv',
        'Retatrutide / Cagrilintide Blend' => '/compare/retatrutide-cagrilintide-blend',
        'Retatrutide / Tirzepatide Blend' => '/compare/retatrutide-tirzepatide-blend',
        'Selank/Semax' => '/compare/selanksemax',
        'Semaglutide / Cagrilintide Blend' => '/compare/semaglutide-cagrilintide-blend',
        'Tesamorlin / Ipamorelin' => '/compare/tesamorlin-ipamorelin',
    ];

    public function test_sitemap_omits_slash_blend_encyclopedia_locs(): void
    {
        foreach (array_keys(self::BLENDS) as $slug) {
            ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'is_active' => true,
            ]);
        }
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);
        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        ProductCategory::create(['name' => 'BPC-157 / TB-500', 'slug' => 'BPC-157-TB-500', 'is_active' => true]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $locs = $this->locs($xml);

        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/Vitamin%20B12', $locs);
        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/BPC-157', $locs);
        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/BPC-157-TB-500', $locs);

        foreach (self::BLENDS as $slug => $compare) {
            $this->assertStringNotContainsString('/encyclopedia/'.$slug, $xml);
            $this->assertStringNotContainsString('/encyclopedia/'.rawurlencode($slug), $xml);
            $this->assertStringContainsString('https://peptidemap.com'.$compare, $locs);
        }

        $this->assertStringNotContainsString('/encyclopedia/Selank/', $xml);
        $this->assertStringNotContainsString('/encyclopedia/GHK-CU/', $xml);
        $this->assertStringNotContainsString('/encyclopedia/Adalank%20/', $xml);
        $this->assertStringNotContainsString('/encyclopedia/Tesamorlin', $xml);
        $this->assertStringNotContainsString('/encyclopedia/BPC-157%20/', $xml);
        $this->assertStringNotContainsString('Retatrutide%20/', $xml);
        $this->assertStringNotContainsString('Semaglutide%20/', $xml);
    }

    public function test_slash_blend_urls_redirect_to_the_live_compare_page(): void
    {
        foreach (array_keys(self::BLENDS) as $slug) {
            ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'is_active' => true,
            ]);
        }

        foreach (self::BLENDS as $slug => $compare) {
            $this->get('/encyclopedia/'.$this->sitemapPath($slug))
                ->assertStatus(301)
                ->assertRedirect($compare);
        }

        $this->get('/encyclopedia/Nope/Thing')->assertNotFound();
    }

    public function test_spaced_encyclopedia_slug_still_resolves_and_hyphen_form_redirects(): void
    {
        ProductCategory::create(['name' => 'Vitamin B12', 'slug' => 'Vitamin B12', 'is_active' => true]);

        $this->get('/encyclopedia/vitamin-b12')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/Vitamin B12');

        $this->get('/encyclopedia/article/Vitamin%20B12')
            ->assertStatus(301)
            ->assertRedirect('/encyclopedia/Vitamin B12');

        $this->withoutVite();
        $this->get('/encyclopedia/Vitamin%20B12')->assertOk();
    }

    private function sitemapPath(string $slug): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $slug)));
    }

    private function locs(string $xml): string
    {
        preg_match_all('#<loc>(.*?)</loc>#', $xml, $matches);

        return implode("\n", $matches[1] ?? []);
    }
}
