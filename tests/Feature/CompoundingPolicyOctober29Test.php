<?php

namespace Tests\Feature;

use App\Content\EducationalContentPublisher;
use App\Models\EducationalGuide;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaCompoundingPolicyOctober29Seeder as Policy;
use Database\Seeders\EncyclopediaFreshnessOctober31Seeder as Freshness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompoundingPolicyOctober29Test extends TestCase
{
    use RefreshDatabase;

    private const GUIDE = 'peptide-legality-fda-ruo-compounding';

    private const GUIDE_KEY = 'guide:peptide-legality-fda-ruo-compounding';

    private const CATEGORY = [
        'bpc-157' => 'BPC-157', 'tb-500' => 'TB-500', 'kpv' => 'kpv', 'epitalon' => 'epitalon', 'mots-c' => 'mots-c',
        'semax' => 'Semax', 'BPC-157-TB-500' => 'BPC-157-TB-500', 'glow' => 'glow',
        'klow-blend-ghk-cu-bpc-157-tb-500-kpv' => 'klow-blend-ghk-cu-bpc-157-tb-500-kpv',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_payload_hash_matches_migration_and_seeder(): void
    {
        $raw = (string) file_get_contents(database_path(Policy::PAYLOAD));
        $this->assertSame(Policy::SOURCE_PAYLOAD_SHA256, hash('sha256', $raw));
        $migration = (string) file_get_contents(database_path('migrations/2026_10_29_100000_compounding_policy_october_29.php'));
        $this->assertStringContainsString('EncyclopediaCompoundingPolicyOctober29Seeder', $migration);
        $this->assertStringContainsString(Policy::SOURCE_PAYLOAD_SHA256, $migration);
        $this->assertSame(array_keys(self::CATEGORY), array_keys(Policy::payload()['posts']));
    }

    public function test_every_old_value_writes_its_paired_target(): void
    {
        foreach (Policy::payload()['posts'] as $slug => $spec) {
            foreach ($spec['columns'] as $column => $colSpec) {
                foreach ($colSpec['old_values'] as $i => $old) {
                    $this->wipe();
                    $posts = $this->seedPosts([$slug => [$column => $old]]);
                    $seeder = new Policy;
                    $seeder->run();
                    $this->assertSame(Policy::UPDATED, $seeder->columns[$slug][$column], "{$slug}.{$column}[{$i}]");
                    $this->assertSame($colSpec['target_values'][$i], $posts[$slug]->fresh()->{$column}, "{$slug}.{$column}[{$i}] ".$colSpec['labels'][$i]);
                }
            }
        }
    }

    public function test_live_fixture_updates_all_and_leaves_other_posts_untouched(): void
    {
        $posts = $this->seedPosts();
        $cat = ProductCategory::create(['name' => 'GHK-Cu', 'slug' => 'GHK-Cu', 'is_active' => true]);
        $other = $this->linkedPost($cat, 'ghk-cu-linked', [
            'regulatory_subsections' => [['title' => 'Status', 'entries' => [['type' => 'content', 'value' => 'KEEP']]]],
            'regulatory_important_note' => 'KEEP', 'faqs' => [['question' => 'Q', 'answer' => 'A']],
        ]);
        $before = $other->getAttributes();
        // The guide as 100000 left it (2026_10_31_100000 later moves
        // content and reading_time on; see EncyclopediaFreshnessOctober31Test).
        $this->setGuide('target_values');

        $seeder = new Policy;
        $seeder->run();

        $this->assertSame([], $seeder->warnings);
        foreach ($posts as $slug => $post) {
            $this->assertSame(Policy::UPDATED, $seeder->report[$slug], $slug);
            foreach (Policy::payload()['posts'][$slug]['columns'] as $column => $spec) {
                $this->assertSame($spec['target_values'][0], $post->fresh()->{$column}, "{$slug}.{$column}");
            }
        }
        $this->assertSame($before, $other->fresh()->getAttributes());
    }

    public function test_second_run_is_a_no_op(): void
    {
        $posts = $this->seedPosts();
        $this->setGuide('old_values');
        (new Policy)->run();
        $snap = array_map(fn (EducationPost $p) => $p->fresh()->getAttributes(), $posts);
        $guide = $this->guide()->getAttributes();

        $seeder = new Policy;
        $seeder->run();

        $this->assertSame($snap, array_map(fn (EducationPost $p) => $p->fresh()->getAttributes(), $posts));
        $this->assertSame($guide, $this->guide()->getAttributes());
        foreach (array_merge(array_keys($posts), [self::GUIDE_KEY]) as $key) {
            $this->assertSame(Policy::CURRENT, $seeder->report[$key], $key);
        }
        $this->assertSame([], $seeder->warnings);
    }

    public function test_guard_mismatch_skips_only_that_column(): void
    {
        $reg = $this->cols('bpc-157')['regulatory_subsections']['old_values'][0];
        $reg[1]['entries'][0]['value'] .= ' Edited.';
        $posts = $this->seedPosts(['bpc-157' => ['regulatory_subsections' => $reg]]);

        $seeder = new Policy;
        $seeder->run();

        $this->assertSame($reg, $posts['bpc-157']->fresh()->regulatory_subsections);
        $this->assertSame(Policy::MISMATCH, $seeder->columns['bpc-157']['regulatory_subsections']);
        foreach (['regulatory_important_note', 'key_points', 'faqs'] as $column) {
            $this->assertSame(Policy::UPDATED, $seeder->columns['bpc-157'][$column], $column);
        }
        $this->assertSame(Policy::UPDATED, $seeder->report['tb-500']);
        $this->assertStringContainsString('bpc-157 regulatory_subsections: old mismatch', implode("\n", $seeder->warnings));
    }

    public function test_bpc_live_and_main_guards_both_land_the_australia_subsection_once(): void
    {
        $spec = $this->cols('bpc-157')['regulatory_subsections'];
        $this->assertGreaterThanOrEqual(2, count($spec['old_values']));
        foreach ($spec['target_values'] as $i => $target) {
            $titles = array_column($target, 'title');
            $au = array_values(array_filter($titles, fn ($t) => str_starts_with($t, 'Australia (as of')));
            $this->assertCount(1, $au, $spec['labels'][$i]);
            $this->assertSame('Australia (as of 22 October 2026)', end($titles));
            $this->assertStringContainsString('Therapeutic Goods Administration (TGA)', json_encode($target, JSON_UNESCAPED_UNICODE));
            $this->assertContains('U.S. regulatory status — compounding policy (as of 29 October 2026)', $titles);
        }
        $live = json_encode($spec['old_values'][0], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('21 October 2026', $live);
        $this->assertStringNotContainsString('Australia (as of', $live);
    }

    public function test_lookup_collision_writes_nothing_for_that_slug(): void
    {
        $posts = $this->seedPosts();
        ProductCategory::create(['name' => 'KPV dup', 'slug' => ' KPV', 'is_active' => true]);
        $before = $posts['kpv']->fresh()->getAttributes();

        $seeder = new Policy;
        $seeder->run();

        $this->assertSame($before, $posts['kpv']->fresh()->getAttributes());
        $this->assertSame(Policy::NO_MATCH, $seeder->report['kpv']);
        $this->assertSame(Policy::UPDATED, $seeder->report['epitalon']);
    }

    public function test_missing_post_writes_nothing_and_does_not_throw(): void
    {
        $posts = $this->seedPosts();
        $posts['mots-c']->delete();

        $seeder = new Policy;
        $seeder->run();

        $this->assertSame(Policy::NO_MATCH, $seeder->report['mots-c']);
        $this->assertSame(Policy::UPDATED, $seeder->report['semax']);
    }

    public function test_legacy_unlinked_row_is_never_written(): void
    {
        $this->seedPosts();
        $legacy = EducationPost::create([
            'title' => 'TB-500', 'slug' => 'tb-500', 'product_category_id' => null, 'status' => 'published',
            'regulatory_subsections' => $this->cols('tb-500')['regulatory_subsections']['old_values'][0],
        ])->fresh();
        $before = $legacy->getAttributes();

        (new Policy)->run();

        $this->assertSame($before, $legacy->fresh()->getAttributes());
    }

    public function test_guide_is_guarded(): void
    {
        $this->setGuide('old_values');
        $seeder = new Policy;
        $seeder->run();
        foreach ($this->guideCols() as $column => $spec) {
            $this->assertSame(Policy::UPDATED, $seeder->columns[self::GUIDE_KEY][$column], $column);
            $this->assertSame($spec['target_values'][0], $this->guide()->{$column}, $column);
        }

        $this->guide()->forceFill(['seo_description' => 'Hand-edited'])->saveQuietly();
        $this->guide()->forceFill(['content' => $this->guideCols()['content']['old_values'][0]])->saveQuietly();
        $seeder = new Policy;
        $seeder->run();
        $this->assertSame('Hand-edited', $this->guide()->seo_description);
        $this->assertSame(Policy::MISMATCH, $seeder->columns[self::GUIDE_KEY]['seo_description']);
        $this->assertSame(Policy::UPDATED, $seeder->columns[self::GUIDE_KEY]['content']);
    }

    public function test_publisher_output_matches_guide_targets(): void
    {
        // content and reading_time moved on in 2026_10_31_100000, whose
        // guard is this payload's target; every other column is unchanged.
        $fresh = json_decode((string) file_get_contents(database_path(Freshness::PAYLOAD_PATH)), true)['guides'][self::GUIDE]['columns'];
        EducationalContentPublisher::sync();
        foreach ($this->guideCols() as $column => $spec) {
            if (isset($fresh[$column])) {
                $this->assertSame([$spec['target_values'][0]], $fresh[$column]['old_values'], $column);
                $this->assertSame(Policy::normalize($fresh[$column]['target_value']), Policy::normalize($this->guide()->{$column}), $column);

                continue;
            }
            $this->assertSame(Policy::normalize($spec['target_values'][0]), Policy::normalize($this->guide()->{$column}), $column);
        }
        $this->assertSame(['content', 'reading_time'], array_keys($fresh));
    }

    public function test_guide_seo_and_og_descriptions_are_160_characters_or_fewer(): void
    {
        $page = collect((new \ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null))
            ->firstWhere('slug', self::GUIDE);
        $cols = $this->guideCols();
        foreach ([
            'catalog seo_description' => $page['seo_description'],
            'catalog og_description' => $page['og_description'],
            'payload seo_description' => $cols['seo_description']['target_values'][0],
            'payload seo_og_description' => $cols['seo_og_description']['target_values'][0],
        ] as $label => $value) {
            $this->assertLessThanOrEqual(160, mb_strlen($value), $label);
        }
        $this->assertSame(145, mb_strlen($page['seo_description']));
        $this->assertSame($page['seo_description'], $cols['seo_description']['target_values'][0]);
        $this->assertSame($page['og_description'], $cols['seo_og_description']['target_values'][0]);
    }

    public function test_guide_reading_time_moves_from_20_or_19_to_21_and_is_guarded(): void
    {
        $this->assertSame('21 Min Read', $this->guideCols()['reading_time']['target_values'][0]);
        // The catalog now carries the 2026_10_31_100000 value (24 Min Read).
        $this->assertSame('24 Min Read', collect((new \ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null))
            ->firstWhere('slug', self::GUIDE)['read_time']);
        $this->assertSame(['20 Min Read', '19 Min Read'], $this->guideCols()['reading_time']['old_values']);
        foreach (['20 Min Read', '19 Min Read'] as $old) {
            $this->setGuide('old_values');
            $this->guide()->forceFill(['reading_time' => $old])->saveQuietly();
            $seeder = new Policy;
            $seeder->run();
            $this->assertSame('21 Min Read', $this->guide()->reading_time, $old);
            $this->assertSame(Policy::UPDATED, $seeder->columns[self::GUIDE_KEY]['reading_time'], $old);
        }
        $this->guide()->forceFill(['reading_time' => '18 Min Read'])->saveQuietly();
        $seeder = new Policy;
        $seeder->run();
        $this->assertSame('18 Min Read', $this->guide()->reading_time);
        $this->assertSame(Policy::MISMATCH, $seeder->columns[self::GUIDE_KEY]['reading_time']);
    }

    public function test_old_medwatch_and_klow_omb_wording_is_gone(): void
    {
        $haystacks = [
            'payload targets' => json_encode([
                array_map(fn ($p) => array_map(fn ($c) => $c['target_values'], $p['columns']), Policy::payload()['posts']),
                array_map(fn ($c) => $c['target_values'], $this->guideCols()),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'guide md' => (string) file_get_contents(resource_path('content/educational/'.self::GUIDE.'.md')),
            'klow copy' => (string) file_get_contents(app_path('Support/KlowCompareCopy.php')),
        ];
        foreach (['kpv', 'epitalon', 'Semax'] as $shell) {
            $haystacks["shell {$shell}"] = (string) file_get_contents(database_path("seeders/data/encyclopedia-shells/{$shell}/body.md"));
        }
        $old = [
            'to FDA’s MedWatch program and to the States',
            'adverse events and product quality issues to FDA’s MedWatch program and to the States',
            'sent an interim access policy',
            'White House for review',
            'rrid=1568964',
        ];
        foreach ($haystacks as $label => $haystack) {
            foreach ($old as $needle) {
                $this->assertStringNotContainsString($needle, $haystack, "{$label}: {$needle}");
            }
        }
        $payload = $haystacks['payload targets'];
        $this->assertSame(11, substr_count($payload, 'to FDA and to the States, and serious adverse events and product quality issues to FDA’s MedWatch program;'));
        $this->assertSame(1, substr_count($payload, 'to FDA and to the States, and report serious adverse events and product quality issues to FDA’s MedWatch program.'));
        $this->assertStringContainsString('FR Doc. 2026-22192', $haystacks['klow copy']);
        $this->assertStringContainsString('/blog/hhs-503a-peptide-enforcement-discretion-2026', $this->guideCols()['content']['target_values'][0]);
    }

    public function test_guide_link_targets_exist_after_pr_79(): void
    {
        $content = $this->guideCols()['content']['target_values'][0];
        $this->assertSame(1, substr_count($content, 'href="/blog/hhs-503a-peptide-enforcement-discretion-2026"'));
        $this->assertSame(1, substr_count($content, 'href="/guides/compounded-vs-ruo-peptides"'));

        EducationalContentPublisher::sync();
        $this->assertSame(1, \App\Models\Blog::where('slug', 'hhs-503a-peptide-enforcement-discretion-2026')->count());
        $this->assertSame(1, EducationalGuide::where('slug', 'compounded-vs-ruo-peptides')->count());
        $this->get('/blog/hhs-503a-peptide-enforcement-discretion-2026')->assertOk();
        $this->get('/guides/compounded-vs-ruo-peptides')->assertOk();
    }

    public function test_stale_and_banned_phrases_are_gone(): void
    {
        $this->seedPosts();
        $this->setGuide('old_values');
        (new Policy)->run();

        $targets = json_encode([
            array_map(fn ($p) => array_map(fn ($c) => $c['target_values'], $p['columns']), Policy::payload()['posts']),
            array_map(fn ($c) => $c['target_values'], $this->guideCols()),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $files = implode("\n", [
            (string) file_get_contents(resource_path('content/educational/'.self::GUIDE.'.md')),
            (string) file_get_contents(app_path('Content/EducationalContentPublisher.php')),
        ]);
        foreach ([$targets, $files] as $haystack) {
            foreach (['under OIRA review', 'under OMB review', 'HHS/FDA', '67724-34-9', 'legal to use'] as $needle) {
                $this->assertStringNotContainsStringIgnoringCase($needle, $haystack, $needle);
            }
        }
        $this->assertStringContainsString('67727-97-3', json_encode($this->cols('kpv'), JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('Ac-LKKTETQ', json_encode($this->cols('tb-500'), JSON_UNESCAPED_UNICODE));

        $pages = ['/encyclopedia/BPC-157', '/encyclopedia/TB-500', '/encyclopedia/kpv', '/encyclopedia/epitalon', '/encyclopedia/mots-c',
            '/encyclopedia/Semax', '/encyclopedia/BPC-157-TB-500', '/encyclopedia/glow', '/encyclopedia/klow-blend-ghk-cu-bpc-157-tb-500-kpv',
            '/guides/'.self::GUIDE];
        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertOk();
            foreach (['under OIRA review', 'under OMB review', 'HHS/FDA', '67724-34-9'] as $needle) {
                $this->assertStringNotContainsString($needle, html_entity_decode((string) $response->getContent()), "{$url} {$needle}");
            }
        }
    }

    public function test_bpc_faq_json_ld_uses_new_answer(): void
    {
        $this->seedPosts();
        (new Policy)->run();
        $faq = $this->cols('bpc-157')['faqs']['target_values'][0][2];
        $html = html_entity_decode((string) $this->get('/encyclopedia/BPC-157')->assertOk()->getContent());
        $this->assertStringContainsString('draft interim enforcement-discretion policy', $faq['answer']);
        $this->assertStringContainsString(json_encode(strip_tags($faq['answer']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), str_replace('\/', '/', $html));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function cols(string $slug): array
    {
        return Policy::payload()['posts'][$slug]['columns'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function guideCols(): array
    {
        return Policy::payload()['guides'][self::GUIDE]['columns'];
    }

    private function guide(): EducationalGuide
    {
        return EducationalGuide::query()->where('slug', self::GUIDE)->firstOrFail();
    }

    private function setGuide(string $key): void
    {
        if (! EducationalGuide::query()->where('slug', self::GUIDE)->exists()) {
            EducationalContentPublisher::sync();
        }
        $attrs = [];
        foreach ($this->guideCols() as $column => $spec) {
            $attrs[$column] = $spec[$key][0];
        }
        $this->guide()->forceFill($attrs)->saveQuietly();
    }

    private function wipe(): void
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
    }

    /**
     * Live fixture: every column at old_values[0], with per-slug overrides.
     *
     * @param  array<string, array<string, mixed>>  $overrides
     * @return array<string, EducationPost>
     */
    private function seedPosts(array $overrides = []): array
    {
        $out = [];
        foreach (Policy::payload()['posts'] as $slug => $spec) {
            $attrs = [];
            foreach ($spec['columns'] as $column => $colSpec) {
                $attrs[$column] = $colSpec['old_values'][0];
            }
            $cat = ProductCategory::query()->whereRaw('LOWER(TRIM(slug)) = ?', [strtolower(self::CATEGORY[$slug])])->first()
                ?? ProductCategory::create(['name' => $slug, 'slug' => self::CATEGORY[$slug], 'is_active' => true]);
            EducationPost::query()->where('product_category_id', $cat->id)->delete();
            $out[$slug] = $this->linkedPost($cat, strtolower($slug).'-linked', array_merge($attrs, $overrides[$slug] ?? []));
        }

        return $out;
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
