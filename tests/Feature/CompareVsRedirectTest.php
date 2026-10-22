<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\CompareController;
use App\Models\ProductCategory;
use App\Support\VsPairIndexing;
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

        // SEO H1, title, and breadcrumb use the category names. The
        // visible heading still reads the CompoundDisplay props (GLP3-R / GLP2-T).
        $target->assertSee('<h1 class="ssr-seo-h1">Retatrutide vs Tirzepatide</h1>', false);
        $target->assertDontSee('<h1 class="ssr-seo-h1">GLP3-R vs GLP2-T</h1>', false);
        $target->assertSee('<title>Retatrutide vs Tirzepatide — Price Comparison — Peptidemap</title>', false);
        $target->assertSee('"name":"Retatrutide vs Tirzepatide"', false);
        $target->assertInertia(fn ($page) => $page
            ->component('Frontend/CompareCompoundVs')
            ->where('a.name', 'GLP3-R')
            ->where('b.name', 'GLP2-T'));
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

        $target->assertSee('<h1 class="ssr-seo-h1">Cagrilintide vs Retatrutide</h1>', false);
        $target->assertDontSee('<h1 class="ssr-seo-h1">Cagrilintide vs GLP3-R</h1>', false);
        $target->assertSee('<title>Cagrilintide vs Retatrutide — Price Comparison — Peptidemap</title>', false);
        $target->assertSee('"name":"Cagrilintide vs Retatrutide"', false);
        $target->assertDontSee('Retatrutide vs', false);
        $target->assertInertia(fn ($page) => $page
            ->where('a.name', 'Cagrilintide')
            ->where('b.name', 'GLP3-R'));
        $this->assertFalse(VsPairIndexing::isAllowlisted('cagrilintide-vs-retatrutide'));
    }

    public function test_every_featured_vs_pair_returns_200(): void
    {
        $slugs = collect(CompareController::FEATURED_VS_PAIRS)
            ->flatMap(fn (array $pair) => [$pair['a'], $pair['b']])
            ->unique();

        foreach ($slugs as $slug) {
            ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'is_active' => true,
            ]);
        }

        $this->assertNotEmpty(CompareController::FEATURED_VS_PAIRS);

        foreach (CompareController::FEATURED_VS_PAIRS as $pair) {
            $this->get('/compare/'.$pair['a'].'-vs-'.$pair['b'])->assertOk()->assertHeaderMissing('Location');
        }
    }

    public function test_kisspeptin_vs_pt141_redirects_once_to_bremelanotide(): void
    {
        // Production stores these with title-case slugs. Compare URLs stay lowercase.
        ProductCategory::create([
            'name' => 'Kisspeptin',
            'slug' => 'Kisspeptin',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'Bremelanotide',
            'slug' => 'Bremelanotide',
            'is_active' => true,
        ]);

        foreach ([
            '/compare/kisspeptin-vs-pt-141',
            '/compare/pt-141-vs-kisspeptin',
            '/compare/PT-141-vs-Kisspeptin',
            '/compare/kisspeptin-vs-pt141',
            '/compare/pt141-vs-kisspeptin',
        ] as $from) {
            $target = $this->assertOneHop($from, '/compare/bremelanotide-vs-kisspeptin');
        }

        $target->assertSee('<h1 class="ssr-seo-h1">Bremelanotide vs Kisspeptin</h1>', false);
        $target->assertSee('<title>Bremelanotide vs Kisspeptin — Price Comparison — Peptidemap</title>', false);

        $this->assertOneHop(
            '/compare/kisspeptin-vs-pt-141?utm=qa',
            '/compare/bremelanotide-vs-kisspeptin?utm=qa'
        );

        $this->assertOneHop('/compare/pt-141?utm=qa', '/compare/bremelanotide?utm=qa');
        $this->assertOneHop('/compare/Bremelanotide?ref=1', '/compare/bremelanotide?ref=1');
    }

    public function test_pt141_pair_404s_without_a_location_when_bremelanotide_is_missing(): void
    {
        ProductCategory::create([
            'name' => 'Kisspeptin',
            'slug' => 'kisspeptin',
            'is_active' => true,
        ]);

        $this->get('/compare/pt-141-vs-kisspeptin')
            ->assertNotFound()
            ->assertHeaderMissing('Location');
    }

    public function test_ss31_alias_redirects_once_to_the_elamipretide_page(): void
    {
        ProductCategory::create([
            'name' => 'Elamipretide',
            'slug' => 'elamipretide',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'MOTS-c',
            'slug' => 'mots-c',
            'is_active' => true,
        ]);

        foreach (['/compare/ss-31', '/compare/ss31', '/compare/SS-31'] as $from) {
            $this->assertOneHop($from, '/compare/elamipretide');
        }

        $this->assertOneHop('/compare/ss-31?utm=qa', '/compare/elamipretide?utm=qa');
        $this->assertOneHop('/encyclopedia/ss-31', '/encyclopedia/elamipretide');
        $this->assertOneHop('/compare/ss-31-vs-mots-c', '/compare/elamipretide-vs-mots-c');
        $this->assertOneHop('/compare/mots-c-vs-ss31', '/compare/elamipretide-vs-mots-c');
    }

    public function test_ss31_compare_alias_404s_when_elamipretide_is_missing(): void
    {
        $this->get('/compare/ss-31')
            ->assertNotFound()
            ->assertHeaderMissing('Location');
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
