<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListingCanonicalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_canonical_and_og_url_use_the_stored_slug(): void
    {
        ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'Sterile Water',
            'slug' => 'Sterile Water',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'Uncategorized',
            'slug' => 'uncategorized',
            'is_active' => true,
        ]);

        $exact = $this->get('/product/bpc-157')->assertOk()->getContent();
        $this->assertCanonical($exact, 'https://peptidemap.com/product/bpc-157');

        $otherCase = $this->get('/product/BPC-157')->assertOk()->getContent();
        $this->assertCanonical($otherCase, 'https://peptidemap.com/product/bpc-157');

        $space = $this->get('/product/Sterile%20Water')->assertOk()->getContent();
        $this->assertCanonical($space, 'https://peptidemap.com/product/Sterile%20Water');

        $uncat = $this->get('/product/uncategorized')->assertOk()->getContent();
        $this->assertCanonical($uncat, 'https://peptidemap.com/product/uncategorized');
    }

    private function assertCanonical(string $html, string $url): void
    {
        $this->assertSame(1, preg_match_all('/<link rel="canonical"/', $html), $url);
        $this->assertStringContainsString('<link rel="canonical" href="'.$url.'" />', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.$url.'" />', $html);
        $this->assertStringContainsString('<meta name="twitter:url" content="'.$url.'" />', $html);
    }
}
