<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\SitemapController;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductListingRobotsLegitSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::forget(SitemapController::CACHE_KEY);
    }

    public static function legitSlugs(): array
    {
        return [
            'uppercase + hyphen + digits' => ['BPC-157'],
            'leading digit' => ['9-Me-BC'],
            'mixed case' => ['GHK-Cu'],
            'hyphenated vitamin' => ['Vitamin-B12'],
            'underscore' => ['vitamin_b12'],
            'dot' => ['GHK.Cu'],
            'alnum only' => ['ABC123'],
            'contains uncategorized' => ['uncategorized-blend'],
        ];
    }

    #[DataProvider('legitSlugs')]
    public function test_route_safe_slugs_stay_indexable_and_in_the_sitemap(string $slug): void
    {
        ProductCategory::create(['name' => 'Legit '.$slug, 'slug' => $slug, 'is_active' => true]);

        $html = $this->get('/product/'.$slug)->assertOk()->assertHeaderMissing('X-Robots-Tag')->getContent();
        $this->assertStringNotContainsString('<meta name="robots"', $html);
        $this->assertStringNotContainsString('noindex', $html);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertSame(1, substr_count($xml, '<loc>https://peptidemap.com/product/'.$slug.'</loc>'));
        $this->assertNotFalse(simplexml_load_string($xml));
    }

    public function test_noindex_pages_emit_exactly_one_robots_meta_and_one_canonical(): void
    {
        ProductCategory::create(['name' => 'Uncategorized', 'slug' => 'uncategorized', 'is_active' => true]);
        ProductCategory::create(['name' => 'Sterile Water', 'slug' => 'Sterile Water', 'is_active' => true]);

        foreach (['/product/uncategorized', '/product/Sterile%20Water'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();
            $this->assertSame(1, preg_match_all('/<meta name="robots"/', $html), $path);
            $this->assertStringContainsString('<meta name="robots" content="noindex, follow" />', $html);
            $this->assertSame(1, preg_match_all('/<link rel="canonical"/', $html), $path);
        }
    }
}
