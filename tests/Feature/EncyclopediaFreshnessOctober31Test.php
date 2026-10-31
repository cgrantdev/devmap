<?php

namespace Tests\Feature;

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaFraming;
use App\Support\GuardedFieldWriter;
use Database\Seeders\EncyclopediaBpcCleanupAddendumSeeder;
use Database\Seeders\EncyclopediaCompoundingPolicyOctober29Seeder;
use Database\Seeders\EncyclopediaFaqAuditBatch1Seeder;
use Database\Seeders\EncyclopediaFieldReapplyOctober22Seeder;
use Database\Seeders\EncyclopediaFieldUpdatesOctober22Seeder;
use Database\Seeders\EncyclopediaFramingSeeder;
use Database\Seeders\EncyclopediaFreshnessOctober31Seeder as Fresh;
use Database\Seeders\EncyclopediaHubCardsOctober22Seeder;
use Database\Seeders\EncyclopediaOrfoRetaOctober22Seeder;
use Database\Seeders\EncyclopediaProdDriftReconcileOctober29Seeder;
use Database\Seeders\EncyclopediaRetatrutideOsaLitigationSeeder;
use Database\Seeders\EncyclopediaRetatrutideS505Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 2026_10_31_100000 freshness update: orforglipron and eloralintide posts,
 * the two 503A legality guides and the HHS 503A blog. Every value that reaches the seeder
 * is re-encoded in production MySQL key order (shorter keys first) or in
 * a shuffled key order, because sqlite keeps insertion order.
 */
class EncyclopediaFreshnessOctober31Test extends TestCase
{
    use RefreshDatabase;

    private const SHA = 'a809a4c386e3b66ca6d649ac5bec7026d8622d8acf0fb06f54b9c97b26232a5c';

    private const M = '2026_10_31_100000';

    private const LEGALITY = 'peptide-legality-fda-ruo-compounding';

    private const CVR = 'compounded-vs-ruo-peptides';

    private const BLOG = 'hhs-503a-peptide-enforcement-discretion-2026';

    /** sha256 of origin/main's rendered blog content (the guard). */
    private const BLOG_OLD_SHA = '0fd1507513c31d28466b08b34c522c70a6d78642d225c8625d7f0457813dc396';

    private const CATEGORY = ['orforglipron' => 'orforglipron', 'eloralintide' => 'Eloralintide'];

    /** @var list<array{level: string, message: string}> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e): void {
            $this->logged[] = ['level' => $e->level, 'message' => $e->message];
        });
    }

    public function test_migration_slot_payload_sha_and_shape_are_pinned(): void
    {
        $files = glob(database_path('migrations/2026_10_31_*.php')) ?: [];
        $this->assertSame(['2026_10_31_100000_encyclopedia_freshness_october_31.php'], array_map('basename', $files));
        $this->assertStringContainsString('EncyclopediaFreshnessOctober31Seeder', (string) file_get_contents($files[0]));
        $this->assertSame(self::SHA, Fresh::PAYLOAD_SHA256);
        $this->assertSame(self::SHA, hash_file('sha256', database_path(Fresh::PAYLOAD_PATH)));
        $this->assertSame(self::M, Fresh::MIGRATION);

        $payload = $this->payload();
        $this->assertSame(array_keys(Fresh::COLUMNS), array_keys($payload['posts']));
        $this->assertSame(array_keys(Fresh::GUIDES), array_keys($payload['guides']));
        $this->assertSame(array_keys(Fresh::BLOGS), array_keys($payload['blogs']));
        $this->assertSame(['content'], array_keys($payload['blogs'][self::BLOG]['columns']));
        $this->assertSame([self::BLOG_OLD_SHA], array_map(fn ($v) => hash('sha256', $v), $payload['blogs'][self::BLOG]['columns']['content']['old_values']));
        foreach ($this->specs() as $key => $spec) {
            $this->assertNotSame([], $spec['old_values'], $key);
            foreach ($spec['old_values'] as $old) {
                $this->assertNotEquals(GuardedFieldWriter::comparable($spec['target_value']), GuardedFieldWriter::comparable($old), $key);
            }
        }
        $this->assertSame([[], null], $payload['posts']['eloralintide']['columns']['references']['old_values']);
    }

    public function test_targets_match_the_fallbacks_so_a_fresh_database_agrees(): void
    {
        $orfo = EncyclopediaFraming::match('orforglipron', 'orforglipron')->articleAttributes();
        $elor = EncyclopediaFraming::match('Eloralintide', 'Eloralintide')->articleAttributes();
        $this->assertSame($this->target('orforglipron', 'human_use_subsections'), $orfo['human_use_subsections']);
        $this->assertSame($this->target('eloralintide', 'human_use_subsections'), $elor['human_use_subsections']);
        $this->assertSame($this->target('eloralintide', 'references'), $elor['references']);

        // references: main's chain value (2026_10_29_120000 target) plus two.
        $drift = json_decode((string) file_get_contents(database_path(EncyclopediaProdDriftReconcileOctober29Seeder::PAYLOAD_PATH)), true);
        $mainRefs = $drift['posts']['orforglipron']['columns']['references']['target_value'];
        $this->assertSame($mainRefs, $this->payload()['posts']['orforglipron']['columns']['references']['old_values'][0]);
        $this->assertSame($mainRefs, array_slice($this->target('orforglipron', 'references'), 0, count($mainRefs)));
        $this->assertCount(count($mainRefs) + 3, $this->target('orforglipron', 'references'));

        EducationalContentPublisher::sync();
        foreach (Fresh::GUIDES as $slug => $fields) {
            $guide = EducationalGuide::where('slug', $slug)->firstOrFail();
            foreach ($fields as $field) {
                $this->assertSame(
                    GuardedFieldWriter::comparable($this->guideTarget($slug, $field)),
                    GuardedFieldWriter::comparable($guide->{$field}),
                    $slug.'.'.$field
                );
            }
        }
        $blog = Blog::where('slug', self::BLOG)->firstOrFail();
        $this->assertSame($this->blogTarget(), $blog->content);
        $this->assertSame('4 Min Read', $blog->read_time);
    }

    public function test_blog_target_is_the_publisher_render_of_the_repo_markdown(): void
    {
        $md = (string) file_get_contents(resource_path('content/educational/'.self::BLOG.'.md'));
        $this->assertSame(\App\Support\SimpleMarkdown::toHtml(EducationalContentPublisher::prepare($md)), $this->blogTarget());
        $this->assertSame('4 Min Read', collect((new \ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null))->firstWhere('slug', self::BLOG)['read_time']);

        $html = $this->blogTarget();
        $this->assertStringContainsString('91 FR 68526', $html);
        $this->assertStringContainsString('13 Nov 2026', $html);
        $this->assertStringContainsString('https://www.regulations.gov/commenton/HHS-OASH-2026-0496-0002', $html);
        $this->assertStringNotContainsString('scheduled for publication', $html);
        $this->assertStringNotContainsString('Scheduled Federal Register publication', $html);
        $this->assertStringNotContainsString('public-inspection.federalregister.gov', $html);
        $this->assertStringContainsString('public-inspection.federalregister.gov', $this->old('blog:'.self::BLOG, 'content'));
    }

    public function test_live_values_in_prod_key_order_are_updated_and_logged(): void
    {
        $ids = $this->seedLive(fn ($v) => self::mysqlKeyOrder($v));
        $seeder = new Fresh;
        $seeder->run();

        foreach (Fresh::COLUMNS as $slug => $fields) {
            $post = EducationPost::findOrFail($ids[$slug]);
            foreach ($fields as $field) {
                $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results[$slug.'.'.$field], $slug.'.'.$field);
                $this->assertSame($this->target($slug, $field), $post->{$field}, $slug.'.'.$field);
                $this->assertTrue($this->hasLog('info', self::M.' '.$slug.'.'.$field.': updated'), $slug.'.'.$field);
            }
        }
        foreach (Fresh::GUIDES as $slug => $fields) {
            $guide = EducationalGuide::where('slug', $slug)->firstOrFail();
            foreach ($fields as $field) {
                $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['guide:'.$slug.'.'.$field]);
                $this->assertSame($this->guideTarget($slug, $field), $guide->{$field});
            }
        }
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['blog:'.self::BLOG.'.content']);
        $this->assertSame($this->blogTarget(), Blog::where('slug', self::BLOG)->firstOrFail()->content);
        $this->assertTrue($this->hasLog('info', self::M.' blog:'.self::BLOG.'.content: updated'));
        $this->assertSame(['orforglipron', 'eloralintide', 'guide:'.self::LEGALITY, 'guide:'.self::CVR, 'blog:'.self::BLOG], $seeder->transactions);
        $this->assertSame([], $this->warnings());

        $again = new Fresh;
        $again->run();
        $this->assertSame(GuardedFieldWriter::ALREADY, $again->results['blog:'.self::BLOG.'.content']);
        $this->assertSame($this->blogTarget(), Blog::where('slug', self::BLOG)->firstOrFail()->content);
        $this->assertSame([], $this->warnings());
    }

    public function test_shuffled_json_key_order_is_neither_a_mismatch_nor_a_rewrite(): void
    {
        foreach ([11, 29, 31] as $seed) {
            $this->refreshTables();
            mt_srand($seed);
            $ids = $this->seedLive(fn ($v) => self::shuffleKeys($v));
            $this->assertNotSame(
                json_encode($this->payload()['posts']['orforglipron']['columns']['references']['old_values'][0]),
                json_encode(EducationPost::findOrFail($ids['orforglipron'])->references),
                'seed '.$seed.' should actually reorder keys'
            );
            $seeder = new Fresh;
            $seeder->run();
            foreach ($seeder->results as $key => $status) {
                $this->assertSame(GuardedFieldWriter::UPDATED, $status, $seed.' '.$key);
            }

            // Re-store the written targets in another key order: already current.
            foreach (Fresh::COLUMNS as $slug => $fields) {
                $post = EducationPost::findOrFail($ids[$slug]);
                foreach ($fields as $field) {
                    $post->{$field} = self::shuffleKeys($post->{$field});
                }
                $post->saveQuietly();
            }
            $before = $this->snapshot();
            $again = new Fresh;
            $again->run();
            foreach ($again->results as $key => $status) {
                $this->assertSame(GuardedFieldWriter::ALREADY, $status, $seed.' '.$key);
            }
            $this->assertSame($before, $this->snapshot());
            $this->assertSame([], $this->warnings());
        }
    }

    public function test_second_run_writes_nothing(): void
    {
        $this->seedLive(fn ($v) => self::mysqlKeyOrder($v));
        (new Fresh)->run();
        $before = $this->snapshot();
        $this->logged = [];

        $again = new Fresh;
        $again->run();

        $this->assertCount(9, $again->results);
        foreach ($again->results as $key => $status) {
            $this->assertSame(GuardedFieldWriter::ALREADY, $status, $key);
            $this->assertContains(self::M.' '.$key.': already current', $again->lines);
        }
        $this->assertSame($before, $this->snapshot());
        $this->assertSame([], $this->warnings());
    }

    public function test_eloralintide_null_references_are_accepted(): void
    {
        $ids = $this->seedLive(fn ($v) => $v);
        EducationPost::whereKey($ids['eloralintide'])->update(['references' => null]);
        $this->assertNull(EducationPost::findOrFail($ids['eloralintide'])->references);

        $seeder = new Fresh;
        $seeder->run();

        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['eloralintide.references']);
        $this->assertSame($this->target('eloralintide', 'references'), EducationPost::findOrFail($ids['eloralintide'])->references);
    }

    public function test_guard_mismatch_is_loud_and_writes_nothing(): void
    {
        $ids = $this->seedLive(fn ($v) => self::mysqlKeyOrder($v));
        $orfo = EducationPost::findOrFail($ids['orforglipron']);
        $edited = $orfo->references;
        $edited[0]['description'] = 'Admin edit';
        $orfo->references = $edited;
        $orfo->saveQuietly();
        EducationalGuide::where('slug', self::CVR)->firstOrFail()->forceFill(['content' => '<p>Hand-edited guide body</p>'])->saveQuietly();
        Blog::where('slug', self::BLOG)->firstOrFail()->forceFill(['content' => '<p>Hand-edited blog body</p>'])->saveQuietly();

        $seeder = new Fresh;
        $seeder->run();

        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results['orforglipron.references']);
        $this->assertSame($edited, EducationPost::findOrFail($ids['orforglipron'])->references);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['orforglipron.human_use_subsections']);
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results['guide:'.self::CVR.'.content']);
        $this->assertSame('<p>Hand-edited guide body</p>', EducationalGuide::where('slug', self::CVR)->firstOrFail()->content);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['guide:'.self::CVR.'.reading_time']);
        $this->assertTrue($this->hasLog('warning', self::M.' orforglipron.references: guard mismatch — nothing written'));
        $this->assertTrue($this->hasLog('warning', self::M.' guide:'.self::CVR.'.content: guard mismatch — nothing written'));
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results['blog:'.self::BLOG.'.content']);
        $this->assertSame('<p>Hand-edited blog body</p>', Blog::where('slug', self::BLOG)->firstOrFail()->content);
        $this->assertTrue($this->hasLog('warning', self::M.' blog:'.self::BLOG.'.content: guard mismatch — nothing written'));
        $this->assertCount(3, $this->warnings());
    }

    public function test_missing_rows_are_left_alone_and_warned(): void
    {
        $ids = $this->seedLive(fn ($v) => $v);
        $elor = EducationPost::findOrFail($ids['eloralintide']);
        $elorCategory = ProductCategory::findOrFail($elor->product_category_id);
        $elor->delete();
        $elorCategory->delete();
        EducationalGuide::where('slug', self::CVR)->delete();
        Blog::where('slug', self::BLOG)->delete();
        $blogs = Blog::count();
        $posts = EducationPost::count();
        $guides = EducationalGuide::count();
        $categories = ProductCategory::count();

        $seeder = new Fresh;
        $seeder->run();

        $this->assertSame('post lookup missing', $seeder->results['eloralintide.human_use_subsections']);
        $this->assertSame('post lookup missing', $seeder->results['eloralintide.references']);
        $this->assertSame('guide lookup missing', $seeder->results['guide:'.self::CVR.'.content']);
        $this->assertTrue($this->hasLog('warning', self::M.' eloralintide.references: post lookup missing — nothing written'));
        $this->assertTrue($this->hasLog('warning', self::M.' guide:'.self::CVR.'.content: guide lookup missing — nothing written'));
        $this->assertSame('blog lookup missing', $seeder->results['blog:'.self::BLOG.'.content']);
        $this->assertTrue($this->hasLog('warning', self::M.' blog:'.self::BLOG.'.content: blog lookup missing — nothing written'));
        $this->assertSame([$posts, $guides, $categories, $blogs], [EducationPost::count(), EducationalGuide::count(), ProductCategory::count(), Blog::count()]);
        $this->assertNotContains('eloralintide', $seeder->transactions);
        $this->assertNotContains('blog:'.self::BLOG, $seeder->transactions);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['orforglipron.references']);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['guide:'.self::LEGALITY.'.content']);
    }

    public function test_duplicate_guide_slug_is_a_collision(): void
    {
        $this->seedLive(fn ($v) => $v);
        $dup = EducationalGuide::where('slug', self::LEGALITY)->firstOrFail()->replicate();
        $dup->slug = ' '.strtoupper(self::LEGALITY).' ';
        $dup->saveQuietly();

        $seeder = new Fresh;
        $seeder->run();

        $this->assertSame('guide lookup collision', $seeder->results['guide:'.self::LEGALITY.'.content']);
        $this->assertSame($this->old('guide:'.self::LEGALITY, 'content'), EducationalGuide::where('slug', self::LEGALITY)->firstOrFail()->content);
    }

    public function test_duplicate_blog_slug_is_a_collision(): void
    {
        $this->seedLive(fn ($v) => $v);
        $dup = Blog::where('slug', self::BLOG)->firstOrFail()->replicate();
        $dup->slug = ' '.strtoupper(self::BLOG).' ';
        $dup->saveQuietly();

        $seeder = new Fresh;
        $seeder->run();

        $this->assertSame('blog lookup collision', $seeder->results['blog:'.self::BLOG.'.content']);
        $this->assertTrue($this->hasLog('warning', self::M.' blog:'.self::BLOG.'.content: blog lookup collision — nothing written'));
        $this->assertSame($this->old('blog:'.self::BLOG, 'content'), Blog::where('slug', self::BLOG)->firstOrFail()->content);
    }

    public function test_no_other_post_guide_blog_or_legacy_row_is_touched(): void
    {
        $ids = $this->seedLive(fn ($v) => self::mysqlKeyOrder($v));
        $otherCategory = ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true, 'description' => 'T']);
        $other = EducationPost::create([
            'title' => 'Tirzepatide', 'slug' => 'tirzepatide-linked', 'product_category_id' => $otherCategory->id,
            'status' => 'published', 'show_in_encyclopedia' => true,
            'references' => $this->old('orforglipron', 'references'),
            'human_use_subsections' => $this->old('eloralintide', 'human_use_subsections'),
        ]);
        $legacy = EducationPost::create([
            'title' => 'Orforglipron legacy', 'slug' => 'orforglipron', 'product_category_id' => null,
            'status' => 'draft', 'show_in_encyclopedia' => false,
            'references' => $this->old('orforglipron', 'references'),
            'human_use_subsections' => $this->old('orforglipron', 'human_use_subsections'),
        ]);
        $keep = fn () => [
            'posts' => EducationPost::whereNotIn('id', array_values($ids))->orderBy('id')->get()->map->getAttributes()->all(),
            'categories' => ProductCategory::orderBy('id')->get()->map->getAttributes()->all(),
            'guides' => EducationalGuide::whereNotIn('slug', array_keys(Fresh::GUIDES))->orderBy('id')->get()->map->getAttributes()->all(),
            'blogs' => Blog::where('slug', '!=', self::BLOG)->orderBy('id')->get()->map->getAttributes()->all(),
            // The target blog: every column except content stays put.
            'blog' => collect(Blog::where('slug', self::BLOG)->firstOrFail()->getAttributes())->except(['content'])->all(),
        ];
        $before = $keep();
        $this->assertNotEmpty($before['guides']);
        $this->assertNotEmpty($before['blogs']);

        $seeder = new Fresh;
        $seeder->run();

        $this->assertSame($before, $keep());
        $this->assertSame($this->old('orforglipron', 'references'), $other->fresh()->references);
        $this->assertSame($this->old('orforglipron', 'references'), $legacy->fresh()->references);
        $this->assertSame($this->target('orforglipron', 'references'), EducationPost::findOrFail($ids['orforglipron'])->references);
        // Only the listed columns of the matched rows change.
        $orfo = EducationPost::findOrFail($ids['orforglipron']);
        $this->assertSame('KEEP-overview', $orfo->overview);
        $legality = EducationalGuide::where('slug', self::LEGALITY)->firstOrFail();
        $this->assertSame('KEEP-seo', $legality->seo_description);
        $this->assertSame($this->blogTarget(), Blog::where('slug', self::BLOG)->firstOrFail()->content);
    }

    public function test_full_chain_from_main_reaches_targets_with_no_warnings(): void
    {
        $fixture = json_decode((string) file_get_contents(base_path('tests/Fixtures/prod-drift-2026-10-29/main-chain-s0.json')), true);
        $ids = [];
        foreach ($fixture['posts'] as $slug => $fields) {
            $cat = $fixture['categories'][$slug];
            $category = ProductCategory::create(['name' => $cat['name'], 'slug' => $cat['slug'], 'is_active' => true, 'description' => 'CATEGORY-'.$slug]);
            if ($slug === 'orforglipron') {
                // The s0 fixture omits columns no 2026_10 payload touched;
                // main's profile seeds orforglipron human_use_subsections.
                $fields['human_use_subsections'] = $this->old('orforglipron', 'human_use_subsections');
            }
            $ids[$slug] = EducationPost::create(array_merge([
                'title' => $cat['name'], 'slug' => $slug.'-linked', 'product_category_id' => $category->id,
                'status' => 'published', 'show_in_encyclopedia' => true,
            ], $fields))->id;
        }
        $elorCategory = ProductCategory::create(['name' => 'Eloralintide', 'slug' => 'Eloralintide', 'is_active' => true, 'description' => 'CATEGORY-eloralintide']);
        $ids['eloralintide'] = EducationPost::create([
            'title' => 'Eloralintide', 'slug' => 'eloralintide-linked', 'product_category_id' => $elorCategory->id,
            'status' => 'published', 'show_in_encyclopedia' => true,
            'human_use_subsections' => $this->old('eloralintide', 'human_use_subsections'),
            'references' => [],
        ])->id;

        // Guides as main leaves them before 2026_10_29_100000.
        EducationalContentPublisher::sync();
        $policyGuide = EncyclopediaCompoundingPolicyOctober29Seeder::payload()['guides'][self::LEGALITY]['columns'];
        $attrs = [];
        foreach ($policyGuide as $column => $spec) {
            $attrs[$column] = $spec['old_values'][0];
        }
        EducationalGuide::where('slug', self::LEGALITY)->firstOrFail()->forceFill($attrs)->saveQuietly();
        EducationalGuide::where('slug', self::CVR)->firstOrFail()->forceFill([
            'content' => $this->old('guide:'.self::CVR, 'content'),
            'reading_time' => $this->old('guide:'.self::CVR, 'reading_time'),
        ])->saveQuietly();
        Blog::where('slug', self::BLOG)->firstOrFail()->forceFill(['content' => $this->old('blog:'.self::BLOG, 'content')])->saveQuietly();

        foreach ([
            EncyclopediaFieldUpdatesOctober22Seeder::class,
            EncyclopediaFieldReapplyOctober22Seeder::class,
            EncyclopediaOrfoRetaOctober22Seeder::class,
            EncyclopediaFaqAuditBatch1Seeder::class,
            EncyclopediaRetatrutideS505Seeder::class,
            EncyclopediaBpcCleanupAddendumSeeder::class,
            EncyclopediaHubCardsOctober22Seeder::class,
            EncyclopediaCompoundingPolicyOctober29Seeder::class,
            EncyclopediaRetatrutideOsaLitigationSeeder::class,
            EncyclopediaProdDriftReconcileOctober29Seeder::class,
        ] as $class) {
            (new $class)->run();
        }
        $this->reencodeAll();

        // Main's chain lands on this payload's guards.
        foreach (Fresh::COLUMNS as $slug => $fields) {
            foreach ($fields as $field) {
                $this->assertSame(
                    GuardedFieldWriter::comparable($this->old($slug, $field)),
                    GuardedFieldWriter::comparable(EducationPost::findOrFail($ids[$slug])->{$field}),
                    'main chain '.$slug.'.'.$field
                );
            }
        }
        foreach (Fresh::GUIDES as $slug => $fields) {
            foreach ($fields as $field) {
                $this->assertSame(
                    GuardedFieldWriter::comparable($this->old('guide:'.$slug, $field)),
                    GuardedFieldWriter::comparable(EducationalGuide::where('slug', $slug)->firstOrFail()->{$field}),
                    'main chain guide '.$slug.'.'.$field
                );
            }
        }
        $this->assertSame(self::BLOG_OLD_SHA, hash('sha256', (string) Blog::where('slug', self::BLOG)->firstOrFail()->content), 'main chain blog content');
        $this->logged = [];

        $seeder = new Fresh;
        $seeder->run();
        foreach ($seeder->results as $key => $status) {
            $this->assertSame(GuardedFieldWriter::UPDATED, $status, $key);
        }
        $this->assertArrayHasKey('blog:'.self::BLOG.'.content', $seeder->results);
        $this->assertSame([], $this->warnings());
    }

    public function test_fresh_database_framing_and_publisher_output_is_already_current(): void
    {
        ProductCategory::create(['name' => 'Eloralintide', 'slug' => 'Eloralintide', 'is_active' => true]);
        (new EncyclopediaFramingSeeder)->run();
        EducationalContentPublisher::sync();
        $this->reencodeAll();

        $seeder = new Fresh;
        $seeder->run();

        $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results['eloralintide.human_use_subsections']);
        $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results['eloralintide.references']);
        foreach (Fresh::GUIDES as $slug => $fields) {
            foreach ($fields as $field) {
                $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results['guide:'.$slug.'.'.$field], $slug.'.'.$field);
            }
        }
        $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results['blog:'.self::BLOG.'.content']);
        // orforglipron has no category here: left alone, loudly.
        $this->assertSame('post lookup missing', $seeder->results['orforglipron.references']);
        $this->assertSame([
            Fresh::LABEL.' orforglipron: missing (categories 0, linked posts 0, slug-matching posts 0). Stored categories: none. Stored linked posts: none. Stored slug-matching posts: none.',
            self::M.' orforglipron.human_use_subsections: post lookup missing — nothing written',
            self::M.' orforglipron.references: post lookup missing — nothing written',
        ], $this->warnings());
    }

    /**
     * Fresh databases only: earlier migrations sync the publisher, so the
     * legality guide already holds this branch's copy when 2026_10_29_100000
     * runs. 100000 then logs "old mismatch" for content and reading_time and
     * leaves them (production ran 100000 on 29 Oct, so it never sees this).
     */
    public function test_fresh_database_100000_leaves_the_newer_guide_copy_alone(): void
    {
        EducationalContentPublisher::sync();
        $before = EducationalGuide::where('slug', self::LEGALITY)->firstOrFail()->only(['content', 'reading_time']);
        $this->logged = [];

        $policy = new EncyclopediaCompoundingPolicyOctober29Seeder;
        $policy->run();

        $guideWarnings = array_values(array_filter($this->warnings(), fn ($w) => str_contains($w, 'guide:')));
        $this->assertCount(2, $guideWarnings);
        $this->assertStringContainsString('guide:'.self::LEGALITY.' content: old mismatch, column left unchanged', $guideWarnings[0]);
        $this->assertStringContainsString('guide:'.self::LEGALITY.' reading_time: old mismatch, column left unchanged', $guideWarnings[1]);
        $this->assertSame($before, EducationalGuide::where('slug', self::LEGALITY)->firstOrFail()->only(['content', 'reading_time']));

        $seeder = new Fresh;
        $seeder->run();
        $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results['guide:'.self::LEGALITY.'.content']);
        $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results['guide:'.self::LEGALITY.'.reading_time']);
    }

    public function test_rendered_pages_show_the_new_copy(): void
    {
        $this->seedLive(fn ($v) => self::mysqlKeyOrder($v));
        (new Fresh)->run();

        $legality = html_entity_decode((string) $this->get('/guides/'.self::LEGALITY)->assertOk()->getContent());
        $this->assertStringContainsString('91 FR 68526', $legality);
        $this->assertStringContainsString('13 November 2026', $legality);
        $this->assertStringContainsString('FDA-2026-P-13366', $legality);
        $this->assertStringContainsString('states may not follow the federal government', $legality);
        $cvr = html_entity_decode((string) $this->get('/guides/'.self::CVR)->assertOk()->getContent());
        $this->assertStringContainsString('91 FR 68526', $cvr);
        $this->assertStringContainsString('13 Nov 2026', $cvr);

        $orfo = html_entity_decode((string) $this->get('/encyclopedia/orforglipron')->assertOk()->getContent());
        $this->assertStringContainsString('ACHIEVE-4', $orfo);
        $this->assertStringContainsString('42815506', $orfo);
        $elor = html_entity_decode((string) $this->get('/encyclopedia/Eloralintide')->assertOk()->getContent());
        $this->assertStringContainsString('obstructive sleep apnea (OSA)', $elor);
        $this->assertStringContainsString('0000059478-26-000108', $elor);
        $this->assertStringContainsString('to start in Q4 2026', $elor);
        $this->assertStringContainsString('0000059478-26-000104', $elor);
        $this->assertStringContainsString('Submitted orforglipron for type 2 diabetes in the U.S. and Japan', $orfo);

        $blog = Blog::where('slug', self::BLOG)->firstOrFail();
        $page = $this->get('/blog/'.self::BLOG)->assertOk();
        $html = html_entity_decode((string) $page->getContent());
        $this->assertStringContainsString('91 FR 68526', $html);
        $this->assertStringContainsString('13 Nov 2026', $html);
        $this->assertStringNotContainsString('scheduled for publication', $html);
        $this->assertStringNotContainsString('public-inspection.federalregister.gov', $html);
        $page->assertSee('4 Min Read', false);

        // FAQ: markdown = PHP catalog = JSON-LD, unchanged by the content write.
        $entry = collect((new \ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null))->firstWhere('slug', self::BLOG);
        $this->assertSame($entry['faq'], $blog->seo_schema[0] ?? null);
        $this->assertCount(4, $entry['faq']['mainEntity']);
        $md = (string) file_get_contents(resource_path('content/educational/'.self::BLOG.'.md'));
        foreach ($entry['faq']['mainEntity'] as $entity) {
            $this->assertStringContainsString('### '.$entity['name'], $md);
            $this->assertStringContainsString($entity['acceptedAnswer']['text'], $md);
        }
        $page->assertSee((string) json_encode($blog->seo_schema[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), false);
    }

    public function test_new_copy_follows_the_content_rules(): void
    {
        $guideTargets = [];
        foreach (Fresh::GUIDES as $slug => $fields) {
            $guideTargets[$slug] = $this->guideTarget($slug, 'content');
        }
        $postText = json_encode(array_map(
            fn ($p) => array_map(fn ($c) => $c['target_value'], $p['columns']),
            $this->payload()['posts']
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $all = implode("\n", [...array_values($guideTargets), $postText, $this->blogTarget()]);
        $this->assertDoesNotMatchRegularExpression('/\d+(?:\.\d+)?\s*mg\b/i', $this->blogTarget());
        $this->assertDoesNotMatchRegularExpression('/%|percent/i', $this->blogTarget());
        $this->assertStringNotContainsString('tirzepatide 15', $postText);
        $this->assertStringNotContainsString('greater weight loss', $postText);
        foreach (['still forthcoming', 'scheduled for publication', 'scheduled for Federal Register publication', 'does not state a calendar closing date', 'legal to use', 'HHS/FDA'] as $needle) {
            if ($needle === 'legal to use') {
                // Only the existing negated disclaimer may use the phrase.
                $this->assertSame(1, substr_count(strtolower($guideTargets[self::CVR]), 'legal to use'));
                $this->assertStringContainsString('does not say that any compounded or RUO product is safe, effective or legal to use', $guideTargets[self::CVR]);
                $this->assertStringNotContainsStringIgnoringCase($needle, $postText);

                continue;
            }
            $this->assertStringNotContainsStringIgnoringCase($needle, $all, $needle);
        }
        $this->assertStringNotContainsStringIgnoringCase('forthcoming', $guideTargets[self::LEGALITY]);
        $this->assertStringNotContainsStringIgnoringCase('forthcoming', $guideTargets[self::CVR]);

        // No mg doses and no percentages in the new post copy or CVR guide.
        $this->assertDoesNotMatchRegularExpression('/\d+(?:\.\d+)?\s*mg\b/i', $postText);
        $this->assertDoesNotMatchRegularExpression('/%|percent/i', $postText);
        $this->assertDoesNotMatchRegularExpression('/%|percent/i', $guideTargets[self::CVR]);

        // Semax 503A context is on hold: no semax line may change.
        foreach (Fresh::GUIDES as $slug => $fields) {
            $old = $this->semaxLines($this->old('guide:'.$slug, 'content'));
            $new = $this->semaxLines($guideTargets[$slug]);
            $this->assertSame($old, $new, $slug);
        }
        $this->assertStringNotContainsStringIgnoringCase('semax', $postText);

        // Petition is described as a petition, and sources are labelled.
        $this->assertStringContainsString('citizen petition', $guideTargets[self::LEGALITY]);
        $this->assertStringContainsString('not litigation', $guideTargets[self::LEGALITY]);
        $this->assertStringContainsString('[company-reported]', $postText);
        $this->assertStringContainsString('[developer-affiliated]', $postText);
    }

    public function test_seeder_has_no_bare_stream_writes(): void
    {
        $source = (string) file_get_contents(database_path('seeders/EncyclopediaFreshnessOctober31Seeder.php'));
        $this->assertDoesNotMatchRegularExpression('/\bSTD(ERR|OUT)\b/', $source);
        $this->assertStringContainsString('GuardedFieldWriter', $source);
        $this->assertStringContainsString('EncyclopediaPostMatch::find', $source);
        $this->assertStringContainsString('saveQuietly', $source);
        $this->assertStringContainsString('DB::transaction', $source);
    }

    /**
     * Live production (31 Oct 2026): every column at old_values[0].
     *
     * @param  callable(mixed): mixed  $encode
     * @return array<string, int>
     */
    private function seedLive(callable $encode): array
    {
        $ids = [];
        foreach (Fresh::COLUMNS as $slug => $fields) {
            $category = ProductCategory::create(['name' => self::CATEGORY[$slug], 'slug' => self::CATEGORY[$slug], 'is_active' => true, 'description' => 'CATEGORY-'.$slug]);
            $attrs = ['title' => self::CATEGORY[$slug], 'slug' => $slug.'-linked', 'product_category_id' => $category->id,
                'status' => 'published', 'show_in_encyclopedia' => true, 'overview' => 'KEEP-overview'];
            foreach ($fields as $field) {
                $attrs[$field] = $encode($this->old($slug, $field));
            }
            $ids[$slug] = EducationPost::create($attrs)->id;
        }
        if (! EducationalGuide::query()->exists()) {
            EducationalContentPublisher::sync();
        }
        foreach (Fresh::GUIDES as $slug => $fields) {
            $attrs = ['seo_description' => 'KEEP-seo'];
            foreach ($fields as $field) {
                $attrs[$field] = $this->old('guide:'.$slug, $field);
            }
            EducationalGuide::where('slug', $slug)->firstOrFail()->forceFill($attrs)->saveQuietly();
        }
        Blog::where('slug', self::BLOG)->firstOrFail()->forceFill(['content' => $this->old('blog:'.self::BLOG, 'content')])->saveQuietly();

        return $ids;
    }

    private function refreshTables(): void
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        return [
            'posts' => EducationPost::orderBy('id')->get()->map->getAttributes()->all(),
            'guides' => EducationalGuide::orderBy('id')->get()->map->getAttributes()->all(),
            'blogs' => Blog::orderBy('id')->get()->map->getAttributes()->all(),
        ];
    }

    private function reencodeAll(): void
    {
        foreach ([EducationPost::class, EducationalGuide::class] as $model) {
            foreach ($model::query()->get() as $row) {
                foreach ($row->getCasts() as $column => $cast) {
                    if (in_array($cast, ['array', 'json'], true) && is_array($row->{$column})) {
                        $row->{$column} = self::mysqlKeyOrder($row->{$column});
                    }
                }
                if ($row->isDirty()) {
                    $row->saveQuietly();
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return json_decode((string) file_get_contents(database_path(Fresh::PAYLOAD_PATH)), true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function specs(): array
    {
        $out = [];
        foreach ($this->payload()['posts'] as $slug => $p) {
            foreach ($p['columns'] as $field => $spec) {
                $out[$slug.'.'.$field] = $spec;
            }
        }
        foreach ($this->payload()['guides'] as $slug => $g) {
            foreach ($g['columns'] as $field => $spec) {
                $out['guide:'.$slug.'.'.$field] = $spec;
            }
        }
        foreach ($this->payload()['blogs'] as $slug => $b) {
            foreach ($b['columns'] as $field => $spec) {
                $out['blog:'.$slug.'.'.$field] = $spec;
            }
        }

        return $out;
    }

    private function target(string $slug, string $field): mixed
    {
        return $this->payload()['posts'][$slug]['columns'][$field]['target_value'];
    }

    private function guideTarget(string $slug, string $field): mixed
    {
        return $this->payload()['guides'][$slug]['columns'][$field]['target_value'];
    }

    private function blogTarget(): string
    {
        return $this->payload()['blogs'][self::BLOG]['columns']['content']['target_value'];
    }

    private function old(string $key, string $field): mixed
    {
        return $this->specs()[$key.'.'.$field]['old_values'][0];
    }

    /**
     * @return list<string>
     */
    private function semaxLines(string $html): array
    {
        // Sentence-level, so a sentence that merely lists semax among the
        // six names is compared on its own.
        $lines = preg_split('/(?<=<\/p>|<\/li>|<\/tr>|<\/td>|<\/h2>|<\/h3>)|(?<=[.;])\s+/', $html) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn ($l) => stripos($l, 'semax') !== false));
    }

    public static function mysqlKeyOrder(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $value = array_map(fn ($v) => self::mysqlKeyOrder($v), $value);
        if (! array_is_list($value)) {
            uksort($value, fn ($a, $b) => [strlen((string) $a), (string) $a] <=> [strlen((string) $b), (string) $b]);
        }

        return $value;
    }

    public static function shuffleKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $value = array_map(fn ($v) => self::shuffleKeys($v), $value);
        if (array_is_list($value)) {
            return $value;
        }
        $keys = array_keys($value);
        for ($i = count($keys) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$keys[$i], $keys[$j]] = [$keys[$j], $keys[$i]];
        }
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $value[$k];
        }

        return $out;
    }

    private function hasLog(string $level, string $message): bool
    {
        foreach ($this->logged as $entry) {
            if ($entry['level'] === $level && $entry['message'] === $message) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function warnings(): array
    {
        return array_values(array_map(fn ($e) => $e['message'], array_filter($this->logged, fn ($e) => $e['level'] === 'warning')));
    }
}
