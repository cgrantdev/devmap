<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\ComparePageSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacteriostaticWaterSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_bacteriostatic_water_seo_is_neutral_and_within_length(): void
    {
        $category = ProductCategory::create([
            'name' => 'Bacteriostatic Water',
            'slug' => 'bacteriostatic-water',
            'is_active' => true,
        ]);
        $brand = Brand::create([
            'name' => 'Alpha Research',
            'slug' => 'alpha-research',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Bacteriostatic Water 10ml',
            'slug' => 'bac-water-10ml',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 12.50,
            'status' => 'active',
            'hidden' => false,
        ]);

        $response = $this->get('/bacteriostatic-water')->assertOk();
        $html = $response->getContent();

        $title = $this->headContent($html, '/<title>(.*?)<\/title>/s');
        $description = $this->headContent($html, '/<meta name="description" content="(.*?)"/s');
        $ogDescription = $this->headContent($html, '/property="og:description" content="(.*?)"/s');

        $this->assertSame(ComparePageSeo::renderedTitle('Bacteriostatic Water', 1), $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title), $title);
        $this->assertSame(ComparePageSeo::description('Bacteriostatic Water', 1), $description);
        $this->assertSame($description, $ogDescription);
        $this->assertLessThanOrEqual(155, mb_strlen($description), $description);
        $this->assertStringNotContainsString('Cheapest', $title);
        $this->assertStringNotContainsString('$', $title);
        $this->assertStringNotContainsString('$', $description);
        $this->assertStringNotContainsString('coupon', strtolower($description));
        $this->assertStringNotContainsString('Cheapest', $description);
        $this->assertStringContainsString('Bacteriostatic Water', $html);
        $this->assertStringNotContainsString('Cheapest Bacteriostatic Water', $html);

        $response->assertInertia(function ($page) {
            $page->component('Frontend/BacteriostaticWater')
                ->where('seo.h1', 'Bacteriostatic Water')
                ->where('seo.title', ComparePageSeo::title('Bacteriostatic Water', 1))
                ->has('faqs', 7);

            $faqs = $page->toArray()['props']['faqs'];
            $blob = '';
            foreach ($faqs as $faq) {
                $blob .= ' '.$faq['q'].' '.$faq['a'];
            }
            $this->assertStringNotContainsString('$', $blob);
            $this->assertStringNotContainsString('cheapest', strtolower($blob));
            $this->assertStringNotContainsString('best deal', strtolower($blob));
            $this->assertStringNotContainsString('best per-ml', strtolower($blob));
        });
    }

    private function headContent(string $html, string $pattern): string
    {
        $this->assertSame(1, preg_match($pattern, $html, $matches), $pattern);

        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
