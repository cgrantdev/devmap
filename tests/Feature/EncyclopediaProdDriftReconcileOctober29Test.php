<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\GuardedFieldWriter;
use Database\Seeders\EncyclopediaBpcCleanupAddendumSeeder;
use Database\Seeders\EncyclopediaFaqAuditBatch1Seeder;
use Database\Seeders\EncyclopediaFieldReapplyOctober22Seeder;
use Database\Seeders\EncyclopediaFieldUpdatesOctober22Seeder;
use Database\Seeders\EncyclopediaHubCardsOctober22Seeder;
use Database\Seeders\EncyclopediaOrfoRetaOctober22Seeder;
use Database\Seeders\EncyclopediaProdDriftReconcileOctober29Seeder;
use Database\Seeders\EncyclopediaRetatrutideOsaLitigationSeeder;
use Database\Seeders\EncyclopediaRetatrutideS505Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EncyclopediaProdDriftReconcileOctober29Test extends TestCase
{
    use RefreshDatabase;

    private const SHA = 'ca00711567409031d36b8aa32b6ffb21a0a20ad82926d869a4737586f4581153';

    private const M = '2026_10_29_120000';

    /** @var list<array{level: string, message: string}> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e): void {
            $this->logged[] = ['level' => $e->level, 'message' => $e->message];
        });
    }

    public function test_migration_file_and_payload_sha_are_pinned(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_29_120000_reconcile_encyclopedia_prod_drift_october_29.php'));
        $this->assertStringContainsString('EncyclopediaProdDriftReconcileOctober29Seeder', $migration);
        $this->assertSame(self::SHA, EncyclopediaProdDriftReconcileOctober29Seeder::PAYLOAD_SHA256);
        $this->assertSame(self::SHA, hash_file('sha256', database_path(EncyclopediaProdDriftReconcileOctober29Seeder::PAYLOAD_PATH)));

        $payload = $this->payload();
        $this->assertSame(array_keys(EncyclopediaProdDriftReconcileOctober29Seeder::COLUMNS), array_keys($payload['posts']));
        $this->assertArrayNotHasKey('regulatory_subsections', $payload['posts']['bpc-157']['columns']);
        $this->assertArrayNotHasKey('regulatory_subsections', $payload['posts']['retatrutide']['columns']);
        foreach ($payload['posts'] as $slug => $post) {
            foreach ($post['columns'] as $field => $spec) {
                $this->assertStringContainsString('live 29 Oct 2026', $spec['labels'][0], $slug.'.'.$field);
                foreach ($spec['old_values'] as $old) {
                    $this->assertNotEquals(GuardedFieldWriter::normalize($spec['target_value']), GuardedFieldWriter::normalize($old));
                }
            }
        }
    }

    public function test_live_values_are_reconciled_to_main_targets_and_log_updated(): void
    {
        $ids = $this->seedFixture('live.json');
        $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
        $seeder->run();

        foreach (EncyclopediaProdDriftReconcileOctober29Seeder::COLUMNS as $slug => $fields) {
            $post = EducationPost::findOrFail($ids[$slug]);
            foreach ($fields as $field) {
                $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results[$slug.'.'.$field], $slug.'.'.$field);
                $this->assertSame($this->target($slug, $field), $post->{$field}, $slug.'.'.$field);
                $this->assertContains(self::M.' '.$slug.'.'.$field.': updated', $seeder->lines);
                $this->assertTrue($this->hasLog('info', self::M.' '.$slug.'.'.$field.': updated'), $slug.'.'.$field);
            }
        }
        $this->assertSame([], $this->warnings());
        $this->assertSame(array_keys(EncyclopediaProdDriftReconcileOctober29Seeder::COLUMNS), $seeder->transactions);
    }

    public function test_live_values_after_110000_reach_the_same_targets(): void
    {
        $ids = $this->seedFixture('live.json');
        (new EncyclopediaRetatrutideOsaLitigationSeeder)->run();

        $reta = EducationPost::findOrFail($ids['retatrutide']);
        $osa = json_decode((string) file_get_contents(database_path(EncyclopediaRetatrutideOsaLitigationSeeder::PAYLOAD_PATH)), true)['columns'];
        $this->assertEquals($osa['references']['target_values'][0], $reta->references);
        $this->assertEquals($osa['human_use_subsections']['target_values'][0], $reta->human_use_subsections);

        $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
        $seeder->run();
        $reta->refresh();

        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['retatrutide.references']);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['retatrutide.human_use_subsections']);
        $this->assertEquals($osa['references']['target_values'][1], $reta->references);
        $this->assertEquals($osa['human_use_subsections']['target_values'][1], $reta->human_use_subsections);
        $this->assertEquals($osa['regulatory_subsections']['target_values'][0], $reta->regulatory_subsections);
        $this->assertSame([], $this->warnings());
    }

    public function test_second_run_is_a_no_op(): void
    {
        $ids = $this->seedFixture('live.json');
        (new EncyclopediaProdDriftReconcileOctober29Seeder)->run();
        $before = EducationPost::whereIn('id', array_values($ids))->orderBy('id')->get()->map->getAttributes()->all();
        $categories = ProductCategory::orderBy('id')->get()->map->getAttributes()->all();
        $this->logged = [];

        $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
        $seeder->run();

        foreach ($seeder->results as $key => $status) {
            $this->assertSame(GuardedFieldWriter::ALREADY, $status, $key);
            $this->assertContains(self::M.' '.$key.': already current', $seeder->lines);
        }
        $this->assertCount(12, $seeder->results);
        $this->assertSame($before, EducationPost::whereIn('id', array_values($ids))->orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame($categories, ProductCategory::orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame([], $this->warnings());
    }

    public function test_guard_mismatch_skips_the_column_and_logs_a_warning(): void
    {
        $ids = $this->seedFixture('live.json');
        $ghk = EducationPost::findOrFail($ids['ghk-cu']);
        $edited = $ghk->references;
        $edited[0]['description'] = 'Admin-edited description.';
        $ghk->references = $edited;
        $ghk->saveQuietly();
        $cagri = EducationPost::findOrFail($ids['cagrilintide']);
        $cagri->conclusion = $cagri->conclusion."\n\nAdmin addition.";
        $cagri->saveQuietly();

        $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
        $seeder->run();

        $line = self::M.' ghk-cu.references: guard mismatch — nothing written';
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results['ghk-cu.references']);
        $this->assertTrue($this->hasLog('warning', $line));
        $this->assertTrue($this->hasLog('warning', self::M.' cagrilintide.conclusion: guard mismatch — nothing written'));
        $this->assertEquals($edited, EducationPost::findOrFail($ids['ghk-cu'])->references);
        $this->assertStringEndsWith('Admin addition.', EducationPost::findOrFail($ids['cagrilintide'])->conclusion);

        // The other columns of the same posts are still reconciled.
        $this->assertSame($this->target('ghk-cu', 'regulatory_subsections'), EducationPost::findOrFail($ids['ghk-cu'])->regulatory_subsections);
        $this->assertSame($this->target('cagrilintide', 'overview'), EducationPost::findOrFail($ids['cagrilintide'])->overview);
        $this->assertCount(2, $this->warnings());
    }

    public function test_other_posts_and_legacy_rows_are_untouched(): void
    {
        $ids = $this->seedFixture('live.json');
        $otherCategory = ProductCategory::create(['name' => 'TB-500', 'slug' => 'tb-500', 'is_active' => true, 'description' => 'TB category']);
        $other = EducationPost::create([
            'title' => 'TB-500', 'slug' => 'tb-500', 'product_category_id' => $otherCategory->id,
            'status' => 'published', 'show_in_encyclopedia' => true,
            'references' => $this->live('ghk-cu', 'references'),
            'overview' => $this->live('cagrilintide', 'overview'),
        ]);
        $legacy = EducationPost::create([
            'title' => 'GHK-Cu legacy', 'slug' => 'ghk-cu', 'product_category_id' => null,
            'status' => 'draft', 'show_in_encyclopedia' => false,
            'references' => $this->live('ghk-cu', 'references'),
            'regulatory_subsections' => $this->live('ghk-cu', 'regulatory_subsections'),
        ]);
        $otherBefore = $other->fresh()->getAttributes();
        $legacyBefore = $legacy->fresh()->getAttributes();
        $categoriesBefore = ProductCategory::orderBy('id')->get()->map->getAttributes()->all();

        (new EncyclopediaProdDriftReconcileOctober29Seeder)->run();

        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertSame($legacyBefore, $legacy->fresh()->getAttributes());
        // Post description is non-empty, so no category description changes.
        $this->assertSame($categoriesBefore, ProductCategory::orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame($this->target('ghk-cu', 'references'), EducationPost::findOrFail($ids['ghk-cu'])->references);
    }

    public function test_cagrilintide_description_falls_back_to_the_category_column(): void
    {
        $ids = $this->seedFixture('live.json');
        $post = EducationPost::findOrFail($ids['cagrilintide']);
        $post->description = null;
        $post->saveQuietly();
        $category = ProductCategory::findOrFail($post->product_category_id);
        $category->description = $this->live('cagrilintide', 'description');
        $category->saveQuietly();

        $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
        $seeder->run();

        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['cagrilintide.description']);
        $this->assertSame($this->target('cagrilintide', 'description'), $category->fresh()->description);
        $this->assertNull($post->fresh()->description);
    }

    public function test_lookup_collision_writes_nothing_and_warns(): void
    {
        $ids = $this->seedFixture('live.json');
        $ghk = EducationPost::findOrFail($ids['ghk-cu']);
        // A second category whose slug matches after LOWER(TRIM()).
        ProductCategory::create(['name' => 'GHK-Cu duplicate', 'slug' => ' GHK-CU ', 'is_active' => false, 'description' => 'dup']);
        $before = $ghk->fresh()->getAttributes();

        $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
        $seeder->run();

        $this->assertSame($before, $ghk->fresh()->getAttributes());
        $this->assertSame('post lookup collision', $seeder->results['ghk-cu.references']);
        $this->assertTrue($this->hasLog('warning', self::M.' ghk-cu.references: post lookup collision — nothing written'));
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['retatrutide.references']);
        $this->assertNotContains('ghk-cu', $seeder->transactions);
    }

    public function test_full_chain_from_main_through_120000_gives_main_values(): void
    {
        $ids = $this->seedFixture('main-chain-s0.json');
        foreach ([
            EncyclopediaFieldUpdatesOctober22Seeder::class,
            EncyclopediaFieldReapplyOctober22Seeder::class,
            EncyclopediaOrfoRetaOctober22Seeder::class,
            EncyclopediaFaqAuditBatch1Seeder::class,
            EncyclopediaRetatrutideS505Seeder::class,
            EncyclopediaBpcCleanupAddendumSeeder::class,
            EncyclopediaHubCardsOctober22Seeder::class,
            EncyclopediaRetatrutideOsaLitigationSeeder::class,
        ] as $class) {
            (new $class)->run();
        }
        $this->logged = [];

        $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
        $seeder->run();

        foreach (EncyclopediaProdDriftReconcileOctober29Seeder::COLUMNS as $slug => $fields) {
            $post = EducationPost::findOrFail($ids[$slug]);
            foreach ($fields as $field) {
                $this->assertSame(GuardedFieldWriter::ALREADY, $seeder->results[$slug.'.'.$field], $slug.'.'.$field);
                $this->assertEquals($this->target($slug, $field), $post->{$field}, $slug.'.'.$field);
            }
        }
        $this->assertSame([], $this->warnings());
    }

    public function test_writer_logs_each_outcome(): void
    {
        $category = ProductCategory::create(['name' => 'X', 'slug' => 'x', 'is_active' => true, 'description' => "old\u{00A0}text \r\n"]);
        $writer = new GuardedFieldWriter('2099_01_01_000000');

        $this->assertSame(GuardedFieldWriter::UPDATED, $writer->apply($category, 'x', 'description', 'new text', ['old text']));
        $this->assertSame('new text', $category->description);
        $this->assertSame(GuardedFieldWriter::ALREADY, $writer->apply($category, 'x', 'description', "new text\n", ['old text']));
        $this->assertSame(GuardedFieldWriter::MISMATCH, $writer->apply($category, 'x', 'description', 'other', ['old text']));
        $this->assertSame('new text', $category->description);

        $this->assertSame([
            '2099_01_01_000000 x.description: updated',
            '2099_01_01_000000 x.description: already current',
            '2099_01_01_000000 x.description: guard mismatch — nothing written',
        ], $writer->lines);
        $this->assertTrue($this->hasLog('info', '2099_01_01_000000 x.description: updated'));
        $this->assertTrue($this->hasLog('warning', '2099_01_01_000000 x.description: guard mismatch — nothing written'));
        $this->assertFalse($category->fresh()->description === 'new text', 'apply() must not save');
    }

    /**
     * @return array<string, int> slug => education post id
     */
    private function seedFixture(string $file): array
    {
        $fixture = json_decode((string) file_get_contents(base_path('tests/Fixtures/prod-drift-2026-10-29/'.$file)), true);
        $ids = [];
        foreach ($fixture['posts'] as $slug => $fields) {
            $cat = $fixture['categories'][$slug] ?? ['name' => $slug, 'slug' => $slug];
            $category = ProductCategory::create([
                'name' => $cat['name'], 'slug' => $cat['slug'], 'is_active' => true, 'description' => 'CATEGORY-'.$slug,
            ]);
            $ids[$slug] = EducationPost::create(array_merge([
                'title' => $cat['name'], 'slug' => $slug.'-linked', 'product_category_id' => $category->id,
                'status' => 'published', 'show_in_encyclopedia' => true,
            ], $fields))->id;
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return json_decode((string) file_get_contents(database_path(EncyclopediaProdDriftReconcileOctober29Seeder::PAYLOAD_PATH)), true);
    }

    private function target(string $slug, string $field): mixed
    {
        return $this->payload()['posts'][$slug]['columns'][$field]['target_value'];
    }

    private function live(string $slug, string $field): mixed
    {
        $fixture = json_decode((string) file_get_contents(base_path('tests/Fixtures/prod-drift-2026-10-29/live.json')), true);

        return $fixture['posts'][$slug][$field];
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
        return array_values(array_map(
            fn (array $e) => $e['message'],
            array_filter($this->logged, fn (array $e) => $e['level'] === 'warning' && ! str_starts_with($e['message'], 'diagnostic'))
        ));
    }
}
