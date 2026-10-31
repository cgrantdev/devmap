<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\SitemapController;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductListingCaseRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::forget(SitemapController::CACHE_KEY);
    }

    private function category(string $slug, bool $active = true): ProductCategory
    {
        return ProductCategory::create(['name' => 'QA '.$slug, 'slug' => $slug, 'is_active' => $active]);
    }

    private function assertCanonical(string $html, string $url): void
    {
        $this->assertSame(1, preg_match_all('/<link rel="canonical"/', $html), $url);
        $this->assertStringContainsString('<link rel="canonical" href="'.$url.'" />', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.$url.'" />', $html);
        $this->assertStringContainsString('<meta name="twitter:url" content="'.$url.'" />', $html);
    }

    public static function caseMismatches(): array
    {
        return [
            'lowercase request, mixed-case stored' => ['BPC-157', '/product/bpc-157', '/product/BPC-157'],
            'uppercase request, lowercase stored' => ['nad', '/product/NAD', '/product/nad'],
            'mixed request, mixed stored' => ['GHK-Cu', '/product/GHK-CU', '/product/GHK-Cu'],
            'query string kept' => ['BPC-157', '/product/bpc-157?page=2&sort=price', '/product/BPC-157?page=2&sort=price'],
            'trailing slash dropped' => ['BPC-157', '/product/bpc-157/', '/product/BPC-157'],
            'space slug stays encoded' => ['Sterile Water', '/product/sterile%20water', '/product/Sterile%20Water'],
            'plus and spaces encoded' => ['MIC + B12', '/product/mic%20%2B%20b12', '/product/MIC%20%2B%20B12'],
            'uncategorized' => ['uncategorized', '/product/UNCATEGORIZED', '/product/uncategorized'],
        ];
    }

    #[DataProvider('caseMismatches')]
    public function test_case_variant_301s_once_to_the_stored_slug(string $stored, string $request, string $target): void
    {
        $this->category($stored);

        $this->get($request)->assertStatus(301)->assertHeader('Location', 'http://localhost'.$target);

        // The target is served directly: no second hop, no loop.
        $html = $this->get($target)->assertOk()->getContent();
        $this->assertCanonical($html, 'https://peptidemap.com'.strtok($target, '?'));
    }

    public function test_stored_slug_urls_are_served_without_a_redirect(): void
    {
        foreach (['BPC-157', 'nad', 'GHK-Cu', '9-Me-BC', 'hCG', 'Sterile Water', 'MIC + B12', 'uncategorized'] as $slug) {
            $this->category($slug);
            $url = '/product/'.rawurlencode($slug);
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertCanonical($html, 'https://peptidemap.com'.$url);
        }
        $this->get('/product/BPC-157/')->assertOk();
        $this->get('/product/BPC-157?page=2')->assertOk();
    }

    public function test_noindex_follows_the_stored_slug_after_the_redirect(): void
    {
        $this->category('Vitamin B12');
        $this->category('uncategorized');

        foreach (['/product/vitamin%20b12' => '/product/Vitamin%20B12', '/product/Uncategorized' => '/product/uncategorized'] as $from => $to) {
            $this->get($from)->assertStatus(301)->assertHeader('Location', 'http://localhost'.$to);
            $html = $this->get($to)->assertOk()->getContent();
            $this->assertSame(1, preg_match_all('/<meta name="robots"/', $html));
            $this->assertStringContainsString('<meta name="robots" content="noindex, follow" />', $html);
        }
    }

    public function test_slash_slugs_still_404_in_any_case(): void
    {
        $this->category('Selank/Semax');

        $this->get('/product/Selank%2FSemax')->assertNotFound();
        $this->get('/product/selank%2Fsemax')->assertNotFound();
    }

    public function test_exact_match_wins_over_a_case_variant_row(): void
    {
        // SQLite's unique index is case-sensitive, so both rows can exist here.
        // Production MySQL (utf8mb4_unicode_ci) rejects the second row.
        $upper = $this->category('Dup-X');
        $lower = $this->category('dup-x');

        $a = $this->get('/product/Dup-X')->assertOk()->getContent();
        $this->assertCanonical($a, 'https://peptidemap.com/product/Dup-X');
        $this->assertStringContainsString('QA Dup-X', $a);

        $b = $this->get('/product/dup-x')->assertOk()->getContent();
        $this->assertCanonical($b, 'https://peptidemap.com/product/dup-x');
        $this->assertStringContainsString('QA dup-x', $b);

        $this->assertNotSame($upper->id, $lower->id);
    }

    public function test_ambiguous_case_insensitive_match_404s(): void
    {
        $this->category('Dup-X');
        $this->category('dup-x');

        $this->get('/product/DUP-X')->assertNotFound();
        $this->get('/product/dUp-X')->assertNotFound();
    }

    public function test_inactive_rows_are_not_matched_in_any_case(): void
    {
        $this->category('Retired-1', false);

        $this->get('/product/Retired-1')->assertNotFound();
        $this->get('/product/retired-1')->assertNotFound();
    }

    public function test_sitemap_lists_only_the_stored_slug(): void
    {
        $this->category('BPC-157');

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertSame(1, substr_count($xml, '<loc>https://peptidemap.com/product/BPC-157</loc>'));
        $this->assertSame(0, substr_count($xml, '<loc>https://peptidemap.com/product/bpc-157</loc>'));
    }
}
