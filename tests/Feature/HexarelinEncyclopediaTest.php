<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\CompareController;
use App\Models\Brand;
use App\Models\CategoryAlias;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\EncyclopediaShellParser;
use Database\Seeders\EncyclopediaCategoryCreateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HexarelinEncyclopediaTest extends TestCase
{
    use RefreshDatabase;

    private const STATUS = 'Not approved. No FDA approval (Drugs@FDA) or EU marketing authorization (EMA medicines database) was found, and ClinicalTrials.gov lists no registered studies. Prohibited at all times in sport (WADA 2026 and 2027 Prohibited Lists, S2.2.4).';

    private const HALF_LIFE = 'No human plasma half-life for hexarelin was found in the primary sources cited here. Animal studies report an intravenous half-life of about 76 minutes in rats and a terminal half-life of about 120 minutes in dogs. Not a dosing guide.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_parser_yields_the_qa_field_counts(): void
    {
        $body = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/hexarelin/body.md'));
        $meta = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/hexarelin/meta.md'));
        $parser = new EncyclopediaShellParser;
        $fields = (new \ReflectionMethod($parser, 'fields'))->invoke($parser, $body);
        $parsed = $parser->parse('hexarelin', $body, $meta);

        $this->assertCount(24, $fields);
        $this->assertCount(41, $parsed['references']);
        $this->assertCount(6, $parsed['faqs']);
        $this->assertCount(4, $parsed['mechanism_subsections']);
        $this->assertCount(3, $parsed['preclinical_subsections']);
        $this->assertCount(5, $parsed['human_use_subsections']);
        $this->assertCount(3, $parsed['regulatory_subsections']);
        $this->assertCount(4, $parsed['potential_applications']);
        $this->assertCount(5, $parsed['areas_of_research']);
        $this->assertCount(6, $parsed['key_points']);
        $this->assertSame('140703-51-1', $parsed['cas_registry_number']);
        $this->assertSame('C47H58N12O6', $parsed['molecular_formula']);
        $this->assertSame('887.04 g/mol', $parsed['molecular_weight']);
        $this->assertNull($parsed['amino_acid_sequence']);
        $this->assertSame(234, mb_strlen((string) $parsed['half_life']));
        $this->assertSame(self::HALF_LIFE, $parsed['half_life']);
        $this->assertSame('Hexarelin', $parsed['seo_h1']);
        $this->assertSame('What is Hexarelin (Examorelin)? GHRP Research Overview', $parsed['seo_page_title']);
        $this->assertSame('Synthetic GH-releasing hexapeptide (INN examorelin) · not approved · WADA S2', $parsed['peptide_full_name']);
        $this->assertStringNotContainsString('/encyclopedia/ghrp-2', json_encode($parsed));
        $this->assertStringNotContainsString('/encyclopedia/ghrp-6', json_encode($parsed));
        $this->assertStringNotContainsString('/compare/hexarelin', json_encode($parsed));

        foreach ($parsed['references'] as $index => $reference) {
            $this->assertNotSame('', $reference['authors'], 'reference '.$index);
            $this->assertNotSame('', $reference['citation'], 'reference '.$index);
            $this->assertNotSame('', $reference['description'], 'reference '.$index);
            $this->assertNotEmpty($reference['links'], 'reference '.$index);
        }
    }

    public function test_featured_image_is_the_qa_png(): void
    {
        $path = public_path('images/encyclopedia/hexarelin-featured.png');
        $this->assertFileExists($path);
        $this->assertSame(
            'd96b003b8d9d8bf9e0942c75982b026423a4e18246b3fa2cd10419a83a627fbe',
            hash_file('sha256', $path)
        );
        $this->assertSame("\x89PNG", substr((string) file_get_contents($path), 0, 4));
        $size = getimagesize($path);
        $this->assertSame(1920, $size[0]);
        $this->assertSame(1080, $size[1]);
    }

    public function test_migrate_creates_an_inactive_category_and_a_second_run_changes_nothing(): void
    {
        $category = ProductCategory::query()->where('slug', 'hexarelin')->firstOrFail();
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame('Hexarelin', $category->name);
        $this->assertFalse($category->is_active);
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->show_in_encyclopedia);
        $this->assertSame('140703-51-1', $post->cas_registry_number);
        $this->assertSame('C47H58N12O6', $post->molecular_formula);
        $this->assertSame('887.04 g/mol', $post->molecular_weight);
        $this->assertSame(self::HALF_LIFE, $post->half_life);
        $this->assertSame('/images/encyclopedia/hexarelin-featured.png', $post->seo_og_image);
        $this->assertCount(41, $post->references);
        $this->assertCount(6, $post->faqs);
        $this->assertSame(0, Product::query()->where('product_category_id', $category->id)->count());
        $this->assertNotContains('hexarelin', CompareController::FEATURED_COMPOUND_NAMES);
        $this->assertStringNotContainsString('hexarelin', json_encode(CompareController::FEATURED_VS_PAIRS));

        $categories = ProductCategory::count();
        $posts = EducationPost::count();
        $products = Product::count();
        $brands = Brand::count();
        $snapshot = $post->only(['overview', 'slug', 'cas_registry_number', 'seo_og_image', 'seo_h1', 'status', 'half_life']);
        $categorySnapshot = $category->only(['name', 'slug', 'description', 'research_area', 'is_active']);
        $categoryUpdated = $category->updated_at?->toJSON();
        $postUpdated = $post->updated_at?->toJSON();

        $this->travel(5)->seconds();
        $again = new EncyclopediaCategoryCreateSeeder;
        $again->run();

        $this->assertSame([], $again->report['created_categories']);
        $this->assertSame([], $again->report['filled']);
        $this->assertSame(['pemvidutide', 'hexarelin'], $again->report['skipped_already_filled']);
        $this->assertSame($categories, ProductCategory::count());
        $this->assertSame($posts, EducationPost::count());
        $this->assertSame($products, Product::count());
        $this->assertSame($brands, Brand::count());
        $post->refresh();
        $category->refresh();
        $this->assertSame($snapshot, $post->only(['overview', 'slug', 'cas_registry_number', 'seo_og_image', 'seo_h1', 'status', 'half_life']));
        $this->assertSame($categorySnapshot, $category->only(['name', 'slug', 'description', 'research_area', 'is_active']));
        $this->assertFalse($category->is_active);
        $this->assertSame($categoryUpdated, $category->updated_at?->toJSON());
        $this->assertSame($postUpdated, $post->updated_at?->toJSON());
    }

    public function test_an_existing_inactive_category_stays_inactive_and_is_not_rewritten(): void
    {
        $this->forgetHexarelin();
        $category = ProductCategory::create([
            'name' => 'Hexarelin',
            'slug' => 'hexarelin',
            'description' => 'Catalog blurb that must stay.',
            'is_active' => false,
            'research_area' => 'Leave this research area',
        ]);
        $before = $category->only(['name', 'slug', 'description', 'is_active', 'research_area', 'meta_title', 'meta_description']);
        $updated = $category->updated_at?->toJSON();
        $productsBefore = Product::count();

        $this->travel(5)->seconds();
        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['pemvidutide', 'hexarelin'], $seeder->report['reused_categories']);
        $this->assertSame(['hexarelin'], $seeder->report['filled']);
        $this->assertSame(['hexarelin'], $seeder->report['created_posts']);
        $this->assertSame([], $seeder->report['created_categories']);
        $category->refresh();
        $this->assertSame($before, $category->only(['name', 'slug', 'description', 'is_active', 'research_area', 'meta_title', 'meta_description']));
        $this->assertFalse($category->is_active);
        $this->assertSame($updated, $category->updated_at?->toJSON());
        $this->assertSame($productsBefore, Product::count());

        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame('hexarelin', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertSame('140703-51-1', $post->cas_registry_number);
        $this->assertSame(self::HALF_LIFE, $post->half_life);

        $stamp = $post->updated_at?->toJSON();
        $this->travel(5)->seconds();
        $again = new EncyclopediaCategoryCreateSeeder;
        $again->run();
        $this->assertSame(['pemvidutide', 'hexarelin'], $again->report['skipped_already_filled']);
        $this->assertSame([], $again->report['filled']);
        $this->assertSame($stamp, $post->fresh()->updated_at?->toJSON());
        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame('Catalog blurb that must stay.', $category->fresh()->description);
    }

    public function test_an_existing_active_category_stays_active(): void
    {
        $this->forgetHexarelin();
        $category = ProductCategory::create([
            'name' => 'HEXARELIN',
            'slug' => 'Hexarelin',
            'description' => 'Already active catalog text.',
            'is_active' => true,
        ]);

        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $category->refresh();
        $this->assertTrue($category->is_active);
        $this->assertSame('HEXARELIN', $category->name);
        $this->assertSame('Hexarelin', $category->slug);
        $this->assertSame('Already active catalog text.', $category->description);
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame('Hexarelin', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertSame(0, Product::query()->where('product_category_id', $category->id)->count());
    }

    public function test_examorelin_alias_reuses_that_category_without_activating_it(): void
    {
        $this->forgetHexarelin();
        $holding = ProductCategory::create([
            'name' => 'Examorelin stock',
            'slug' => 'examorelin-stock',
            'description' => 'Alias holder stays.',
            'is_active' => false,
        ]);
        CategoryAlias::create([
            'product_category_id' => $holding->id,
            'keyword' => 'Examorelin',
        ]);

        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['pemvidutide', 'hexarelin'], $seeder->report['reused_categories']);
        $this->assertSame(['hexarelin'], $seeder->report['filled']);
        $this->assertNull(ProductCategory::query()->where('slug', 'hexarelin')->first());
        $holding->refresh();
        $this->assertFalse($holding->is_active);
        $this->assertSame('Examorelin stock', $holding->name);
        $this->assertSame('examorelin-stock', $holding->slug);
        $this->assertSame('Alias holder stays.', $holding->description);

        $post = EducationPost::query()->where('product_category_id', $holding->id)->firstOrFail();
        $this->assertSame('examorelin-stock', $post->slug);
        $this->assertSame('140703-51-1', $post->cas_registry_number);
    }

    public function test_alias_that_points_at_a_different_category_than_the_slug_is_skipped(): void
    {
        $existing = ProductCategory::query()->where('slug', 'hexarelin')->firstOrFail();
        $this->assertFalse($existing->is_active);
        $overview = (string) $existing->educationPost()->firstOrFail()->overview;
        $other = ProductCategory::create([
            'name' => 'Other examorelin',
            'slug' => 'other-examorelin',
            'is_active' => true,
        ]);
        CategoryAlias::create([
            'product_category_id' => $other->id,
            'keyword' => 'examorelin',
        ]);

        $before = ProductCategory::count();
        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['hexarelin'], $seeder->report['skipped_slug_collision']);
        $this->assertSame([], $seeder->report['filled']);
        $this->assertSame($before, ProductCategory::count());
        $existing->refresh();
        $this->assertFalse($existing->is_active);
        $this->assertSame($overview, $existing->educationPost()->firstOrFail()->overview);
        $this->assertTrue($other->fresh()->is_active);
        $this->assertNull(EducationPost::query()->where('product_category_id', $other->id)->first());
    }

    public function test_inactive_category_stays_404_and_out_of_the_sitemap(): void
    {
        $category = ProductCategory::query()->where('slug', 'hexarelin')->firstOrFail();
        $this->assertFalse($category->is_active);
        $post = $category->educationPost()->firstOrFail();
        $this->assertSame('published', $post->status);
        $this->assertGreaterThanOrEqual(20, mb_strlen(trim(strip_tags((string) $post->overview))));

        $this->get('/encyclopedia/hexarelin')->assertNotFound();
        $this->get('/compare/hexarelin')->assertNotFound();

        $this->get('/encyclopedia')
            ->assertOk()
            ->assertDontSee('/encyclopedia/hexarelin', false);
        $this->get('/compare')
            ->assertOk()
            ->assertDontSee('/compare/hexarelin', false);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringNotContainsString('https://peptidemap.com/encyclopedia/hexarelin', $xml);
        $this->assertStringNotContainsString('https://peptidemap.com/compare/hexarelin', $xml);
        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/pemvidutide', $xml);
    }

    public function test_activating_the_category_renders_the_page_and_priced_listings(): void
    {
        $category = ProductCategory::query()->where('slug', 'hexarelin')->firstOrFail();
        $category->is_active = true;
        $category->save();

        $this->get('/encyclopedia/hexarelin')
            ->assertOk()
            ->assertSee('140703-51-1', false)
            ->assertSee('Hexarelin', false)
            ->assertSee(self::STATUS, false)
            ->assertSee(self::HALF_LIFE, false)
            ->assertSee('/images/encyclopedia/hexarelin-featured.png', false)
            ->assertSee('Is hexarelin approved?', false)
            ->assertDontSee('/encyclopedia/ghrp-2', false)
            ->assertDontSee('/encyclopedia/ghrp-6', false)
            ->assertDontSee('/compare/hexarelin', false)
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/EncyclopediaArticleDetail')
                ->where('seo.h1', 'Hexarelin')
                ->where('slug', 'hexarelin')
                ->where('seo.canonical', url('/encyclopedia/hexarelin'))
                ->where('molecularInfo.casNumber', '140703-51-1')
                ->where('molecularInfo.formula', 'C47H58N12O6')
                ->where('molecularInfo.molecularWeight', '887.04 g/mol')
                ->where('drugStatus', self::STATUS)
                ->where('halfLife', self::HALF_LIFE)
                ->where('vendorCount', 0)
                ->where('relatedPages', [])
                ->where('products', [])
                ->where('featuredImage', '/images/encyclopedia/hexarelin-featured.png')
                ->where('routes', [])
            );

        $this->get('/compare/hexarelin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/CompareCompound')
                ->where('compound.vendor_count', 0)
                ->where('compound.product_count', 0)
                ->where('compound.products', [])
            );

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/hexarelin', $xml);
        $this->assertStringNotContainsString('https://peptidemap.com/compare/hexarelin', $xml);

        $brand = Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Hexarelin 5mg',
            'slug' => 'hexarelin-5mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
        ]);

        Cache::forget('sitemap.xml.v4');

        $this->get('/encyclopedia/hexarelin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('vendorCount', 1)
                ->where('relatedPages.compare.anchor', 'Compare Hexarelin prices across vendors')
                ->where('relatedPages.compare.url', url('/compare/hexarelin'))
                ->where('relatedPages.shop.anchor', 'Shop Hexarelin — all available products')
                ->where('relatedPages.shop.url', url('/products?category=hexarelin'))
                ->where('products.0.name', 'Hexarelin 5mg')
            );

        $this->get('/compare/hexarelin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('compound.vendor_count', 1)
                ->where('compound.product_count', 1)
                ->where('compound.products.0.name', 'Hexarelin 5mg')
            );

        $listed = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/hexarelin', $listed);
        $this->assertStringContainsString('https://peptidemap.com/compare/hexarelin', $listed);
    }

    private function forgetHexarelin(): void
    {
        $ids = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', ['hexarelin'])
            ->pluck('id');
        EducationPost::query()
            ->where(function ($query) use ($ids) {
                $query->whereIn('product_category_id', $ids->all())
                    ->orWhereRaw('LOWER(slug) = ?', ['hexarelin']);
            })
            ->delete();
        if ($ids->isNotEmpty()) {
            ProductCategory::query()->whereIn('id', $ids)->delete();
        }
    }
}
