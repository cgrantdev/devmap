<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\CompareHubBlurb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareHubBlurbTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_hub_cards_do_not_render_outcome_claims_beside_a_price(): void
    {
        $brand = Brand::create([
            'name' => 'Listed Research',
            'slug' => 'listed-research',
            'is_active' => true,
        ]);

        $this->compound($brand, 'Cagrilintide', 'cagrilintide', 22.00, 'Cagrilintide is a long-acting amylin analog being developed by Novo Nordisk as a complement to GLP-1 agonists. The combination with semaglutide (CagriSema) showed up to 25% weight reduction.');
        $this->compound($brand, 'Retatrutide', 'retatrutide', 49.00, "Retatrutide is a triple agonist targeting GLP-1, GIP, and glucagon receptors simultaneously. Phase 2 data showed up to 24% weight reduction — the highest of any peptide compound studied.");
        $this->compound($brand, 'Tesofensine', 'tesofensine', 18.00, 'Tesofensine is a small-molecule inhibitor of serotonin, norepinephrine, and dopamine reuptake. It is not a peptide.');
        $this->compound($brand, 'BPC-157', 'bpc-157', 27.00, 'BPC-157 is a synthetic peptide derived from a protective protein found in the stomach. It has shown remarkable healing properties for tendons, ligaments, muscles, and the gastrointestinal tract.');

        $response = $this->get('/compare');
        $response->assertOk();

        $compounds = [];
        $response->assertInertia(function ($page) use (&$compounds) {
            $compounds = $page->toArray()['props']['compounds'];

            return $page->component('Frontend/Compare');
        });

        $bySlug = collect($compounds)->keyBy('slug');

        $this->assertSame('Cagrilintide', $bySlug['cagrilintide']['name']);
        $this->assertSame(
            'Cagrilintide is an amylin analog studied in obesity research. Research use only.',
            $bySlug['cagrilintide']['description']
        );
        $this->assertEquals(22.0, $bySlug['cagrilintide']['products'][0]['price']);

        $this->assertSame('GLP3-R', $bySlug['retatrutide']['name']);
        $this->assertSame(
            'Retatrutide is a triple agonist (GLP-1, GIP, and glucagon) studied in obesity research. Research use only.',
            $bySlug['retatrutide']['description']
        );
        $this->assertEquals(49.0, $bySlug['retatrutide']['products'][0]['price']);

        $this->assertSame(
            'Tesofensine is a small-molecule inhibitor of serotonin, norepinephrine, and dopamine reuptake. It is not a peptide.',
            $bySlug['tesofensine']['description']
        );
        $this->assertEquals(18.0, $bySlug['tesofensine']['products'][0]['price']);

        $this->assertSame(
            'BPC-157 is a synthetic pentadecapeptide derived from a gastric protein, studied in musculoskeletal and gastrointestinal research. Research use only.',
            $bySlug['bpc-157']['description']
        );

        foreach ($compounds as $compound) {
            $this->assertFalse(
                CompareHubBlurb::hasOutcomeClaim($compound['description'] ?? ''),
                $compound['slug']
            );
        }
    }

    private function compound(Brand $brand, string $name, string $slug, float $price, string $description): void
    {
        $category = ProductCategory::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => $name,
            'slug' => $slug,
            'description' => $description,
            'status' => 'published',
            'product_category_id' => $category->id,
        ]);
        Product::create([
            'name' => $name.' — 10mg',
            'slug' => $slug.'-10mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'product_type' => 'Peptide',
            'size_mg' => '10mg',
            'price' => $price,
            'status' => 'active',
            'hidden' => false,
        ]);
    }
}
