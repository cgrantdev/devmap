<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CompareVsRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_tesamorelin_vs_hgh_redirects_to_the_live_encyclopedia_entry(): void
    {
        // Production stores Tesamorelin with that casing. /encyclopedia/tesamorelin
        // 301s to it, so the compare redirect must land on the stored slug.
        ProductCategory::create([
            'name' => 'Tesamorelin',
            'slug' => 'Tesamorelin',
            'is_active' => true,
        ]);

        foreach ([
            '/compare/tesamorelin-vs-hgh',
            '/compare/hgh-vs-tesamorelin',
            '/compare/Tesamorelin-vs-HGH',
        ] as $from) {
            $this->assertOneHop($from, '/encyclopedia/Tesamorelin');
        }
    }

    public function test_tesamorelin_hgh_stays_on_the_encyclopedia_when_an_hgh_category_exists(): void
    {
        ProductCategory::create([
            'name' => 'Tesamorelin',
            'slug' => 'Tesamorelin',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'HGH',
            'slug' => 'hgh',
            'is_active' => true,
        ]);

        $this->assertOneHop('/compare/hgh-vs-tesamorelin', '/encyclopedia/Tesamorelin');
    }

    public function test_missing_vs_pair_does_not_redirect_to_the_reordered_url(): void
    {
        ProductCategory::create([
            'name' => 'Tesamorelin',
            'slug' => 'tesamorelin',
            'is_active' => true,
        ]);

        // Alphabetical order would be tesamorelin-vs-zzzz. The reverse
        // request used to 301 there, and that URL 404s.
        $this->get('/compare/zzzz-vs-tesamorelin')
            ->assertNotFound()
            ->assertHeaderMissing('Location');

        $this->get('/compare/tesamorelin-vs-zzzz')
            ->assertNotFound()
            ->assertHeaderMissing('Location');

        $this->get('/compare/zzzz-vs-aaaa')
            ->assertNotFound()
            ->assertHeaderMissing('Location');
    }

    public function test_live_vs_pair_still_redirects_to_alphabetical_order_in_one_hop(): void
    {
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'Retatrutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'Tirzepatide', 'is_active' => true]);

        $target = $this->assertOneHop(
            '/compare/Tirzepatide-vs-Retatrutide',
            '/compare/retatrutide-vs-tirzepatide'
        );

        $target->assertSee('<h1 class="ssr-seo-h1">GLP3-R vs GLP2-T</h1>', false);
        $target->assertSee('<title>GLP3-R vs GLP2-T — Vendor &amp; Price Comparison — Peptidemap</title>', false);
    }

    public function test_cagrilintide_vs_reta_redirects_to_the_retatrutide_pair_in_one_hop(): void
    {
        ProductCategory::create(['name' => 'Cagrilintide', 'slug' => 'cagrilintide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);

        $target = null;
        foreach ([
            '/compare/cagrilintide-vs-reta',
            '/compare/reta-vs-cagrilintide',
            '/compare/Cagrilintide-vs-Reta',
        ] as $from) {
            $target = $this->assertOneHop($from, '/compare/cagrilintide-vs-retatrutide');
        }

        $target->assertSee('<h1 class="ssr-seo-h1">Cagrilintide vs GLP3-R</h1>', false);
        $target->assertSee('<title>Cagrilintide vs GLP3-R — Price Comparison — Peptidemap</title>', false);
        $target->assertDontSee('Retatrutide vs', false);
    }

    private function assertOneHop(string $from, string $to): TestResponse
    {
        $response = $this->get($from);
        $response->assertStatus(301);
        $response->assertHeader('Location', url($to));

        $target = $this->get($response->headers->get('Location'));
        $target->assertOk();

        return $target;
    }
}
