<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\GuardedFieldWriter;
use App\Support\RetatrutideTirzepatideCompareCopy;
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

    private const SHA = 'c5c52c29c842fcf7989ecb46a7316e98df15521e202d38db2f2d1cd157aa960f';

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
                    $this->assertNotEquals(GuardedFieldWriter::comparable($spec['target_value']), GuardedFieldWriter::comparable($old));
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

    public function test_prod_110000_merged_text_is_corrected_to_20_october(): void
    {
        $prod = json_decode((string) file_get_contents(base_path('tests/Fixtures/prod-drift-2026-10-29/live-after-110000.json')), true)['posts']['retatrutide'];
        $columns = $this->payload()['posts']['retatrutide']['columns'];
        $variants = ['prod 29 Oct 09:35' => $prod];
        foreach (['references', 'human_use_subsections'] as $field) {
            $i = $this->labelIndex($field, 'main chain final before the TRIUMPH-OSA date fix');
            $variants['pre-fix main chain'][$field] = $columns[$field]['old_values'][$i];
        }

        $ids = $this->seedFixture('live.json');
        foreach ($variants as $name => $values) {
            $reta = EducationPost::findOrFail($ids['retatrutide']);
            $this->assertNotSame([], $this->osaDateHits($values['references']), $name);
            $this->assertNotSame([], $this->osaDateHits($values['human_use_subsections']), $name);
            $reta->references = $values['references'];
            $reta->human_use_subsections = $values['human_use_subsections'];
            $reta->saveQuietly();
            $this->logged = [];

            $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
            $seeder->run();
            $reta->refresh();

            foreach (['references', 'human_use_subsections'] as $field) {
                $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['retatrutide.'.$field], $name.' '.$field);
                $this->assertTrue($this->hasLog('info', self::M.' retatrutide.'.$field.': updated'), $name.' '.$field);
                $this->assertEquals($this->target('retatrutide', $field), $reta->{$field}, $name.' '.$field);
                $this->assertSame([], $this->osaDateHits($reta->{$field}), $name.' '.$field);
            }
            $this->assertSame([], $this->warnings(), $name);

            $subsection = collect($reta->human_use_subsections)->firstWhere('title', 'Obstructive sleep apnea trials (NEJM, 20 Oct 2026) — developer-affiliated (Lilly-funded)');
            $this->assertNotNull($subsection, $name);
            $this->assertStringStartsWith('On 20 October 2026, The New England Journal of Medicine published online the TRIUMPH-1-OSA and TRIUMPH-2-OSA results', $subsection['entries'][0]['value']);
            $reference = collect($reta->references)->first(fn ($r) => str_contains((string) ($r['citation'] ?? ''), '10.1056/NEJMoa2611477'));
            $this->assertSame('N Engl J Med. Published online 20 October 2026. DOI 10.1056/NEJMoa2611477. PMID 42904878. ClinicalTrials.gov NCT05929066, NCT05929079.', $reference['citation']);
        }
    }

    public function test_mysql_json_key_order_is_not_a_guard_mismatch(): void
    {
        $ids = $this->seedFixture('live.json');
        (new EncyclopediaProdDriftReconcileOctober29Seeder)->run();

        // Production's MySQL JSON columns return object keys shortest-first
        // (links, title, authors, citation, description), not payload order.
        foreach (EncyclopediaProdDriftReconcileOctober29Seeder::COLUMNS as $slug => $fields) {
            $post = EducationPost::findOrFail($ids[$slug]);
            foreach ($fields as $field) {
                if (is_array($post->{$field})) {
                    $post->{$field} = $this->mysqlKeyOrder($post->{$field});
                }
            }
            $post->saveQuietly();
        }
        $reordered = EducationPost::findOrFail($ids['retatrutide'])->references;
        $targetRefs = $this->target('retatrutide', 'references');
        $this->assertNotSame(array_keys(end($targetRefs)), array_keys(end($reordered)));
        $this->logged = [];

        $seeder = new EncyclopediaProdDriftReconcileOctober29Seeder;
        $seeder->run();

        $this->assertCount(12, $seeder->results);
        foreach ($seeder->results as $key => $status) {
            $this->assertSame(GuardedFieldWriter::ALREADY, $status, $key);
        }
        $this->assertSame([], $this->warnings());
        $this->assertSame($reordered, EducationPost::findOrFail($ids['retatrutide'])->references);
    }

    public function test_date_fix_changes_only_the_triumph_osa_dates(): void
    {
        $prod = json_decode((string) file_get_contents(base_path('tests/Fixtures/prod-drift-2026-10-29/live-after-110000.json')), true)['posts']['retatrutide'];
        $osa = json_decode((string) file_get_contents(database_path(EncyclopediaRetatrutideOsaLitigationSeeder::PAYLOAD_PATH)), true)['columns'];
        $swap = [
            'Obstructive sleep apnea trials (NEJM, 21 Oct 2026)' => 'Obstructive sleep apnea trials (NEJM, 20 Oct 2026)',
            'On 21 October 2026, The New England Journal of Medicine published the' => 'On 20 October 2026, The New England Journal of Medicine published online the',
            'N Engl J Med. Published 21 October 2026.' => 'N Engl J Med. Published online 20 October 2026.',
        ];
        foreach (['references', 'human_use_subsections'] as $field) {
            $fixed = json_decode(strtr(json_encode($prod[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $swap), true);
            // 110000 on a fresh DB (its target_values[0]) == prod's merged text with only the three date strings changed.
            $this->assertEquals($fixed, GuardedFieldWriter::normalize($osa[$field]['target_values'][0]), $field);
            $this->assertEquals($osa[$field]['target_values'][1], $this->target('retatrutide', $field), $field);
            $this->assertSame([], $this->osaDateHits($osa[$field]['target_values'][0]), $field);
            $this->assertSame([], $this->osaDateHits($osa[$field]['target_values'][1]), $field);
        }
        $this->assertSame([], $this->osaDateHits($osa['human_use_subsections']['insert']));
        $this->assertSame([], $this->osaDateHits($osa['references']['append']));

        $copy = (string) file_get_contents(app_path('Support/RetatrutideTirzepatideCompareCopy.php'));
        $this->assertDoesNotMatchRegularExpression('/\b21 Oct(ober)?\b|October 21/', $copy);
        $this->assertStringContainsString('(published online'."\n".' * 20 October 2026) and the 20 December 2024 FDA announcement.', $copy);
        $this->assertStringContainsString('Published online October 20, 2026.', json_encode(RetatrutideTirzepatideCompareCopy::evidence(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
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

    public function test_stream_writes_are_guarded_for_non_cli_callers(): void
    {
        foreach (['app/Support/GuardedFieldWriter.php', 'app/Support/EncyclopediaPostMatch.php'] as $file) {
            $source = (string) file_get_contents(base_path($file));
            $this->assertDoesNotMatchRegularExpression('/emit\(STD(ERR|OUT)/', $source, $file);
            $this->assertStringContainsString("defined('STDERR') ? STDERR : null", $source, $file);
            $this->assertStringContainsString("defined('STDOUT') ? STDOUT : null", $source, $file);
        }
    }

    private function labelIndex(string $field, string $needle): int
    {
        foreach ($this->payload()['posts']['retatrutide']['columns'][$field]['labels'] as $i => $label) {
            if (str_contains($label, $needle)) {
                return $i;
            }
        }
        $this->fail('label not found: '.$needle);
    }

    /**
     * 21 October dates attached to TRIUMPH-OSA (NEJM 10.1056/NEJMoa2611477):
     * the OSA human-use subsection and the paper's reference entry.
     *
     * @return list<string>
     */
    private function osaDateHits(mixed $value): array
    {
        $items = is_array($value) && array_is_list($value) ? $value : [$value];
        $hits = [];
        foreach ($items as $item) {
            $blob = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            $isOsa = str_contains($blob, 'Obstructive sleep apnea trials') || str_contains($blob, 'NEJMoa2611477');
            if ($isOsa && preg_match_all('/\b21 Oct(?:ober)?\b|October 21/', $blob, $m)) {
                array_push($hits, ...$m[0]);
            }
        }

        return $hits;
    }

    private function mysqlKeyOrder(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $value = array_map(fn ($v) => $this->mysqlKeyOrder($v), $value);
        if (! array_is_list($value)) {
            uksort($value, fn ($a, $b) => [strlen((string) $a), (string) $a] <=> [strlen((string) $b), (string) $b]);
        }

        return $value;
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
