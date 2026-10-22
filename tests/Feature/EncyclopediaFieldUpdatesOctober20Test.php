<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\CompareController;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaFieldUpdatesOctober20Seeder;
use Database\Seeders\EncyclopediaFramingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EncyclopediaFieldUpdatesOctober20Test extends TestCase
{
    use RefreshDatabase;

    private const SOURCE_PHRASE = 'While not technically a peptide, it is widely included in peptide therapy protocols due to its injectable format and complementary role in cellular repair pathways.';

    private const RENDERED_PHRASE = 'It is a dinucleotide coenzyme, not a peptide.';

    private const LANDSCAPE_SOURCE = 'in the peptide and longevity research landscape';

    private const LANDSCAPE_RENDERED = 'in cellular-energy and longevity research';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_migration_widens_half_life_and_runs_the_seeder(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_20_130000_update_encyclopedia_fields_october_20_2026.php'));
        $this->assertStringContainsString('EncyclopediaFieldUpdatesOctober20Seeder', $migration);
        $this->assertStringContainsString('MODIFY half_life TEXT', $migration);
        $this->assertStringContainsString('Previous encyclopedia field values are not restored.', $migration);

        $seeder = (string) file_get_contents(database_path('seeders/EncyclopediaFieldUpdatesOctober20Seeder.php'));
        $this->assertStringContainsString('LOWER(TRIM(slug))', $seeder);
        $this->assertStringContainsString('educationPost', $seeder);
        $this->assertStringNotContainsString('where(\'id\'', $seeder);
    }

    public function test_writes_when_old_values_match_and_leaves_every_other_row_unchanged(): void
    {
        $seeded = $this->seedMatchingPosts();
        $protected = $this->protectedSnapshots();
        $messages = $this->captureLogs();

        $seeder = new EncyclopediaFieldUpdatesOctober20Seeder;
        $seeder->run();

        $this->assertSame('updated', $seeder->report['nad']);
        $this->assertSame('updated', $seeder->report['sermorelin']);
        $this->assertProtectedUnchanged($protected);
        $this->assertSame('LEGACY-NAD', $seeded['legacyNad']->fresh()->overview);
        $this->assertSame('LEGACY-SERMORELIN', $seeded['legacySermorelin']->fresh()->overview);
        $this->assertNotSame('nad', $seeded['nad']->fresh()->slug);
        $this->assertNotSame('Sermorelin', $seeded['sermorelin']->fresh()->slug);
        $this->assertLogged($messages, 'info', '20 Oct 2026 encyclopedia field update: nad matched 1 product_categories and 1 education_posts.');
        $this->assertLogged($messages, 'info', '20 Oct 2026 encyclopedia field update: sermorelin matched 1 product_categories and 1 education_posts.');

        $nadPayload = $this->payload('nad.json');
        $nad = $seeded['nad']->fresh();
        $this->assertSame($this->expectedChangedKeys($nadPayload), $this->changedKeys($seeded['nadBefore'], $nad));
        foreach ($nadPayload['columns'] as $column => $spec) {
            $this->assertSame($spec['new_value'], $nad->{$column}, $column);
        }
        $this->assertSame('KEEP-NAD', $nad->mechanism_of_action_intro);
        $intravenous = $nad->human_use_subsections[0]['entries'][0]['value'];
        $this->assertStringContainsString('\\n', $intravenous);
        $this->assertStringNotContainsString("\n", $intravenous);
        $this->assertCount(6, $nad->faqs);

        $sermorelinPayload = $this->payload('sermorelin.json');
        $sermorelin = $seeded['sermorelin']->fresh();
        $this->assertSame($this->expectedChangedKeys($sermorelinPayload), $this->changedKeys($seeded['sermorelinBefore'], $sermorelin));
        foreach ($sermorelinPayload['columns'] as $column => $spec) {
            if ($column === 'faqs') {
                continue;
            }
            $this->assertSame($spec['new_value'], $sermorelin->{$column}, $column);
        }
        $this->assertCount(6, $sermorelin->faqs);
        $this->assertSame('kept-source', $sermorelin->faqs[1]['source']);
        $this->assertSame($sermorelinPayload['columns']['faqs']['new_value'][1]['question'], $sermorelin->faqs[1]['question']);
        $this->assertSame($sermorelinPayload['columns']['faqs']['new_value'][1]['answer'], $sermorelin->faqs[1]['answer']);
        $this->assertSame($sermorelinPayload['columns']['faqs']['new_value'][0], $sermorelin->faqs[0]);
        $this->assertSame('KEEP-SERMORELIN', $sermorelin->mechanism_of_action_intro);
        $this->assertGreaterThan(255, strlen((string) $sermorelin->half_life));
    }

    public function test_second_run_writes_nothing(): void
    {
        $seeded = $this->seedMatchingPosts();
        (new EncyclopediaFieldUpdatesOctober20Seeder)->run();

        $nad = $seeded['nad']->fresh();
        $sermorelin = $seeded['sermorelin']->fresh();
        foreach ([$nad, $sermorelin] as $post) {
            $post->updated_at = now()->subDay();
            $post->saveQuietly();
        }
        $nadStamp = $nad->fresh()->updated_at;
        $sermorelinStamp = $sermorelin->fresh()->updated_at;
        $snapshots = [
            $nad->id => $nad->fresh()->getAttributes(),
            $sermorelin->id => $sermorelin->fresh()->getAttributes(),
        ];
        $protected = $this->protectedSnapshots();

        $again = new EncyclopediaFieldUpdatesOctober20Seeder;
        $again->run();

        $this->assertSame('skipped', $again->report['nad']);
        $this->assertSame('skipped', $again->report['sermorelin']);
        $this->assertProtectedUnchanged($protected);
        $this->assertTrue($nad->fresh()->updated_at->equalTo($nadStamp));
        $this->assertTrue($sermorelin->fresh()->updated_at->equalTo($sermorelinStamp));
        $this->assertSame($snapshots[$nad->id], $nad->fresh()->getAttributes());
        $this->assertSame($snapshots[$sermorelin->id], $sermorelin->fresh()->getAttributes());
        $this->assertSame('kept-source', $sermorelin->fresh()->faqs[1]['source']);
        foreach ($again->log as $line) {
            $this->assertDoesNotMatchRegularExpression('/\bwrote\b/', $line);
        }
    }

    public function test_guards_skip_when_the_marker_is_already_present(): void
    {
        $nad = $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', $this->markedAttributes('nad.json'));
        $sermorelin = $this->linkedPost($this->category('Sermorelin', 'Sermorelin'), 'What is Sermorelin?', $this->markedAttributes('sermorelin.json'));
        $before = [
            $nad->id => $nad->getAttributes(),
            $sermorelin->id => $sermorelin->getAttributes(),
        ];

        $seeder = new EncyclopediaFieldUpdatesOctober20Seeder;
        $seeder->run();

        $this->assertSame('skipped', $seeder->report['nad']);
        $this->assertSame('skipped', $seeder->report['sermorelin']);
        $this->assertSame($before[$nad->id], $nad->fresh()->getAttributes());
        $this->assertSame($before[$sermorelin->id], $sermorelin->fresh()->getAttributes());
        foreach (['nad' => 'nad.json', 'sermorelin' => 'sermorelin.json'] as $slug => $file) {
            foreach (array_keys($this->payload($file)['columns']) as $column) {
                $this->assertContains($slug.' '.$column.': skipped: already applied', $seeder->log);
            }
        }
        foreach ($seeder->log as $line) {
            $this->assertStringNotContainsString('does not match', $line);
        }
    }

    public function test_empty_research_url_is_skipped_and_not_filled_from_the_profile(): void
    {
        $attributes = $this->oldAttributes('nad.json');
        $attributes['research_url'] = null;
        $nad = $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', $attributes);

        $seeder = new EncyclopediaFieldUpdatesOctober20Seeder;
        $seeder->run();

        $this->assertNull($nad->fresh()->research_url);
        $this->assertContains('nad research_url: skipped: empty', $seeder->log);
        $this->assertSame($this->payload('nad.json')['columns']['overview']['new_value'], $nad->fresh()->overview);
    }

    public function test_missing_category_is_a_no_op_and_a_collision_writes_nothing(): void
    {
        $legacy = $this->legacyPost('nad', 'LEGACY-ONLY');
        $decoy = $this->legacyPost('semaglutide', 'DECOY');
        $before = [
            $legacy->id => $legacy->getAttributes(),
            $decoy->id => $decoy->getAttributes(),
        ];
        $messages = $this->captureLogs();

        $missing = new EncyclopediaFieldUpdatesOctober20Seeder;
        $missing->run();

        $this->assertSame('missing', $missing->report['nad']);
        $this->assertSame('missing', $missing->report['sermorelin']);
        $this->assertSame($before[$legacy->id], $legacy->fresh()->getAttributes());
        $this->assertSame($before[$decoy->id], $decoy->fresh()->getAttributes());
        $this->assertLogged($messages, 'warning', 'WARNING 20 Oct 2026 encyclopedia field update: nad matched 0 product_categories and 0 education_posts; nothing written');
        $this->assertLogged($messages, 'warning', 'WARNING 20 Oct 2026 encyclopedia field update: sermorelin matched 0 product_categories and 0 education_posts; nothing written');

        $first = $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', ['overview' => 'FIRST-NAD']);
        $second = $this->linkedPost($this->category('NAD plus', 'NAD'), 'NAD monograph', ['overview' => 'SECOND-NAD']);

        $collision = new EncyclopediaFieldUpdatesOctober20Seeder;
        $collision->run();

        $this->assertSame('collision', $collision->report['nad']);
        $this->assertSame('FIRST-NAD', $first->fresh()->overview);
        $this->assertSame('SECOND-NAD', $second->fresh()->overview);
        $this->assertTrue(collect($collision->log)->contains(
            fn (string $line) => str_contains($line, 'WARNING')
                && str_contains($line, 'nad matched 2 product_categories and 2 education_posts')
                && str_contains($line, 'nothing written')
        ));
    }

    public function test_legacy_unlinked_post_with_the_same_slug_is_never_written(): void
    {
        $legacy = $this->legacyPost('nad', 'UNLINKED-NAD');
        $linked = $this->linkedPost(
            $this->category('NAD+', 'nad'),
            'What is NAD+?',
            $this->oldAttributes('nad.json')
        );
        $legacyBefore = $legacy->getAttributes();

        $seeder = new EncyclopediaFieldUpdatesOctober20Seeder;
        $seeder->run();

        $this->assertSame('updated', $seeder->report['nad']);
        $this->assertSame($legacyBefore, $legacy->fresh()->getAttributes());
        $this->assertSame('nad', $legacy->fresh()->slug);
        $this->assertNull($legacy->fresh()->product_category_id);
        $this->assertSame($this->payload('nad.json')['columns']['overview']['new_value'], $linked->fresh()->overview);
        $this->assertNotSame('nad', $linked->fresh()->slug);
    }

    public function test_case_and_trimmed_category_slugs_match(): void
    {
        $nad = $this->linkedPost($this->category('NAD+', 'NAD'), 'What is NAD+?', $this->oldAttributes('nad.json'));
        $sermorelin = $this->linkedPost($this->category('Sermorelin', ' Sermorelin '), 'What is Sermorelin?', $this->oldAttributes('sermorelin.json'));

        $seeder = new EncyclopediaFieldUpdatesOctober20Seeder;
        $seeder->run();

        $this->assertSame('updated', $seeder->report['nad']);
        $this->assertSame('updated', $seeder->report['sermorelin']);
        $this->assertSame($this->payload('nad.json')['columns']['half_life']['new_value'], $nad->fresh()->half_life);
        $this->assertSame($this->payload('sermorelin.json')['columns']['overview']['new_value'], $sermorelin->fresh()->overview);
        $this->assertSame('NAD', $nad->fresh()->category->slug);
        $this->assertSame(' Sermorelin ', $sermorelin->fresh()->category->slug);
    }

    public function test_duplicate_category_rows_warn_and_write_nothing(): void
    {
        $lower = $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', $this->oldAttributes('nad.json'));
        $upper = $this->linkedPost($this->category('NAD plus', 'NAD'), 'NAD monograph', $this->oldAttributes('nad.json'));
        $before = [
            $lower->id => $lower->getAttributes(),
            $upper->id => $upper->getAttributes(),
        ];
        $messages = $this->captureLogs();

        $seeder = new EncyclopediaFieldUpdatesOctober20Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report['nad']);
        $this->assertSame($before[$lower->id], $lower->fresh()->getAttributes());
        $this->assertSame($before[$upper->id], $upper->fresh()->getAttributes());
        $this->assertLogged(
            $messages,
            'warning',
            'WARNING 20 Oct 2026 encyclopedia field update: nad matched 2 product_categories and 2 education_posts; nothing written'
        );
    }

    public function test_old_value_mismatch_leaves_that_column_unchanged_and_does_not_append(): void
    {
        $nadAttributes = $this->oldAttributes('nad.json');
        $nadAttributes['overview'] = 'NOT-THE-OLD-OVERVIEW';
        $nadAttributes['human_use_subsections'] = [['title' => 'KEEP-SECTION', 'entries' => [['value' => 'KEEP-ENTRY']]]];
        $sermorelinAttributes = $this->oldAttributes('sermorelin.json');
        $sermorelinAttributes['conclusion'] = 'NOT-THE-OLD-CONCLUSION';
        $nad = $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', $nadAttributes);
        $sermorelin = $this->linkedPost($this->category('Sermorelin', 'Sermorelin'), 'What is Sermorelin?', $sermorelinAttributes);

        $seeder = new EncyclopediaFieldUpdatesOctober20Seeder;
        $seeder->run();

        $nad = $nad->fresh();
        $sermorelin = $sermorelin->fresh();
        $this->assertSame('NOT-THE-OLD-OVERVIEW', $nad->overview);
        $this->assertSame([['title' => 'KEEP-SECTION', 'entries' => [['value' => 'KEEP-ENTRY']]]], $nad->human_use_subsections);
        $this->assertSame('NOT-THE-OLD-CONCLUSION', $sermorelin->conclusion);
        $this->assertContains('nad overview: skipped: old value does not match', $seeder->log);
        $this->assertContains('nad human_use_subsections: skipped: old value does not match', $seeder->log);
        $this->assertContains('sermorelin conclusion: skipped: old value does not match', $seeder->log);
        $this->assertSame($this->payload('nad.json')['columns']['background']['new_value'], $nad->background);
        $this->assertSame($this->payload('sermorelin.json')['columns']['overview']['new_value'], $sermorelin->overview);
        $this->assertStringNotContainsString($this->payload('nad.json')['columns']['overview']['new_value'], $nad->overview);
    }

    public function test_rendered_text_guard_writes_a_source_phrase_and_skips_a_different_rendering(): void
    {
        $stored = $this->oldAttributes('nad.json');
        $this->assertStringContainsString(self::SOURCE_PHRASE, $stored['overview']);
        $this->assertStringContainsString(self::LANDSCAPE_SOURCE, $stored['conclusion']);
        $nad = $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', $stored);

        $seeder = new EncyclopediaFieldUpdatesOctober20Seeder;
        $seeder->run();

        $this->assertSame($this->payload('nad.json')['columns']['overview']['new_value'], $nad->fresh()->overview);
        $this->assertSame($this->payload('nad.json')['columns']['faqs']['new_value'], $nad->fresh()->faqs);
        $this->assertStringNotContainsString(self::SOURCE_PHRASE, $nad->fresh()->overview);

        $nad->overview = 'While not technically a peptide, this sentence is not the live paragraph.';
        $nad->saveQuietly();
        $stuck = $nad->fresh()->overview;

        $again = new EncyclopediaFieldUpdatesOctober20Seeder;
        $again->run();

        $this->assertSame($stuck, $nad->fresh()->overview);
        $this->assertContains('nad overview: skipped: old value does not match', $again->log);
    }

    public function test_whitespace_variants_still_match_without_changing_the_written_value(): void
    {
        $nadAttributes = $this->oldAttributes('nad.json');
        $nadAttributes['background'] = $this->noise($nadAttributes['background']);
        $nadAttributes['regulatory_important_note'] = $this->noise($nadAttributes['regulatory_important_note']);
        $sermorelinAttributes = $this->oldAttributes('sermorelin.json');
        $sermorelinAttributes['overview'] = $this->noise($sermorelinAttributes['overview']);
        $sermorelinAttributes['human_use_intro'] = $this->noise($sermorelinAttributes['human_use_intro']);
        $nad = $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', $nadAttributes);
        $sermorelin = $this->linkedPost($this->category('Sermorelin', 'Sermorelin'), 'What is Sermorelin?', $sermorelinAttributes);

        (new EncyclopediaFieldUpdatesOctober20Seeder)->run();

        $nadPayload = $this->payload('nad.json');
        $sermorelinPayload = $this->payload('sermorelin.json');
        $this->assertSame($nadPayload['columns']['background']['new_value'], $nad->fresh()->background);
        $this->assertSame($nadPayload['columns']['regulatory_important_note']['new_value'], $nad->fresh()->regulatory_important_note);
        $this->assertSame($sermorelinPayload['columns']['overview']['new_value'], $sermorelin->fresh()->overview);
        $this->assertSame($sermorelinPayload['columns']['human_use_intro']['new_value'], $sermorelin->fresh()->human_use_intro);
        $this->assertStringNotContainsString("\u{00A0}", $nad->fresh()->background);
    }

    public function test_framing_seeder_leaves_the_new_nad_fields_unchanged(): void
    {
        $category = $this->category('NAD+', 'nad', 'Coenzyme reference.');
        $nad = $this->linkedPost($category, 'What is NAD+?', array_merge($this->oldAttributes('nad.json'), [
            'seo_description' => 'Coenzyme overview.',
            'seo_page_title' => 'NAD+ coenzyme',
        ]));
        (new EncyclopediaFieldUpdatesOctober20Seeder)->run();
        $before = [];
        foreach (array_keys($this->payload('nad.json')['columns']) as $column) {
            $before[$column] = $nad->fresh()->{$column};
        }

        (new EncyclopediaFramingSeeder)->run();

        $nad->refresh();
        foreach ($before as $column => $value) {
            $this->assertSame($value, $nad->{$column}, $column);
        }
        $this->assertSame('Coenzyme reference.', $category->fresh()->description);
    }

    public function test_expand_articles_seeder_no_longer_ships_the_sermorelin_commercial_line(): void
    {
        $source = (string) file_get_contents(database_path('seeders/ExpandArticlesSeeder.php'));
        $this->assertStringNotContainsString('commercial (not safety)', $source);
        $this->assertStringNotContainsString("'sermorelin'", $source);
        $this->assertStringContainsString("'tesamorelin'", $source);
    }

    public function test_new_values_do_not_add_doses(): void
    {
        // "first 2 hours" is a locked observation from the Grant 2019 pilot
        // (NAD+ removed from plasma), not a dose or a schedule. The hours
        // pattern would otherwise flag that citation. Real doses such as
        // "4-8 hours", "1000mg", "bedtime", and "/kg" still fail.
        $dose = [
            '/\d+\s?(mg|mcg|µg|IU|mL)/u',
            '/\d+\s?-?\s?(hours?|h)\b/u',
            '/twice daily|nightly|bedtime|\/kg/u',
        ];
        foreach (['nad.json', 'sermorelin.json'] as $file) {
            foreach ($this->payload($file)['columns'] as $column => $spec) {
                $hits = $this->doseHits($spec['new_value'], $column === 'references', $dose);
                $this->assertSame([], $hits, $file.' '.$column.' '.implode(', ', $hits));
            }
        }
    }

    public function test_rendered_pages_use_the_new_status_half_life_faqs_and_research_url(): void
    {
        $nad = $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', $this->oldAttributes('nad.json'));
        $sermorelin = $this->linkedPost($this->category('Sermorelin', 'Sermorelin'), 'What is Sermorelin?', $this->oldAttributes('sermorelin.json'));
        (new EncyclopediaFieldUpdatesOctober20Seeder)->run();

        $nadPayload = $this->payload('nad.json');
        $nadPage = $this->get('/encyclopedia/nad')->assertOk();
        $nadProps = $nadPage->inertiaPage()['props'];
        $nadPage->assertInertia(fn ($page) => $page
            ->component('Frontend/EncyclopediaArticleDetail')
            ->where('drugStatus', 'Not FDA-approved')
            ->where('halfLife', '')
            ->where('primaryResearch.url', 'https://pubchem.ncbi.nlm.nih.gov/compound/5892')
        );
        $nadFaqs = $this->faqPage($nadProps);
        $this->assertCount(6, $nadFaqs['mainEntity']);
        foreach ($nadPayload['columns']['faqs']['new_value'] as $index => $faq) {
            $this->assertSame($faq['question'], $nadFaqs['mainEntity'][$index]['name']);
            $this->assertSame($faq['answer'], $nadFaqs['mainEntity'][$index]['acceptedAnswer']['text']);
        }
        $this->assertSame(1, substr_count($nadFaqs['mainEntity'][0]['acceptedAnswer']['text'], 'dinucleotide coenzyme'));
        $this->assertStringNotContainsString('CID 5893', json_encode($nadProps));
        $this->assertStringNotContainsString('CID 5893', $nadPage->getContent());
        $this->assertNotSame('nad', $nad->fresh()->slug);

        $sermorelinPayload = $this->payload('sermorelin.json');
        $sermorelinPage = $this->get('/encyclopedia/Sermorelin')->assertOk();
        $sermorelinProps = $sermorelinPage->inertiaPage()['props'];
        $sermorelinPage->assertInertia(fn ($page) => $page
            ->where('drugStatus', 'Not FDA-approved (approvals withdrawn 2009)')
            ->where('halfLife', $sermorelinPayload['columns']['half_life']['new_value'])
        );
        $encoded = json_encode($sermorelinProps).$sermorelinPage->getContent();
        foreach (['commercial reasons', 'compounding pharmacies', 'Not currently on the WADA'] as $phrase) {
            $this->assertStringNotContainsString($phrase, $encoded);
        }
        $this->assertNotSame('Sermorelin', $sermorelin->fresh()->slug);
    }

    public function test_sermorelin_compare_pairs_drop_the_commercial_wording(): void
    {
        $pairs = [];
        foreach (CompareController::FEATURED_VS_PAIRS as $pair) {
            $pairs[] = $pair['a'].'-vs-'.$pair['b'];
        }
        foreach (config('seo.vs_sitemap_allowlist') as $slug) {
            $pairs[] = $slug;
        }
        $sermorelinPairs = array_values(array_unique(array_filter(
            $pairs,
            fn (string $slug) => str_contains($slug, 'sermorelin')
        )));
        sort($sermorelinPairs);
        $this->assertSame(['ipamorelin-vs-sermorelin', 'sermorelin-vs-tesamorelin'], $sermorelinPairs);

        $this->category('Ipamorelin', 'ipamorelin');
        $this->category('Tesamorelin', 'tesamorelin');
        $this->linkedPost($this->category('Sermorelin', 'Sermorelin'), 'What is Sermorelin?', $this->oldAttributes('sermorelin.json'));
        (new EncyclopediaFieldUpdatesOctober20Seeder)->run();

        foreach ($sermorelinPairs as $slug) {
            $response = $this->get('/compare/'.$slug)->assertOk();
            $blob = json_encode($response->inertiaPage()['props']).$response->getContent();
            $this->assertStringNotContainsString('commercial reasons', $blob, $slug);
            $this->assertStringNotContainsString('commercial (not safety)', $blob, $slug);
        }
    }

    public function test_mots_c_vs_nad_keeps_an_empty_half_life(): void
    {
        $this->category('MOTS-c', 'mots-c');
        $this->linkedPost($this->category('NAD+', 'nad'), 'What is NAD+?', $this->oldAttributes('nad.json'));
        (new EncyclopediaFieldUpdatesOctober20Seeder)->run();

        $response = $this->get('/compare/mots-c-vs-nad')->assertOk();
        $props = $response->inertiaPage()['props'];
        $response->assertInertia(fn ($page) => $page->component('Frontend/CompareCompoundVs'));
        $this->assertSame('mots-c', $props['a']['slug']);
        $this->assertSame('nad', $props['b']['slug']);
        $this->assertArrayHasKey('half_life', $props['b']);
        $this->assertSame('', $props['b']['half_life']);
        $this->assertNotSame('Varies', $props['b']['half_life']);
        $this->assertNotNull($props['a']['name']);
        $this->assertNotNull($props['b']['name']);
        $this->assertIsArray($props['a']['key_effects']);
        $this->assertIsArray($props['b']['use_cases']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function seedMatchingPosts(): array
    {
        $nadCategory = $this->category('NAD+', 'nad');
        $sermorelinCategory = $this->category('Sermorelin', 'Sermorelin');
        $nadAttributes = array_merge($this->oldAttributes('nad.json'), [
            'mechanism_of_action_intro' => 'KEEP-NAD',
        ]);
        $sermorelinAttributes = array_merge($this->oldAttributes('sermorelin.json'), [
            'mechanism_of_action_intro' => 'KEEP-SERMORELIN',
        ]);
        $sermorelinAttributes['faqs'][1]['source'] = 'kept-source';

        $nad = $this->linkedPost($nadCategory, 'What is NAD+?', $nadAttributes);
        $sermorelin = $this->linkedPost($sermorelinCategory, 'What is Sermorelin?', $sermorelinAttributes);
        $legacyNad = $this->legacyPost('nad', 'LEGACY-NAD');
        $legacySermorelin = $this->legacyPost('Sermorelin', 'LEGACY-SERMORELIN');
        $this->legacyPost('retatrutide', 'LOCKED-RETATRUTIDE');

        return [
            'nad' => $nad,
            'sermorelin' => $sermorelin,
            'legacyNad' => $legacyNad,
            'legacySermorelin' => $legacySermorelin,
            'nadBefore' => $nad->getAttributes(),
            'sermorelinBefore' => $sermorelin->getAttributes(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function oldAttributes(string $file): array
    {
        $attributes = [];
        foreach ($this->payload($file)['columns'] as $name => $spec) {
            $value = $spec['old_value'];
            if ($file === 'nad.json' && in_array($name, ['overview', 'background', 'conclusion', 'faqs'], true)) {
                $value = $this->withSourcePhrases($value);
            }
            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function markedAttributes(string $file): array
    {
        $slug = $file === 'nad.json' ? 'nad' : 'sermorelin';
        $attributes = [];
        foreach ($this->payload($file)['columns'] as $column => $spec) {
            $attributes[$column] = $this->markerFor($slug, $column, $spec['new_value']);
        }

        return $attributes;
    }

    private function markerFor(string $slug, string $column, mixed $new): mixed
    {
        if ($slug === 'nad') {
            return match ($column) {
                'overview' => 'Nature Metabolism sentinel',
                'background' => 'William John Young sentinel',
                'conclusion' => 'Nat Metab 2026 sentinel',
                'regulatory_subsections' => [['title' => 'Regulatory status (as of sentinel)']],
                'references' => [['citation' => 'PMID 41083806 sentinel']],
                'human_use_subsections' => [['title' => 'Intravenous NAD+', 'entries' => [['value' => 'sentinel']]]],
                'mechanism_subsections' => [['items' => [['description' => 'Cell Metab 2016 sentinel']]]],
                'preclinical_subsections' => [['findings' => [['content' => 'Cell 2013 sentinel']]]],
                'half_life' => '',
                'faqs' => [['question' => 'Do NAD+ levels fall with age in humans?', 'answer' => 'sentinel']],
                'human_use_intro' => 'A pilot study sentinel',
                'potential_applications' => [['title' => 'Aging research', 'description' => 'sentinel']],
                'areas_of_research' => [['description' => 'placebo-controlled trials are lacking sentinel']],
                'research_url' => $new,
                default => $new,
            };
        }

        return match ($column) {
            'faqs' => [['question' => 'What was GEREF Diagnostic used for?', 'answer' => 'sentinel']],
            'overview', 'background' => 'NDA 019863 sentinel',
            'human_use_subsections' => [['entries' => [['value' => 'NDA 020443 sentinel']]]],
            'regulatory_subsections' => [['title' => 'FDA history (as of sentinel)']],
            'potential_applications' => [['title' => 'Sleep', 'description' => 'sentinel']],
            'conclusion' => 'not withdrawn for reasons of safety sentinel',
            'references' => [['citation' => '74 FR 23407 sentinel']],
            'half_life' => 'J Clin Endocrinol Metab 1994 sentinel',
            'areas_of_research' => [['name' => 'GHRH and sleep', 'description' => 'sentinel']],
            default => $new,
        };
    }

    private function category(string $name, string $slug, string $description = 'Catalog entry.'): ProductCategory
    {
        return ProductCategory::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function linkedPost(ProductCategory $category, string $title, array $attributes): EducationPost
    {
        $post = EducationPost::create(array_merge([
            'title' => $title,
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'description' => 'DESCRIPTION-'.$title,
        ], $attributes));
        $post->updated_at = now()->subDays(4);
        $post->saveQuietly();

        return $post->fresh();
    }

    private function legacyPost(string $slug, string $overview): EducationPost
    {
        $post = EducationPost::create([
            'title' => 'Legacy '.$slug,
            'slug' => $slug,
            'product_category_id' => null,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'description' => 'LEGACY-DESCRIPTION-'.$slug,
            'overview' => $overview,
        ]);
        $post->updated_at = now()->subDays(4);
        $post->saveQuietly();

        return $post->fresh();
    }

    private function withSourcePhrases(mixed $value): mixed
    {
        if (is_string($value)) {
            return strtr($value, [
                self::RENDERED_PHRASE => self::SOURCE_PHRASE,
                self::LANDSCAPE_RENDERED => self::LANDSCAPE_SOURCE,
            ]);
        }
        if (! is_array($value)) {
            return $value;
        }
        $converted = [];
        foreach ($value as $key => $item) {
            $converted[$key] = $this->withSourcePhrases($item);
        }

        return $converted;
    }

    private function noise(string $value): string
    {
        $value = preg_replace('/ /', "\u{00A0}", $value, 1) ?? $value;

        return str_replace("\n", "\r\n", $value)." \t";
    }

    /**
     * @param  list<string>  $patterns
     * @return list<string>
     */
    private function doseHits(mixed $value, bool $references, array $patterns): array
    {
        $hits = [];
        $walk = function (mixed $item, ?string $key) use (&$walk, &$hits, $references, $patterns): void {
            if (is_array($item)) {
                foreach ($item as $childKey => $child) {
                    if ($references && $childKey === 'title') {
                        continue;
                    }
                    $walk($child, is_string($childKey) ? $childKey : $key);
                }

                return;
            }
            if (! is_string($item)) {
                return;
            }
            $scan = str_replace('first 2 hours', '', $item);
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $scan, $matches)) {
                    array_push($hits, ...$matches[0]);
                }
            }
        };
        $walk($value, null);

        return $hits;
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private function faqPage(array $props): array
    {
        foreach ($props['seo']['schema'] as $schema) {
            if (($schema['@type'] ?? null) === 'FAQPage') {
                return $schema;
            }
        }

        $this->fail('FAQPage schema was not rendered.');
    }

    private function captureLogs(): \ArrayObject
    {
        $messages = new \ArrayObject;
        Log::listen(function (MessageLogged $event) use ($messages) {
            $messages->append(['level' => $event->level, 'message' => $event->message]);
        });

        return $messages;
    }

    private function assertLogged(\ArrayObject $messages, string $level, string $message): void
    {
        $this->assertContains(['level' => $level, 'message' => $message], $messages->getArrayCopy());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $decoded = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-updates/2026-10-20/'.$file)), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function protectedSnapshots(): array
    {
        return EducationPost::query()
            ->whereNull('product_category_id')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (EducationPost $post) => [$post->id => $post->getAttributes()])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $before
     */
    private function assertProtectedUnchanged(array $before): void
    {
        $this->assertNotEmpty($before);
        foreach ($before as $id => $attributes) {
            $post = EducationPost::query()->findOrFail($id);
            $this->assertSame($attributes, $post->getAttributes(), $post->slug);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function expectedChangedKeys(array $payload): array
    {
        $keys = array_keys($payload['columns']);
        $keys[] = 'updated_at';
        sort($keys);

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $before
     * @return list<string>
     */
    private function changedKeys(array $before, EducationPost $model): array
    {
        $after = $model->getAttributes();
        $changed = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }
        sort($changed);

        return $changed;
    }
}
