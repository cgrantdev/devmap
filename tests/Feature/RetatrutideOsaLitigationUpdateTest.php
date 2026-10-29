<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\Bpc157RetatrutideOctober2026Seeder;
use Database\Seeders\EncyclopediaFieldReapplyOctober22Seeder;
use Database\Seeders\EncyclopediaFieldUpdatesOctober22Seeder;
use Database\Seeders\EncyclopediaOrfoRetaOctober22Seeder;
use Database\Seeders\EncyclopediaRetatrutideOsaLitigationSeeder;
use Database\Seeders\EncyclopediaRetatrutideS505Seeder;
use Database\Seeders\RetatrutideEncyclopediaFaqSeeder;
use Database\Seeders\RetatrutideTimelineLiteracySeeder;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RetatrutideOsaLitigationUpdateTest extends TestCase
{
    use RefreshDatabase;

    private const PAYLOAD_SHA256 = 'd5d05de947fa83a70332bb8087b3b12f949a3b38ac5072cdaa72b9af8809a742';

    private const COLUMNS = ['human_use_subsections', 'regulatory_subsections', 'references'];

    private const OSA_TITLE = 'Obstructive sleep apnea trials (NEJM, 20 Oct 2026) — developer-affiliated (Lilly-funded)';

    private const LAW_TITLE = 'Lilly lawsuits against retatrutide sellers (2026) — separate from the classification case';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_migration_and_payload_are_pinned(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_29_110000_retatrutide_osa_and_litigation_update.php'));
        $this->assertStringContainsString('EncyclopediaRetatrutideOsaLitigationSeeder', $migration);
        $this->assertStringContainsString('Previous encyclopedia field values are not restored.', $migration);
        $this->assertSame(self::PAYLOAD_SHA256, hash_file('sha256', $this->payloadPath()));
        $this->assertSame(self::PAYLOAD_SHA256, EncyclopediaRetatrutideOsaLitigationSeeder::PAYLOAD_SHA256);
        $this->assertSame([], glob(database_path('migrations/2026_10_22_195000_*.php')));
    }

    public function test_live_guard_writes_all_three_columns_and_only_adds(): void
    {
        $post = $this->seedColumns($this->state(0));
        $other = $post->only(['description', 'overview', 'key_points', 'faqs', 'conclusion', 'regulatory_important_note']);
        $began = $this->countTransactions();

        $seeder = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $seeder->run();
        $post->refresh();

        $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::UPDATED, $seeder->report);
        $this->assertSame(1, $began());
        foreach (self::COLUMNS as $column) {
            $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::UPDATED, $seeder->columnReport[$column], $column);
            $this->assertSame($this->target($column, 0), $post->{$column}, $column);
        }
        $this->assertSame($other, $post->only(array_keys($other)));
        $this->assertStringContainsString('categories=1 linked_posts=1', implode("\n", $seeder->log));
        $this->assertFalse($this->logsMismatch($seeder->log));

        // Only insertions: every old subsection/reference survives in order.
        $this->assertSame($this->old('human_use_subsections', 0), array_values(array_filter(
            $post->human_use_subsections,
            fn ($s) => ($s['title'] ?? null) !== self::OSA_TITLE
        )));
        $this->assertSame($this->old('regulatory_subsections', 0), array_values(array_filter(
            $post->regulatory_subsections,
            fn ($s) => ($s['title'] ?? null) !== self::LAW_TITLE
        )));
        $this->assertSame($this->old('references', 0), array_slice($post->references, 0, count($this->old('references', 0))));

        $titles = array_column($post->human_use_subsections, 'title');
        $this->assertSame(self::OSA_TITLE, $titles[array_search('TRIUMPH program map (names + disclosure type)', $titles, true) + 1]);
        $regTitles = array_column($post->regulatory_subsections, 'title');
        $classification = array_search('Biologic vs drug classification — FDA decision on remand (2 Oct 2026); Seventh Circuit appeal pending', $regTitles, true);
        $this->assertSame(self::LAW_TITLE, $regTitles[$classification + 1]);
    }

    public function test_main_chain_guard_candidate_is_accepted(): void
    {
        $post = $this->seedColumns($this->state(1));
        $seeder = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $seeder->run();
        $post->refresh();

        $this->assertSame($this->target('human_use_subsections', 1), $post->human_use_subsections);
        $this->assertSame($this->target('regulatory_subsections', 0), $post->regulatory_subsections);
        $this->assertSame($this->target('references', 1), $post->references);
    }

    public function test_full_chain_from_the_160000_fixture_then_110000_has_no_mismatch(): void
    {
        $post = $this->seedColumns($this->state160Fixture());
        $logs = $this->runChain();
        $seeder = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $seeder->run();
        $post->refresh();

        $this->assertFalse($this->logsMismatch(array_merge($logs, $seeder->log)), implode("\n", $seeder->log));
        $this->assertSame($this->target('human_use_subsections', 1), $post->human_use_subsections);
        $this->assertSame($this->target('regulatory_subsections', 0), $post->regulatory_subsections);
        $this->assertSame($this->target('references', 1), $post->references);
    }

    public function test_a_second_run_writes_nothing(): void
    {
        $post = $this->seedColumns($this->state(0));
        (new EncyclopediaRetatrutideOsaLitigationSeeder)->run();
        $before = $post->fresh()->getAttributes();

        $again = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $again->run();

        $this->assertSame($before, $post->fresh()->getAttributes());
        $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::ALREADY, $again->report);
        foreach (self::COLUMNS as $column) {
            $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::ALREADY, $again->columnReport[$column], $column);
        }
        $this->assertStringContainsString('wrote nothing', implode("\n", $again->log));
        $this->assertCount(1, array_filter($post->fresh()->human_use_subsections, fn ($s) => ($s['title'] ?? null) === self::OSA_TITLE));
        $this->assertCount(1, array_filter($post->fresh()->regulatory_subsections, fn ($s) => ($s['title'] ?? null) === self::LAW_TITLE));
    }

    public function test_guard_mismatch_skips_that_column_with_a_warning(): void
    {
        $columns = $this->state(0);
        $columns['regulatory_subsections'][3]['entries'][1]['value'] .= ' Edited by hand.';
        $post = $this->seedColumns($columns);
        $warnings = $this->captureWarnings();

        $seeder = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $seeder->run();
        $post->refresh();

        $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::MISMATCH, $seeder->columnReport['regulatory_subsections']);
        $this->assertSame($columns['regulatory_subsections'], $post->regulatory_subsections);
        $this->assertSame($this->target('human_use_subsections', 0), $post->human_use_subsections);
        $this->assertSame($this->target('references', 0), $post->references);
        $this->assertTrue($this->warningsContain($warnings, 'retatrutide.regulatory_subsections: skipped (old mismatch)'));
    }

    public function test_all_guards_mismatched_writes_nothing(): void
    {
        $columns = $this->state(0);
        foreach (self::COLUMNS as $column) {
            array_pop($columns[$column]);
        }
        $post = $this->seedColumns($columns);
        $before = $post->fresh()->getAttributes();
        $warnings = $this->captureWarnings();

        $seeder = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $seeder->run();

        $this->assertSame($before, $post->fresh()->getAttributes());
        $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::MISMATCH, $seeder->report);
        foreach (self::COLUMNS as $column) {
            $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::MISMATCH, $seeder->columnReport[$column], $column);
            $this->assertTrue($this->warningsContain($warnings, 'retatrutide.'.$column.': skipped (old mismatch)'), $column);
        }
    }

    public function test_crlf_nbsp_and_trailing_spaces_compare_as_equal(): void
    {
        $post = $this->seedColumns($this->driftValue($this->state(0)));
        $seeder = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $seeder->run();
        $post->refresh();

        foreach (self::COLUMNS as $column) {
            $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::UPDATED, $seeder->columnReport[$column], $column);
            $this->assertSame(
                EncyclopediaRetatrutideOsaLitigationSeeder::normalize($this->target($column, 0)),
                EncyclopediaRetatrutideOsaLitigationSeeder::normalize($post->{$column}),
                $column
            );
        }

        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $drifted = $this->driftValue([
            'human_use_subsections' => $this->target('human_use_subsections', 0),
            'regulatory_subsections' => $this->target('regulatory_subsections', 0),
            'references' => $this->target('references', 0),
        ]);
        $current = $this->seedColumns($drifted);
        $before = $current->fresh()->getAttributes();
        $again = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $again->run();
        $this->assertSame($before, $current->fresh()->getAttributes());
        $this->assertSame(EncyclopediaRetatrutideOsaLitigationSeeder::ALREADY, $again->report);
    }

    public function test_other_posts_and_an_unlinked_legacy_post_stay_byte_identical(): void
    {
        $others = [];
        foreach (['bpc-157', 'tb-500', 'tirzepatide'] as $slug) {
            $category = ProductCategory::create(['name' => strtoupper($slug), 'slug' => $slug, 'is_active' => true]);
            $others[] = $this->educationPost($category, $slug, $this->state(0));
        }
        $legacy = $this->educationPost(null, 'retatrutide-legacy', $this->state(0));
        $post = $this->seedColumns($this->state(0));
        $before = array_map(fn (EducationPost $p) => $p->fresh()->getAttributes(), array_merge($others, [$legacy]));

        (new EncyclopediaRetatrutideOsaLitigationSeeder)->run();

        $after = array_map(fn (EducationPost $p) => $p->fresh()->getAttributes(), array_merge($others, [$legacy]));
        $this->assertSame($before, $after);
        $this->assertSame($this->target('references', 0), $post->fresh()->references);
    }

    public function test_missing_or_duplicate_category_writes_nothing_and_warns(): void
    {
        $began = $this->countTransactions();
        $warnings = $this->captureWarnings();
        $missing = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $missing->run();
        $this->assertSame('missing', $missing->report);
        $this->assertSame([], $missing->transactions);
        $this->assertTrue($this->warningsContain($warnings, 'Retatrutide OSA + litigation update retatrutide: missing; wrote nothing'));

        $category = ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        $second = ProductCategory::create(['name' => 'Retatrutide spaced', 'slug' => ' RETATRUTIDE ', 'is_active' => true]);
        $left = $this->educationPost($category, 'reta-dup-a', $this->state(0));
        $right = $this->educationPost($second, 'reta-dup-b', $this->state(0));
        $before = [$left->fresh()->getAttributes(), $right->fresh()->getAttributes()];
        $collision = new EncyclopediaRetatrutideOsaLitigationSeeder;
        $collision->run();
        $this->assertSame('collision', $collision->report);
        $this->assertSame([], $collision->transactions);
        $this->assertSame($before, [$left->fresh()->getAttributes(), $right->fresh()->getAttributes()]);
        $this->assertSame(0, $began());
    }

    public function test_new_copy_has_no_dose_labels_and_keeps_the_appeal_facts(): void
    {
        $osa = $this->payload()['columns']['human_use_subsections']['insert'];
        $law = $this->payload()['columns']['regulatory_subsections']['insert'];
        $refs = $this->payload()['columns']['references']['append'];
        $added = json_encode([$osa, $law], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertDoesNotMatchRegularExpression('/\d\s*mg\b/i', $added);
        $this->assertDoesNotMatchRegularExpression('/subcutaneous|inject|once[- ]weekly|titrat|protocol|dosing/i', $added);
        $this->assertStringContainsString('Developer-affiliated', $added);
        $this->assertStringContainsString('funded by Eli Lilly', $added);
        $this->assertStringContainsString('10.1056/NEJMoa2611477', $added);
        $this->assertStringContainsString('42904878', $added);
        $this->assertStringContainsString('Bloomberg Law reported on 26 October 2026', $added);
        $this->assertStringContainsString('at least 11 federal lawsuits', $added);
        $this->assertStringNotContainsString('contest', strtolower($added));
        $this->assertCount(5, $refs);

        $target = json_encode($this->target('regulatory_subsections', 0), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('beyond the scope of this appeal', $target);
        $this->assertStringContainsString('No. 26-1301, Dkt. 46-2', $target);
        $this->assertStringContainsString('No. 1:24-cv-01503', $target);
        $this->assertStringContainsString('As of 22 October 2026, no ruling appeared on the docket.', $target);
    }

    public function test_payload_names_no_defendant_and_links_no_complaint(): void
    {
        $raw = (string) file_get_contents($this->payloadPath());
        $defendants = [
            'Aesthetic Envy', 'Astra LLC', 'Astra Peptides', 'Legendary Peptides', 'Striker Pharmacy',
            'Texas Peptides', 'Lone Star Peptide', 'American Peptides', 'LiveWell', 'Cenexa',
            'Medrein', 'Medshape', 'MedShape', 'Southlake', 'Mhya',
        ];
        foreach ($defendants as $name) {
            $this->assertStringNotContainsStringIgnoringCase($name, $raw, $name);
        }

        $complaint = $this->payload()['columns']['references']['append'][4];
        $this->assertSame('Eli Lilly and Company complaint, No. 4:26-cv-01294 (N.D. Tex.)', $complaint['title']);
        $this->assertSame([], $complaint['links']);
        $this->assertStringNotContainsString('http', (string) json_encode($complaint));
        $this->assertStringNotContainsString('txnd.428373', $raw);
        foreach ([0, 1] as $index) {
            $last = $this->target('references', $index)[count($this->target('references', $index)) - 1];
            $this->assertSame($complaint, $last);
        }
    }

    public function test_rendered_page_shows_the_new_subsections(): void
    {
        $this->seedColumns($this->state(0));
        (new EncyclopediaRetatrutideOsaLitigationSeeder)->run();

        $this->get('/encyclopedia/retatrutide')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/EncyclopediaArticleDetail')
                ->where('humanUseSubsections', fn ($s) => collect($s)->contains(fn ($x) => ($x['title'] ?? null) === self::OSA_TITLE))
                ->where('regulatorySubsections', fn ($s) => collect($s)->contains(fn ($x) => ($x['title'] ?? null) === self::LAW_TITLE))
                ->where('references', fn ($r) => collect($r)->contains(fn ($x) => str_contains((string) ($x['citation'] ?? ''), '10.1056/NEJMoa2611477'))));
    }

    public function test_older_retatrutide_seeders_neither_drop_nor_duplicate_the_new_content(): void
    {
        $post = $this->seedColumns($this->state160Fixture());
        $this->runChain();
        (new EncyclopediaRetatrutideOsaLitigationSeeder)->run();
        $before = $post->fresh()->getAttributes();

        $timeline = new RetatrutideTimelineLiteracySeeder;
        $timeline->run();
        $faq = new RetatrutideEncyclopediaFaqSeeder;
        $faq->run();

        $this->assertFalse($timeline->report['updated']);
        $this->assertFalse($faq->report['updated']);
        $this->assertSame($before, $post->fresh()->getAttributes());

        (new Bpc157RetatrutideOctober2026Seeder)->run();
        $fresh = $post->fresh();
        foreach ([[$fresh->human_use_subsections, self::OSA_TITLE], [$fresh->regulatory_subsections, self::LAW_TITLE]] as [$sections, $title]) {
            $this->assertCount(1, array_filter($sections, fn ($s) => ($s['title'] ?? null) === $title), $title);
        }
        $blob = json_encode($fresh->references, JSON_UNESCAPED_SLASHES);
        $this->assertSame(1, substr_count($blob, '"PMID 42904878') + substr_count($blob, 'PMID 42904878.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function state(int $candidate): array
    {
        $state = [];
        foreach (self::COLUMNS as $column) {
            $olds = $this->payload()['columns'][$column]['old_values'];
            $state[$column] = $olds[$candidate] ?? $olds[0];
        }

        return $state;
    }

    /**
     * The fixture the 160000 test chain starts from (state A plus the 120000/130000 inputs).
     *
     * @return array<string, mixed>
     */
    private function state160Fixture(): array
    {
        $s505 = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-updates/2026-10-22e/retatrutide.json')), true, 512, JSON_THROW_ON_ERROR);
        $state = [];
        foreach ($s505['columns'] as $column => $spec) {
            $state[$column] = $spec['old_values'][0];
        }
        $state['regulatory_important_note'] = $s505['columns']['regulatory_important_note']['pre_140000_values'][0];
        $updates = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-updates/2026-10-22/retatrutide.json')), true, 512, JSON_THROW_ON_ERROR);
        $reapply = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-reapply/2026-10-22/retatrutide.json')), true, 512, JSON_THROW_ON_ERROR);
        $state['references'] = $updates['columns']['references']['old_values'][0];
        $state['human_use_subsections'] = $reapply['columns']['human_use_subsections']['old_value'];

        return $state;
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

    private function target(string $column, int $index): mixed
    {
        return $this->payload()['columns'][$column]['target_values'][$index];
    }

    private function old(string $column, int $index): mixed
    {
        return $this->payload()['columns'][$column]['old_values'][$index];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return json_decode((string) file_get_contents($this->payloadPath()), true, 512, JSON_THROW_ON_ERROR);
    }

    private function payloadPath(): string
    {
        return database_path(EncyclopediaRetatrutideOsaLitigationSeeder::PAYLOAD_PATH);
    }

    private function driftValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace(' ', "\u{00A0}", substr($value, 0, 1)).substr(str_replace("\n", "\r\n", $value), 1)."  \r\n";
        }
        if (is_array($value)) {
            return array_map(fn ($item) => $this->driftValue($item), $value);
        }

        return $value;
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

    private function captureWarnings(): \ArrayObject
    {
        $warnings = new \ArrayObject;
        Log::listen(function (MessageLogged $event) use ($warnings) {
            if ($event->level === 'warning') {
                $warnings->append($event->message);
            }
        });

        return $warnings;
    }

    private function warningsContain(\ArrayObject $warnings, string $needle): bool
    {
        foreach ($warnings as $message) {
            if (str_contains((string) $message, $needle)) {
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
