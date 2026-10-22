<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaFieldReapplyOctober22Seeder;
use Database\Seeders\EncyclopediaFieldUpdatesOctober22Seeder;
use Database\Seeders\EncyclopediaOrfoRetaOctober22Seeder;
use Database\Seeders\EncyclopediaRetatrutideS505Seeder;
use Database\Seeders\RetatrutideEncyclopediaFaqSeeder;
use Database\Seeders\RetatrutideTimelineLiteracySeeder;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class RetatrutideSection505FollowupTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD_SHA256 = '07bbde75fdb4529ee92b747e0960a643aaa3a26663683973cf1e4411f3769dbc';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_live_fixture_writes_six_columns_and_skips_the_pre_140000_note(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_22_160000_retatrutide_section_505_followup.php'));
        $this->assertStringContainsString('EncyclopediaRetatrutideS505Seeder', $migration);
        $this->assertStringContainsString('Previous encyclopedia field values are not restored.', $migration);
        $this->assertSame(self::PAYLOAD_SHA256, hash_file('sha256', $this->payloadPath()));

        $post = $this->seedColumns($this->stateA());
        $seeder = new EncyclopediaRetatrutideS505Seeder;
        $seeder->run();

        $post->refresh();
        $this->assertSame(EncyclopediaRetatrutideS505Seeder::UPDATED, $seeder->report);
        foreach (['regulatory_subsections', 'faqs', 'key_points', 'conclusion', 'overview', 'description'] as $column) {
            $this->assertSame(EncyclopediaRetatrutideS505Seeder::UPDATED, $seeder->columnReport[$column], $column);
            $this->assertSame($this->target($column, 0), $post->{$column}, $column);
        }
        $this->assertSame(EncyclopediaRetatrutideS505Seeder::PRE140, $seeder->columnReport['regulatory_important_note']);
        $this->assertSame($this->columns()['regulatory_important_note']['pre_140000_values'][0], $post->regulatory_important_note);
        $this->assertContains(EncyclopediaRetatrutideS505Seeder::PRE140_LOG, $seeder->log);
        $this->assertFalse($this->logsMismatch($seeder->log));
        $this->assertStringContainsString('categories=1 linked_posts=1', implode("\n", $seeder->log));
    }

    public function test_fixture_after_120000_and_130000_uses_the_matching_candidates(): void
    {
        $columns = $this->stateA();
        $columns['regulatory_subsections'] = $this->columns()['regulatory_subsections']['old_values'][1];
        $columns['key_points'] = $this->columns()['key_points']['old_values'][1];
        $columns['regulatory_important_note'] = $this->columns()['regulatory_important_note']['pre_140000_values'][1];
        $post = $this->seedColumns($columns);

        $seeder = new EncyclopediaRetatrutideS505Seeder;
        $seeder->run();
        $post->refresh();

        $this->assertSame($this->target('regulatory_subsections', 1), $post->regulatory_subsections);
        $this->assertSame($this->target('key_points', 1), $post->key_points);
        $this->assertSame($this->target('faqs', 0), $post->faqs);
        $this->assertSame($this->target('conclusion', 0), $post->conclusion);
        $this->assertSame($this->target('overview', 0), $post->overview);
        $this->assertSame($this->target('description', 0), $post->description);
        $this->assertSame(EncyclopediaRetatrutideS505Seeder::PRE140, $seeder->columnReport['regulatory_important_note']);
        $this->assertSame($columns['regulatory_important_note'], $post->regulatory_important_note);
        $this->assertContains(EncyclopediaRetatrutideS505Seeder::PRE140_LOG, $seeder->log);
    }

    public function test_fixture_after_120000_130000_and_140000_writes_all_seven_columns(): void
    {
        $post = $this->seedColumns($this->stateAfter140());
        $seeder = new EncyclopediaRetatrutideS505Seeder;
        $seeder->run();
        $post->refresh();

        $this->assertSame($this->target('regulatory_subsections', 3), $post->regulatory_subsections);
        $this->assertSame($this->target('key_points', 3), $post->key_points);
        $this->assertSame($this->target('faqs', 0), $post->faqs);
        $this->assertSame($this->target('conclusion', 0), $post->conclusion);
        $this->assertSame($this->target('overview', 0), $post->overview);
        $this->assertSame($this->target('description', 0), $post->description);
        $this->assertSame($this->target('regulatory_important_note', 1), $post->regulatory_important_note);
        foreach ($this->columns() as $column => $spec) {
            $this->assertSame(EncyclopediaRetatrutideS505Seeder::UPDATED, $seeder->columnReport[$column], $column);
        }
        $this->assertStringContainsString(
            'Lilly said on 29 September 2026 that it plans to submit a Biologics License Application (BLA) in Q1 2027.',
            $post->regulatory_important_note
        );
    }

    public function test_a_second_run_writes_nothing(): void
    {
        $post = $this->seedColumns($this->stateAfter140());
        (new EncyclopediaRetatrutideS505Seeder)->run();
        $post->refresh();
        $before = $post->getAttributes();

        $again = new EncyclopediaRetatrutideS505Seeder;
        $again->run();

        $this->assertSame($before, $post->fresh()->getAttributes());
        foreach ($this->columns() as $column => $spec) {
            $this->assertSame(EncyclopediaRetatrutideS505Seeder::ALREADY, $again->columnReport[$column], $column);
        }
        $this->assertTrue($this->logContains($again->log, 'already current'));
        $this->assertStringContainsString('wrote nothing', implode("\n", $again->log));
    }

    public function test_full_chain_from_state_a_has_no_retatrutide_mismatch_and_a_second_run_writes_nothing(): void
    {
        $columns = $this->stateA();
        $updates = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-updates/2026-10-22/retatrutide.json')), true, 512, JSON_THROW_ON_ERROR);
        $reapply = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-reapply/2026-10-22/retatrutide.json')), true, 512, JSON_THROW_ON_ERROR);
        $columns['references'] = $updates['columns']['references']['old_values'][0];
        $columns['human_use_subsections'] = $reapply['columns']['human_use_subsections']['old_value'];
        $post = $this->seedColumns($columns);

        $logs = $this->runChain();
        $this->assertFalse($this->logsMismatch($logs), implode("\n", $logs));
        $post->refresh();
        $this->assertSame($this->target('regulatory_subsections', 3), $post->regulatory_subsections);
        $this->assertSame($this->target('key_points', 3), $post->key_points);
        $this->assertSame($this->target('regulatory_important_note', 1), $post->regulatory_important_note);
        $this->assertSame($this->target('description', 0), $post->description);

        $before = $post->fresh()->getAttributes();
        $again = $this->runChain();
        $this->assertFalse($this->logsMismatch($again), implode("\n", $again));
        $this->assertSame($before, $post->fresh()->getAttributes());
    }

    public function test_an_unexpected_regulatory_item_warns_without_rolling_back_the_other_columns(): void
    {
        $columns = $this->stateAfter140();
        $columns['regulatory_subsections'][3]['title'] = 'UNEXPECTED classification text';
        $post = $this->seedColumns($columns);
        $warnings = $this->captureWarnings();
        $began = $this->countTransactions();

        $seeder = new EncyclopediaRetatrutideS505Seeder;
        $seeder->run();
        $post->refresh();

        $this->assertSame(EncyclopediaRetatrutideS505Seeder::MISMATCH, $seeder->columnReport['regulatory_subsections']);
        $this->assertSame('UNEXPECTED classification text', $post->regulatory_subsections[3]['title']);
        $this->assertSame($this->target('description', 0), $post->description);
        $this->assertSame($this->target('overview', 0), $post->overview);
        $this->assertSame($this->target('conclusion', 0), $post->conclusion);
        $this->assertSame($this->target('faqs', 0), $post->faqs);
        $this->assertSame($this->target('key_points', 3), $post->key_points);
        $this->assertSame($this->target('regulatory_important_note', 1), $post->regulatory_important_note);
        $this->assertGreaterThan(0, $began());
        $this->assertNotSame([], $seeder->transactions);
        $this->assertTrue($this->warningsContain($warnings, 'regulatory_subsections', 'old mismatch'));
    }

    public function test_crlf_nbsp_and_trailing_spaces_compare_as_equal(): void
    {
        $driftedOld = $this->driftValue($this->stateA());
        $post = $this->seedColumns($driftedOld);
        $seeder = new EncyclopediaRetatrutideS505Seeder;
        $seeder->run();
        $post->refresh();

        foreach (['regulatory_subsections', 'faqs', 'key_points', 'conclusion', 'overview', 'description'] as $column) {
            $this->assertSame(
                EncyclopediaRetatrutideS505Seeder::normalize($this->target($column, 0)),
                EncyclopediaRetatrutideS505Seeder::normalize($post->{$column}),
                $column
            );
            $this->assertSame(EncyclopediaRetatrutideS505Seeder::UPDATED, $seeder->columnReport[$column], $column);
        }
        $this->assertSame($driftedOld['regulatory_important_note'], $post->regulatory_important_note);
        $this->assertSame(EncyclopediaRetatrutideS505Seeder::PRE140, $seeder->columnReport['regulatory_important_note']);

        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $driftedTarget = $this->driftValue([
            'regulatory_subsections' => $this->target('regulatory_subsections', 0),
            'faqs' => $this->target('faqs', 0),
            'key_points' => $this->target('key_points', 0),
            'conclusion' => $this->target('conclusion', 0),
            'overview' => $this->target('overview', 0),
            'description' => $this->target('description', 0),
            'regulatory_important_note' => $this->target('regulatory_important_note', 0),
        ]);
        $current = $this->seedColumns($driftedTarget, 'retatrutide-current');
        $before = $current->fresh()->getAttributes();
        $again = new EncyclopediaRetatrutideS505Seeder;
        $again->run();

        $this->assertSame($before, $current->fresh()->getAttributes());
        foreach (array_keys($driftedTarget) as $column) {
            $this->assertSame(EncyclopediaRetatrutideS505Seeder::ALREADY, $again->columnReport[$column], $column);
        }
    }

    public function test_duplicate_categories_or_posts_and_a_missing_post_write_nothing(): void
    {
        $began = $this->countTransactions();
        $missing = new EncyclopediaRetatrutideS505Seeder;
        $missing->run();
        $this->assertSame('missing', $missing->report);
        $this->assertSame([], $missing->transactions);
        $this->assertSame(0, $began());

        $category = ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        $noPost = new EncyclopediaRetatrutideS505Seeder;
        $noPost->run();
        $this->assertSame('missing', $noPost->report);
        $this->assertSame([], $noPost->transactions);
        $this->assertSame(0, EducationPost::query()->where('product_category_id', $category->id)->count());

        $second = ProductCategory::create(['name' => 'Retatrutide spaced', 'slug' => ' RETATRUTIDE ', 'is_active' => true]);
        $left = $this->educationPost($category, 'reta-dup-a', $this->stateA());
        $right = $this->educationPost($second, 'reta-dup-b', $this->stateA());
        $before = [$left->fresh()->getAttributes(), $right->fresh()->getAttributes()];
        $collision = new EncyclopediaRetatrutideS505Seeder;
        $collision->run();
        $this->assertSame('collision', $collision->report);
        $this->assertSame([], $collision->transactions);
        $this->assertSame($before[0], $left->fresh()->getAttributes());
        $this->assertSame($before[1], $right->fresh()->getAttributes());

        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $oneCategory = ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        try {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->dropUnique(['product_category_id']);
            });
        } catch (\Throwable) {
            // The unique index is already absent in this process.
        }
        $one = $this->educationPost($oneCategory, 'reta-post-a', $this->stateA());
        $two = $this->educationPost($oneCategory, 'reta-post-b', $this->stateA());
        $postBefore = [$one->fresh()->getAttributes(), $two->fresh()->getAttributes()];
        $posts = new EncyclopediaRetatrutideS505Seeder;
        $posts->run();
        $this->assertSame('collision', $posts->report);
        $this->assertSame([], $posts->transactions);
        $this->assertSame($postBefore[0], $one->fresh()->getAttributes());
        $this->assertSame($postBefore[1], $two->fresh()->getAttributes());
        try {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->unique('product_category_id');
            });
        } catch (\Throwable) {
            // Leave the schema as the rest of the suite expects when the index is already present.
        }
    }

    public function test_an_unlinked_legacy_post_stays_byte_identical(): void
    {
        $legacy = $this->educationPost(null, 'retatrutide', $this->stateA());
        $linked = $this->seedColumns($this->stateA(), 'retatrutide-linked');
        $before = $legacy->fresh()->getAttributes();

        $seeder = new EncyclopediaRetatrutideS505Seeder;
        $seeder->run();

        $this->assertSame($before, $legacy->fresh()->getAttributes());
        $this->assertNull($legacy->fresh()->product_category_id);
        $this->assertSame($this->target('description', 0), $linked->fresh()->description);
        $blob = implode("\n", $seeder->log);
        $this->assertStringContainsString('id='.$legacy->id.' ', $blob);
        $this->assertStringContainsString('slug=[retatrutide]', $blob);
        $this->assertStringContainsString('product_category_id=null', $blob);
    }

    public function test_rendered_faq_schema_and_index_card_use_the_new_copy(): void
    {
        $post = $this->seedColumns($this->stateAfter140());
        (new EncyclopediaRetatrutideS505Seeder)->run();
        $post->refresh();

        $faqs = $this->target('faqs', 0);
        $response = $this->get('/encyclopedia/retatrutide');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Frontend/EncyclopediaArticleDetail')
            ->where('faqs', [])
            ->where('regulatoryImportantNote', function ($note) {
                return is_string($note)
                    && str_contains($note, 'Lilly said on 29 September 2026 that it plans to submit a Biologics License Application (BLA) in Q1 2027.');
            })
            ->where('seo.schema', function ($schema) use ($faqs) {
                $blocks = collect($schema instanceof \Illuminate\Support\Collection ? $schema->all() : $schema);
                $faqPage = $blocks->firstWhere('@type', 'FAQPage');
                if (! is_array($faqPage)) {
                    return false;
                }
                $entities = $faqPage['mainEntity'] ?? [];
                foreach ([0, 1, 5, 6, 7] as $index) {
                    $entity = $entities[$index] ?? null;
                    if (! is_array($entity) || ($entity['name'] ?? null) !== $faqs[$index]['question']) {
                        return false;
                    }
                    if (($entity['acceptedAnswer']['text'] ?? null) !== $faqs[$index]['answer']) {
                        return false;
                    }
                }

                return true;
            }));

        $visible = html_entity_decode((string) $response->getContent());
        $visible = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', fn (array $match) => mb_chr(hexdec($match[1])), $visible) ?? $visible;
        $this->assertStringNotContainsString('Does “BLA in Q1 2027”', $visible);
        $this->assertStringNotContainsString('beyond what dual agonists achieve', $visible);
        $this->assertStringNotContainsString('As of early October 2026', $visible);

        $this->get('/encyclopedia')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('encyclopediaEntries', function ($entries) {
                $card = collect($entries)->firstWhere('slug', 'retatrutide');

                return is_array($card)
                    && is_string($card['description'] ?? null)
                    && ! str_contains($card['description'], 'highest of any peptide');
            }));
    }

    public function test_timeline_and_faq_seeders_do_not_restore_stale_text_after_160000(): void
    {
        $columns = $this->stateA();
        $updates = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-updates/2026-10-22/retatrutide.json')), true, 512, JSON_THROW_ON_ERROR);
        $reapply = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-reapply/2026-10-22/retatrutide.json')), true, 512, JSON_THROW_ON_ERROR);
        $columns['references'] = $updates['columns']['references']['old_values'][0];
        $columns['human_use_subsections'] = $reapply['columns']['human_use_subsections']['old_value'];
        $post = $this->seedColumns($columns);
        $this->runChain();
        $before = $post->fresh()->getAttributes();

        $timeline = new RetatrutideTimelineLiteracySeeder;
        $timeline->run();
        $faq = new RetatrutideEncyclopediaFaqSeeder;
        $faq->run();

        $this->assertFalse($timeline->report['updated']);
        $this->assertFalse($faq->report['updated']);
        $this->assertSame($before, $post->fresh()->getAttributes());
        $this->assertCount(count($this->target('key_points', 3)), $post->fresh()->key_points);
        $this->assertCount(count($this->target('faqs', 0)), $post->fresh()->faqs);
    }

    public function test_new_fda_answer_and_approval_status_pass_the_existing_heuristics(): void
    {
        $seeder = new RetatrutideEncyclopediaFaqSeeder;
        $fda = new ReflectionMethod($seeder, 'fdaAnswerIsCurrent');
        $approval = new ReflectionMethod($seeder, 'approvalStatusNeedsRefresh');

        $this->assertTrue($fda->invoke($seeder, RetatrutideEncyclopediaFaqSeeder::FDA_ANSWER));
        $this->assertFalse($approval->invoke($seeder, RetatrutideEncyclopediaFaqSeeder::APPROVAL_STATUS));
    }

    /**
     * @param  array<string, mixed>  $columns
     */
    private function seedColumns(array $columns, string $postSlug = 'retatrutide'): EducationPost
    {
        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'description' => 'Catalog description that is not the encyclopedia card.',
            'is_active' => true,
        ]);

        return $this->educationPost($category, $postSlug, $columns);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function educationPost(?ProductCategory $category, string $slug, array $attributes): EducationPost
    {
        $post = EducationPost::create(array_merge([
            'title' => 'Retatrutide',
            'slug' => $slug,
            'product_category_id' => $category?->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
        ], $attributes));
        $post->updated_at = now()->subDays(4);
        $post->saveQuietly();

        return $post->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function stateA(): array
    {
        $state = [];
        foreach ($this->columns() as $column => $spec) {
            $state[$column] = $spec['old_values'][0];
        }
        $state['regulatory_important_note'] = $this->columns()['regulatory_important_note']['pre_140000_values'][0];

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    private function stateAfter140(): array
    {
        $state = $this->stateA();
        $state['regulatory_subsections'] = $this->columns()['regulatory_subsections']['old_values'][3];
        $state['key_points'] = $this->columns()['key_points']['old_values'][3];
        $state['regulatory_important_note'] = $this->columns()['regulatory_important_note']['old_values'][1];

        return $state;
    }

    private function target(string $column, int $index): mixed
    {
        return $this->columns()[$column]['target_values'][$index];
    }

    /**
     * @return array<string, mixed>
     */
    private function columns(): array
    {
        $columns = $this->payload()['columns'] ?? null;
        $this->assertIsArray($columns);

        return $columns;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $decoded = json_decode((string) file_get_contents($this->payloadPath()), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    private function payloadPath(): string
    {
        return database_path('seeders/data/encyclopedia-field-updates/2026-10-22e/retatrutide.json');
    }

    /**
     * @return list<string>
     */
    private function runChain(): array
    {
        $updates = new EncyclopediaFieldUpdatesOctober22Seeder;
        $updates->run();
        $reapply = new EncyclopediaFieldReapplyOctober22Seeder;
        $reapply->run();
        $orfo = new EncyclopediaOrfoRetaOctober22Seeder;
        $orfo->run();
        $followup = new EncyclopediaRetatrutideS505Seeder;
        $followup->run();

        return array_merge($updates->log, $reapply->log, $orfo->log, $followup->log);
    }

    /**
     * @param  list<string>  $logs
     */
    private function logsMismatch(array $logs): bool
    {
        foreach ($logs as $line) {
            if (str_contains($line, 'retatrutide') && str_contains($line, 'old mismatch')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $logs
     */
    private function logContains(array $logs, string $needle): bool
    {
        foreach ($logs as $line) {
            if (str_contains($line, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function driftValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = str_replace("\n", "\r\n", $value);
            $value = preg_replace('/ /', "\u{00A0}", $value, 1) ?? $value;

            return ' '.$value." \t";
        }
        if (! is_array($value)) {
            return $value;
        }
        $drifted = [];
        foreach ($value as $key => $item) {
            $drifted[$key] = $this->driftValue($item);
        }

        return $drifted;
    }

    /**
     * @return \ArrayObject<int, string>
     */
    private function captureWarnings(): \ArrayObject
    {
        $warnings = new \ArrayObject;
        Log::listen(function (MessageLogged $event) use ($warnings) {
            if ($event->level === 'warning') {
                $warnings[] = $event->message;
            }
        });

        return $warnings;
    }

    private function warningsContain(\ArrayObject $warnings, string $column, string $needle): bool
    {
        foreach ($warnings as $warning) {
            if (is_string($warning) && str_contains($warning, $column) && str_contains($warning, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function countTransactions(): \Closure
    {
        $count = 0;
        Event::listen(TransactionBeginning::class, function () use (&$count) {
            $count++;
        });

        return function () use (&$count): int {
            return $count;
        };
    }
}
