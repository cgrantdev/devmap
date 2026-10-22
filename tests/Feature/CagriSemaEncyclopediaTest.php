<?php

namespace Tests\Feature;

use App\Models\CategoryAlias;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\EncyclopediaShellParser;
use Database\Seeders\EncyclopediaCategoryCreateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CagriSemaEncyclopediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_parser_yields_the_shell_counts_and_the_image_is_the_qa_png(): void
    {
        $body = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/cagrisema/body.md'));
        $meta = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/cagrisema/meta.md'));
        $parser = new EncyclopediaShellParser;
        $fields = (new \ReflectionMethod($parser, 'fields'))->invoke($parser, $body);
        $parsed = $parser->parse('cagrisema', $body, $meta);

        $this->assertCount(24, $fields);
        $this->assertCount(17, $parsed['references']);
        $this->assertCount(6, $parsed['faqs']);
        $this->assertCount(3, $parsed['mechanism_subsections']);
        $this->assertCount(1, $parsed['preclinical_subsections']);
        $this->assertCount(3, $parsed['human_use_subsections']);
        $this->assertCount(3, $parsed['regulatory_subsections']);
        $this->assertCount(3, $parsed['potential_applications']);
        $this->assertCount(4, $parsed['areas_of_research']);
        $this->assertCount(6, $parsed['key_points']);
        $this->assertNull($parsed['molecular_formula']);
        $this->assertNull($parsed['molecular_weight']);
        $this->assertNull($parsed['cas_registry_number']);
        $this->assertNotNull($parsed['half_life']);
        $this->assertLessThanOrEqual(255, mb_strlen((string) $parsed['half_life']));
        $this->assertSame('CagriSema', $parsed['seo_h1']);
        $this->assertSame(55, mb_strlen((string) $parsed['seo_page_title']));
        $this->assertSame(152, mb_strlen((string) $parsed['seo_description']));
        foreach ($parsed['faqs'] as $faq) {
            $this->assertStringContainsString($faq['question'], $meta);
        }

        $path = public_path('images/encyclopedia/cagrisema-featured.png');
        $this->assertFileExists($path);
        $this->assertSame(
            '92ac47cd1cffd50c17739c51dc0b11f533ebc374110e96eb35eb07ec857bf05e',
            hash_file('sha256', $path)
        );
        $this->assertSame(145061, filesize($path));
        $size = getimagesize($path);
        $this->assertSame(1920, $size[0]);
        $this->assertSame(1080, $size[1]);
    }

    public function test_seeder_creates_an_inactive_category_without_aliases_and_leaves_592_unchanged(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_19_140000_create_cagrisema_encyclopedia_entry.php'));
        $this->assertStringContainsString('EncyclopediaCategoryCreateSeeder', $migration);

        $this->forgetCagrisema();
        $blend = new ProductCategory;
        $blend->forceFill([
            'id' => 592,
            'name' => 'Semaglutide / Cagrilintide Blend',
            'slug' => 'semaglutide-cagrilintide-blend',
            'description' => 'KEEP-592',
            'is_active' => true,
        ]);
        $blend->save();
        $blendBefore = $blend->fresh()->getAttributes();
        $aliasesBefore = CategoryAlias::query()->count();

        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertContains('cagrisema', $seeder->report['created_categories']);
        $this->assertContains('cagrisema', $seeder->report['filled']);
        $this->assertSame([], $seeder->report['skipped_slug_collision']);
        $category = ProductCategory::query()->where('slug', 'cagrisema')->firstOrFail();
        $this->assertSame('CagriSema', $category->name);
        $this->assertFalse($category->is_active);
        $this->assertSame(0, $category->aliases()->count());
        $this->assertSame($aliasesBefore, CategoryAlias::query()->count());
        $this->assertSame(0, Product::query()->where('product_category_id', $category->id)->count());
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame('cagrisema', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->show_in_encyclopedia);
        $this->assertNull($post->molecular_formula);
        $this->assertNull($post->molecular_weight);
        $this->assertNull($post->cas_registry_number);
        $this->assertSame('/images/encyclopedia/cagrisema-featured.png', $post->seo_og_image);
        $this->assertCount(6, $post->faqs);
        $this->assertCount(17, $post->references);
        $this->assertSame($blendBefore, $blend->fresh()->getAttributes());

        $categoryUpdated = $category->updated_at?->toJSON();
        $postUpdated = $post->updated_at?->toJSON();
        $this->travel(5)->seconds();
        $again = new EncyclopediaCategoryCreateSeeder;
        $again->run();
        $this->assertContains('cagrisema', $again->report['skipped_already_filled']);
        $this->assertNotContains('cagrisema', $again->report['filled']);
        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame($categoryUpdated, $category->fresh()->updated_at?->toJSON());
        $this->assertSame($postUpdated, $post->fresh()->updated_at?->toJSON());
        $this->assertSame($blendBefore, $blend->fresh()->getAttributes());
    }

    public function test_an_existing_cagrisema_row_is_reused_without_writing_is_active(): void
    {
        $this->forgetCagrisema();
        $category = ProductCategory::create([
            'name' => 'Old CagriSema',
            'slug' => 'cagrisema',
            'description' => 'Catalog blurb that must stay.',
            'is_active' => true,
        ]);
        $before = $category->only(['name', 'slug', 'description', 'is_active', 'research_area', 'meta_title', 'meta_description']);
        $updated = $category->updated_at?->toJSON();

        $this->travel(5)->seconds();
        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertContains('cagrisema', $seeder->report['reused_categories']);
        $this->assertContains('cagrisema', $seeder->report['filled']);
        $this->assertNotContains('cagrisema', $seeder->report['created_categories']);
        $category->refresh();
        $this->assertSame($before, $category->only(['name', 'slug', 'description', 'is_active', 'research_area', 'meta_title', 'meta_description']));
        $this->assertTrue($category->is_active);
        $this->assertSame($updated, $category->updated_at?->toJSON());
        $this->assertSame(0, $category->aliases()->count());
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame('cagrisema', $post->slug);
        $this->assertNull($post->molecular_formula);
        $this->assertSame('/images/encyclopedia/cagrisema-featured.png', $post->seo_og_image);
    }

    public function test_inactive_cagrisema_is_not_listed_and_bacteriostatic_water_is_excluded_by_name(): void
    {
        $category = ProductCategory::query()->where('slug', 'cagrisema')->firstOrFail();
        $this->assertFalse($category->is_active);
        $post = $category->educationPost()->firstOrFail();
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->show_in_encyclopedia);

        $water = ProductCategory::create([
            'name' => 'Bacteriostatic Water',
            'slug' => 'bacteriostatic-water',
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => 'Bacteriostatic Water',
            'slug' => 'bacteriostatic-water',
            'product_category_id' => $water->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'A published diluent page that must stay off the encyclopedia index.',
        ]);
        $hidden = ProductCategory::create([
            'name' => 'Hidden Peptide',
            'slug' => 'hidden-peptide',
            'is_active' => false,
        ]);
        EducationPost::create([
            'title' => 'Hidden Peptide',
            'slug' => 'hidden-peptide',
            'product_category_id' => $hidden->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'Published under an inactive category and excluded by the is_active filter.',
        ]);

        $this->get('/encyclopedia/cagrisema')->assertNotFound();
        $this->get('/encyclopedia')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('encyclopediaEntries', function ($entries) {
                $slugs = collect($entries)->pluck('slug');

                return $slugs->contains('pemvidutide')
                    && ! $slugs->contains('cagrisema')
                    && ! $slugs->contains('bacteriostatic-water')
                    && ! $slugs->contains('hidden-peptide');
            }));
    }

    public function test_ghk_cu_routes_are_topical_and_residue_count_ignores_prose(): void
    {
        ProductCategory::create([
            'name' => 'GHK-Cu',
            'slug' => 'GHK-Cu',
            'is_active' => true,
        ]);
        $prose = ProductCategory::create([
            'name' => 'Cagrilintide',
            'slug' => 'Cagrilintide',
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => 'Cagrilintide',
            'slug' => 'Cagrilintide',
            'product_category_id' => $prose->id,
            'status' => 'published',
            'amino_acid_sequence' => 'Modified 37-amino-acid amylin analog with fatty acid acylation',
        ]);
        $sequence = ProductCategory::create([
            'name' => 'GHK Sequence',
            'slug' => 'ghk-sequence',
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => 'GHK Sequence',
            'slug' => 'ghk-sequence',
            'product_category_id' => $sequence->id,
            'status' => 'published',
            'amino_acid_sequence' => 'Gly-His-Lys',
        ]);

        $this->get('/encyclopedia/GHK-Cu')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('routes', ['Topical']));
        $this->get('/encyclopedia/Cagrilintide')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('aminoAcidSequence.residueCount', 0)
                ->where('drugStatus', 'Investigational; not approved (as of 22 Oct 2026)')
                ->where('routes', [])
            );
        $this->get('/encyclopedia/ghk-sequence')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('aminoAcidSequence.residueCount', 3));

        $cagrisema = ProductCategory::query()->where('slug', 'cagrisema')->firstOrFail();
        $cagrisema->is_active = true;
        $cagrisema->save();
        $this->get('/encyclopedia/cagrisema')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('drugStatus', 'Investigational; not approved (as of 22 Oct 2026)')
                ->where('routes', [])
                ->where('featuredImage', '/images/encyclopedia/cagrisema-featured.png')
                ->where('molecularInfo.formula', '')
                ->where('molecularInfo.casNumber', '')
            );
    }

    private function forgetCagrisema(): void
    {
        $ids = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', ['cagrisema'])
            ->pluck('id');
        EducationPost::query()
            ->where(function ($query) use ($ids) {
                $query->whereIn('product_category_id', $ids->all())
                    ->orWhereRaw('LOWER(slug) = ?', ['cagrisema']);
            })
            ->delete();
        if ($ids->isNotEmpty()) {
            ProductCategory::query()->whereIn('id', $ids)->delete();
        }
    }
}
