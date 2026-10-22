<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaBpcCleanupAddendumSeeder as Addendum;
use Database\Seeders\EncyclopediaFieldUpdatesOctober22Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BpcCleanupAddendumOctober22Test extends TestCase
{
    use RefreshDatabase;

    private const BLOG = 'blog:safe-handling-of-peptides-in-laboratory-settings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_payload_hash_matches_migration_and_seeder(): void
    {
        $raw = (string) file_get_contents(database_path(Addendum::PAYLOAD));
        $this->assertSame(Addendum::SOURCE_PAYLOAD_SHA256, hash('sha256', $raw));
        $migration = (string) file_get_contents(database_path('migrations/2026_10_22_170000_bpc_157_cleanup_addendum.php'));
        $this->assertStringContainsString('EncyclopediaBpcCleanupAddendumSeeder', $migration);
        $this->assertStringContainsString(Addendum::SOURCE_PAYLOAD_SHA256, $migration);
    }

    public function test_live_fixture_writes_every_column_to_its_target(): void
    {
        [$bpc, $tb, $blog] = $this->seedLive();
        $seeder = new Addendum;
        $seeder->run();

        $this->assertSame([], $seeder->warnings);
        foreach ($this->cols('bpc-157') as $column => $spec) {
            $this->assertSame(Addendum::UPDATED, $seeder->columns['bpc-157'][$column], $column);
            $this->assertSame($this->target($spec, 0), $bpc->fresh()->{$column}, $column);
        }
        foreach ($this->cols('tb-500') as $column => $spec) {
            $this->assertSame(Addendum::UPDATED, $seeder->columns['tb-500'][$column], $column);
            $this->assertSame($this->target($spec, 0), $tb->fresh()->{$column}, $column);
        }
        $this->assertCount(8, $this->cols('bpc-157'));
        $this->assertCount(4, $this->cols('tb-500'));
        $this->assertSame($this->cols(self::BLOG)['description']['new_value'], $blog->fresh()->description);
    }

    public function test_post_65_fixture_then_addendum_keeps_65_columns(): void
    {
        [$bpc] = $this->seedLive(with65: true);
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        $after65 = $bpc->fresh();
        $kept = ['faqs' => $after65->faqs, 'regulatory_important_note' => $after65->regulatory_important_note, 'key_points' => $after65->key_points];

        $seeder = new Addendum;
        $seeder->run();
        $post = $bpc->fresh();

        $this->assertSame($this->cols('bpc-157')['regulatory_subsections']['target_values'][2], $post->regulatory_subsections);
        $this->assertSame($this->cols('bpc-157')['references']['target_values'][1], $post->references);
        $this->assertCount(18, $post->references);
        foreach ($kept as $column => $value) {
            $this->assertSame($value, $post->{$column}, $column);
        }
    }

    public function test_addendum_then_manual_120000_leaves_bpc_unchanged(): void
    {
        [$bpc] = $this->seedLive(with65: true);
        (new Addendum)->run();
        $before = $bpc->fresh()->only(['regulatory_subsections', 'references', 'human_use_subsections', 'preclinical_subsections']);

        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();

        $this->assertSame($before, $bpc->fresh()->only(array_keys($before)));
    }

    public function test_second_run_is_idempotent(): void
    {
        [$bpc, $tb, $blog] = $this->seedLive();
        (new Addendum)->run();
        $snap = [$bpc->fresh()->getAttributes(), $tb->fresh()->getAttributes(), $blog->fresh()->getAttributes()];

        $seeder = new Addendum;
        $seeder->run();

        $this->assertSame($snap, [$bpc->fresh()->getAttributes(), $tb->fresh()->getAttributes(), $blog->fresh()->getAttributes()]);
        foreach (['bpc-157', 'tb-500', self::BLOG] as $key) {
            $this->assertSame(Addendum::CURRENT, $seeder->report[$key], $key);
        }
    }

    public function test_mismatch_skips_only_that_column(): void
    {
        $reg = $this->cols('bpc-157')['regulatory_subsections']['old_values'][0];
        $reg[3]['entries'][0]['value'] .= ' Edited.';
        $refs = $this->cols('bpc-157')['references']['old_values'][0];
        $refs[] = ['title' => 'Extra', 'links' => []];
        [$bpc] = $this->seedLive(['regulatory_subsections' => $reg, 'references' => $refs]);

        $seeder = new Addendum;
        $seeder->run();

        $this->assertSame($reg, $bpc->fresh()->regulatory_subsections);
        $this->assertSame($refs, $bpc->fresh()->references);
        $this->assertSame(Addendum::MISMATCH, $seeder->columns['bpc-157']['regulatory_subsections']);
        $this->assertSame(Addendum::MISMATCH, $seeder->columns['bpc-157']['references']);
        $this->assertSame(Addendum::UPDATED, $seeder->columns['bpc-157']['human_use_subsections']);
        $this->assertSame('No human PK data', $bpc->fresh()->half_life);
        $this->assertStringContainsString('regulatory_subsections: old mismatch', implode("\n", $seeder->warnings));
    }

    public function test_m3_and_m4_subsection_guards(): void
    {
        $pre = $this->cols('bpc-157')['preclinical_subsections']['old_values'][0];
        $pre[0]['findings'][1]['content'] = 'Edited tendon text.';
        $reg = $this->cols('bpc-157')['regulatory_subsections']['old_values'][0];
        $reg[4]['entries'][0]['value'] = 'Edited theoretical concerns.';
        [$bpc] = $this->seedLive(['preclinical_subsections' => $pre, 'regulatory_subsections' => $reg]);

        $seeder = new Addendum;
        $seeder->run();

        $this->assertSame($pre, $bpc->fresh()->preclinical_subsections);
        $this->assertSame($reg, $bpc->fresh()->regulatory_subsections);
        $this->assertSame(Addendum::MISMATCH, $seeder->columns['bpc-157']['preclinical_subsections']);
        $this->assertSame(Addendum::MISMATCH, $seeder->columns['bpc-157']['regulatory_subsections']);
    }

    public function test_normalization_treats_crlf_nbsp_and_trailing_spaces_as_equal(): void
    {
        $bg = $this->cols('bpc-157')['background']['old_values'][0];
        $messy = str_replace("\n", "  \r\n", $bg)."\u{00A0} ";
        [$bpc] = $this->seedLive(['background' => $messy, 'half_life' => " ~4 hours\u{00A0}"]);

        $seeder = new Addendum;
        $seeder->run();

        $this->assertSame(Addendum::UPDATED, $seeder->columns['bpc-157']['background']);
        $this->assertSame('No human PK data', $bpc->fresh()->half_life);
    }

    public function test_lookup_collision_writes_nothing_for_that_slug(): void
    {
        [$bpc, $tb] = $this->seedLive();
        $other = ProductCategory::create(['name' => 'BPC dup', 'slug' => 'bpc-157 ', 'is_active' => true]);
        $before = $bpc->fresh()->getAttributes();

        $seeder = new Addendum;
        $seeder->run();

        $this->assertSame($before, $bpc->fresh()->getAttributes());
        $this->assertSame(Addendum::NO_MATCH, $seeder->report['bpc-157']);
        $this->assertSame(Addendum::UPDATED, $seeder->report['tb-500']);
        $this->assertNotNull($other->id);
    }

    public function test_legacy_unlinked_row_is_never_written(): void
    {
        $this->seedLive();
        $legacy = EducationPost::create([
            'title' => 'BPC-157', 'slug' => 'bpc-157', 'product_category_id' => null, 'status' => 'published',
            'description' => $this->cols('bpc-157')['description']['old_values'][0],
            'half_life' => '~4 hours',
        ])->fresh();
        $before = $legacy->getAttributes();

        (new Addendum)->run();

        $this->assertSame($before, $legacy->fresh()->getAttributes());
    }

    public function test_description_seeder_string_replaced_and_empty_skipped(): void
    {
        [$bpc] = $this->seedLive(['description' => $this->cols('bpc-157')['description']['old_values'][0]]);
        (new Addendum)->run();
        $this->assertSame($this->cols('bpc-157')['description']['new_value'], $bpc->fresh()->description);

        $this->refreshDatabaseForNext();
        [$bpc2, , , $cat] = $this->seedLive(['description' => '']);
        $catBefore = $cat->fresh()->getAttributes();
        $seeder = new Addendum;
        $seeder->run();
        $this->assertSame('', (string) $bpc2->fresh()->description);
        $this->assertSame(Addendum::EMPTY, $seeder->columns['bpc-157']['description']);
        $this->assertSame($catBefore, $cat->fresh()->getAttributes());
    }

    public function test_blog_guards(): void
    {
        [, , $blog] = $this->seedLive();
        $other = Blog::create(['title' => 'Other', 'slug' => 'other', 'description' => 'KEEP', 'status' => 'published']);
        (new Addendum)->run();
        $this->assertSame($this->cols(self::BLOG)['description']['new_value'], $blog->fresh()->description);
        $this->assertSame('KEEP', $other->fresh()->description);

        $blog->forceFill(['description' => 'Different text'])->saveQuietly();
        $seeder = new Addendum;
        $seeder->run();
        $this->assertSame('Different text', $blog->fresh()->description);
        $this->assertSame(Addendum::MISMATCH, $seeder->report[self::BLOG]);

        Blog::create(['title' => 'Dup', 'slug' => ' Safe-Handling-of-Peptides-in-Laboratory-Settings', 'description' => 'x', 'status' => 'published']);
        $seeder = new Addendum;
        $seeder->run();
        $this->assertSame(Addendum::NO_MATCH, $seeder->report[self::BLOG]);
    }

    public function test_tb500_ascii_target_drift_writes_nothing_and_does_not_throw(): void
    {
        [, $tb] = $this->seedLive([], ['molecular_formula' => 'C38H68N10O14']);
        $seeder = new Addendum;
        $seeder->run();
        $this->assertSame('C38H68N10O14', $tb->fresh()->molecular_formula);
        $this->assertSame(Addendum::MISMATCH, $seeder->columns['tb-500']['molecular_formula']);
        $this->assertSame('889.0 g/mol', $tb->fresh()->molecular_weight);
    }

    public function test_bpc_page_after_120000_and_170000(): void
    {
        $this->seedLive(with65: true);
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        (new Addendum)->run();
        $payload = Addendum::payload();

        $this->get('/encyclopedia/BPC-157')->assertOk()->assertInertia(function ($page) use ($payload) {
            $p = $page->toArray()['props'];
            $this->assertSame('No human PK data', $p['halfLife']);
            foreach ($p['routes'] as $route) {
                $this->assertStringContainsString('(studied in animals)', $route);
            }
            $this->assertNotContains('Anecdotal Clinical Use', array_column($p['humanUseSubsections'], 'title'));
            $refs = json_encode($p['references']);
            $this->assertStringNotContainsString('wikipedia.org', $refs);
            $this->assertStringNotContainsString('orthoandwellness.com', $refs);
            $this->assertStringContainsString('fda.gov\/media\/193343', $refs);
            $this->assertStringContainsString('2026list_en_final_clean_september_2025.pdf', $refs);
            $this->assertStringContainsString('14554208', $refs);
            $this->assertStringNotContainsString('Kazmi A, et al.', $refs);
            $all = json_encode([$p['humanUseSubsections'], $p['regulatorySubsections'], $p['preclinicalSubsections']], JSON_UNESCAPED_UNICODE);
            $this->assertStringNotContainsString('without control groups', $all);
            $this->assertStringContainsString('placebo-controlled studies FDA identified', $all);
            $this->assertStringContainsString('Staresinic et al. 2003', $all);
            $this->assertStringNotContainsString('dramatically improved', $all);
            $this->assertStringContainsString('did not identify any carcinogenicity studies', $all);
            $this->assertStringNotContainsString('no study has shown', $all);
            $this->assertStringContainsString('developer-affiliated', $all);

            $content = json_encode(array_intersect_key($p, array_flip([
                'humanUseSubsections', 'regulatorySubsections', 'preclinicalSubsections', 'references',
                'background', 'conclusion', 'halfLife', 'routes', 'keyPoints', 'regulatoryImportantNote',
            ])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            foreach (['dose', 'sourcing'] as $rx) {
                preg_match_all('~'.str_replace('~', '\~', substr($payload['regex'][$rx], 4)).'~iu', $content, $m);
                $this->assertSame([], $m[0], $rx);
            }

            return true;
        });
    }

    public function test_tb500_page(): void
    {
        $this->seedLive();
        (new Addendum)->run();
        $this->get('/encyclopedia/TB-500')->assertOk()->assertInertia(function ($page) {
            $p = $page->toArray()['props'];
            $this->assertSame('C₃₈H₆₈N₁₀O₁₄', $p['molecularInfo']['formula']);
            $this->assertSame('889.0 g/mol', $p['molecularInfo']['molecularWeight']);
            $this->assertSame('No human PK data', $p['halfLife']);
            $this->assertStringNotContainsString('source from vendors', $p['conclusion']);
            $this->assertStringNotContainsString('verified', $p['conclusion']);

            return true;
        });
    }

    public function test_hub_has_no_high_safety_tag(): void
    {
        $this->seedLive();
        $this->get('/encyclopedia')->assertOk()->assertDontSee('High Safety', false);
    }

    public function test_seeder_and_controller_text(): void
    {
        $edu = (string) file_get_contents(database_path('seeders/EducationPostSeeder.php'));
        $blog = (string) file_get_contents(database_path('seeders/BlogSeeder.php'));
        $controller = (string) file_get_contents(app_path('Http/Controllers/Frontend/EncyclopediaController.php'));
        foreach (['faster recovery', 'dosage optimization', 'usage protocols'] as $needle) {
            $this->assertStringNotContainsString($needle, $edu, $needle);
        }
        $this->assertStringNotContainsString('faster recovery', $blog);
        foreach (['250-500 mcg', '~4 hours', 'High Safety', 'getDosage', 'getStackingRecommendations', 'Leaky gut'] as $needle) {
            $this->assertStringNotContainsString($needle, $controller, $needle);
        }
        $this->assertFileExists(resource_path('js/Pages/Frontend/EncyclopediaDetail.vue'));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function cols(string $key): array
    {
        return Addendum::payload()['posts'][$key]['columns'];
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function target(array $spec, int $i): mixed
    {
        return $spec['target_values'][$i] ?? $spec['new_value'];
    }

    private function refreshDatabaseForNext(): void
    {
        Blog::query()->delete();
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
    }

    /**
     * @param  array<string, mixed>  $bpcOverrides
     * @param  array<string, mixed>  $tbOverrides
     * @return array{0: EducationPost, 1: EducationPost, 2: Blog, 3: ProductCategory}
     */
    private function seedLive(array $bpcOverrides = [], array $tbOverrides = [], bool $with65 = false): array
    {
        $bpcAttrs = [];
        foreach ($this->cols('bpc-157') as $column => $spec) {
            $bpcAttrs[$column] = $spec['old_values'][$column === 'description' ? 1 : 0];
        }
        if ($with65) {
            $p65 = json_decode((string) file_get_contents(EncyclopediaFieldUpdatesOctober22Seeder::directory().'/bpc-157.json'), true);
            foreach (['regulatory_important_note', 'key_points', 'faqs'] as $column) {
                $bpcAttrs[$column] = $p65['columns'][$column]['old_value'];
            }
        }
        $cat = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true, 'description' => 'KEEP-CATEGORY']);
        $bpc = $this->linkedPost($cat, 'bpc-157-linked', array_merge($bpcAttrs, $bpcOverrides));

        $tbAttrs = [];
        foreach ($this->cols('tb-500') as $column => $spec) {
            $tbAttrs[$column] = $spec['old_values'][0];
        }
        $tbCat = ProductCategory::create(['name' => 'TB-500', 'slug' => 'TB-500', 'is_active' => true]);
        $tb = $this->linkedPost($tbCat, 'tb-500-linked', array_merge($tbAttrs, $tbOverrides));

        $blog = Blog::create([
            'title' => 'Safe Handling of Peptides in Laboratory Settings',
            'slug' => 'safe-handling-of-peptides-in-laboratory-settings',
            'description' => $this->cols(self::BLOG)['description']['old_values'][0],
            'status' => 'published',
        ])->fresh();

        return [$bpc, $tb, $blog, $cat];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function linkedPost(ProductCategory $category, string $slug, array $attributes): EducationPost
    {
        return EducationPost::create(array_merge([
            'title' => $slug,
            'slug' => $slug,
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
        ], $attributes))->fresh();
    }
}
