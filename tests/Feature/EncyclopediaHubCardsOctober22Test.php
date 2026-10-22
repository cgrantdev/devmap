<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaHubCardsOctober22Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncyclopediaHubCardsOctober22Test extends TestCase
{
    use RefreshDatabase;

    private const SCAN = [
        '/\d+(\.\d+)?\s?%/u',
        '/\b(heal|heals|healed|healing)\b/iu',
        '/fat[- ]?burn/iu',
        '/burn(s|ing)?\s+fat/iu',
        '/remarkable/iu',
        '/miracle/iu',
        '/recover(y|ies)?/iu',
        '/anti[- ]?age?ing/iu',
        '/\b\d+(\.\d+)?\s?(mg|mcg|µg|ug|iu|ml|units?)\b/iu',
        '/\b(mg|mcg|µg|iu)\b/iu',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_payload_hash_matches_migration_and_seeder(): void
    {
        $raw = (string) file_get_contents(database_path(EncyclopediaHubCardsOctober22Seeder::PAYLOAD));
        $this->assertSame(EncyclopediaHubCardsOctober22Seeder::SOURCE_PAYLOAD_SHA256, hash('sha256', $raw));
        $migration = (string) file_get_contents(database_path('migrations/2026_10_22_180000_neutralise_encyclopedia_hub_card_descriptions.php'));
        $this->assertStringContainsString('EncyclopediaHubCardsOctober22Seeder', $migration);
        $this->assertStringContainsString(EncyclopediaHubCardsOctober22Seeder::SOURCE_PAYLOAD_SHA256, $migration);
    }

    public function test_new_copy_has_zero_scan_hits(): void
    {
        $hits = [];
        foreach ($this->posts() as $slug => $spec) {
            foreach ($spec['columns'] as $column => $col) {
                $text = isset($col['append_item']) ? json_encode($col['append_item'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $col['new_value'];
                foreach (self::SCAN as $re) {
                    if (preg_match($re, $text, $m)) {
                        $hits[] = "{$slug}.{$column} {$re} [{$m[0]}]";
                    }
                }
            }
        }
        $this->assertSame([], $hits);
    }

    public function test_writes_guarded_values_and_leaves_everything_else_untouched(): void
    {
        $rows = $this->seedAll();
        $other = $this->linkedPost($this->category('KEEP-OTHER', 'semax'), 'semax', 'KEEP-SEMAX-DESCRIPTION');
        $legacy = EducationPost::create([
            'title' => 'legacy', 'slug' => 'bpc-157', 'product_category_id' => null, 'status' => 'published',
            'show_in_encyclopedia' => true, 'description' => $this->posts()['bpc-157']['columns']['description']['old_values'][0],
        ])->fresh();
        $protected = [$other->id => $other->getAttributes(), $legacy->id => $legacy->getAttributes()];
        $categories = [];
        foreach ($rows as $slug => [$category]) {
            $categories[$slug] = $category->getAttributes();
        }

        $seeder = new EncyclopediaHubCardsOctober22Seeder;
        $seeder->run();

        foreach ($protected as $id => $attributes) {
            $this->assertSame($attributes, EducationPost::query()->findOrFail($id)->getAttributes());
        }
        foreach ($this->posts() as $slug => $spec) {
            $this->assertSame('updated', $seeder->report[$slug], $slug);
            [$category, $post] = $rows[$slug];
            foreach ($spec['columns'] as $column => $col) {
                $expected = isset($col['append_item']) ? $col['target_values'][0] : $col['new_value'];
                $this->assertSame($expected, $post->fresh()->{$column}, "{$slug}.{$column}");
            }
            $this->assertSame($categories[$slug], $category->fresh()->getAttributes(), $slug.' category untouched');
        }
        $this->assertSame([], $seeder->warnings);
    }

    public function test_second_run_is_idempotent(): void
    {
        $rows = $this->seedAll();
        (new EncyclopediaHubCardsOctober22Seeder)->run();
        $after = [];
        foreach ($rows as $slug => [, $post]) {
            $after[$slug] = $post->fresh()->getAttributes();
        }

        $seeder = new EncyclopediaHubCardsOctober22Seeder;
        $seeder->run();

        foreach ($rows as $slug => [, $post]) {
            $this->assertSame($after[$slug], $post->fresh()->getAttributes(), $slug);
            $this->assertSame(EncyclopediaHubCardsOctober22Seeder::CURRENT, $seeder->report[$slug], $slug);
        }
    }

    public function test_overlap_states_are_accepted(): void
    {
        $posts = $this->posts();
        // 130000 (reapply, on main) may already have written its REDEFINE text.
        [, $cagri] = $this->pair('cagrilintide', $posts['cagrilintide']['columns']['description']['old_values'][1]);
        // 160000 / 170000 write the same target text this package uses.
        [, $reta] = $this->pair('retatrutide', $posts['retatrutide']['columns']['description']['new_value']);
        [, $bpc] = $this->pair('bpc-157', $posts['bpc-157']['columns']['description']['new_value']);

        $seeder = new EncyclopediaHubCardsOctober22Seeder;
        $seeder->run();

        $this->assertSame($posts['cagrilintide']['columns']['description']['new_value'], $cagri->fresh()->description);
        $this->assertSame(EncyclopediaHubCardsOctober22Seeder::CURRENT, $seeder->report['retatrutide']);
        $this->assertSame(EncyclopediaHubCardsOctober22Seeder::CURRENT, $seeder->report['bpc-157']);
    }

    public function test_normalizes_crlf_nbsp_and_trailing_whitespace(): void
    {
        $old = $this->posts()['retatrutide']['columns']['description']['old_values'][0];
        $messy = str_replace(["\n", ' — '], ["  \r\n", "\u{00A0}— "], $old)."  \r\n";
        [, $post] = $this->pair('retatrutide', $messy);

        (new EncyclopediaHubCardsOctober22Seeder)->run();

        $this->assertSame($this->posts()['retatrutide']['columns']['description']['new_value'], $post->fresh()->description);
    }

    public function test_mismatch_and_bad_match_write_nothing(): void
    {
        [, $drift] = $this->pair('aod-9604', 'Edited by hand in the CMS.');
        $a = $this->category('X', 'ghk-cu');
        $b = $this->category('Y', 'GHK-Cu ');
        $ghkA = $this->linkedPost($a, 'ghk-cu-a', $this->posts()['ghk-cu']['columns']['description']['old_values'][0]);
        $ghkB = $this->linkedPost($b, 'ghk-cu-b', $this->posts()['ghk-cu']['columns']['description']['old_values'][0]);
        $before = [$drift->getAttributes(), $ghkA->getAttributes(), $ghkB->getAttributes()];

        $seeder = new EncyclopediaHubCardsOctober22Seeder;
        $seeder->run();

        $this->assertSame($before, [$drift->fresh()->getAttributes(), $ghkA->fresh()->getAttributes(), $ghkB->fresh()->getAttributes()]);
        $this->assertSame(EncyclopediaHubCardsOctober22Seeder::MISMATCH, $seeder->report['aod-9604']);
        $this->assertSame(EncyclopediaHubCardsOctober22Seeder::NO_MATCH, $seeder->report['ghk-cu']);
        $warnings = implode("\n", $seeder->warnings);
        $this->assertStringContainsString('ghk-cu: collision, nothing written (categories 2, linked posts 2', $warnings);
        $this->assertStringContainsString('aod-9604 education_posts.description: old mismatch', $warnings);
    }

    public function test_empty_post_description_falls_back_to_category_column_only(): void
    {
        $old = $this->posts()['dsip']['columns']['description']['old_values'][0];
        $category = $this->category($old, 'dsip');
        $post = $this->linkedPost($category, 'dsip', '');

        (new EncyclopediaHubCardsOctober22Seeder)->run();

        $this->assertSame($this->posts()['dsip']['columns']['description']['new_value'], $category->fresh()->description);
        $this->assertSame('', (string) $post->fresh()->description);
    }

    public function test_hub_and_compare_render_the_new_copy(): void
    {
        $this->seedAll();
        (new EncyclopediaHubCardsOctober22Seeder)->run();
        $posts = $this->posts();

        $this->get('/encyclopedia')->assertOk()->assertInertia(fn ($page) => $page->where('peptides', function ($cards) use ($posts) {
            $bySlug = collect($cards)->keyBy(fn ($c) => strtolower((string) $c['slug']));
            foreach ($posts as $slug => $spec) {
                if (! isset($spec['columns']['description'])) {
                    continue;
                }
                \PHPUnit\Framework\Assert::assertSame($spec['columns']['description']['new_value'], $bySlug[$slug]['description'] ?? null, $slug);
            }

            return true;
        }));

        $html = $this->get('/encyclopedia')->getContent();
        foreach (['remarkable healing', 'fat-burning', 'up to 25%', 'up to 24%', 'exercise mimetic, activating', 'hair follicle stimulation', 'arginine-to-glutamate'] as $needle) {
            $this->assertStringNotContainsString($needle, (string) $html, $needle);
        }
    }

    public function test_compare_link_is_appended_once_and_renders(): void
    {
        $rows = $this->seedAll();
        $posts = $this->posts();
        $url = 'https://peptidemap.com/compare/orforglipron-vs-oral-semaglutide';
        // Orforglipron after 140000 (on main) uses the second guard.
        $rows['orforglipron'][1]->forceFill(['references' => $posts['orforglipron']['columns']['references']['old_values'][1]])->saveQuietly();

        (new EncyclopediaHubCardsOctober22Seeder)->run();
        $seeder = new EncyclopediaHubCardsOctober22Seeder;
        $seeder->run();

        $this->assertSame($posts['orforglipron']['columns']['references']['target_values'][1], $rows['orforglipron'][1]->fresh()->references);
        $this->assertSame($posts['semaglutide']['columns']['references']['target_values'][0], $rows['semaglutide'][1]->fresh()->references);
        $this->assertSame(EncyclopediaHubCardsOctober22Seeder::CURRENT, $seeder->columns['orforglipron']['education_posts.references']);
        foreach (['orforglipron', 'semaglutide'] as $slug) {
            $count = 0;
            foreach ($rows[$slug][1]->fresh()->references as $ref) {
                $count += collect($ref['links'] ?? [])->where('url', $url)->count();
            }
            $this->assertSame(1, $count, $slug);
            $this->get('/encyclopedia/'.$slug)->assertOk()->assertSee('orforglipron-vs-oral-semaglutide', false);
        }
        $this->get('/compare/orforglipron-vs-oral-semaglutide')->assertOk();
    }

    public function test_compare_link_guard_mismatch_writes_nothing(): void
    {
        $rows = $this->seedAll();
        $edited = [['title' => 'Edited by hand', 'links' => []]];
        $rows['semaglutide'][1]->forceFill(['references' => $edited])->saveQuietly();

        $seeder = new EncyclopediaHubCardsOctober22Seeder;
        $seeder->run();

        $this->assertSame($edited, $rows['semaglutide'][1]->fresh()->references);
        $this->assertSame(EncyclopediaHubCardsOctober22Seeder::MISMATCH, $seeder->columns['semaglutide']['education_posts.references']);
        $this->assertSame(EncyclopediaHubCardsOctober22Seeder::UPDATED, $seeder->columns['semaglutide']['education_posts.description']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function posts(): array
    {
        return EncyclopediaHubCardsOctober22Seeder::payload()['posts'];
    }

    /**
     * @return array<string, array{0: ProductCategory, 1: EducationPost}>
     */
    private function seedAll(): array
    {
        $rows = [];
        foreach ($this->posts() as $slug => $spec) {
            $rows[$slug] = $this->pair($slug, null);
        }

        return $rows;
    }

    /**
     * @return array{0: ProductCategory, 1: EducationPost}
     */
    private function pair(string $slug, ?string $description): array
    {
        $spec = $this->posts()[$slug];
        $category = $this->category('KEEP-CATEGORY-'.$slug, (string) ($spec['hub_slug_live'] ?? $slug), (string) ($spec['category_name_live'] ?? $slug));
        $attributes = [];
        foreach ($spec['columns'] as $column => $col) {
            $attributes[$column] = $column === 'description' && $description !== null ? $description : $col['old_values'][0];
        }
        $post = $this->linkedPost($category, $slug.'-linked', $attributes['description'] ?? 'KEEP-DESCRIPTION-'.$slug, $attributes);

        return [$category->fresh(), $post];
    }

    private function category(string $description, string $slug, ?string $name = null): ProductCategory
    {
        $needle = strtolower(trim($slug));
        $ids = ProductCategory::query()->whereRaw('LOWER(TRIM(slug)) = ?', [$needle])->pluck('id');
        if (! in_array($slug, ['GHK-Cu '], true)) {
            EducationPost::query()->whereIn('product_category_id', $ids->all())->delete();
            ProductCategory::query()->whereIn('id', $ids->all())->delete();
        }
        $category = new ProductCategory;
        $category->forceFill(['name' => $name ?? $slug, 'slug' => $slug, 'description' => $description, 'is_active' => true]);
        $category->save();

        return $category->fresh();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function linkedPost(ProductCategory $category, string $slug, string $description, array $extra = []): EducationPost
    {
        $post = EducationPost::create(array_merge([
            'title' => $slug,
            'slug' => $slug,
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
        ], $extra, ['description' => $description]));

        return $post->fresh();
    }
}
