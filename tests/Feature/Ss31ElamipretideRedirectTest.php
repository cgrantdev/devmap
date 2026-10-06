<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ss31ElamipretideRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_ss31_encyclopedia_aliases_redirect_to_elamipretide(): void
    {
        foreach (['ss-31', 'SS-31', 'Ss-31', 'ss31', 'SS31'] as $slug) {
            $this->get('/encyclopedia/'.$slug)
                ->assertStatus(301)
                ->assertHeader('Location', url('/encyclopedia/elamipretide'));
        }

        $this->get('/encyclopedia/article/ss-31')
            ->assertStatus(301)
            ->assertHeader('Location', url('/encyclopedia/elamipretide'));

        $this->get('/encyclopedia/article/SS-31')
            ->assertStatus(301)
            ->assertHeader('Location', url('/encyclopedia/elamipretide'));
    }

    public function test_ss31_compare_pairs_redirect_to_elamipretide_vs_mots_c(): void
    {
        foreach ([
            'ss-31-vs-mots-c',
            'mots-c-vs-ss-31',
            'SS-31-vs-MOTS-C',
            'MOTS-C-vs-SS-31',
            'ss31-vs-mots-c',
            'mots-c-vs-ss31',
        ] as $slug) {
            $this->get('/compare/'.$slug)
                ->assertStatus(301)
                ->assertHeader('Location', url('/compare/elamipretide-vs-mots-c'));
        }
    }

    public function test_live_elamipretide_urls_are_not_redirected(): void
    {
        $this->withoutVite();

        $this->get('/encyclopedia/elamipretide')
            ->assertOk()
            ->assertHeaderMissing('Location');

        $this->get('/compare/elamipretide-vs-mots-c')
            ->assertOk()
            ->assertHeaderMissing('Location');
    }
}
