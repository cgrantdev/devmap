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
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PemvidutideEncyclopediaTest extends TestCase
{
    use RefreshDatabase;

    private const STATUS = 'Investigational. Not approved by the FDA or by any other regulator found in the sources checked. FDA Fast Track (MASH; AUD) and Breakthrough Therapy (MASH) designations have been announced by the sponsor. Phase 3 (PERFORMA) is enrolling.';

    private const HALF_LIFE = 'No numeric half-life is reported in the primary sources cited on this page. The sponsor attributes its once-weekly trial schedule to the lipidated EuPort domain. Not a dosing guide.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_parser_yields_the_qa_field_counts(): void
    {
        $body = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/pemvidutide/body.md'));
        $meta = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/pemvidutide/meta.md'));
        $parser = new EncyclopediaShellParser;
        $fields = (new \ReflectionMethod($parser, 'fields'))->invoke($parser, $body);
        $parsed = $parser->parse('pemvidutide', $body, $meta);

        $this->assertCount(24, $fields);
        $this->assertCount(17, $parsed['references']);
        $this->assertCount(6, $parsed['faqs']);
        $this->assertCount(2, $parsed['mechanism_subsections']);
        $this->assertCount(1, $parsed['preclinical_subsections']);
        $this->assertCount(6, $parsed['human_use_subsections']);
        $this->assertCount(3, $parsed['regulatory_subsections']);
        $this->assertCount(4, $parsed['potential_applications']);
        $this->assertCount(5, $parsed['areas_of_research']);
        $this->assertCount(6, $parsed['key_points']);
        $this->assertSame('2538014-94-5', $parsed['cas_registry_number']);
        $this->assertNull($parsed['molecular_formula']);
        $this->assertNull($parsed['molecular_weight']);
        $this->assertSame(181, mb_strlen((string) $parsed['half_life']));
        $this->assertSame(self::HALF_LIFE, $parsed['half_life']);
        $this->assertSame('Pemvidutide', $parsed['seo_h1']);
        $this->assertSame('GLP-1/glucagon dual receptor agonist · investigational, not approved', $parsed['peptide_full_name']);

        foreach ($parsed['references'] as $index => $reference) {
            $this->assertNotSame('', $reference['authors'], 'reference '.$index);
            $this->assertNotSame('', $reference['citation'], 'reference '.$index);
            $this->assertNotSame('', $reference['description'], 'reference '.$index);
            $this->assertNotEmpty($reference['links'], 'reference '.$index);
        }
    }

    public function test_featured_image_is_the_qa_png(): void
    {
        $path = public_path('images/encyclopedia/pemvidutide-featured.png');
        $this->assertFileExists($path);
        $this->assertSame(
            '156f0311e8471510eef983a22a028a13c3213880857df40a9b507a4f2fb13215',
            hash_file('sha256', $path)
        );
        $this->assertSame("\x89PNG", substr((string) file_get_contents($path), 0, 4));
        $size = getimagesize($path);
        $this->assertSame(1920, $size[0]);
        $this->assertSame(1080, $size[1]);
    }

    public function test_migrate_creates_the_entry_and_a_second_run_changes_nothing(): void
    {
        $category = ProductCategory::query()->where('slug', 'pemvidutide')->firstOrFail();
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame('Pemvidutide', $category->name);
        $this->assertTrue($category->is_active);
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->show_in_encyclopedia);
        $this->assertSame('2538014-94-5', $post->cas_registry_number);
        $this->assertNull($post->molecular_formula);
        $this->assertNull($post->molecular_weight);
        $this->assertSame(self::HALF_LIFE, $post->half_life);
        $this->assertCount(17, $post->references);
        $this->assertCount(6, $post->faqs);
        $this->assertSame(0, Product::query()->where('product_category_id', $category->id)->count());
        $this->assertStringNotContainsString('pemvidutide', json_encode(CompareController::FEATURED_VS_PAIRS));

        $categories = ProductCategory::count();
        $posts = EducationPost::count();
        $products = Product::count();
        $snapshot = $post->only(['overview', 'slug', 'cas_registry_number', 'seo_og_image', 'seo_h1']);
        $categorySnapshot = $category->only(['name', 'slug', 'description', 'research_area', 'is_active']);

        $this->travel(5)->seconds();
        $again = new EncyclopediaCategoryCreateSeeder;
        $again->run();

        $this->assertSame([], $again->report['created_categories']);
        $this->assertSame([], $again->report['filled']);
        $this->assertSame(['pemvidutide'], $again->report['skipped_already_filled']);
        $this->assertSame($categories, ProductCategory::count());
        $this->assertSame($posts, EducationPost::count());
        $this->assertSame($products, Product::count());
        $post->refresh();
        $category->refresh();
        $this->assertSame($snapshot, $post->only(['overview', 'slug', 'cas_registry_number', 'seo_og_image', 'seo_h1']));
        $this->assertSame($categorySnapshot, $category->only(['name', 'slug', 'description', 'research_area', 'is_active']));
    }

    public function test_seeder_creates_a_missing_category_once_and_copies_survodutide_research_area(): void
    {
        $this->forgetPemvidutide();
        $this->assertFalse(Schema::hasColumn('product_categories', 'type'));

        ProductCategory::create([
            'name' => 'Survodutide',
            'slug' => 'Survodutide',
            'is_active' => true,
            'research_area' => 'Metabolic / liver',
        ]);

        $productsBefore = Product::count();
        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['pemvidutide'], $seeder->report['created_categories']);
        $this->assertSame(['pemvidutide'], $seeder->report['created_posts']);
        $this->assertSame(['pemvidutide'], $seeder->report['filled']);
        $this->assertSame($productsBefore, Product::count());

        $category = ProductCategory::query()->where('slug', 'pemvidutide')->firstOrFail();
        $this->assertSame('Pemvidutide', $category->name);
        $this->assertTrue($category->is_active);
        $this->assertSame('Metabolic / liver', $category->research_area);
        $this->assertSame('What is Pemvidutide? GLP-1/Glucagon Agonist (MASH)', $category->meta_title);
        $this->assertStringStartsWith('Pemvidutide (development code ALT-801)', (string) $category->description);
        $this->assertNull($category->image_url);

        $post = $category->educationPost()->firstOrFail();
        $this->assertSame('pemvidutide', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->show_in_encyclopedia);
        $this->assertSame('Pemvidutide', $post->seo_h1);
        $this->assertSame('2538014-94-5', $post->cas_registry_number);
        $this->assertNull($post->molecular_formula);
        $this->assertNull($post->molecular_weight);
        $this->assertSame('/images/encyclopedia/pemvidutide-featured.png', $post->seo_og_image);
        $this->assertCount(17, $post->references);
        $this->assertCount(6, $post->faqs);

        $stamp = $post->updated_at?->toJSON();
        $this->travel(5)->seconds();
        $again = new EncyclopediaCategoryCreateSeeder;
        $again->run();
        $this->assertSame(['pemvidutide'], $again->report['skipped_already_filled']);
        $this->assertSame([], $again->report['created_categories']);
        $this->assertSame([], $again->report['filled']);
        $this->assertSame(1, ProductCategory::query()->whereRaw('LOWER(slug) = ?', ['pemvidutide'])->count());
        $this->assertSame(1, EducationPost::query()->where('product_category_id', $category->id)->count());
        $this->assertSame($stamp, $post->fresh()->updated_at?->toJSON());
        $this->assertSame($productsBefore, Product::count());
    }

    public function test_more_than_one_slug_match_is_skipped(): void
    {
        $this->forgetPemvidutide();
        ProductCategory::create(['name' => 'Pemvidutide', 'slug' => 'Pemvidutide', 'is_active' => true]);
        ProductCategory::create(['name' => 'Pemvidutide', 'slug' => 'PEMVIDUTIDE', 'is_active' => true]);

        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['pemvidutide'], $seeder->report['skipped_slug_collision']);
        $this->assertSame([], $seeder->report['created_categories']);
        $this->assertSame([], $seeder->report['filled']);
        $this->assertSame(2, ProductCategory::query()->whereRaw('LOWER(slug) = ?', ['pemvidutide'])->count());
        $this->assertSame(0, EducationPost::query()->whereRaw('LOWER(slug) = ?', ['pemvidutide'])->count());
    }

    public function test_alias_match_reuses_that_category_without_renaming_it(): void
    {
        $this->forgetPemvidutide();
        $holding = ProductCategory::create([
            'name' => 'Holding',
            'slug' => 'alt-holding',
            'description' => 'A real catalog description that should stay.',
            'is_active' => true,
        ]);
        CategoryAlias::create([
            'product_category_id' => $holding->id,
            'keyword' => 'ALT-801',
        ]);

        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['pemvidutide'], $seeder->report['reused_categories']);
        $this->assertSame(['pemvidutide'], $seeder->report['filled']);
        $this->assertNull(ProductCategory::query()->where('slug', 'pemvidutide')->first());
        $holding->refresh();
        $this->assertSame('Holding', $holding->name);
        $this->assertSame('alt-holding', $holding->slug);
        $this->assertSame('A real catalog description that should stay.', $holding->description);

        $post = EducationPost::query()->where('product_category_id', $holding->id)->firstOrFail();
        $this->assertSame('alt-holding', $post->slug);
        $this->assertSame('2538014-94-5', $post->cas_registry_number);
        $this->assertGreaterThanOrEqual(20, mb_strlen(trim(strip_tags((string) $post->overview))));

        $this->travel(5)->seconds();
        $again = new EncyclopediaCategoryCreateSeeder;
        $again->run();
        $this->assertSame(['pemvidutide'], $again->report['skipped_already_filled']);
        $this->assertSame('Holding', $holding->fresh()->name);
        $this->assertSame('alt-holding', $post->fresh()->slug);
    }

    public function test_alias_that_points_at_a_different_category_than_the_slug_is_skipped(): void
    {
        $existing = ProductCategory::query()->where('slug', 'pemvidutide')->firstOrFail();
        $overview = (string) $existing->educationPost()->firstOrFail()->overview;
        $other = ProductCategory::create([
            'name' => 'Other',
            'slug' => 'other-alt',
            'is_active' => true,
        ]);
        CategoryAlias::create([
            'product_category_id' => $other->id,
            'keyword' => 'alt-801',
        ]);

        $before = ProductCategory::count();
        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['pemvidutide'], $seeder->report['skipped_slug_collision']);
        $this->assertSame([], $seeder->report['filled']);
        $this->assertSame($before, ProductCategory::count());
        $this->assertSame($overview, $existing->educationPost()->firstOrFail()->overview);
        $this->assertNull(EducationPost::query()->where('product_category_id', $other->id)->first());
    }

    public function test_an_existing_category_is_not_renamed_or_refilled(): void
    {
        $this->forgetPemvidutide();
        $category = ProductCategory::create([
            'name' => 'PEMVIDUTIDE',
            'slug' => 'Pemvidutide',
            'description' => 'A real catalog description that should stay.',
            'is_active' => true,
            'research_area' => 'Leave this research area',
        ]);
        $overview = 'This overview is already long enough to count as filled content.';
        EducationPost::create([
            'title' => 'Existing',
            'slug' => 'Pemvidutide',
            'product_category_id' => $category->id,
            'status' => 'draft',
            'show_in_encyclopedia' => false,
            'overview' => $overview,
        ]);

        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['pemvidutide'], $seeder->report['skipped_already_filled']);
        $this->assertSame([], $seeder->report['filled']);
        $category->refresh();
        $this->assertSame('PEMVIDUTIDE', $category->name);
        $this->assertSame('Pemvidutide', $category->slug);
        $this->assertSame('A real catalog description that should stay.', $category->description);
        $this->assertSame('Leave this research area', $category->research_area);
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame($overview, $post->overview);
        $this->assertSame('draft', $post->status);
        $this->assertFalse($post->show_in_encyclopedia);
    }

    public function test_a_taken_education_slug_does_not_create_a_second_post(): void
    {
        $this->forgetPemvidutide();
        $other = ProductCategory::create([
            'name' => 'Other',
            'slug' => 'other-holding',
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => 'Held',
            'slug' => 'pemvidutide',
            'product_category_id' => $other->id,
            'status' => 'draft',
            'overview' => null,
        ]);

        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['pemvidutide'], $seeder->report['created_categories']);
        $this->assertSame(['pemvidutide'], $seeder->report['skipped_slug_taken']);
        $this->assertSame([], $seeder->report['filled']);
        $this->assertSame($other->id, EducationPost::query()->where('slug', 'pemvidutide')->firstOrFail()->product_category_id);
        $created = ProductCategory::query()->where('slug', 'pemvidutide')->firstOrFail();
        $this->assertNull(EducationPost::query()->where('product_category_id', $created->id)->first());

        $again = new EncyclopediaCategoryCreateSeeder;
        $again->run();
        $this->assertSame(['pemvidutide'], $again->report['skipped_slug_taken']);
        $this->assertSame([], $again->report['created_categories']);
        $this->assertSame(1, EducationPost::query()->whereRaw('LOWER(slug) = ?', ['pemvidutide'])->count());
    }

    public function test_encyclopedia_renders_and_empty_compare_is_noindex_and_out_of_the_sitemap(): void
    {
        $this->get('/encyclopedia/pemvidutide')
            ->assertOk()
            ->assertSee('2538014-94-5', false)
            ->assertSee('Pemvidutide', false)
            ->assertSee(self::STATUS, false)
            ->assertSee(self::HALF_LIFE, false)
            ->assertSee('/images/encyclopedia/pemvidutide-featured.png', false)
            ->assertSee('Is pemvidutide approved?', false)
            ->assertDontSee('/products?category=pemvidutide', false)
            ->assertDontSee('/compare/pemvidutide', false)
            ->assertDontSee('>Buy<', false)
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/EncyclopediaArticleDetail')
                ->where('seo.h1', 'Pemvidutide')
                ->where('slug', 'pemvidutide')
                ->where('molecularInfo.casNumber', '2538014-94-5')
                ->where('molecularInfo.formula', '')
                ->where('molecularInfo.molecularWeight', '')
                ->where('drugStatus', self::STATUS)
                ->where('halfLife', self::HALF_LIFE)
                ->where('vendorCount', 0)
                ->where('relatedPages', [])
                ->where('featuredImage', '/images/encyclopedia/pemvidutide-featured.png')
                ->where('routes', [])
            );

        $compare = $this->get('/compare/pemvidutide');
        $compare->assertOk();
        $compare->assertSee('<meta name="robots" content="noindex, follow" />', false);
        $compare->assertDontSee('>Buy<', false);
        $compare->assertDontSee('Shop Pemvidutide', false);
        $compare->assertInertia(fn ($page) => $page
            ->component('Frontend/CompareCompound')
            ->where('seo.robots', 'noindex, follow')
            ->where('compound.vendor_count', 0)
            ->where('compound.product_count', 0)
            ->where('compound.products', [])
            ->where('compound.faqs', [])
        );

        $vue = (string) file_get_contents(resource_path('js/Pages/Frontend/CompareCompound.vue'));
        $this->assertStringContainsString('v-if="compound.vendor_count > 0 && !compound.price_intro" class="sr-only">Buy', $vue);
        $this->assertStringContainsString('v-if="compound.products.length"', $vue);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('https://peptidemap.com/encyclopedia/pemvidutide', $xml);
        $this->assertStringNotContainsString('https://peptidemap.com/compare/pemvidutide', $xml);
    }

    public function test_compare_with_a_priced_listing_stays_indexable_and_in_the_sitemap(): void
    {
        $category = ProductCategory::query()->where('slug', 'pemvidutide')->firstOrFail();
        $brand = Brand::create([
            'name' => 'Example Research',
            'slug' => 'example-research',
            'is_active' => true,
        ]);
        Product::create([
            'name' => 'Pemvidutide 10mg',
            'slug' => 'pemvidutide-10mg',
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
        ]);

        $this->get('/compare/pemvidutide')
            ->assertOk()
            ->assertDontSee('noindex', false)
            ->assertInertia(fn ($page) => $page
                ->where('compound.vendor_count', 1)
                ->where('compound.product_count', 1)
                ->missing('seo.robots')
            );

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringContainsString('https://peptidemap.com/compare/pemvidutide', $xml);
    }

    private function forgetPemvidutide(): void
    {
        $ids = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', ['pemvidutide'])
            ->pluck('id');
        EducationPost::query()
            ->where(function ($query) use ($ids) {
                $query->whereIn('product_category_id', $ids->all())
                    ->orWhereRaw('LOWER(slug) = ?', ['pemvidutide']);
            })
            ->delete();
        if ($ids->isNotEmpty()) {
            ProductCategory::query()->whereIn('id', $ids)->delete();
        }
    }
}
