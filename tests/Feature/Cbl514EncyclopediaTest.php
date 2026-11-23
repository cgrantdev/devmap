<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\EncyclopediaController;
use App\Models\CategoryAlias;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\EncyclopediaShellParser;
use App\Support\FaqPage;
use Database\Seeders\EncyclopediaCategoryCreateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Cbl514EncyclopediaTest extends TestCase
{
    use RefreshDatabase;

    private const HALF_LIFE = 'Not established in the cited public sources for CBL-514 as a single registered substance.';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_migration_creates_one_inactive_category_and_post_and_second_run_changes_nothing(): void
    {
        $this->assertCblEntry();

        $others = $this->otherPosts();
        $category = ProductCategory::query()->where('slug', 'cbl-514')->firstOrFail();
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->forgetCbl514();

        $this->migrateUp();

        $this->assertCblEntry();
        $this->assertSame($others, $this->otherPosts());
        $this->assertSame(0, Product::query()->where('product_category_id', ProductCategory::query()->where('slug', 'cbl-514')->value('id'))->count());
        $this->assertSame(0, CategoryAlias::query()->count() - $this->aliasCountExcludingCbl());

        $category = ProductCategory::query()->where('slug', 'cbl-514')->firstOrFail();
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $categoryStamp = $category->updated_at?->toJSON();
        $postStamp = $post->updated_at?->toJSON();
        $postSnapshot = $post->getAttributes();
        $categorySnapshot = $category->getAttributes();
        $allOthers = $this->otherPosts();

        $this->travel(5)->seconds();
        $this->migrateUp();

        $this->assertSame($categoryStamp, $category->fresh()->updated_at?->toJSON());
        $this->assertSame($postStamp, $post->fresh()->updated_at?->toJSON());
        $this->assertSame($postSnapshot, $post->fresh()->getAttributes());
        $this->assertSame($categorySnapshot, $category->fresh()->getAttributes());
        $this->assertSame($allOthers, $this->otherPosts());
        $this->assertSame(1, ProductCategory::query()->whereRaw('LOWER(slug) = ?', ['cbl-514'])->count());
        $this->assertSame(1, EducationPost::query()->where('product_category_id', $category->id)->count());
        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_shell_faqs_match_json_ld_and_stored_fields_have_no_vendor_or_dose_strings(): void
    {
        $body = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/cbl-514/body.md'));
        $meta = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/cbl-514/meta.md'));
        $parsed = (new EncyclopediaShellParser)->parse('cbl-514', $body, $meta);
        $jsonFaqs = $this->jsonFaqs($meta);

        $this->assertCount(5, $parsed['faqs']);
        $this->assertCount(5, $jsonFaqs);
        $this->assertSame($jsonFaqs, $parsed['faqs']);
        $this->assertNull($parsed['amino_acid_sequence']);
        $this->assertNull($parsed['cas_registry_number']);
        $this->assertNull($parsed['molecular_formula']);
        $this->assertNull($parsed['molecular_weight']);
        $this->assertSame(self::HALF_LIFE, $parsed['half_life']);
        $this->assertSame('What is CBL-514?', $parsed['seo_h1']);
        $this->assertSame(48, mb_strlen((string) $parsed['seo_page_title']));
        $this->assertSame(148, mb_strlen((string) $parsed['seo_description']));
        $this->assertStringContainsString('Leave the amino_acid_sequence field EMPTY', $body);

        $category = ProductCategory::query()->where('slug', 'cbl-514')->firstOrFail();
        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame($jsonFaqs, $post->faqs);
        $this->assertSame('11', $post->read_time);
        $this->assertNull($post->seo_og_image);
        $this->assertNull($post->amino_acid_sequence);
        $this->assertNull($post->cas_registry_number);
        $this->assertSame(self::HALF_LIFE, $post->half_life);
        $this->assertStringContainsString(
            'It has no FDA or EU marketing approval in the regulator records checked on 23 November 2026, and its developer says it is not approved in any country.',
            (string) $post->overview
        );

        $schema = FaqPage::schema($post->faqs, url('/encyclopedia/cbl-514#faq'));
        $this->assertSame('FAQPage', $schema['@type']);
        $this->assertCount(5, $schema['mainEntity']);
        foreach ($post->faqs as $index => $faq) {
            $this->assertSame($faq['question'], $schema['mainEntity'][$index]['name']);
            $this->assertSame($faq['answer'], $schema['mainEntity'][$index]['acceptedAnswer']['text']);
        }

        $visible = (new \ReflectionMethod(EncyclopediaController::class, 'visibleFaqs'))
            ->invoke(new EncyclopediaController, $post, null, 'cbl-514');
        $this->assertSame($post->faqs, $visible);
        $this->assertSame('', (new \ReflectionMethod(EncyclopediaController::class, 'clinicalLabel'))
            ->invoke(new EncyclopediaController, $category, 'status'));
        $this->assertStringNotContainsString("'CBL-514'", (string) file_get_contents(app_path('Http/Controllers/Frontend/EncyclopediaController.php')));

        $haystack = $body."\n".$meta."\n".json_encode($post->only([
            'overview', 'description', 'background', 'conclusion', 'faqs', 'key_points',
            'seo_page_title', 'seo_description', 'seo_og_title', 'seo_og_description',
            'mechanism_subsections', 'regulatory_subsections', 'references', 'half_life',
            'human_use_intro', 'human_use_subsections',
        ]), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('Southern Aminos', $haystack);
        $this->assertStringNotContainsString('/product/', $haystack);
        $this->assertStringNotContainsString('7220', $haystack);
        $this->assertStringNotContainsString('7221', $haystack);
        $this->assertDoesNotMatchRegularExpression('/\bmg\b/i', $haystack);

        $seeder = (string) file_get_contents(database_path('seeders/EncyclopediaCategoryCreateSeeder.php'));
        $match = (string) file_get_contents(database_path('seeders/EncyclopediaCategoryMatch.php'));
        foreach ([$seeder, $match] as $source) {
            $this->assertStringContainsString("defined('STDERR') ? STDERR : null", $source);
            $this->assertStringContainsString("defined('STDOUT') ? STDOUT : null", $source);
            $this->assertDoesNotMatchRegularExpression('/fwrite\(STD(ERR|OUT)/', $source);
        }
        $this->assertStringContainsString('DB::transaction', $seeder);
        $this->assertStringContainsString('saveQuietly()', $seeder);
        $this->assertFileDoesNotExist(public_path('images/encyclopedia/cbl-514-featured.png'));
    }

    public function test_inactive_category_is_404_and_absent_from_the_index_and_sitemap(): void
    {
        $category = ProductCategory::query()->where('slug', 'cbl-514')->firstOrFail();
        $this->assertFalse($category->is_active);

        $this->get('/encyclopedia/cbl-514')->assertNotFound();
        $this->get('/encyclopedia/CBL-514')->assertNotFound();
        $this->get('/compare/cbl-514')->assertNotFound();

        $this->get('/encyclopedia')
            ->assertOk()
            ->assertDontSee('/encyclopedia/cbl-514', false)
            ->assertInertia(fn ($page) => $page->where('encyclopediaEntries', function ($entries) {
                return ! collect($entries)->pluck('slug')->contains('cbl-514');
            }));

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringNotContainsString('/encyclopedia/cbl-514', $xml);
        $this->assertStringNotContainsString('/compare/cbl-514', $xml);
    }

    public function test_assembled_page_faq_json_ld_matches_visible_faqs_and_status_stays_blank(): void
    {
        $category = ProductCategory::query()->where('slug', 'cbl-514')->firstOrFail();
        $category->is_active = true;
        $category->save();

        $this->get('/encyclopedia/cbl-514')
            ->assertOk()
            ->assertInertia(function ($page) {
                $props = $page->toArray()['props'];
                $this->assertCount(5, $props['faqs']);
                $faqSchema = collect($props['seo']['schema'])->firstWhere('@type', 'FAQPage');
                $this->assertIsArray($faqSchema);
                $this->assertCount(5, $faqSchema['mainEntity']);
                foreach ($props['faqs'] as $index => $faq) {
                    $this->assertSame($faq['question'], $faqSchema['mainEntity'][$index]['name']);
                    $this->assertSame($faq['answer'], $faqSchema['mainEntity'][$index]['acceptedAnswer']['text']);
                }
                $this->assertSame('', $props['drugStatus']);
                $this->assertSame('', $props['aminoAcidSequence']['sequence']);
                $this->assertSame(0, $props['aminoAcidSequence']['residueCount']);
                $this->assertNull($props['featuredImage']);
                $this->assertSame([], $props['products']);
                $this->assertSame('11', EducationPost::query()->where('slug', 'cbl-514')->firstOrFail()->read_time);
                $blob = json_encode($props, JSON_UNESCAPED_UNICODE);
                $this->assertStringNotContainsString('Southern Aminos', $blob);
                $this->assertStringNotContainsString('/product/', $blob);
                $this->assertDoesNotMatchRegularExpression('/\bmg\b/i', $blob);

                return true;
            });
    }

    public function test_a_slug_collision_is_skipped_and_writes_nothing(): void
    {
        $this->forgetCbl514();
        ProductCategory::create(['name' => 'CBL-514', 'slug' => 'cbl-514', 'is_active' => true]);
        ProductCategory::create(['name' => 'CBL-514', 'slug' => 'CBL-514', 'is_active' => false]);
        $others = $this->otherPosts();
        $posts = EducationPost::count();

        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $this->assertSame(['cbl-514'], $seeder->report['skipped_slug_collision']);
        $this->assertNotContains('cbl-514', $seeder->report['filled']);
        $this->assertNotContains('cbl-514', $seeder->report['created_categories']);
        $this->assertSame(2, ProductCategory::query()->whereRaw('LOWER(slug) = ?', ['cbl-514'])->count());
        $this->assertSame(0, EducationPost::query()->whereRaw('LOWER(slug) = ?', ['cbl-514'])->count());
        $this->assertSame($posts, EducationPost::count());
        $this->assertSame($others, $this->otherPosts());
    }

    public function test_read_time_backfills_once_when_the_overview_is_already_filled(): void
    {
        $post = EducationPost::query()->where('slug', 'cbl-514')->firstOrFail();
        $overview = $post->overview;
        $post->read_time = null;
        $post->saveQuietly();
        $others = $this->otherPosts();

        $this->travel(5)->seconds();
        $seeder = new EncyclopediaCategoryCreateSeeder;
        $seeder->run();

        $post->refresh();
        $this->assertSame('11', $post->read_time);
        $this->assertSame($overview, $post->overview);
        $this->assertContains('cbl-514', $seeder->report['skipped_already_filled']);
        $this->assertNotContains('cbl-514', $seeder->report['filled']);
        $this->assertSame($others, $this->otherPosts());

        $stamp = $post->updated_at?->toJSON();
        $this->travel(5)->seconds();
        $again = new EncyclopediaCategoryCreateSeeder;
        $again->run();
        $this->assertSame($stamp, $post->fresh()->updated_at?->toJSON());
        $this->assertSame('11', $post->fresh()->read_time);
        $this->assertSame($others, $this->otherPosts());
    }

    private function assertCblEntry(): void
    {
        $this->assertSame(1, ProductCategory::query()->whereRaw('LOWER(slug) = ?', ['cbl-514'])->count());
        $category = ProductCategory::query()->where('slug', 'cbl-514')->firstOrFail();
        $this->assertSame('CBL-514', $category->name);
        $this->assertFalse($category->is_active);
        $this->assertSame(0, $category->aliases()->count());
        $posts = EducationPost::query()->where('product_category_id', $category->id)->get();
        $this->assertCount(1, $posts);
        $post = $posts->first();
        $this->assertSame('cbl-514', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->show_in_encyclopedia);
        $this->assertSame('11', $post->read_time);
        $this->assertNull($post->seo_og_image);
        $this->assertNull($post->amino_acid_sequence);
        $this->assertNull($post->cas_registry_number);
        $this->assertNull($post->molecular_formula);
        $this->assertNull($post->molecular_weight);
        $this->assertCount(5, $post->faqs);
        $this->assertSame(0, Product::query()->where('product_category_id', $category->id)->count());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function otherPosts(): array
    {
        return EducationPost::query()
            ->whereRaw('LOWER(slug) != ?', ['cbl-514'])
            ->orderBy('id')
            ->get()
            ->map(fn (EducationPost $post) => $post->getAttributes())
            ->all();
    }

    private function forgetCbl514(): void
    {
        $ids = ProductCategory::query()->whereRaw('LOWER(slug) = ?', ['cbl-514'])->pluck('id');
        EducationPost::query()->where(function ($query) use ($ids) {
            $query->whereIn('product_category_id', $ids)
                ->orWhereRaw('LOWER(slug) = ?', ['cbl-514']);
        })->delete();
        ProductCategory::query()->whereIn('id', $ids)->delete();
    }

    private function migrateUp(): void
    {
        $migration = require database_path('migrations/2026_11_23_100000_create_cbl_514_encyclopedia_entry.php');
        $migration->up();
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function jsonFaqs(string $meta): array
    {
        $this->assertSame(1, preg_match('/```json\s*(\{.*\})\s*```/s', $meta, $match));
        $decoded = json_decode($match[1], true);
        $this->assertIsArray($decoded);
        $faqs = [];
        foreach ($decoded['mainEntity'] as $entity) {
            $faqs[] = [
                'question' => $entity['name'],
                'answer' => $entity['acceptedAnswer']['text'],
            ];
        }

        return $faqs;
    }

    private function aliasCountExcludingCbl(): int
    {
        $ids = ProductCategory::query()->whereRaw('LOWER(slug) = ?', ['cbl-514'])->pluck('id');

        return CategoryAlias::query()->whereNotIn('product_category_id', $ids)->count();
    }
}
