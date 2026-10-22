<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaFieldReapplyOctober22Seeder;
use Database\Seeders\EncyclopediaFieldUpdatesOctober22Seeder;
use Database\Seeders\EncyclopediaOrfoRetaOctober22Seeder;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class EncyclopediaOrfoRetaOctober22Test extends TestCase
{
    use RefreshDatabase;

    private const DOSE_PATTERN = '/\d+(?:[.·]\d+)?\s?(mg|mcg|µg|IU|mL)\b|\/kg/';

    private const COMMERCE_PATTERN = '/\bbuy\b|coupon|discount|\$\s?\d|in stock|\bCOA\b|per day|daily dose|\bcycle\b|reconstitute\b/i';

    private const PERCENT_PATTERN = '/\d+(?:\.\d+)?\s?%/';

    private const POISON_SENTENCE = 'a 265% increase in poisonings related to illicit retatrutide in the first four months of 2026 compared to the last four months of 2025.';

    private const CUSTOMS_QUOTE = 'to work more closely with U.S. Customs & Border Protection and other law enforcement agencies to take these illicit drugs off the market and hold the companies selling them accountable';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_legacy_unlinked_rows_stay_byte_identical_and_are_named_in_the_diagnostic(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_22_140000_orforglipron_retatrutide_regulatory_october_22.php'));
        $this->assertStringContainsString('EncyclopediaOrfoRetaOctober22Seeder', $migration);
        $this->assertStringContainsString('Previous encyclopedia field values are not restored.', $migration);

        $legacyOrfo = $this->educationPost(null, 'orforglipron', $this->oldAttributes('orforglipron'));
        $legacyReta = $this->educationPost(null, 'retatrutide', $this->oldAttributes('retatrutide'));
        $linked = $this->seedPair();
        $legacyBefore = [
            $legacyOrfo->id => $legacyOrfo->fresh()->getAttributes(),
            $legacyReta->id => $legacyReta->fresh()->getAttributes(),
        ];

        $seeder = new EncyclopediaOrfoRetaOctober22Seeder;
        $seeder->run();

        $this->assertSame('updated', $seeder->report['orforglipron']);
        $this->assertSame('updated', $seeder->report['retatrutide']);
        $this->assertSame($legacyBefore[$legacyOrfo->id], $legacyOrfo->fresh()->getAttributes());
        $this->assertSame($legacyBefore[$legacyReta->id], $legacyReta->fresh()->getAttributes());
        $this->assertNull($legacyOrfo->fresh()->product_category_id);
        $this->assertNull($legacyReta->fresh()->product_category_id);
        $blob = implode("\n", $seeder->log);
        $this->assertStringContainsString('id='.$legacyOrfo->id.' ', $blob);
        $this->assertStringContainsString('id='.$legacyReta->id.' ', $blob);
        $this->assertStringContainsString('slug=[orforglipron]', $blob);
        $this->assertStringContainsString('slug=[retatrutide]', $blob);
        $this->assertStringContainsString('product_category_id=null', $blob);
        $this->assertSame(
            $this->payload('orforglipron')['columns']['key_points']['target_value'],
            $linked['orforglipron']->fresh()->key_points
        );
    }

    public function test_case_and_trimmed_category_slugs_match_and_write(): void
    {
        $cases = [
            ['orforglipron', 'orforglipron'],
            ['orforglipron', 'Orforglipron'],
            ['orforglipron', ' ORFORGLIPRON '],
            ['retatrutide', 'retatrutide'],
            ['retatrutide', 'Retatrutide'],
            ['retatrutide', ' RETATRUTIDE '],
        ];

        foreach ($cases as [$slug, $categorySlug]) {
            $this->deleteNormalized($slug);
            $post = $this->seedOne($slug, $categorySlug, [], $slug.'-case-'.md5($categorySlug));
            $seeder = new EncyclopediaOrfoRetaOctober22Seeder;
            $seeder->run();

            $this->assertSame('updated', $seeder->report[$slug], $categorySlug);
            $this->assertSame($this->expected($slug), $this->readColumns($post->fresh(), $slug), $categorySlug);
            $this->assertSame($categorySlug, $post->fresh()->category->slug);
        }
    }

    public function test_duplicate_category_or_posts_collide_without_opening_a_transaction(): void
    {
        $first = ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        $second = ProductCategory::create(['name' => 'Retatrutide spaced', 'slug' => ' RETATRUTIDE ', 'is_active' => true]);
        $left = $this->educationPost($first, 'reta-dup-a', $this->oldAttributes('retatrutide'));
        $right = $this->educationPost($second, 'reta-dup-b', $this->oldAttributes('retatrutide'));
        $before = [$left->fresh()->getAttributes(), $right->fresh()->getAttributes()];
        $warnings = $this->captureWarnings();
        $began = $this->countTransactions();

        $seeder = new EncyclopediaOrfoRetaOctober22Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report['retatrutide']);
        $this->assertSame('missing', $seeder->report['orforglipron']);
        $this->assertSame([], $seeder->transactions);
        $this->assertSame(0, $began());
        $this->assertSame($before[0], $left->fresh()->getAttributes());
        $this->assertSame($before[1], $right->fresh()->getAttributes());
        $this->assertTrue($this->warningsContain($warnings, 'retatrutide', 'categories 2'), json_encode($warnings->getArrayCopy()));

        $this->deleteNormalized('retatrutide');
        $category = ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        try {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->dropUnique(['product_category_id']);
            });
        } catch (\Throwable) {
            // The unique index is already absent in this process.
        }
        $one = $this->educationPost($category, 'reta-post-a', array_merge($this->oldAttributes('retatrutide'), ['overview' => 'DUP-A']));
        $two = $this->educationPost($category, 'reta-post-b', array_merge($this->oldAttributes('retatrutide'), ['overview' => 'DUP-B']));
        $postBefore = [$one->fresh()->getAttributes(), $two->fresh()->getAttributes()];
        $warnings = $this->captureWarnings();
        $began = $this->countTransactions();

        $seeder = new EncyclopediaOrfoRetaOctober22Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report['retatrutide']);
        $this->assertSame([], $seeder->transactions);
        $this->assertSame(0, $began());
        $this->assertSame($postBefore[0], $one->fresh()->getAttributes());
        $this->assertSame($postBefore[1], $two->fresh()->getAttributes());
        $this->assertSame('DUP-A', $one->fresh()->overview);
        $this->assertSame('DUP-B', $two->fresh()->overview);
        $this->assertTrue($this->warningsContain($warnings, 'retatrutide', 'linked posts 2'), json_encode($warnings->getArrayCopy()));

        EducationPost::query()->where('product_category_id', $category->id)->delete();
        try {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->unique('product_category_id');
            });
        } catch (\Throwable) {
            // Leave the schema as the rest of the suite expects when the index is already present.
        }
    }

    public function test_second_run_changes_nothing_and_only_reports_skips(): void
    {
        $seeded = $this->seedPair();
        (new EncyclopediaOrfoRetaOctober22Seeder)->run();

        $snapshots = [];
        foreach ($seeded as $slug => $post) {
            $fresh = $post->fresh();
            $fresh->updated_at = now()->subDay();
            $fresh->saveQuietly();
            $snapshots[$slug] = $fresh->fresh()->getAttributes();
        }

        $again = new EncyclopediaOrfoRetaOctober22Seeder;
        $again->run();

        foreach ($seeded as $slug => $post) {
            $this->assertContains($again->report[$slug], [
                EncyclopediaOrfoRetaOctober22Seeder::MARKER,
                EncyclopediaOrfoRetaOctober22Seeder::AT_TARGET,
            ], $slug);
            $this->assertSame($snapshots[$slug], $post->fresh()->getAttributes(), $slug);
            foreach ($again->columnReport[$slug] as $column => $status) {
                $this->assertContains($status, [
                    EncyclopediaOrfoRetaOctober22Seeder::MARKER,
                    EncyclopediaOrfoRetaOctober22Seeder::AT_TARGET,
                ], $slug.'.'.$column);
            }
        }
        foreach ($again->log as $line) {
            $this->assertStringNotContainsString(': updated', $line);
        }
    }

    public function test_protected_rows_stay_byte_identical_and_only_payload_columns_change(): void
    {
        $protected = $this->seedProtected();
        $protectedBefore = [];
        foreach ($protected as $slug => $post) {
            $protectedBefore[$slug] = $post->fresh()->getAttributes();
        }
        $seeded = $this->seedPair();
        $before = [];
        foreach ($seeded as $slug => $post) {
            $before[$slug] = $post->fresh()->getAttributes();
        }

        $seeder = new EncyclopediaOrfoRetaOctober22Seeder;
        $seeder->run();

        foreach ($protected as $slug => $post) {
            $this->assertSame($protectedBefore[$slug], $post->fresh()->getAttributes(), $slug);
        }
        foreach ($seeded as $slug => $post) {
            $this->assertSame($this->expectedChangedKeys($slug), $this->changedKeys($before[$slug], $post->fresh()), $slug);
            $this->assertSame($this->expected($slug), $this->readColumns($post->fresh(), $slug), $slug);
            $this->assertSame('KEEP-'.$slug, $post->fresh()->overview);
        }
    }

    public function test_order_independence_with_reapply_and_australia(): void
    {
        $post = $this->seedRetatrutide($this->state('B'));
        (new EncyclopediaOrfoRetaOctober22Seeder)->run();
        $this->assertSame($this->targets(1), $this->readColumns($post->fresh()));

        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        (new EncyclopediaFieldReapplyOctober22Seeder)->run();
        $afterOthers = $this->readColumns($post->fresh());
        $this->assertSame($this->targets(1), $afterOthers);

        $this->deleteNormalized('retatrutide');
        $post = $this->seedRetatrutide($this->state('A'));
        (new EncyclopediaOrfoRetaOctober22Seeder)->run();
        (new EncyclopediaFieldReapplyOctober22Seeder)->run();
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
        $this->assertSame($afterOthers, $this->readColumns($post->fresh()));

        $orders = [
            ['au', 're', 'this'],
            ['au', 'this', 're'],
            ['re', 'au', 'this'],
            ['re', 'this', 'au'],
            ['this', 'au', 're'],
            ['this', 're', 'au'],
        ];
        $results = [];
        foreach ($orders as $order) {
            $this->deleteNormalized('retatrutide');
            $post = $this->seedRetatrutide($this->state('A'));
            foreach ($order as $step) {
                $this->runPackage($step);
            }
            $results[] = $this->readColumns($post->fresh());

            $fresh = $post->fresh();
            $fresh->updated_at = now()->subDay();
            $fresh->saveQuietly();
            $stamp = $fresh->fresh()->getAttributes();
            foreach ($order as $step) {
                $this->runPackage($step);
            }
            $this->assertSame($stamp, $post->fresh()->getAttributes(), implode(',', $order));
        }

        foreach ($results as $index => $result) {
            $this->assertSame($results[0], $result, 'order '.$index);
        }
        $this->assertSame($this->targets(1), $results[0]);
    }

    public function test_whitespace_drift_matches_and_a_changed_word_does_not(): void
    {
        $orfoOld = $this->oldAttributes('orforglipron');
        $retaOld = $this->oldAttributes('retatrutide');
        $driftedOrfo = $this->driftAttributes($orfoOld);
        $driftedReta = $this->driftAttributes($retaOld, true, 5);
        $driftedOrfo['key_points'][0] .= ' CHANGED';

        $orfo = $this->seedOne('orforglipron', 'orforglipron', $driftedOrfo);
        $reta = $this->seedOne('retatrutide', 'retatrutide', $driftedReta);
        $seeder = new EncyclopediaOrfoRetaOctober22Seeder;
        $seeder->run();

        $this->assertSame(
            EncyclopediaOrfoRetaOctober22Seeder::MISMATCH,
            $seeder->columnReport['orforglipron']['key_points']
        );
        $this->assertSame($driftedOrfo['key_points'], $orfo->fresh()->key_points);
        $this->assertSame(
            EncyclopediaOrfoRetaOctober22Seeder::UPDATED,
            $seeder->columnReport['orforglipron']['regulatory_subsections']
        );
        $warning = $this->findLog($seeder, 'orforglipron.key_points', 'old mismatch');
        $this->assertNotNull($warning);
        $excerpt = substr($warning, strpos($warning, 'excerpt=') + strlen('excerpt='));
        $this->assertLessThanOrEqual(200, mb_strlen($excerpt));
        $this->assertNotSame('', $excerpt);

        $note = $reta->fresh()->regulatory_important_note;
        $spec = $this->payload('retatrutide')['columns']['regulatory_important_note'];
        $this->assertStringContainsString($spec['new_sentence'], $note);
        $this->assertStringNotContainsString($spec['old_sentence'], $note);
        $this->assertTrue(EncyclopediaOrfoRetaOctober22Seeder::normalize($note) === EncyclopediaOrfoRetaOctober22Seeder::normalize($spec['target_values'][0]));
        $this->assertSame(
            $driftedReta['regulatory_subsections'][0]['title'],
            $reta->fresh()->regulatory_subsections[0]['title']
        );
        $this->assertNotSame(
            $retaOld['regulatory_subsections'][0]['title'],
            $reta->fresh()->regulatory_subsections[0]['title']
        );
        $this->assertCount(count($driftedReta['key_points']), $reta->fresh()->key_points);
        $this->assertSame(
            $this->payload('retatrutide')['columns']['key_points']['new_item'],
            $reta->fresh()->key_points[5]
        );
        $this->assertSame($driftedReta['key_points'][0], $reta->fresh()->key_points[0]);
    }

    public function test_sentence_and_key_point_guards_reject_a_bad_current_value(): void
    {
        $noteSpec = $this->payload('retatrutide')['columns']['regulatory_important_note'];
        $pointSpec = $this->payload('retatrutide')['columns']['key_points'];
        $doubled = $noteSpec['old_values'][0].' '.$noteSpec['old_sentence'];
        $absent = 'A different regulatory note with no section 505 marker.';
        $points = $pointSpec['old_values'][0];
        $points[5] = 'A different planned filing line';

        $doublePost = $this->seedOne('retatrutide', 'retatrutide', [
            'regulatory_important_note' => $doubled,
            'key_points' => $pointSpec['old_values'][0],
        ]);
        $doubleSeeder = new EncyclopediaOrfoRetaOctober22Seeder;
        $doubleSeeder->run();
        $this->assertSame($doubled, $doublePost->fresh()->regulatory_important_note);
        $this->assertSame(
            EncyclopediaOrfoRetaOctober22Seeder::MISMATCH,
            $doubleSeeder->columnReport['retatrutide']['regulatory_important_note']
        );
        $this->assertNotNull($this->findLog($doubleSeeder, 'regulatory_important_note', 'old mismatch'));

        $this->deleteNormalized('retatrutide');
        $absentPost = $this->seedOne('retatrutide', 'retatrutide', [
            'regulatory_important_note' => $absent,
        ]);
        $absentSeeder = new EncyclopediaOrfoRetaOctober22Seeder;
        $absentSeeder->run();
        $this->assertSame($absent, $absentPost->fresh()->regulatory_important_note);
        $this->assertStringNotContainsString('section 505 of the Federal Food, Drug, and Cosmetic Act', $absent);
        $this->assertNotNull($this->findLog($absentSeeder, 'regulatory_important_note', 'old mismatch'));

        $this->deleteNormalized('retatrutide');
        $pointPost = $this->seedOne('retatrutide', 'retatrutide', ['key_points' => $points]);
        $pointSeeder = new EncyclopediaOrfoRetaOctober22Seeder;
        $pointSeeder->run();
        $freshPoints = $pointPost->fresh()->key_points;
        $this->assertCount(count($points), $freshPoints);
        $this->assertSame('A different planned filing line', $freshPoints[5]);
        $this->assertNotContains($pointSpec['new_item'], $freshPoints);
        $this->assertNotNull($this->findLog($pointSeeder, 'key_points', 'old mismatch'));
    }

    public function test_a_failure_on_the_second_retatrutide_column_rolls_back_only_that_slug(): void
    {
        $seeded = $this->seedPair();
        $retaBefore = $seeded['retatrutide']->fresh()->getAttributes();
        $seeder = new class extends EncyclopediaOrfoRetaOctober22Seeder
        {
            protected function beforeColumn(string $slug, string $column, int $index): void
            {
                if ($slug === 'retatrutide' && $index === 2) {
                    throw new RuntimeException('forced write failure');
                }
            }
        };

        $seeder->run();

        $this->assertSame('updated', $seeder->report['orforglipron']);
        $this->assertSame('failed', $seeder->report['retatrutide']);
        $this->assertSame($this->expected('orforglipron'), $this->readColumns($seeded['orforglipron']->fresh(), 'orforglipron'));
        $this->assertSame($retaBefore, $seeded['retatrutide']->fresh()->getAttributes());
        $this->assertNotNull($this->findLog($seeder, 'retatrutide', 'forced write failure'));
        $this->assertContains('orforglipron', $seeder->transactions);
        $this->assertSame(['orforglipron'], array_values(array_filter(
            $seeder->transactions,
            fn (string $slug): bool => $slug === 'orforglipron',
        )));
    }

    public function test_rendered_pages_show_the_new_eu_and_senate_text(): void
    {
        $this->seedPair();
        (new EncyclopediaOrfoRetaOctober22Seeder)->run();

        $orfo = $this->payload('orforglipron');
        $reta = $this->payload('retatrutide');
        $euTitle = $orfo['columns']['regulatory_subsections']['new_subsection']['title'];
        $euPoint = $orfo['columns']['key_points']['new_item'];
        $ema = 'https://www.ema.europa.eu/en/documents/smop-initial/chmp-summary-positive-opinion-foundayo_en.pdf';
        $senateTitle = $reta['columns']['regulatory_subsections']['new_subsection']['title'];
        $oldSentence = $reta['columns']['regulatory_important_note']['old_sentence'];
        $gone = strstr($oldSentence, ' is a filing plan', true);
        $this->assertIsString($gone);

        $this->get('/encyclopedia/orforglipron')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/EncyclopediaArticleDetail')
                ->where('regulatorySubsections', function ($sections) use ($euTitle) {
                    $last = collect($sections)->last();

                    return is_array($last) && ($last['title'] ?? null) === $euTitle;
                })
                ->where('keyPoints', function ($points) use ($euPoint) {
                    return collect($points)->last() === $euPoint;
                })
                ->where('references', function ($references) use ($ema) {
                    $encoded = json_encode($references, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    return is_string($encoded) && str_contains($encoded, $ema);
                }));

        $this->get('/encyclopedia/retatrutide')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/EncyclopediaArticleDetail')
                ->where('regulatorySubsections.5.title', $senateTitle)
                ->where('regulatoryImportantNote', function ($note) use ($gone) {
                    return is_string($note)
                        && str_contains($note, 'section 505 of the Federal Food, Drug, and Cosmetic Act')
                        && ! str_contains($note, $gone);
                })
                ->where('keyPoints.5', function ($point) {
                    return is_string($point) && str_starts_with($point, 'U.S. pathway (as of 22 Oct 2026)');
                }));
    }

    public function test_dose_and_commerce_patterns_match_only_the_allowlisted_poison_centre_figure(): void
    {
        $corpus = $this->newCorpus();
        $this->assertSame(0, preg_match(self::DOSE_PATTERN, $corpus));
        $this->assertSame(0, preg_match(self::COMMERCE_PATTERN, $corpus));
        preg_match_all(self::PERCENT_PATTERN, $corpus, $matches);
        $this->assertSame(['265%'], $matches[0]);

        $payload = (string) file_get_contents(database_path('seeders/data/encyclopedia-field-updates/2026-10-22c/orfo-reta-2026-10-22-fields.json'));
        $this->assertStringContainsString(self::POISON_SENTENCE, $payload);
        $this->assertStringContainsString(self::POISON_SENTENCE, $corpus);
        $entry = $this->payload('retatrutide')['columns']['regulatory_subsections']['new_subsection']['entries'][1]['value'];
        $this->assertStringContainsString(self::POISON_SENTENCE, $entry);
        $this->assertSame(1, substr_count($corpus, '265%'));
    }

    public function test_quoted_source_strings_occur_verbatim_in_the_new_text(): void
    {
        $path = database_path('seeders/data/encyclopedia-field-updates/2026-10-22c/orfo-reta-2026-10-22-fields.json');
        $this->assertSame(EncyclopediaOrfoRetaOctober22Seeder::SOURCE_PAYLOAD_SHA256, hash_file('sha256', $path));
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(EncyclopediaOrfoRetaOctober22Seeder::PAYLOAD_BASE, $decoded['base']);

        $corpus = $this->newCorpus();
        foreach ([
            'knockoff retatrutide',
            self::CUSTOMS_QUOTE,
            'including weight loss and weight maintenance',
            'pending EC decision',
        ] as $quote) {
            $this->assertStringContainsString($quote, $corpus, $quote);
            $this->assertStringContainsString($quote, (string) file_get_contents($path), $quote);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedOne(string $slug, string $categorySlug, array $overrides = [], ?string $postSlug = null): EducationPost
    {
        $category = ProductCategory::create([
            'name' => trim($categorySlug) === '' ? $slug : $categorySlug,
            'slug' => $categorySlug,
            'description' => 'Catalog '.$slug,
            'is_active' => true,
        ]);

        return $this->educationPost($category, $postSlug ?? $slug.'-linked', array_merge($this->oldAttributes($slug), $overrides));
    }

    /**
     * @return array{orforglipron: EducationPost, retatrutide: EducationPost}
     */
    private function seedPair(): array
    {
        return [
            'orforglipron' => $this->seedOne('orforglipron', 'orforglipron'),
            'retatrutide' => $this->seedOne('retatrutide', 'retatrutide'),
        ];
    }

    /**
     * @param  array<string, mixed>  $columns
     */
    private function seedRetatrutide(array $columns): EducationPost
    {
        $reapply = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-reapply/2026-10-22/retatrutide.json')), true, 512, JSON_THROW_ON_ERROR);
        $human = $reapply['columns']['human_use_subsections'];
        $columns['human_use_subsections'] = $human['old_value_whole_array'] ?? $human['old_value'] ?? $human['old_values'][0];

        return $this->seedOne('retatrutide', 'retatrutide', $columns);
    }

    /**
     * @return array<string, EducationPost>
     */
    private function seedProtected(): array
    {
        $rows = [];
        foreach ([
            'retatrutide-vs-tirzepatide' => 'KEEP-VS',
            'cagrisema-like' => 'KEEP-CAGRI',
            'unrelated-compound' => 'KEEP-OTHER',
        ] as $slug => $marker) {
            $category = ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'is_active' => true,
            ]);
            $rows[$slug] = $this->educationPost($category, $slug, [
                'overview' => $marker,
                'description' => $marker,
                'regulatory_subsections' => [['title' => $marker, 'entries' => [['type' => 'content', 'value' => $marker]]]],
                'regulatory_important_note' => $marker,
                'key_points' => [$marker],
                'references' => [['title' => $marker, 'description' => $marker]],
            ]);
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function educationPost(?ProductCategory $category, string $slug, array $attributes): EducationPost
    {
        $post = EducationPost::create(array_merge([
            'title' => $slug,
            'slug' => $slug,
            'product_category_id' => $category?->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'description' => 'DESCRIPTION-'.$slug,
            'overview' => 'KEEP-'.strtok($slug, '-'),
        ], $attributes));
        $post->updated_at = now()->subDays(4);
        $post->saveQuietly();

        return $post->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function oldAttributes(string $slug): array
    {
        $attributes = [];
        foreach ($this->payload($slug)['columns'] as $column => $spec) {
            if (! is_string($column) || ! is_array($spec)) {
                continue;
            }
            if (array_key_exists('old_values', $spec)) {
                $attributes[$column] = $spec['old_values'][0];
            } elseif (array_key_exists('old_value', $spec)) {
                $attributes[$column] = $spec['old_value'];
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function expected(string $slug): array
    {
        $expected = [];
        foreach ($this->payload($slug)['columns'] as $column => $spec) {
            $expected[$column] = $spec['target_values'][0] ?? $spec['target_value'];
        }

        return $expected;
    }

    /**
     * @return array<string, mixed>
     */
    private function state(string $which): array
    {
        $index = $which === 'B' ? 1 : 0;
        $state = [];
        foreach ($this->payload('retatrutide')['columns'] as $column => $spec) {
            $state[$column] = $spec['old_values'][$index];
        }

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    private function targets(int $index): array
    {
        $targets = [];
        foreach ($this->payload('retatrutide')['columns'] as $column => $spec) {
            $targets[$column] = $spec['target_values'][$index];
        }

        return $targets;
    }

    /**
     * @return array<string, mixed>
     */
    private function readColumns(EducationPost $post, string $slug = 'retatrutide'): array
    {
        $columns = [];
        foreach (array_keys($this->payload($slug)['columns']) as $column) {
            $columns[$column] = $post->{$column};
        }

        return $columns;
    }

    private function runPackage(string $name): void
    {
        match ($name) {
            'au' => (new EncyclopediaFieldUpdatesOctober22Seeder)->run(),
            're' => (new EncyclopediaFieldReapplyOctober22Seeder)->run(),
            'this' => (new EncyclopediaOrfoRetaOctober22Seeder)->run(),
            default => throw new RuntimeException($name),
        };
    }

    private function deleteNormalized(string $slug): void
    {
        $ids = ProductCategory::query()->whereRaw('LOWER(TRIM(slug)) = ?', [$slug])->pluck('id');
        if ($ids->isNotEmpty()) {
            EducationPost::query()->whereIn('product_category_id', $ids)->delete();
        }
        EducationPost::query()->whereRaw('LOWER(TRIM(slug)) = ?', [$slug])->delete();
        if ($ids->isNotEmpty()) {
            ProductCategory::query()->whereIn('id', $ids)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function driftAttributes(array $attributes, bool $protectNote = false, ?int $protectKeyPointIndex = null): array
    {
        $sentence = $protectNote
            ? $this->payload('retatrutide')['columns']['regulatory_important_note']['old_sentence']
            : null;

        foreach ($attributes as $column => $value) {
            if ($column === 'key_points' && $protectKeyPointIndex !== null && is_array($value)) {
                foreach ($value as $index => $item) {
                    if ($index === $protectKeyPointIndex) {
                        continue;
                    }
                    $value[$index] = $this->driftValue($item, null);
                }
                $attributes[$column] = $value;

                continue;
            }
            $attributes[$column] = $this->driftValue($value, $column === 'regulatory_important_note' ? $sentence : null);
        }

        return $attributes;
    }

    private function driftValue(mixed $value, ?string $protect): mixed
    {
        if (is_string($value)) {
            if ($protect !== null && $protect !== '' && str_contains($value, $protect)) {
                [$head, $tail] = explode($protect, $value, 2);

                return $this->driftNormalizeSafe($head).$protect.$this->driftNormalizeSafe($tail);
            }

            return $this->driftString($value);
        }
        if (! is_array($value)) {
            return $value;
        }
        $drifted = [];
        foreach ($value as $key => $item) {
            $drifted[$key] = $this->driftValue($item, $protect);
        }

        return $drifted;
    }

    private function driftString(string $value): string
    {
        return ' '.$this->driftNormalizeSafe($value)." \t";
    }

    private function driftNormalizeSafe(string $value): string
    {
        $value = str_replace("\n", "\r\n", $value);

        return preg_replace('/ /', "\u{00A0}", $value, 1) ?? $value;
    }

    private function newCorpus(): string
    {
        $parts = [];
        foreach (['orforglipron', 'retatrutide'] as $slug) {
            $columns = $this->payload($slug)['columns'];
            $subsection = $columns['regulatory_subsections']['new_subsection'] ?? null;
            if (is_array($subsection)) {
                $parts[] = (string) ($subsection['title'] ?? '');
                foreach ($subsection['entries'] ?? [] as $entry) {
                    $parts[] = is_array($entry) ? (string) ($entry['value'] ?? '') : (string) $entry;
                }
            }
            if (isset($columns['regulatory_important_note']['new_sentence'])) {
                $parts[] = (string) $columns['regulatory_important_note']['new_sentence'];
            }
            if (isset($columns['key_points']['new_item'])) {
                $parts[] = (string) $columns['key_points']['new_item'];
            }
            foreach ($columns['references']['new_items'] ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $parts[] = (string) ($item['description'] ?? '');
                $parts[] = (string) ($item['citation'] ?? '');
            }
        }

        return implode("\n", $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $slug): array
    {
        $decoded = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-updates/2026-10-22c/'.$slug.'.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @return list<string>
     */
    private function expectedChangedKeys(string $slug): array
    {
        $keys = array_keys($this->payload($slug)['columns']);
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

    /**
     * @return array<int, string>
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

    private function warningsContain(\ArrayObject $warnings, string $slug, string $needle): bool
    {
        foreach ($warnings as $warning) {
            if (is_string($warning) && str_contains($warning, $slug) && str_contains($warning, $needle)) {
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

    private function findLog(EncyclopediaOrfoRetaOctober22Seeder $seeder, string $left, string $right): ?string
    {
        foreach ($seeder->log as $line) {
            if (str_contains($line, $left) && str_contains($line, $right)) {
                return $line;
            }
        }

        return null;
    }
}
