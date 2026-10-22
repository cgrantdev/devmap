<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaFieldUpdatesOctober22Seeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class EncyclopediaFieldUpdatesOctober22Test extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private const PUBLIC_SLUGS = [
        'bpc-157' => 'BPC-157',
        'ghk-cu' => 'GHK-Cu',
        'tb-500' => 'TB-500',
        'retatrutide' => 'retatrutide',
        'cjc-1295' => 'CJC-1295',
    ];

    private const FAQ_DOSE_PATTERN = '/\bmg\b|inject|injection site|orally|cycle|stack|reconstitut|store|buy|vendor/i';

    private const DOSE_UNIT_PATTERN = '/\d+\s?(mg|mcg|µg|IU|mL)/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_writes_when_old_values_match_and_leaves_every_other_row_unchanged(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_22_120000_update_encyclopedia_fields_october_22_2026.php'));
        $this->assertStringContainsString('EncyclopediaFieldUpdatesOctober22Seeder', $migration);
        $this->assertStringContainsString('Previous encyclopedia field values are not restored.', $migration);

        $seeded = $this->seedAll();
        $before = [];
        foreach ($seeded['posts'] as $slug => $post) {
            $before[$slug] = $post->getAttributes();
        }
        $protected = $this->snapshots($seeded['protected']);

        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();

        foreach (array_keys(self::PUBLIC_SLUGS) as $slug) {
            $this->assertSame('updated', $seeder->report[$slug], $slug);
            $fresh = $seeded['posts'][$slug]->fresh();
            $this->assertSame($this->expectedChangedKeys($slug), $this->changedKeys($before[$slug], $fresh), $slug);
            $this->assertSame($this->expectedRegulatory($slug), $fresh->regulatory_subsections, $slug);
            $this->assertSame($this->expectedReferences($slug), $fresh->references, $slug);
            $this->assertSame('KEEP-'.$slug, $fresh->overview, $slug);
            $this->assertSame('KEEP-DESC-'.$slug, $fresh->description, $slug);
        }

        $bpc = $seeded['posts']['bpc-157']->fresh();
        $bpcPayload = $this->payload('bpc-157.json');
        $this->assertSame($bpcPayload['columns']['regulatory_important_note']['new_value'], $bpc->regulatory_important_note);
        $this->assertSame(
            array_merge($bpcPayload['columns']['key_points']['old_value'], [$bpcPayload['columns']['key_points']['new_item']]),
            $bpc->key_points
        );
        $this->assertSame($bpcPayload['columns']['faqs']['new_value'], $bpc->faqs);
        $this->assertStringContainsString('as of 22 October 2026 it is not on the 503A Bulks List', $bpc->faqs[2]['answer']);
        $titles = array_column($bpc->regulatory_subsections, 'title');
        $this->assertSame('U.S. regulatory status — compounding review (as of 19 October 2026)', $titles[2]);
        $this->assertSame('Australia (as of 22 October 2026)', $titles[array_key_last($titles)]);

        $this->assertSnapshots($protected);
    }

    public function test_second_run_writes_nothing(): void
    {
        $seeded = $this->seedAll();
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();

        $stamps = [];
        foreach ($seeded['posts'] as $slug => $post) {
            $fresh = $post->fresh();
            $fresh->updated_at = now()->subDay();
            $fresh->saveQuietly();
            $stamps[$slug] = $fresh->fresh()->getAttributes();
        }
        $protected = $this->snapshots($seeded['protected']);

        $again = new EncyclopediaFieldUpdatesOctober22Seeder;
        $again->run();

        foreach (array_keys(self::PUBLIC_SLUGS) as $slug) {
            $this->assertSame('unchanged', $again->report[$slug], $slug);
            $this->assertSame($stamps[$slug], $seeded['posts'][$slug]->fresh()->getAttributes(), $slug);
        }
        $this->assertSnapshots($protected);
        foreach ($again->log as $line) {
            $this->assertDoesNotMatchRegularExpression('/\b(wrote|appended)\b/', $line);
        }
        $bpcPoints = $seeded['posts']['bpc-157']->fresh()->key_points;
        $this->assertCount(1, array_filter(
            $bpcPoints,
            fn ($point) => is_string($point) && str_starts_with($point, 'Regulatory status (as of 19 Oct 2026)')
        ));
    }

    public function test_guards_skip_when_marker_present(): void
    {
        $bpc = $this->payload('bpc-157.json');
        $ops = $bpc['columns']['regulatory_subsections']['ops'];
        $nsw = $bpc['columns']['references']['new_items'][0]['links'][0]['url'];

        $this->seedAll([
            'bpc-157' => [
                'regulatory_subsections' => array_merge($ops[0]['old_value'], [$ops[0]['new_value'][2]]),
            ],
        ]);
        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();
        $regulatory = $this->only('bpc-157')->regulatory_subsections;
        $this->assertNotContains('Australia (as of 22 October 2026)', array_column($regulatory, 'title'));
        $this->assertTrue($this->logContains($seeder, 'title equals U.S. regulatory status — compounding review (as of 19 October 2026)'));

        $this->wipe();
        $withAustralia = $ops[0]['new_value'];
        $withAustralia[] = ['title' => 'Australia (as of 1 January 2000)', 'entries' => [['type' => 'content', 'value' => 'ALREADY AU']]];
        $this->seedAll(['bpc-157' => ['regulatory_subsections' => $withAustralia]]);
        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();
        $regulatory = $this->only('bpc-157')->regulatory_subsections;
        $this->assertSame('ALREADY AU', $regulatory[array_key_last($regulatory)]['entries'][0]['value']);
        $this->assertCount(1, array_filter(array_column($regulatory, 'title'), fn ($title) => str_starts_with((string) $title, 'Australia (as of')));
        $this->assertTrue($this->logContains($seeder, 'title starts with Australia (as of'));

        $this->wipe();
        $withNsw = $ops[0]['new_value'];
        $withNsw[] = ['title' => 'Note', 'entries' => [['type' => 'content', 'value' => 'See NSW Health bulletin']]];
        $this->seedAll(['bpc-157' => ['regulatory_subsections' => $withNsw]]);
        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();
        $this->assertNotContains('Australia (as of 22 October 2026)', array_column($this->only('bpc-157')->regulatory_subsections, 'title'));
        $this->assertTrue($this->logContains($seeder, 'entry contains NSW Health'));

        $this->wipe();
        $references = $bpc['columns']['references']['old_value'];
        $references[0]['links'][] = ['url' => $nsw, 'label' => 'already'];
        $this->seedAll(['bpc-157' => ['references' => $references]]);
        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();
        $storedRefs = $this->only('bpc-157')->references;
        $this->assertCount(count($references), $storedRefs);
        $this->assertNotContains('Health authorities issue unapproved peptide use warning after surge in NSW hospital visits', array_column($storedRefs, 'title'));
        $this->assertTrue($this->logContains($seeder, 'link url equals '.$nsw));
        $this->assertSame('Australia (as of 22 October 2026)', array_column($this->only('bpc-157')->regulatory_subsections, 'title')[array_key_last(array_column($this->only('bpc-157')->regulatory_subsections, 'title'))]);

        $this->wipe();
        $points = $bpc['columns']['key_points']['old_value'];
        $points[] = 'Regulatory status (as of 1 January 1999): already recorded.';
        $this->seedAll(['bpc-157' => ['key_points' => $points]]);
        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();
        $this->assertSame($points, $this->only('bpc-157')->key_points);
        $this->assertTrue($this->logContains($seeder, 'item starts with Regulatory status (as of '));

        $this->wipe();
        $faqs = $bpc['columns']['faqs']['old_value'];
        $faqs[] = ['question' => 'Has BPC-157 been tested in humans?', 'answer' => 'skip me'];
        $this->seedAll(['bpc-157' => ['faqs' => $faqs]]);
        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();
        $storedFaqs = $this->only('bpc-157')->faqs;
        $this->assertSame($faqs, $storedFaqs);
        $this->assertNotContains('What does the animal research on BPC-157 show?', array_column($storedFaqs, 'question'));
        $this->assertTrue($this->logContains($seeder, 'question equals Has BPC-157 been tested in humans?'));
        $this->assertContains(
            'NSW Health warns of surge in hospital presentations linked to peptide use',
            array_column($this->only('bpc-157')->references, 'title')
        );
    }

    public function test_missing_slug_is_a_no_op_and_warns(): void
    {
        $decoy = $this->encyclopediaPost(null, 'semaglutide', [
            'overview' => 'DECOY',
            'regulatory_subsections' => [['title' => 'DECOY']],
        ]);
        $legacy = $this->encyclopediaPost(null, 'bpc-157', [
            'status' => 'draft',
            'overview' => 'LEGACY-OVERVIEW',
            'regulatory_subsections' => [['title' => 'LEGACY-REG', 'entries' => [['type' => 'content', 'value' => 'LEGACY-BODY']]]],
            'key_points' => ['LEGACY-POINT'],
        ]);
        $decoyBefore = $decoy->getAttributes();
        $legacyBefore = $legacy->getAttributes();

        Log::spy();
        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();

        $this->assertSame('missing', $seeder->report['bpc-157']);
        $this->assertSame(0, $seeder->report_counts['bpc-157']['categories']);
        $this->assertSame(0, $seeder->report_counts['bpc-157']['posts']);
        $this->assertSame($decoyBefore, $decoy->fresh()->getAttributes());
        $this->assertSame($legacyBefore, $legacy->fresh()->getAttributes());
        $blob = implode("\n", $seeder->log);
        $this->assertStringContainsString('bpc-157', $blob);
        $this->assertStringContainsString('category_count=0', $blob);
        $this->assertStringContainsString('post_count=0', $blob);
        $this->assertStringContainsString('slug=[bpc-157]', $blob);
        $this->assertStringContainsString('product_category_id=null', $blob);
        $this->assertStringContainsString('LEGACY-REG', $blob);
        $this->assertStringContainsString('LEGACY-POINT', $blob);
        Log::shouldHaveReceived('warning')->withArgs(function ($message) {
            return is_string($message)
                && str_contains($message, 'bpc-157')
                && str_contains($message, 'category_count=0')
                && str_contains($message, 'post_count=0');
        })->atLeast()->once();
    }

    public function test_old_value_mismatch_leaves_that_column_unchanged(): void
    {
        $bpc = $this->payload('bpc-157.json');
        $faqs = $bpc['columns']['faqs']['old_value'];
        $faqs[0]['answer'] .= ' MISMATCH';
        $this->seedAll(['bpc-157' => ['faqs' => $faqs]]);
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        $post = $this->only('bpc-157');
        $this->assertSame($faqs, $post->faqs);
        $this->assertSame('Australia (as of 22 October 2026)', $post->regulatory_subsections[array_key_last($post->regulatory_subsections)]['title']);

        $this->wipe();
        $references = $bpc['columns']['references']['old_value'];
        $references[0]['title'] .= ' MISMATCH';
        $this->seedAll(['bpc-157' => ['references' => $references]]);
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        $post = $this->only('bpc-157');
        $this->assertSame($references, $post->references);
        $this->assertSame($bpc['columns']['faqs']['new_value'], $post->faqs);

        foreach (['ghk-cu', 'tb-500', 'retatrutide', 'cjc-1295'] as $slug) {
            $this->wipe();
            $payload = $this->payload($slug.'.json');
            $op = $payload['columns']['regulatory_subsections']['ops'][0];
            $regulatory = $op['old_values'][0] ?? $op['old_value'];
            $regulatory[0]['entries'][0]['value'] .= ' MISMATCH';
            $this->seedAll([$slug => ['regulatory_subsections' => $regulatory]]);
            (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
            $post = $this->only($slug);
            $this->assertSame($regulatory, $post->regulatory_subsections, $slug);
            $this->assertNotContains('Australia (as of 22 October 2026)', array_column($post->regulatory_subsections, 'title'));
            $this->assertSame($this->expectedReferences($slug), $post->references, $slug);
        }

        $this->wipe();
        $points = $bpc['columns']['key_points']['old_value'];
        $points[0] .= ' MISMATCH';
        $note = $bpc['columns']['regulatory_important_note']['old_value'].' MISMATCH';
        $this->seedAll(['bpc-157' => ['key_points' => $points, 'regulatory_important_note' => $note]]);
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        $post = $this->only('bpc-157');
        $this->assertSame($points, $post->key_points);
        $this->assertSame($note, $post->regulatory_important_note);
        $this->assertStringNotContainsString('Regulatory status (as of 19 Oct 2026)', implode("\n", $post->key_points));
        $this->assertStringNotContainsString('Regulatory status (as of 19 October 2026)', $post->regulatory_important_note);
    }

    public function test_case_variants_match_and_legacy_row_is_never_written(): void
    {
        $legacy = $this->encyclopediaPost(null, 'bpc-157', [
            'status' => 'draft',
            'regulatory_subsections' => [['title' => 'LEGACY-REG', 'entries' => [['type' => 'content', 'value' => 'LEGACY-BODY']]]],
            'key_points' => ['LEGACY-POINT'],
            'overview' => 'LEGACY-OVERVIEW',
        ]);
        $legacyBefore = $legacy->getAttributes();

        foreach (['BPC-157', 'bpc-157', ' BPC-157 ', 'Bpc-157'] as $index => $categorySlug) {
            $category = ProductCategory::create([
                'name' => 'BPC-157',
                'slug' => $categorySlug,
                'is_active' => true,
            ]);
            $linked = $this->encyclopediaPost($category, 'linked-bpc-'.$index, array_merge($this->oldAttributes('bpc-157'), [
                'overview' => 'KEEP-LINKED',
            ]));

            $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
            $seeder->run();

            $this->assertSame('updated', $seeder->report['bpc-157'], $categorySlug);
            $this->assertSame('Australia (as of 22 October 2026)', $linked->fresh()->regulatory_subsections[array_key_last($linked->fresh()->regulatory_subsections)]['title']);
            $this->assertSame($legacyBefore, $legacy->fresh()->getAttributes(), $categorySlug);
            $blob = implode("\n", $seeder->log);
            $this->assertStringContainsString('slug=[bpc-157]', $blob);
            $this->assertStringContainsString('product_category_id=null', $blob);
            $this->assertStringContainsString('LEGACY-REG', $blob);
            $this->assertStringContainsString('LEGACY-POINT', $blob);
            $this->assertStringContainsString('status=draft', $blob);

            $linked->delete();
            $category->delete();
        }

        $this->assertSame($legacyBefore, $legacy->fresh()->getAttributes());
    }

    public function test_duplicate_category_or_posts_warn_and_write_nothing(): void
    {
        $first = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        $second = ProductCategory::create(['name' => 'BPC-157 other', 'slug' => 'bpc-157', 'is_active' => true]);
        $post = $this->encyclopediaPost($first, 'linked-dup-cat', $this->oldAttributes('bpc-157'));
        $other = $this->encyclopediaPost($second, 'linked-dup-other', ['overview' => 'OTHER', 'regulatory_subsections' => [['title' => 'OTHER']]]);
        $postBefore = $post->getAttributes();
        $otherBefore = $other->getAttributes();

        Log::spy();
        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report['bpc-157']);
        $this->assertSame(2, $seeder->report_counts['bpc-157']['categories']);
        $this->assertSame($postBefore, $post->fresh()->getAttributes());
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertTrue($this->logContains($seeder, 'category_count=2'));
        Log::shouldHaveReceived('warning')->withArgs(function ($message) {
            return is_string($message) && str_contains($message, 'bpc-157') && str_contains($message, 'category_count=2');
        })->atLeast()->once();

        $this->wipe();
        $category = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        // product_category_id is unique, so two linked posts cannot be
        // inserted on a migrated database. Drop that index only here so
        // the seeder's post_count=2 guard is still exercised.
        Schema::table('education_posts', function (Blueprint $table) {
            $table->dropUnique(['product_category_id']);
        });
        $left = $this->encyclopediaPost($category, 'dup-post-a', array_merge($this->oldAttributes('bpc-157'), ['overview' => 'DUP-A']));
        $right = $this->encyclopediaPost($category, 'dup-post-b', array_merge($this->oldAttributes('bpc-157'), ['overview' => 'DUP-B']));
        $leftBefore = $left->getAttributes();
        $rightBefore = $right->getAttributes();

        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report['bpc-157']);
        $this->assertSame(2, $seeder->report_counts['bpc-157']['posts']);
        $this->assertSame(1, $seeder->report_counts['bpc-157']['categories']);
        $this->assertSame($leftBefore, $left->fresh()->getAttributes());
        $this->assertSame($rightBefore, $right->fresh()->getAttributes());
        $this->assertSame('DUP-A', $left->fresh()->overview);
        $this->assertSame('DUP-B', $right->fresh()->overview);
        $this->assertTrue($this->logContains($seeder, 'post_count=2'));
    }

    public function test_whitespace_drift_matches_and_a_word_change_does_not(): void
    {
        $overrides = [];
        $original = [];
        foreach (array_keys(self::PUBLIC_SLUGS) as $slug) {
            $original[$slug] = $this->oldAttributes($slug);
            $overrides[$slug] = $this->drift($original[$slug]);
        }
        foreach ($overrides['bpc-157']['faqs'] as &$faq) {
            $faq['answer'] = '  <p>'.$faq['answer'].'</p>  ';
        }
        unset($faq);
        $this->seedAll($overrides);

        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();

        foreach (array_keys(self::PUBLIC_SLUGS) as $slug) {
            $this->assertSame('updated', $seeder->report[$slug], $slug);
        }

        $bpc = $this->only('bpc-157');
        $bpcPayload = $this->payload('bpc-157.json');
        $this->assertSame($this->expectedRegulatory('bpc-157'), $bpc->regulatory_subsections);
        $this->assertSame($bpcPayload['columns']['regulatory_important_note']['new_value'], $bpc->regulatory_important_note);
        $this->assertSame($bpcPayload['columns']['faqs']['new_value'], $bpc->faqs);
        $this->assertAppendedToStored($original['bpc-157']['key_points'], $bpc->key_points, $bpcPayload['columns']['key_points']['new_item']);
        $this->assertAppendedToStored($original['bpc-157']['references'], $bpc->references, $bpcPayload['columns']['references']['new_items']);

        foreach (['ghk-cu', 'tb-500', 'retatrutide', 'cjc-1295'] as $slug) {
            $post = $this->only($slug);
            $payload = $this->payload($slug.'.json');
            $au = $payload['columns']['regulatory_subsections']['ops'][0]['au_subsection'];
            $this->assertAppendedToStored($original[$slug]['regulatory_subsections'], $post->regulatory_subsections, $au);
            $this->assertAppendedToStored($original[$slug]['references'], $post->references, $payload['columns']['references']['new_items']);
        }

        $this->wipe();
        $note = $bpcPayload['columns']['regulatory_important_note']['old_value'];
        $this->assertStringContainsString('safety', $note);
        $changed = preg_replace('/safety/', 'safetyWORD', $note, 1);
        $this->seedAll(['bpc-157' => ['regulatory_important_note' => $changed]]);
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        $post = $this->only('bpc-157');
        $this->assertSame($changed, $post->regulatory_important_note);
        $this->assertStringNotContainsString('Regulatory status (as of 19 October 2026)', $post->regulatory_important_note);
        $this->assertSame($this->expectedReferences('bpc-157'), $post->references);
    }

    public function test_rendered_pages(): void
    {
        $this->seedAll();
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();

        $sermorelin = ProductCategory::create(['name' => 'Sermorelin', 'slug' => 'Sermorelin', 'is_active' => true]);
        $this->encyclopediaPost($sermorelin, 'Sermorelin', [
            'faqs' => [[
                'question' => 'What is the best time to inject Sermorelin?',
                'answer' => '  <p>Bedtime is often discussed.</p>  ',
            ]],
            'regulatory_subsections' => [['title' => 'Current Status', 'entries' => [['type' => 'content', 'value' => 'SER-REG']]]],
        ]);

        $bpcFaqs = $this->payload('bpc-157.json')['columns']['faqs']['new_value'];
        $bpc = $this->get('/encyclopedia/BPC-157');
        $bpc->assertOk();
        $bpc->assertDontSee('2-4 weeks', false);
        $bpc->assertDontSee('What is the recommended dosage?', false);
        $bpc->assertInertia(function ($page) use ($bpcFaqs) {
            $props = $page->toArray()['props'];
            $faqs = $props['faqs'];
            $this->assertSame($bpcFaqs, $faqs);
            $this->assertCount(5, $faqs);
            for ($i = 0; $i < 4; $i++) {
                $blob = $faqs[$i]['question']."\n".$faqs[$i]['answer'];
                $this->assertDoesNotMatchRegularExpression(self::FAQ_DOSE_PATTERN, $blob, 'faq '.$i);
            }
            $this->assertSame($bpcFaqs[4], $faqs[4]);
            $this->assertStringContainsString('stacking', $faqs[4]['answer']);
            $this->assertStringContainsString('not verified', $faqs[4]['answer']);
            $this->assertDoesNotMatchRegularExpression('/verified/i', str_ireplace('not verified', '', $faqs[4]['answer']));

            $faqPage = collect($props['seo']['schema'])->firstWhere('@type', 'FAQPage');
            $this->assertIsArray($faqPage);
            $this->assertCount(5, $faqPage['mainEntity']);
            foreach ($bpcFaqs as $index => $faq) {
                $this->assertSame($faq['question'], $faqPage['mainEntity'][$index]['name']);
                $this->assertSame($faq['answer'], $faqPage['mainEntity'][$index]['acceptedAnswer']['text']);
            }
            $titles = array_column($props['regulatorySubsections'], 'title');
            $this->assertContains('Australia (as of 22 October 2026)', $titles);

            return true;
        });

        foreach (self::PUBLIC_SLUGS as $slug => $public) {
            $this->get('/encyclopedia/'.$public)
                ->assertOk()
                ->assertInertia(function ($page) use ($public) {
                    $titles = array_column($page->toArray()['props']['regulatorySubsections'], 'title');
                    $this->assertContains('Australia (as of 22 October 2026)', $titles, $public);

                    return true;
                });
        }

        $this->get('/encyclopedia/Sermorelin')
            ->assertOk()
            ->assertInertia(function ($page) {
                $props = $page->toArray()['props'];
                $this->assertSame([], $props['faqs']);
                $faqPage = collect($props['seo']['schema'])->firstWhere('@type', 'FAQPage');
                $this->assertIsArray($faqPage);
                $this->assertSame('What is the best time to inject Sermorelin?', $faqPage['mainEntity'][0]['name']);
                $this->assertSame('Bedtime is often discussed.', $faqPage['mainEntity'][0]['acceptedAnswer']['text']);

                return true;
            });
    }

    public function test_ship_date_placeholder_is_rejected(): void
    {
        $directory = EncyclopediaFieldUpdatesOctober22Seeder::directory();
        foreach (glob($directory.'/*.json') as $file) {
            $this->assertStringNotContainsString('[SHIP DATE]', (string) file_get_contents($file), $file);
        }
        $answer = $this->payload('bpc-157.json')['columns']['faqs']['new_value'][2]['answer'];
        $this->assertStringContainsString('as of 22 October 2026 it is not on the 503A Bulks List', $answer);

        $scratch = sys_get_temp_dir().'/oct22-payload-'.uniqid('', true);
        mkdir($scratch);
        foreach (glob($directory.'/*.json') as $file) {
            copy($file, $scratch.'/'.basename($file));
        }
        $copy = json_decode((string) file_get_contents($scratch.'/bpc-157.json'), true, 512, JSON_THROW_ON_ERROR);
        $copy['columns']['faqs']['new_value'][2]['answer'] = str_replace('22 October 2026', '[SHIP DATE]', $copy['columns']['faqs']['new_value'][2]['answer']);
        file_put_contents($scratch.'/bpc-157.json', json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('[SHIP DATE]');
        (new EncyclopediaFieldUpdatesOctober22Seeder)->assertPayload($scratch);
    }

    public function test_committed_payload_sha256(): void
    {
        $expected = [
            'bpc-157.json' => '177d867af5032c4a32d06b10017bd05b8de3409e0cb86082f6ff7177f80d45c0',
            'cjc-1295.json' => '5434d9fb5cecf390cdadad229d38c3ffea00685b95be0d9684de15ec0565069d',
            'ghk-cu.json' => '241255db5ebabeb88736bf748180a4464ccd8f1aa89c8a4788c1ee19d6254fd7',
            'retatrutide.json' => 'f8084ad86b15893c2305484049bbb25c6de8ebc545a97a25f5412a7ccdc74ebc',
            'tb-500.json' => 'ec97e9caff97531331725013d94a8ebd7843014e973c1f84178db190d679c4c3',
        ];
        foreach ($expected as $file => $hash) {
            $path = EncyclopediaFieldUpdatesOctober22Seeder::directory().'/'.$file;
            $this->assertSame($hash, hash_file('sha256', $path), $file);
        }
    }

    public function test_no_dose_units_in_new_values(): void
    {
        foreach (array_keys(self::PUBLIC_SLUGS) as $slug) {
            $payload = $this->payload($slug.'.json');
            $au = $payload['columns']['regulatory_subsections']['ops'][0]['au_subsection']
                ?? $payload['columns']['regulatory_subsections']['ops'][1]['au_subsection'];
            foreach ($this->stringsIn($au) as $index => $text) {
                $this->assertNoDoseUnits($text, $slug.' au '.$index);
            }
            foreach ($payload['columns']['references']['new_items'] as $index => $item) {
                foreach (['description', 'citation'] as $key) {
                    if (isset($item[$key]) && is_string($item[$key])) {
                        $this->assertNoDoseUnits($item[$key], $slug.' reference '.$index.' '.$key);
                    }
                }
            }
        }

        $bpc = $this->payload('bpc-157.json');
        foreach ($this->stringsIn($bpc['columns']['faqs']['new_value']) as $index => $text) {
            $this->assertNoDoseUnits($text, 'faq '.$index);
        }
        $op = $bpc['columns']['regulatory_subsections']['ops'][0];
        $this->assertNoDoseUnits($op['new_value'][1]['entries'][0]['value'], 'united states');
        foreach ($this->stringsIn($op['new_value'][2]) as $index => $text) {
            $this->assertNoDoseUnits($text, 'compounding '.$index);
        }
        $note = $bpc['columns']['regulatory_important_note']['new_value'];
        $oldNote = $bpc['columns']['regulatory_important_note']['old_value'];
        $this->assertStringStartsWith($oldNote, $note);
        $this->assertNoDoseUnits(substr($note, strlen($oldNote)), 'note remainder');
        $this->assertNoDoseUnits($bpc['columns']['key_points']['new_item'], 'key point');

        $animal = json_encode($op['new_value'][4], JSON_UNESCAPED_UNICODE);
        $this->assertMatchesRegularExpression(self::DOSE_UNIT_PATTERN, (string) $animal);
        $this->assertStringContainsString('/kg', (string) $animal);
    }

    public function test_order_independent_with_reapply(): void
    {
        $first = $this->convergeGhk(true);
        $this->wipe();
        $second = $this->convergeGhk(false);

        $this->assertSame($first, $second);
        $reg = $this->payload('ghk-cu.json')['columns']['regulatory_subsections']['ops'][0];
        $refs = $this->payload('ghk-cu.json')['columns']['references'];
        $this->assertStringStartsWith('FDA compounding status (as of', $reg['old_values'][1][1]['title']);
        $this->assertSame(array_merge($reg['old_values'][1], [$reg['au_subsection']]), $first['regulatory_subsections']);
        $this->assertSame(array_merge($refs['old_values'][1], $refs['new_items']), $first['references']);
        $this->assertSame('KEEP-GHK', $first['overview']);
        $titles = array_column($first['regulatory_subsections'], 'title');
        $this->assertSame([
            'Current Status',
            'FDA compounding status (as of 22 Oct 2026)',
            'Australia (as of 22 October 2026)',
        ], $titles);
        $this->assertCount(17, $first['references']);
    }

    public function test_retatrutide_accepts_post_140000_state_and_appends_au_last(): void
    {
        $reg = $this->payload('retatrutide.json')['columns']['regulatory_subsections']['ops'][0];
        $refs = $this->payload('retatrutide.json')['columns']['references'];
        $senate = $reg['old_values'][1];
        $orfoRefs = $refs['old_values'][1];
        $this->assertCount(count($reg['old_values'][0]) + 1, $senate);
        $this->assertCount(count($refs['old_values'][0]) + 3, $orfoRefs);
        $this->assertStringContainsString('research use only', $senate[array_key_last($senate)]['title']);

        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $post = $this->encyclopediaPost($category, 'retatrutide-post', [
            'regulatory_subsections' => $senate,
            'references' => $orfoRefs,
            'overview' => 'KEEP-RETA',
        ]);

        $seeder = new EncyclopediaFieldUpdatesOctober22Seeder;
        $seeder->run();
        $post->refresh();

        $this->assertSame('updated', $seeder->report['retatrutide']);
        $this->assertSame(array_merge($senate, [$reg['au_subsection']]), $post->regulatory_subsections);
        $this->assertSame(array_merge($orfoRefs, $refs['new_items']), $post->references);
        $titles = array_column($post->regulatory_subsections, 'title');
        $this->assertSame($senate[array_key_last($senate)]['title'], $titles[count($titles) - 2]);
        $this->assertSame('Australia (as of 22 October 2026)', $titles[array_key_last($titles)]);
        $this->assertSame($orfoRefs[10]['title'], $post->references[10]['title']);
        $this->assertCount(16, $post->references);
        $this->assertSame('KEEP-RETA', $post->overview);

        $post->updated_at = now()->subDay();
        $post->saveQuietly();
        $stamp = $post->fresh()->getAttributes();
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        $this->assertSame($stamp, $post->fresh()->getAttributes());
    }

    /**
     * The 2026_10_22_130000 re-apply is a separate PR. This applies the
     * contract that PR has to keep: insert the FDA compounding subsection
     * before an Australia subsection, and keep Australia references after
     * its 14-item list. A second application is a no-op.
     *
     * @return array{regulatory_subsections: array<int, mixed>, references: array<int, mixed>, overview: string}
     */
    private function convergeGhk(bool $octoberFirst): array
    {
        $category = ProductCategory::create([
            'name' => 'GHK-Cu',
            'slug' => 'GHK-Cu',
            'is_active' => true,
        ]);
        $post = $this->encyclopediaPost($category, 'ghk-cu-entry', array_merge($this->oldAttributes('ghk-cu'), [
            'overview' => 'KEEP-GHK',
        ]));

        if ($octoberFirst) {
            (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
            $this->runReapplyContract();
        } else {
            $this->runReapplyContract();
            (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        }

        $post->refresh();
        $result = [
            'regulatory_subsections' => $post->regulatory_subsections,
            'references' => $post->references,
            'overview' => $post->overview,
        ];

        $post->updated_at = now()->subDay();
        $post->saveQuietly();
        $stamp = $post->fresh()->getAttributes();
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        $this->runReapplyContract();
        $this->assertSame($stamp, $post->fresh()->getAttributes());

        return $result;
    }

    private function runReapplyContract(): void
    {
        $payload = $this->payload('ghk-cu.json');
        $reg = $payload['columns']['regulatory_subsections']['ops'][0];
        $refs = $payload['columns']['references'];
        $fda = $reg['old_values'][1][1];
        $fourteen = $refs['old_values'][1];

        $categories = ProductCategory::query()->whereRaw('LOWER(TRIM(slug)) = ?', ['ghk-cu'])->orderBy('id')->get();
        if ($categories->count() !== 1) {
            return;
        }
        $posts = EducationPost::query()->where('product_category_id', $categories->first()->id)->orderBy('id')->get();
        if ($posts->count() !== 1) {
            return;
        }
        /** @var EducationPost $post */
        $post = $posts->first();
        $dirty = false;

        $sections = is_array($post->regulatory_subsections) ? $post->regulatory_subsections : [];
        $hasFda = false;
        foreach ($sections as $section) {
            if (is_array($section) && str_starts_with((string) ($section['title'] ?? ''), 'FDA compounding status (as of')) {
                $hasFda = true;
            }
        }
        if (! $hasFda) {
            $next = [];
            $inserted = false;
            foreach ($sections as $section) {
                if (! $inserted && is_array($section) && str_starts_with((string) ($section['title'] ?? ''), 'Australia (as of')) {
                    $next[] = $fda;
                    $inserted = true;
                }
                $next[] = $section;
            }
            if (! $inserted) {
                $next[] = $fda;
            }
            $post->regulatory_subsections = $next;
            $dirty = true;
        }

        $currentRefs = is_array($post->references) ? $post->references : [];
        $auRefs = [];
        foreach ($currentRefs as $reference) {
            if (is_array($reference) && $this->isAuReference($reference, $refs['new_items'])) {
                $auRefs[] = $reference;
            }
        }
        $desired = array_merge($fourteen, $auRefs);
        if (EncyclopediaFieldUpdatesOctober22Seeder::normalizeValue($currentRefs) !== EncyclopediaFieldUpdatesOctober22Seeder::normalizeValue($desired)) {
            $post->references = $desired;
            $dirty = true;
        }

        if ($dirty) {
            $post->saveQuietly();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $newItems
     * @param  array<string, mixed>  $reference
     */
    private function isAuReference(array $reference, array $newItems): bool
    {
        $normalised = EncyclopediaFieldUpdatesOctober22Seeder::normalizeValue($reference);
        foreach ($newItems as $item) {
            if ($normalised === EncyclopediaFieldUpdatesOctober22Seeder::normalizeValue($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array<string, mixed>>  $overrides
     * @return array{posts: array<string, EducationPost>, protected: array<string, EducationPost>}
     */
    private function seedAll(array $overrides = []): array
    {
        $posts = [];
        foreach (self::PUBLIC_SLUGS as $slug => $public) {
            $category = ProductCategory::create([
                'name' => $public,
                'slug' => $public,
                'is_active' => true,
            ]);
            $attributes = array_merge($this->oldAttributes($slug), $overrides[$slug] ?? [], [
                'overview' => 'KEEP-'.$slug,
                'description' => 'KEEP-DESC-'.$slug,
            ]);
            $posts[$slug] = $this->encyclopediaPost($category, $public.'-post', $attributes);
        }

        $protected = [];
        foreach ([
            'CJC-1295-Ipamorelin' => 'LOCKED-CJC-IPA',
            'BPC-157-TB-500' => 'LOCKED-BLEND',
            'Semaglutide' => 'LOCKED-OTHER',
        ] as $slug => $marker) {
            $category = ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'is_active' => true,
            ]);
            $protected[$slug] = $this->encyclopediaPost($category, $slug, [
                'overview' => $marker,
                'description' => $marker,
                'regulatory_subsections' => [['title' => $marker, 'entries' => [['type' => 'content', 'value' => $marker]]]],
                'references' => [['title' => $marker, 'description' => $marker]],
                'key_points' => [$marker],
                'faqs' => [['question' => $marker, 'answer' => $marker]],
            ]);
        }

        return ['posts' => $posts, 'protected' => $protected];
    }

    private function only(string $slug): EducationPost
    {
        $category = ProductCategory::query()->whereRaw('LOWER(TRIM(slug)) = ?', [$slug])->firstOrFail();

        return EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function oldAttributes(string $slug): array
    {
        $columns = $this->payload($slug.'.json')['columns'];
        $attributes = [];
        foreach ($columns as $name => $spec) {
            if (! is_array($spec)) {
                continue;
            }
            if (isset($spec['ops'][0]) && is_array($spec['ops'][0])) {
                $op = $spec['ops'][0];
                $attributes[$name] = $op['old_values'][0] ?? $op['old_value'];

                continue;
            }
            if (array_key_exists('old_values', $spec)) {
                $attributes[$name] = $spec['old_values'][0];

                continue;
            }
            if (array_key_exists('old_value', $spec)) {
                $attributes[$name] = $spec['old_value'];
            }
        }

        return $attributes;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expectedRegulatory(string $slug): array
    {
        $spec = $this->payload($slug.'.json')['columns']['regulatory_subsections'];
        if ($slug === 'bpc-157') {
            return array_merge($spec['ops'][0]['new_value'], [$spec['ops'][1]['au_subsection']]);
        }
        $op = $spec['ops'][0];
        $base = $op['old_values'][0] ?? $op['old_value'];

        return array_merge($base, [$op['au_subsection']]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function expectedReferences(string $slug): array
    {
        $spec = $this->payload($slug.'.json')['columns']['references'];
        $base = $spec['old_values'][0] ?? $spec['old_value'];

        return array_merge($base, $spec['new_items']);
    }

    /**
     * @return list<string>
     */
    private function expectedChangedKeys(string $slug): array
    {
        $keys = array_keys($this->payload($slug.'.json')['columns']);
        $keys[] = 'updated_at';
        sort($keys);

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function encyclopediaPost(?ProductCategory $category, string $slug, array $attributes = []): EducationPost
    {
        $post = EducationPost::create(array_merge([
            'title' => $slug,
            'slug' => $slug,
            'product_category_id' => $category?->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'description' => 'DESCRIPTION-'.$slug,
        ], $attributes));
        $post->updated_at = now()->subDays(4);
        $post->saveQuietly();

        return $post->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $decoded = json_decode(
            (string) file_get_contents(EncyclopediaFieldUpdatesOctober22Seeder::directory().'/'.$file),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @param  array<string, EducationPost>  $posts
     * @return array<int, array<string, mixed>>
     */
    private function snapshots(array $posts): array
    {
        $snapshots = [];
        foreach ($posts as $post) {
            $snapshots[$post->id] = $post->fresh()->getAttributes();
        }
        $this->assertNotEmpty($snapshots);

        return $snapshots;
    }

    /**
     * @param  array<int, array<string, mixed>>  $before
     */
    private function assertSnapshots(array $before): void
    {
        foreach ($before as $id => $attributes) {
            $post = EducationPost::query()->findOrFail($id);
            $this->assertSame($attributes, $post->getAttributes(), $post->slug);
        }
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

    private function logContains(EncyclopediaFieldUpdatesOctober22Seeder $seeder, string $needle): bool
    {
        foreach ($seeder->log as $line) {
            if (str_contains($line, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function wipe(): void
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
    }

    private function assertNoDoseUnits(string $text, string $label): void
    {
        $this->assertDoesNotMatchRegularExpression(self::DOSE_UNIT_PATTERN, $text, $label);
        $this->assertStringNotContainsString('/kg', $text, $label);
    }

    /**
     * @return list<string>
     */
    private function stringsIn(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }
        if (! is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $out = array_merge($out, $this->stringsIn($item));
        }

        return $out;
    }

    private function drift(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = preg_replace('/ /u', "\u{00A0}", $value, 1) ?? $value;
            $value = implode("\n", array_map(static fn (string $line) => $line.'   ', explode("\n", $value)));

            return str_replace("\n", "\r\n", '  '.$value.'  ');
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = $this->drift($item);
            }

            return $out;
        }

        return $value;
    }

    /**
     * @param  list<mixed>|array<string, mixed>  $original
     * @param  list<mixed>  $stored
     */
    private function assertAppendedToStored(array $original, array $stored, mixed $appended): void
    {
        $items = is_array($appended) && array_is_list($appended) && isset($appended[0]) && is_array($appended[0]) && array_key_exists('title', $appended[0]) && ! array_key_exists('entries', $appended[0])
            ? $appended
            : [$appended];
        // References are a list of objects with title and no entries. A single
        // subsection also has a title, plus entries. Key-point strings do not.
        if (is_array($appended) && array_is_list($appended) && isset($appended[0]) && is_array($appended[0]) && ! array_key_exists('entries', $appended[0])) {
            $items = $appended;
        } else {
            $items = [$appended];
        }
        $prefix = array_slice($stored, 0, count($stored) - count($items));
        $suffix = array_slice($stored, -count($items));
        $this->assertSame($items, $suffix);
        $this->assertSame(
            EncyclopediaFieldUpdatesOctober22Seeder::normalizeValue($original),
            EncyclopediaFieldUpdatesOctober22Seeder::normalizeValue($prefix)
        );
        $this->assertNotSame($original, $prefix);
    }
}
