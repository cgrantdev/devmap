<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaFieldReapplyOctober22Seeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class EncyclopediaFieldReapplyOctober22Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_source_payload_sha256_starts_with_19ff7f45(): void
    {
        $this->assertSame(
            '19ff7f45b21a5819d547cea0855d0327d633b2f288895d493494de6984956ef3',
            EncyclopediaFieldReapplyOctober22Seeder::SOURCE_PAYLOAD_SHA256,
        );
        $this->assertStringStartsWith('19ff7f45', EncyclopediaFieldReapplyOctober22Seeder::SOURCE_PAYLOAD_SHA256);

        $migration = (string) file_get_contents(database_path('migrations/2026_10_22_130000_reapply_october_2026_encyclopedia_fields.php'));
        $this->assertStringContainsString('EncyclopediaFieldReapplyOctober22Seeder', $migration);
        $this->assertStringContainsString('19ff7f45b21a5819d547cea0855d0327d633b2f288895d493494de6984956ef3', $migration);

        $note = $this->payload('retatrutide')['columns']['regulatory_important_note'];
        $this->assertArrayNotHasKey('old_value', $note);
        $this->assertCount(2, $note['old_values']);
        $this->assertCount(2, $note['new_values']);
        $this->assertStringContainsString('2026_10_22_140000', $note['op']);
        $points = $this->payload('retatrutide')['columns']['key_points'];
        $this->assertCount(2, $points['old_values']);
        $this->assertSame(12, count($points['old_values'][0]));
        $this->assertNotSame($points['old_values'][0][5], $points['old_values'][1][5]);
    }

    public function test_legacy_rows_stay_byte_identical_and_only_target_columns_change(): void
    {
        $seeded = $this->seedTargets();
        $legacyRetatrutide = $this->legacyPost('retatrutide');
        $legacyGhk = $this->legacyPost('ghk-cu');
        $protected = [
            $seeded['cagrisema']->id => $seeded['cagrisema']->getAttributes(),
            $seeded['vs']->id => $seeded['vs']->getAttributes(),
            $seeded['unrelated']->id => $seeded['unrelated']->getAttributes(),
            $legacyRetatrutide->id => $legacyRetatrutide->getAttributes(),
            $legacyGhk->id => $legacyGhk->getAttributes(),
        ];
        $categoryBefore = $seeded['cagrilintideCategory']->getAttributes();
        $before = [];
        foreach (['retatrutide', 'ghk-cu', 'cagrilintide'] as $slug) {
            $before[$slug] = $seeded[$slug]->getAttributes();
        }

        $seeder = new EncyclopediaFieldReapplyOctober22Seeder;
        $seeder->run();

        foreach ($protected as $id => $attributes) {
            $this->assertSame($attributes, EducationPost::query()->findOrFail($id)->getAttributes(), (string) $id);
        }
        $this->assertSame($categoryBefore, $seeded['cagrilintideCategory']->fresh()->getAttributes());
        $log = implode("\n", $seeder->log);
        $this->assertStringContainsString('id='.$legacyRetatrutide->id.' slug=[retatrutide]', $log);
        $this->assertStringContainsString('id='.$legacyGhk->id.' slug=[ghk-cu]', $log);
        $this->assertStringContainsString('product_category_id=null', $log);

        foreach (['retatrutide', 'ghk-cu', 'cagrilintide'] as $slug) {
            $fresh = $seeded[$slug]->fresh();
            $this->assertSame($this->expectedColumns($slug), $this->changedKeys($before[$slug], $fresh), $slug);
            $this->assertSame('updated', $seeder->report[$slug], $slug);
        }

        $reta = $this->payload('retatrutide');
        $retaPost = $seeded['retatrutide']->fresh();
        $human = $reta['columns']['human_use_subsections'];
        $this->assertSame([...$human['old_value'], $human['new_subsection']], $retaPost->human_use_subsections);
        $this->assertSame($reta['columns']['regulatory_important_note']['new_values'][0], $retaPost->regulatory_important_note);
        $this->assertSame(
            [...$reta['columns']['key_points']['old_values'][0], $reta['columns']['key_points']['new_item']],
            $retaPost->key_points,
        );
        $this->assertSame('KEEP-RETA-OVERVIEW', $retaPost->overview);

        $cagri = $this->payload('cagrilintide');
        $cagriPost = $seeded['cagrilintide']->fresh();
        foreach ([
            'overview', 'background', 'half_life', 'molecular_formula', 'molecular_weight', 'cas_registry_number',
            'human_use_intro', 'human_use_subsections', 'regulatory_subsections', 'regulatory_important_note',
            'potential_applications_important_context', 'conclusion', 'references', 'faqs', 'description',
        ] as $column) {
            $this->assertSame($cagri['columns'][$column]['new_value'], $cagriPost->{$column}, $column);
        }
        $replacements = $cagri['columns']['potential_applications']['replacements'];
        $this->assertSame($replacements[0]['new_item'], $cagriPost->potential_applications[0]);
        $this->assertSame('KEEP-APP-cagrilintide', $cagriPost->potential_applications[1]['description']);
        $this->assertSame($replacements[1]['new_item'], $cagriPost->potential_applications[2]);
        $this->assertSame($cagri['columns']['areas_of_research']['new_item'], $cagriPost->areas_of_research[0]);
        $this->assertSame('KEEP-AREA-BODY', $cagriPost->areas_of_research[1]['description']);
        $this->assertSame('KEEP-POINT', $cagriPost->key_points[0]);
        $this->assertSame($cagri['columns']['key_points']['operations'][0]['new_item'], $cagriPost->key_points[1]);
        $this->assertSame($cagri['columns']['key_points']['operations'][1]['new_item'], $cagriPost->key_points[2]);
        $this->assertSame('KEEP-FINDING', $cagriPost->preclinical_subsections[0]['findings'][0]['content']);
        $this->assertSame($cagri['columns']['preclinical_subsections']['new_finding'], $cagriPost->preclinical_subsections[0]['findings'][1]);
        $this->assertSame('KEEP-OTHER-FINDING', $cagriPost->preclinical_subsections[1]['findings'][0]['content']);

        $ghk = $this->payload('ghk-cu');
        $ghkPost = $seeded['ghk-cu']->fresh();
        $ghkHuman = $ghk['columns']['human_use_subsections'];
        $this->assertSame($ghkHuman['new_dermatological_subsection'], $ghkPost->human_use_subsections[0]);
        $this->assertSame($ghkHuman['new_injectable_subsection'], $ghkPost->human_use_subsections[1]);
        $this->assertSame($ghkHuman['new_subsection'], $ghkPost->human_use_subsections[2]);
        $injectable = $ghkPost->human_use_subsections[1]['entries'][0]['value'];
        $this->assertStringContainsString('\\n', $injectable);
        $this->assertStringNotContainsString("\n", $injectable);
        $this->assertSame($ghk['columns']['regulatory_subsections']['old_values'][0][0], $ghkPost->regulatory_subsections[0]);
        $this->assertSame($ghk['columns']['regulatory_subsections']['new_subsection'], $ghkPost->regulatory_subsections[1]);
        $ghkApps = $ghk['columns']['potential_applications']['replacements'];
        $this->assertSame($ghkApps[0]['new_item'], $ghkPost->potential_applications[0]);
        $this->assertSame('KEEP-APP-ghk-cu', $ghkPost->potential_applications[1]['description']);
        $this->assertSame($ghkApps[1]['new_item'], $ghkPost->potential_applications[2]);
        $this->assertSame([...$ghk['columns']['key_points']['old_value'], $ghk['columns']['key_points']['new_item']], $ghkPost->key_points);
        $this->assertSame($ghk['columns']['references']['new_value'], $ghkPost->references);
        foreach (['faqs', 'conclusion', 'research_url', 'molecular_formula', 'molecular_weight', 'cas_registry_number', 'half_life'] as $column) {
            $this->assertSame($ghk['columns'][$column]['new_value'], $ghkPost->{$column}, $column);
        }
        $this->assertSame('KEEP-GHK-OVERVIEW', $ghkPost->overview);
    }

    public function test_case_variants_match_and_write(): void
    {
        $cases = [
            ['retatrutide', 'retatrutide', 'regulatory_important_note'],
            ['retatrutide', 'Retatrutide', 'regulatory_important_note'],
            ['retatrutide', ' RETATRUTIDE ', 'regulatory_important_note'],
            ['ghk-cu', 'GHK-Cu', 'cas_registry_number'],
            ['ghk-cu', 'ghk-cu', 'cas_registry_number'],
            ['cagrilintide', 'Cagrilintide', 'cas_registry_number'],
        ];

        foreach ($cases as [$slug, $categorySlug, $column]) {
            EducationPost::query()->delete();
            ProductCategory::query()->delete();
            $seeded = $this->seedOne($slug, $categorySlug);
            $seeder = new EncyclopediaFieldReapplyOctober22Seeder;
            $seeder->run();

            $this->assertSame('updated', $seeder->report[$slug], $categorySlug);
            $expected = $slug === 'retatrutide'
                ? $this->payload($slug)['columns'][$column]['new_values'][0]
                : $this->payload($slug)['columns'][$column]['new_value'];
            $this->assertSame($expected, $seeded->fresh()->{$column}, $categorySlug);
        }
    }

    public function test_duplicate_category_or_posts_warn_and_write_nothing(): void
    {
        $warnings = [];
        Log::listen(function ($event) use (&$warnings): void {
            if ($event->level === 'warning') {
                $warnings[] = $event->message;
            }
        });

        $first = $this->seedOne('ghk-cu', 'GHK-Cu', ['cas_registry_number' => 'KEEP-FIRST']);
        $secondCategory = $this->category(null, 'ghk-cu', 'KEEP-SECOND-CATEGORY');
        $second = $this->encyclopediaPost($secondCategory, 'ghk-cu-other', ['cas_registry_number' => 'KEEP-SECOND']);
        $before = [
            $first->id => $first->fresh()->getAttributes(),
            $second->id => $second->fresh()->getAttributes(),
        ];

        $seeder = new EncyclopediaFieldReapplyOctober22Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report['ghk-cu']);
        $this->assertTrue(collect($seeder->warnings)->contains(fn (string $warning) => str_contains($warning, 'categories 2')));
        $this->assertTrue(collect($warnings)->contains(fn (string $warning) => str_contains($warning, 'categories 2')));
        $this->assertSame($before[$first->id], $first->fresh()->getAttributes());
        $this->assertSame($before[$second->id], $second->fresh()->getAttributes());
        $this->assertSame('KEEP-SECOND-CATEGORY', $secondCategory->fresh()->description);

        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        Schema::table('education_posts', function (Blueprint $table) {
            $table->dropUnique(['product_category_id']);
        });
        $category = $this->category(null, 'ghk-cu', 'KEEP-CATEGORY');
        $left = $this->encyclopediaPost($category, 'ghk-cu-a', ['cas_registry_number' => 'KEEP-A']);
        $right = $this->encyclopediaPost($category, 'ghk-cu-b', ['cas_registry_number' => 'KEEP-B']);
        $pair = [
            $left->id => $left->fresh()->getAttributes(),
            $right->id => $right->fresh()->getAttributes(),
        ];

        $again = new EncyclopediaFieldReapplyOctober22Seeder;
        $again->run();

        $this->assertSame('collision', $again->report['ghk-cu']);
        $this->assertTrue(collect($again->warnings)->contains(fn (string $warning) => str_contains($warning, 'linked posts 2')));
        $this->assertSame($pair[$left->id], $left->fresh()->getAttributes());
        $this->assertSame($pair[$right->id], $right->fresh()->getAttributes());
    }

    public function test_second_run_reports_only_marker_present_and_writes_nothing(): void
    {
        $seeded = $this->seedTargets();
        (new EncyclopediaFieldReapplyOctober22Seeder)->run();

        $snapshots = [];
        foreach (['retatrutide', 'ghk-cu', 'cagrilintide'] as $slug) {
            $post = $seeded[$slug]->fresh();
            $post->updated_at = now()->subDay();
            $post->saveQuietly();
            $snapshots[$slug] = $post->fresh()->getAttributes();
        }

        $again = new EncyclopediaFieldReapplyOctober22Seeder;
        $again->run();

        foreach (['retatrutide', 'ghk-cu', 'cagrilintide'] as $slug) {
            $this->assertSame($snapshots[$slug], $seeded[$slug]->fresh()->getAttributes(), $slug);
            $this->assertSame(EncyclopediaFieldReapplyOctober22Seeder::MARKER, $again->report[$slug], $slug);
        }
        $this->assertNotEmpty($again->summary);
        foreach ($again->summary as $line) {
            $this->assertStringContainsString('skipped (marker present)', $line, $line);
            $this->assertStringNotContainsString('updated', $line, $line);
            $this->assertStringNotContainsString('old mismatch', $line, $line);
            $this->assertStringNotContainsString('missing', $line, $line);
            $this->assertStringNotContainsString('collision', $line, $line);
        }
        $expectedLines = 0;
        foreach (['retatrutide', 'ghk-cu', 'cagrilintide'] as $slug) {
            $expectedLines += 1 + count($this->expectedColumns($slug)) - 1;
        }
        $this->assertCount($expectedLines, $again->summary);
        $this->assertSame([], $again->warnings);
    }

    public function test_au_then_reapply_matches_reapply_then_au(): void
    {
        $forward = $this->ghkAfterOrder(true);
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $reverse = $this->ghkAfterOrder(false);

        $this->assertSame($forward['titles'], $reverse['titles']);
        $this->assertSame([
            'Current Status',
            'FDA compounding status (as of 22 Oct 2026)',
            'Australia (as of 22 October 2026)',
        ], $forward['titles']);
        $this->assertSame($forward['references'], $reverse['references']);
        $payload = $this->payload('ghk-cu');
        $auRefs = array_slice(
            $payload['columns']['references']['old_values'][1],
            count($payload['columns']['references']['old_values'][0]),
        );
        $this->assertSame([...$payload['columns']['references']['new_value'], ...$auRefs], $forward['references']);
        $this->assertCount(17, $forward['references']);
    }

    public function test_whitespace_drift_matches_and_a_changed_word_does_not(): void
    {
        $reta = $this->payload('retatrutide');
        $note = $reta['columns']['regulatory_important_note']['old_values'][0];
        $points = $reta['columns']['key_points']['old_values'][0];
        $points[0] .= '  ';
        $points[1] = "\u{00A0}".$points[1];
        $human = $reta['columns']['human_use_subsections']['old_value'];
        $human[0]['entries'][0]['value'] = str_replace('Phase 2', 'Phase TWO-CHANGED', $human[0]['entries'][0]['value']);

        $cagri = $this->payload('cagrilintide');
        $faqs = $cagri['columns']['faqs']['old_value'];
        $faqs[0]['question'] .= ' ';
        $faqs[0]['answer'] = "\u{00A0}".str_replace("\n", "\r\n", $faqs[0]['answer']).'  ';

        $retaPost = $this->seedOne('retatrutide', 'retatrutide', [
            'regulatory_important_note' => "\u{00A0}".str_replace("\n", "\r\n", $note)." \t",
            'key_points' => $points,
            'human_use_subsections' => $human,
        ]);
        $cagriPost = $this->seedOne('cagrilintide', 'cagrilintide', [
            'overview' => 'CHANGED '.$cagri['columns']['overview']['old_value'],
            'faqs' => $faqs,
        ]);
        $humanBefore = $retaPost->fresh()->human_use_subsections;
        $overviewBefore = $cagriPost->fresh()->overview;

        $seeder = new EncyclopediaFieldReapplyOctober22Seeder;
        $seeder->run();

        $this->assertSame($reta['columns']['regulatory_important_note']['new_values'][0], $retaPost->fresh()->regulatory_important_note);
        $writtenPoints = $retaPost->fresh()->key_points;
        $this->assertSame($points[0], $writtenPoints[0]);
        $this->assertSame($reta['columns']['key_points']['new_item'], $writtenPoints[array_key_last($writtenPoints)]);
        $this->assertSame($humanBefore, $retaPost->fresh()->human_use_subsections);
        $this->assertSame($cagri['columns']['faqs']['new_value'], $cagriPost->fresh()->faqs);
        $this->assertSame($overviewBefore, $cagriPost->fresh()->overview);

        $warnings = implode("\n", $seeder->warnings);
        $this->assertStringContainsString('retatrutide.human_use_subsections: skipped (old mismatch)', $warnings);
        $this->assertStringContainsString('Phase TWO-CHANGED', $warnings);
        $this->assertStringContainsString('cagrilintide.overview: skipped (old mismatch)', $warnings);
        $this->assertStringContainsString('CHANGED ', $warnings);
        $excerptLine = (string) (strstr(strstr($warnings, 'excerpt='), "\n", true) ?: '');
        $excerpt = substr($excerptLine, strlen('excerpt='));
        $this->assertNotSame('', $excerpt);
        $this->assertLessThanOrEqual(200, strlen($excerpt));
    }

    public function test_retatrutide_note_rejects_stale_39_value_and_accepts_both_guards(): void
    {
        $reta = $this->payload('retatrutide');
        $stale = $this->seedOne('retatrutide', 'retatrutide', [
            'regulatory_important_note' => $reta['columns']['regulatory_important_note']['merged_39_old_value_for_reference'],
        ]);
        $seeder = new EncyclopediaFieldReapplyOctober22Seeder;
        $seeder->run();

        $this->assertSame(
            $reta['columns']['regulatory_important_note']['merged_39_old_value_for_reference'],
            $stale->fresh()->regulatory_important_note,
        );
        $this->assertStringContainsString('retatrutide.regulatory_important_note: skipped (old mismatch)', implode("\n", $seeder->warnings));
        $this->assertSame(
            [...$reta['columns']['human_use_subsections']['old_value'], $reta['columns']['human_use_subsections']['new_subsection']],
            $stale->fresh()->human_use_subsections,
        );
        $this->assertSame($reta['columns']['regulatory_important_note']['new_values'][0], $this->freshTargetNote());

        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $rewritten = $this->seedOne('retatrutide', 'retatrutide', [
            'regulatory_important_note' => $reta['columns']['regulatory_important_note']['old_values'][1],
            'key_points' => $reta['columns']['key_points']['old_values'][1],
        ]);
        (new EncyclopediaFieldReapplyOctober22Seeder)->run();

        $this->assertSame($reta['columns']['regulatory_important_note']['new_values'][1], $rewritten->fresh()->regulatory_important_note);
        $this->assertSame(
            [...$reta['columns']['key_points']['old_values'][1], $reta['columns']['key_points']['new_item']],
            $rewritten->fresh()->key_points,
        );
        $this->assertSame($reta['columns']['key_points']['old_values'][1][5], $rewritten->fresh()->key_points[5]);
    }

    public function test_rendered_pages_show_new_subsections_faqs_and_status(): void
    {
        $this->seedOne('retatrutide', 'retatrutide', [], 'Retatrutide');
        $this->seedOne('ghk-cu', 'GHK-Cu', [], 'GHK-Cu');
        $this->seedOne('cagrilintide', 'Cagrilintide', [], 'Cagrilintide');
        (new EncyclopediaFieldReapplyOctober22Seeder)->run();

        $this->get('/encyclopedia/retatrutide')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('humanUseSubsections', function ($sections) {
                $titles = collect($sections)->pluck('title');
                Assert::assertTrue($titles->contains('Reported safety signals outside clinical trials (as of 22 October 2026)'));

                return true;
            }));

        $ghkFaqs = $this->payload('ghk-cu')['columns']['faqs']['new_value'];
        $this->get('/encyclopedia/GHK-Cu')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('humanUseSubsections', function ($sections) {
                    Assert::assertTrue(collect($sections)->pluck('title')->contains('Evidence limits (as of 19 Oct 2026)'));

                    return true;
                })
                ->where('regulatorySubsections', function ($sections) {
                    Assert::assertTrue(collect($sections)->pluck('title')->contains('FDA compounding status (as of 22 Oct 2026)'));

                    return true;
                })
                ->where('seo.schema', function ($schema) use ($ghkFaqs) {
                    $this->assertFaqEntities($schema, $ghkFaqs);

                    return true;
                }));

        $cagriFaqs = $this->payload('cagrilintide')['columns']['faqs']['new_value'];
        $this->get('/encyclopedia/Cagrilintide')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('drugStatus', 'Investigational; not approved (as of 22 Oct 2026)')
                ->where('humanUseSubsections', function ($sections) {
                    Assert::assertTrue(collect($sections)->pluck('title')->contains('Clinical program (as of 19 Oct 2026)'));

                    return true;
                })
                ->where('regulatorySubsections', function ($sections) {
                    Assert::assertTrue(collect($sections)->pluck('title')->contains('Development and regulatory status (as of 22 Oct 2026)'));

                    return true;
                })
                ->where('seo.schema', function ($schema) use ($cagriFaqs) {
                    $this->assertFaqEntities($schema, $cagriFaqs);

                    return true;
                }));
    }

    public function test_cagrilintide_description_fallback_uses_the_matched_category(): void
    {
        $spec = $this->payload('cagrilintide')['columns']['description'];
        $category = $this->category(437, 'Cagrilintide', $spec['old_value'], 'cagrilintide');
        $blocked = $this->category(592, 'CagriSema', $spec['old_value'], 'cagrisema');
        $post = $this->encyclopediaPost($category, 'cagrilintide-linked', ['description' => null]);

        (new EncyclopediaFieldReapplyOctober22Seeder)->run();

        $this->assertSame($spec['new_value'], $category->fresh()->description);
        $this->assertNull($post->fresh()->description);
        $this->assertSame($spec['old_value'], $blocked->fresh()->description);

        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $other = $this->category(null, 'Cagrilintide', $spec['old_value'], 'cagrilintide');
        $this->assertNotSame(437, (int) $other->id);
        $this->encyclopediaPost($other, 'cagrilintide-linked', ['description' => null]);
        $seeder = new EncyclopediaFieldReapplyOctober22Seeder;
        $seeder->run();

        $this->assertSame($spec['old_value'], $other->fresh()->description);
        $this->assertTrue(collect($seeder->warnings)->contains(
            fn (string $warning) => str_contains($warning, 'category id '.$other->id.' is not 437')
        ));
    }

    public function test_new_sentence_scan_excludes_reference_titles_and_old_text(): void
    {
        $corpus = [];
        foreach (['retatrutide', 'cagrilintide', 'ghk-cu'] as $slug) {
            foreach ($this->payload($slug)['columns'] as $column => $spec) {
                if (! is_array($spec)) {
                    continue;
                }
                $old = implode("\n", $this->texts($spec, (string) $column, false));
                foreach ($this->texts($spec, (string) $column, true) as $text) {
                    foreach ($this->sentences($text) as $sentence) {
                        if (! str_contains($old, $sentence)) {
                            $corpus[] = $sentence;
                        }
                    }
                }
            }
        }

        $this->assertNotEmpty($corpus);
        $joined = implode("\n", $corpus);
        $this->assertStringContainsString('Products sold online under this name are not Lilly trial supply.', $joined);
        $this->assertStringNotContainsString('2·4 mg', $joined);
        $dose = '/\d+(?:[.·]\d+)?\s?(mg|mcg|µg|IU|mL)\b|\/kg/';
        $commercial = '/\bbuy\b|coupon|discount|\$\s?\d|in stock|\bCOA\b|per day|daily dose|\bcycle\b|reconstitute\b/i';
        foreach ($corpus as $sentence) {
            $this->assertDoesNotMatchRegularExpression($dose, $sentence, $sentence);
            $this->assertDoesNotMatchRegularExpression($commercial, $sentence, $sentence);
        }
    }

    /**
     * @return array{titles: list<string>, references: list<mixed>}
     */
    private function ghkAfterOrder(bool $auFirst): array
    {
        $post = $this->seedOne('ghk-cu', 'ghk-cu');
        if ($auFirst) {
            $this->applyAuGhk($post->fresh());
            (new EncyclopediaFieldReapplyOctober22Seeder)->run();
        } else {
            (new EncyclopediaFieldReapplyOctober22Seeder)->run();
            $this->applyAuGhk($post->fresh());
        }

        $finished = $post->fresh();
        $finished->updated_at = now()->subDay();
        $finished->saveQuietly();
        $snapshot = $finished->fresh()->getAttributes();

        (new EncyclopediaFieldReapplyOctober22Seeder)->run();
        $this->applyAuGhk($post->fresh());
        $this->assertSame($snapshot, $post->fresh()->getAttributes());

        $fresh = $post->fresh();

        return [
            'titles' => array_column($fresh->regulatory_subsections, 'title'),
            'references' => $fresh->references,
        ];
    }

    private function applyAuGhk(EducationPost $post): void
    {
        $payload = $this->payload('ghk-cu');
        $regulatory = $payload['columns']['regulatory_subsections'];
        $auSubsection = $regulatory['old_values'][1][1];
        $withFda = $regulatory['old_values'][0];
        array_splice($withFda, (int) $regulatory['insert_at'], 0, [$regulatory['new_subsection']]);
        $sections = $post->regulatory_subsections;
        $hasAustralia = is_array($sections) && collect($sections)->contains(
            fn ($section) => is_array($section) && is_string($section['title'] ?? null) && str_starts_with($section['title'], 'Australia (as of'),
        );
        if (! $hasAustralia && (EncyclopediaFieldReapplyOctober22Seeder::normalize($sections) === EncyclopediaFieldReapplyOctober22Seeder::normalize($regulatory['old_values'][0])
            || EncyclopediaFieldReapplyOctober22Seeder::normalize($sections) === EncyclopediaFieldReapplyOctober22Seeder::normalize($withFda))) {
            $sections[] = $auSubsection;
            $post->regulatory_subsections = $sections;
        }

        $references = $payload['columns']['references'];
        $auRefs = array_slice($references['old_values'][1], count($references['old_values'][0]));
        $current = $post->references;
        $auTitle = $auRefs[0]['title'] ?? null;
        $hasAu = is_array($current) && collect($current)->contains(
            fn ($reference) => is_array($reference) && ($reference['title'] ?? null) === $auTitle,
        );
        if (! $hasAu && (EncyclopediaFieldReapplyOctober22Seeder::normalize($current) === EncyclopediaFieldReapplyOctober22Seeder::normalize($references['old_values'][0])
            || EncyclopediaFieldReapplyOctober22Seeder::normalize($current) === EncyclopediaFieldReapplyOctober22Seeder::normalize($references['new_value']))) {
            $post->references = array_merge(is_array($current) ? $current : [], $auRefs);
        }
        if ($post->isDirty()) {
            $post->saveQuietly();
        }
    }

    private function freshTargetNote(): string
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $post = $this->seedOne('retatrutide', 'retatrutide');
        (new EncyclopediaFieldReapplyOctober22Seeder)->run();

        return (string) $post->fresh()->regulatory_important_note;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedOne(string $slug, string $categorySlug, array $overrides = [], ?string $name = null): EducationPost
    {
        $category = $this->category(
            $slug === 'cagrilintide' ? 437 : null,
            $name ?? $categorySlug,
            'KEEP-CATEGORY-'.$slug,
            $categorySlug,
        );

        return $this->encyclopediaPost($category, $slug.'-linked', array_merge($this->seedAttributes($slug), $overrides));
    }

    /**
     * @return array{retatrutide: EducationPost, ghk-cu: EducationPost, cagrilintide: EducationPost, cagrisema: EducationPost, vs: EducationPost, unrelated: EducationPost, cagrilintideCategory: ProductCategory}
     */
    private function seedTargets(): array
    {
        $cagrilintideCategory = $this->category(437, 'Cagrilintide', 'KEEP-437-DESCRIPTION', 'cagrilintide');
        $cagrisemaCategory = $this->category(592, 'CagriSema', 'KEEP-592', 'cagrisema');
        $retatrutideCategory = $this->category(null, 'Retatrutide', 'KEEP-RETA-CATEGORY', 'retatrutide');
        $ghkCategory = $this->category(null, 'GHK-Cu', 'KEEP-GHK-CATEGORY', 'ghk-cu');
        $vsCategory = $this->category(null, 'Retatrutide vs Tirzepatide', 'KEEP-VS', 'retatrutide-vs-tirzepatide');
        $unrelatedCategory = $this->category(null, 'Semaglutide', 'KEEP-UNRELATED', 'semaglutide');

        return [
            'retatrutide' => $this->encyclopediaPost($retatrutideCategory, 'retatrutide-linked', array_merge($this->seedAttributes('retatrutide'), [
                'overview' => 'KEEP-RETA-OVERVIEW',
            ])),
            'ghk-cu' => $this->encyclopediaPost($ghkCategory, 'ghk-cu-linked', array_merge($this->seedAttributes('ghk-cu'), [
                'overview' => 'KEEP-GHK-OVERVIEW',
            ])),
            'cagrilintide' => $this->encyclopediaPost($cagrilintideCategory, 'cagrilintide-linked', $this->seedAttributes('cagrilintide')),
            'cagrisema' => $this->encyclopediaPost($cagrisemaCategory, 'cagrisema', [
                'overview' => 'KEEP-CAGRISEMA',
                'description' => 'KEEP-592-POST',
            ]),
            'vs' => $this->encyclopediaPost($vsCategory, 'retatrutide-vs-tirzepatide', [
                'overview' => 'KEEP-VS-POST',
            ]),
            'unrelated' => $this->encyclopediaPost($unrelatedCategory, 'semaglutide', [
                'overview' => 'KEEP-SEMA',
            ]),
            'cagrilintideCategory' => $cagrilintideCategory->fresh(),
        ];
    }

    private function legacyPost(string $slug): EducationPost
    {
        return $this->encyclopediaPost(null, $slug, $this->seedAttributes($slug));
    }

    /**
     * @return array<string, mixed>
     */
    private function seedAttributes(string $slug): array
    {
        $attributes = [];
        foreach ($this->payload($slug)['columns'] as $column => $spec) {
            if (! is_string($column) || ! is_array($spec)) {
                continue;
            }
            $attributes[$column] = $this->seedValue($slug, $column, $spec);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function seedValue(string $slug, string $column, array $spec): mixed
    {
        if (array_key_exists('old_value', $spec)) {
            return $spec['old_value'];
        }
        if (array_key_exists('old_values', $spec)) {
            return $spec['old_values'][0];
        }
        if ($column === 'human_use_subsections' && isset($spec['old_value_whole_array'])) {
            return $spec['old_value_whole_array'];
        }
        if ($column === 'potential_applications') {
            $items = [];
            foreach ($spec['replacements'] as $replacement) {
                $items[$replacement['index']] = $replacement['old_item'];
            }
            if (! array_key_exists(1, $items)) {
                $items[1] = ['title' => 'KEEP-'.$slug, 'description' => 'KEEP-APP-'.$slug];
            }
            ksort($items);

            return array_values($items);
        }
        if ($column === 'areas_of_research') {
            return [
                $spec['old_item'],
                ['name' => 'KEEP-AREA', 'description' => 'KEEP-AREA-BODY'],
            ];
        }
        if ($column === 'key_points') {
            return ['KEEP-POINT', $spec['operations'][0]['old_item']];
        }
        if ($column === 'preclinical_subsections') {
            return [[
                'title' => 'Metabolic Effects',
                'findings' => [
                    ['title' => 'Kept', 'content' => 'KEEP-FINDING'],
                    $spec['old_finding'],
                ],
            ], [
                'title' => 'Other',
                'findings' => [
                    ['title' => 'Other', 'content' => 'KEEP-OTHER-FINDING'],
                ],
            ]];
        }

        $this->fail('No seed value for '.$slug.'.'.$column);
    }

    /**
     * @return list<string>
     */
    private function expectedColumns(string $slug): array
    {
        $columns = [];
        foreach ($this->payload($slug)['columns'] as $column => $spec) {
            if (is_string($column) && is_array($spec)) {
                $columns[] = $column;
            }
        }
        $columns[] = 'updated_at';
        sort($columns);

        return $columns;
    }

    private function category(?int $id, string $name, string $description, ?string $slug = null): ProductCategory
    {
        $storedSlug = $slug ?? $name;
        if (strtolower(trim($storedSlug)) === 'cagrisema') {
            $this->forgetCagrisema();
        }

        $category = new ProductCategory;
        $category->forceFill([
            'name' => $name,
            'slug' => $storedSlug,
            'description' => $description,
            'is_active' => true,
        ]);
        if ($id !== null) {
            $category->id = $id;
        }
        $category->save();

        return $category->fresh();
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

    private function forgetCagrisema(): void
    {
        $ids = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', ['cagrisema'])
            ->pluck('id');
        EducationPost::query()
            ->where(function ($query) use ($ids) {
                $query->whereIn('product_category_id', $ids->all())
                    ->orWhereRaw('LOWER(slug) = ?', ['cagrisema']);
            })
            ->delete();
        if ($ids->isNotEmpty()) {
            ProductCategory::query()->whereIn('id', $ids)->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $slug): array
    {
        $decoded = json_decode(
            (string) file_get_contents(database_path('seeders/data/encyclopedia-field-reapply/2026-10-22/'.$slug.'.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $before
     * @return list<string>
     */
    private function changedKeys(array $before, EducationPost $model): array
    {
        $after = $model->getAttributes();
        $changed = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }
        sort($changed);

        return $changed;
    }

    /**
     * @param  list<array{question: string, answer: string}>  $faqs
     */
    private function assertFaqEntities(mixed $schema, array $faqs): void
    {
        $blocks = collect($schema instanceof \Illuminate\Support\Collection ? $schema->all() : $schema);
        $faq = $blocks->firstWhere('@type', 'FAQPage');
        $this->assertIsArray($faq);
        $reduced = [];
        foreach ($faq['mainEntity'] as $entity) {
            $reduced[] = [
                'question' => $entity['name'],
                'answer' => $entity['acceptedAnswer']['text'],
            ];
        }
        $this->assertSame($faqs, $reduced);
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return list<string>
     */
    private function texts(array $spec, string $column, bool $wantNew): array
    {
        $texts = [];
        $walk = function (mixed $node) use (&$walk, &$texts, $column, $wantNew): void {
            if (! is_array($node)) {
                return;
            }
            foreach ($node as $key => $value) {
                $name = is_string($key) ? $key : '';
                $isNew = $name !== '' && str_starts_with($name, 'new_');
                $isOld = $name !== '' && str_starts_with($name, 'old_');
                if ($wantNew && $isNew) {
                    $this->collectStrings($value, $texts, $column === 'references');

                    continue;
                }
                if (! $wantNew && $isOld) {
                    $this->collectStrings($value, $texts, false);

                    continue;
                }
                if (is_array($value)) {
                    $walk($value);
                }
            }
        };
        $walk($spec);

        return $texts;
    }

    /**
     * @param  list<string>  $texts
     */
    private function collectStrings(mixed $value, array &$texts, bool $skipReferenceTitles): void
    {
        if (is_string($value)) {
            $texts[] = $value;

            return;
        }
        if (! is_array($value)) {
            return;
        }
        foreach ($value as $key => $child) {
            if ($skipReferenceTitles && $key === 'title') {
                continue;
            }
            $this->collectStrings($child, $texts, $skipReferenceTitles);
        }
    }

    /**
     * @return list<string>
     */
    private function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+|\\\\n|\R/u', $text);
        if (! is_array($parts)) {
            return [];
        }
        $sentences = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $sentences[] = $part;
            }
        }

        return $sentences;
    }
}
