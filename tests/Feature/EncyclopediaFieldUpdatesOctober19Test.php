<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaFieldUpdatesOctober19Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncyclopediaFieldUpdatesOctober19Test extends TestCase
{
    use RefreshDatabase;

    public function test_writes_when_old_values_match_and_leaves_every_other_row_unchanged(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_19_130000_update_encyclopedia_fields_october_19_2026.php'));
        $this->assertStringContainsString('EncyclopediaFieldUpdatesOctober19Seeder', $migration);

        $seeded = $this->seedMatchingPosts();
        $protected = $this->protectedSnapshots();
        $category437 = $seeded['category437']->getAttributes();
        $category592 = $seeded['category592']->getAttributes();

        $seeder = new EncyclopediaFieldUpdatesOctober19Seeder;
        $seeder->run();

        $this->assertSame('updated', $seeder->report['cagrilintide']);
        $this->assertSame('updated', $seeder->report['ghk-cu']);
        $this->assertSame('updated', $seeder->report['bpc-157']);
        $this->assertProtectedUnchanged($protected);
        $this->assertSame($category437, $seeded['category437']->fresh()->getAttributes());
        $this->assertSame($category592, $seeded['category592']->fresh()->getAttributes());
        $this->assertSame('KEEP-437-DESCRIPTION', $seeded['category437']->fresh()->description);
        $this->assertSame('KEEP-592', $seeded['category592']->fresh()->description);

        $cagriPayload = $this->payload('cagrilintide.json');
        $cagri = $seeded['cagri']->fresh();
        $this->assertSame($this->expectedChangedKeys($cagriPayload), $this->changedKeys($seeded['cagriBefore'], $cagri));
        foreach ([
            'overview', 'background', 'half_life', 'molecular_formula', 'molecular_weight',
            'cas_registry_number', 'human_use_intro', 'human_use_subsections', 'regulatory_subsections',
            'regulatory_important_note', 'potential_applications_important_context', 'conclusion',
            'references', 'faqs', 'description',
        ] as $column) {
            $this->assertSame($cagriPayload['columns'][$column]['new_value'], $cagri->{$column}, $column);
        }
        $cagriApps = $cagriPayload['columns']['potential_applications']['replacements'];
        $this->assertSame($cagriApps[0]['new_item'], $cagri->potential_applications[0]);
        $this->assertSame('KEEP-APP-BODY', $cagri->potential_applications[1]['description']);
        $this->assertSame($cagriApps[1]['new_item'], $cagri->potential_applications[2]);
        $this->assertSame($cagriPayload['columns']['areas_of_research']['new_item'], $cagri->areas_of_research[0]);
        $this->assertSame('KEEP-AREA-BODY', $cagri->areas_of_research[1]['description']);
        $synergy = $cagriPayload['columns']['key_points']['operations'][0]['new_item'];
        $status = $cagriPayload['columns']['key_points']['operations'][1]['new_item'];
        $this->assertSame(['KEEP-POINT', $synergy, $status], $cagri->key_points);
        $this->assertSame('KEEP-FINDING', $cagri->preclinical_subsections[0]['findings'][0]['content']);
        $this->assertSame($cagriPayload['columns']['preclinical_subsections']['new_finding'], $cagri->preclinical_subsections[0]['findings'][1]);
        $this->assertSame('KEEP-OTHER-FINDING', $cagri->preclinical_subsections[1]['findings'][0]['content']);
        $this->assertSame('KEEP-MECHANISM', $cagri->mechanism_of_action_intro);

        $ghkPayload = $this->payload('ghk-cu.json');
        $ghk = $seeded['ghk']->fresh();
        $this->assertSame($this->expectedChangedKeys($ghkPayload), $this->changedKeys($seeded['ghkBefore'], $ghk));
        $ghkHuman = $ghkPayload['columns']['human_use_subsections'];
        $this->assertSame($ghkHuman['new_injectable_subsection'], $ghk->human_use_subsections[0]);
        $this->assertSame('KEEP-HUMAN', $ghk->human_use_subsections[1]['title']);
        $this->assertSame($ghkHuman['new_dermatological_subsection'], $ghk->human_use_subsections[2]);
        $this->assertSame($ghkHuman['new_subsection'], $ghk->human_use_subsections[3]);
        $this->assertCount(4, $ghk->human_use_subsections);
        $injectable = $ghk->human_use_subsections[0]['entries'][0]['value'];
        $dermatological = $ghk->human_use_subsections[2]['entries'][0]['value'];
        $this->assertStringContainsString('\\n', $injectable);
        $this->assertStringNotContainsString("\n", $injectable);
        $this->assertStringNotContainsString("\n", $dermatological);
        $this->assertSame($ghkHuman['new_injectable_subsection']['entries'][0]['value'], $injectable);
        $this->assertSame($ghkHuman['new_dermatological_subsection']['entries'][0]['value'], $dermatological);
        $this->assertSame($ghkPayload['columns']['regulatory_subsections']['current_value_for_reference'][0], $ghk->regulatory_subsections[0]);
        $this->assertSame($ghkPayload['columns']['regulatory_subsections']['new_subsection'], $ghk->regulatory_subsections[1]);
        $ghkApps = $ghkPayload['columns']['potential_applications']['replacements'];
        $this->assertSame($ghkApps[0]['new_item'], $ghk->potential_applications[0]);
        $this->assertSame('KEEP-GHK-APP', $ghk->potential_applications[1]['description']);
        $this->assertSame($ghkApps[1]['new_item'], $ghk->potential_applications[2]);
        $this->assertSame(['KEEP-GHK-POINT', $ghkPayload['columns']['key_points']['new_item']], $ghk->key_points);
        foreach (['faqs', 'references', 'conclusion', 'research_url', 'molecular_formula', 'molecular_weight', 'cas_registry_number', 'half_life'] as $column) {
            $this->assertSame($ghkPayload['columns'][$column]['new_value'], $ghk->{$column}, $column);
        }
        $this->assertSame('KEEP-GHK-OVERVIEW', $ghk->overview);

        $bpcPayload = $this->payload('bpc-157.json');
        $bpc = $seeded['bpc']->fresh();
        $this->assertSame($this->expectedChangedKeys($bpcPayload), $this->changedKeys($seeded['bpcBefore'], $bpc));
        $this->assertSame('KEEP-BEFORE-BODY', $bpc->regulatory_subsections[0]['entries'][0]['value']);
        $this->assertSame($bpcPayload['columns']['regulatory_subsections']['new_united_states_value'], $bpc->regulatory_subsections[1]['entries'][0]['value']);
        $this->assertSame('marker', $bpc->regulatory_subsections[1]['entries'][0]['keep']);
        $this->assertSame('KEEP-SECOND', $bpc->regulatory_subsections[1]['entries'][1]['value']);
        $this->assertSame($bpcPayload['columns']['regulatory_subsections']['new_compounding_subsection'], $bpc->regulatory_subsections[2]);
        $this->assertSame('KEEP-AFTER-BODY', $bpc->regulatory_subsections[3]['entries'][0]['value']);
        $this->assertSame($bpcPayload['columns']['regulatory_important_note']['new_value'], $bpc->regulatory_important_note);
        $this->assertSame(['KEEP-BPC', $bpcPayload['columns']['key_points']['new_item']], $bpc->key_points);
        $this->assertSame('KEEP-BPC-OVERVIEW', $bpc->overview);
    }

    public function test_second_run_writes_nothing(): void
    {
        $seeded = $this->seedMatchingPosts();
        (new EncyclopediaFieldUpdatesOctober19Seeder)->run();

        $cagri = $seeded['cagri']->fresh();
        $ghk = $seeded['ghk']->fresh();
        $bpc = $seeded['bpc']->fresh();
        foreach ([$cagri, $ghk, $bpc] as $post) {
            $post->updated_at = now()->subDay();
            $post->saveQuietly();
        }
        $cagriStamp = $cagri->fresh()->updated_at;
        $ghkStamp = $ghk->fresh()->updated_at;
        $bpcStamp = $bpc->fresh()->updated_at;
        $snapshots = [
            $cagri->id => $cagri->fresh()->getAttributes(),
            $ghk->id => $ghk->fresh()->getAttributes(),
            $bpc->id => $bpc->fresh()->getAttributes(),
        ];
        $protected = $this->protectedSnapshots();

        $again = new EncyclopediaFieldUpdatesOctober19Seeder;
        $again->run();

        $this->assertSame('unchanged', $again->report['cagrilintide']);
        $this->assertSame('unchanged', $again->report['ghk-cu']);
        $this->assertSame('unchanged', $again->report['bpc-157']);
        $this->assertProtectedUnchanged($protected);
        $this->assertTrue($cagri->fresh()->updated_at->equalTo($cagriStamp));
        $this->assertTrue($ghk->fresh()->updated_at->equalTo($ghkStamp));
        $this->assertTrue($bpc->fresh()->updated_at->equalTo($bpcStamp));
        $this->assertSame($snapshots[$cagri->id], $cagri->fresh()->getAttributes());
        $this->assertSame($snapshots[$ghk->id], $ghk->fresh()->getAttributes());
        $this->assertSame($snapshots[$bpc->id], $bpc->fresh()->getAttributes());
        foreach ($again->log as $line) {
            $this->assertDoesNotMatchRegularExpression('/\b(wrote|replaced|appended)\b/', $line);
        }
        $this->assertCount(1, array_filter(
            $bpc->fresh()->key_points,
            fn ($point) => is_string($point) && str_starts_with($point, 'Regulatory status (as of 19 Oct 2026)')
        ));
    }

    public function test_guards_skip_when_the_new_item_is_already_present(): void
    {
        $cagriPayload = $this->payload('cagrilintide.json');
        $ghkPayload = $this->payload('ghk-cu.json');
        $bpcPayload = $this->payload('bpc-157.json');

        $cagri = $this->postWithNewValues('Cagrilintide', 'cagrilintide.json', [
            'key_points' => ['Status (as of 4 Apr 2024): already recorded.'],
        ]);
        $ghkHuman = $ghkPayload['columns']['human_use_subsections'];
        $ghk = $this->postWithNewValues('GHK-Cu', 'ghk-cu.json', [
            'key_points' => ['Evidence limits (as of 2 Feb 2021): already recorded.'],
            'human_use_subsections' => [
                $ghkHuman['new_injectable_subsection'],
                ['title' => 'Evidence limits (as of 3 Mar 2020)', 'entries' => [['type' => 'item', 'value' => 'ALREADY']]],
            ],
        ]);
        $bpc = $this->postWithNewValues('BPC-157', 'bpc-157.json', [
            'key_points' => [$bpcPayload['columns']['key_points']['new_item']],
        ]);
        $before = [
            $cagri->id => $cagri->getAttributes(),
            $ghk->id => $ghk->getAttributes(),
            $bpc->id => $bpc->getAttributes(),
        ];

        $seeder = new EncyclopediaFieldUpdatesOctober19Seeder;
        $seeder->run();

        $this->assertSame('unchanged', $seeder->report['cagrilintide']);
        $this->assertSame('unchanged', $seeder->report['ghk-cu']);
        $this->assertSame('unchanged', $seeder->report['bpc-157']);
        $this->assertSame($before[$cagri->id], $cagri->fresh()->getAttributes());
        $this->assertSame($before[$ghk->id], $ghk->fresh()->getAttributes());
        $this->assertSame($before[$bpc->id], $bpc->fresh()->getAttributes());
        $this->assertSame(['Status (as of 4 Apr 2024): already recorded.'], $cagri->fresh()->key_points);
        $this->assertNotContains($cagriPayload['columns']['key_points']['operations'][1]['new_item'], $cagri->fresh()->key_points);
        $this->assertSame(['Evidence limits (as of 2 Feb 2021): already recorded.'], $ghk->fresh()->key_points);
        $this->assertCount(2, $ghk->fresh()->human_use_subsections);
        $this->assertSame('ALREADY', $ghk->fresh()->human_use_subsections[1]['entries'][0]['value']);
        $this->assertSame([$bpcPayload['columns']['key_points']['new_item']], $bpc->fresh()->key_points);
    }

    public function test_missing_slug_is_a_no_op_and_a_collision_writes_nothing(): void
    {
        $decoy = $this->encyclopediaPost(null, 'semaglutide', [
            'overview' => 'DECOY-OVERVIEW',
            'key_points' => ['Decoy point'],
        ]);
        $decoyBefore = $decoy->getAttributes();
        $postsBefore = EducationPost::query()->count();

        $missing = new EncyclopediaFieldUpdatesOctober19Seeder;
        $missing->run();

        $this->assertSame('missing', $missing->report['cagrilintide']);
        $this->assertSame('missing', $missing->report['ghk-cu']);
        $this->assertSame('missing', $missing->report['bpc-157']);
        $this->assertSame($postsBefore, EducationPost::query()->count());
        $this->assertSame($decoyBefore, $decoy->fresh()->getAttributes());

        $first = $this->encyclopediaPost(null, 'BPC-157', [
            'key_points' => ['First'],
            'regulatory_important_note' => 'FIRST-NOTE',
        ]);
        $second = $this->encyclopediaPost(null, 'bpc-157', [
            'key_points' => ['Second'],
            'regulatory_important_note' => 'SECOND-NOTE',
        ]);
        $firstBefore = $first->getAttributes();
        $secondBefore = $second->getAttributes();

        $collision = new EncyclopediaFieldUpdatesOctober19Seeder;
        $collision->run();

        $this->assertSame('collision', $collision->report['bpc-157']);
        $this->assertSame('missing', $collision->report['cagrilintide']);
        $this->assertSame('missing', $collision->report['ghk-cu']);
        $this->assertSame($firstBefore, $first->fresh()->getAttributes());
        $this->assertSame($secondBefore, $second->fresh()->getAttributes());
        $this->assertContains('bpc-157 *: skipped: slug collision (BPC-157, bpc-157)', $collision->log);
    }

    public function test_old_value_mismatch_leaves_that_column_unchanged_and_does_not_append(): void
    {
        $cagriPayload = $this->payload('cagrilintide.json');
        $bpcPayload = $this->payload('bpc-157.json');
        $cagri = $this->postWithNewValues('Cagrilintide', 'cagrilintide.json', [
            'overview' => 'NOT THE STORED OVERVIEW',
            'key_points' => ['A point that is not the synergistic line'],
        ]);
        $bpc = $this->postWithNewValues('BPC-157', 'bpc-157.json', [
            'key_points' => ['No October status item'],
            'regulatory_subsections' => [[
                'title' => 'United States',
                'entries' => [['type' => 'content', 'value' => 'NOT THE #39 UNITED STATES TEXT']],
            ]],
        ]);
        $cagriBefore = $cagri->getAttributes();
        $bpcBefore = $bpc->getAttributes();

        $seeder = new EncyclopediaFieldUpdatesOctober19Seeder;
        $seeder->run();

        $status = $cagriPayload['columns']['key_points']['operations'][1]['new_item'];
        $cagri->refresh();
        $bpc->refresh();
        // The synergistic replace never appends that item. The status append
        // is a separate operation and still runs when its prefix is absent.
        $this->assertSame('NOT THE STORED OVERVIEW', $cagri->overview);
        $this->assertSame(['A point that is not the synergistic line', $status], $cagri->key_points);
        $this->assertNotContains($cagriPayload['columns']['key_points']['operations'][0]['new_item'], $cagri->key_points);
        $this->assertNotContains($cagriPayload['columns']['key_points']['operations'][0]['old_item'], $cagri->key_points);
        $this->assertSame(['key_points', 'updated_at'], $this->changedKeys($cagriBefore, $cagri));
        $this->assertSame($bpcBefore, $bpc->getAttributes());
        $this->assertSame(['No October status item'], $bpc->key_points);
        $this->assertNotContains($bpcPayload['columns']['key_points']['new_item'], $bpc->key_points);
        $this->assertTrue(collect($seeder->log)->contains(fn ($line) => str_contains($line, 'cagrilintide overview: skipped: old value does not match')));
        $this->assertTrue(collect($seeder->log)->contains(fn ($line) => str_contains($line, 'cagrilintide key_points: old item not found; skipped')));
        $this->assertTrue(collect($seeder->log)->contains(fn ($line) => str_contains($line, 'cagrilintide key_points: appended')));
        $this->assertTrue(collect($seeder->log)->contains(fn ($line) => str_contains($line, 'bpc-157 key_points: old item not found; skipped')));
    }

    public function test_category_437_description_changes_only_when_the_post_description_is_empty(): void
    {
        $old = $this->payload('cagrilintide.json')['columns']['description']['old_value'];
        $new = $this->payload('cagrilintide.json')['columns']['description']['new_value'];
        $category = $this->categoryWithId(437, 'Cagrilintide', 'Cagrilintide', $old);
        $blend = $this->categoryWithId(592, 'Semaglutide / Cagrilintide Blend', 'semaglutide-cagrilintide-blend', 'KEEP-592');
        $post = $this->postWithNewValues('Cagrilintide', 'cagrilintide.json', [
            'description' => null,
            'overview' => 'NOT OLD',
        ]);
        $post->product_category_id = $category->id;
        $post->updated_at = now()->subDays(4);
        $post->saveQuietly();
        $category->updated_at = now()->subDays(4);
        $category->saveQuietly();
        $blendBefore = $blend->fresh()->getAttributes();
        $categoryBefore = $category->fresh()->getAttributes();
        $postBefore = $post->fresh()->getAttributes();

        $seeder = new EncyclopediaFieldUpdatesOctober19Seeder;
        $seeder->run();

        $category->refresh();
        $post->refresh();
        $this->assertSame('updated', $seeder->report['cagrilintide']);
        $this->assertSame($new, $category->description);
        $this->assertSame(['description', 'updated_at'], $this->changedKeys($categoryBefore, $category));
        $this->assertNull($post->description);
        $this->assertSame('NOT OLD', $post->overview);
        $this->assertSame($postBefore, $post->getAttributes());
        $this->assertSame($blendBefore, $blend->fresh()->getAttributes());

        $category->description = $old;
        $category->updated_at = now()->subDays(2);
        $category->saveQuietly();
        $post->description = 'Filled card text that is not the old value';
        $post->updated_at = now()->subDay();
        $post->saveQuietly();
        $categoryStamp = $category->fresh()->getAttributes();
        $postStamp = $post->fresh()->getAttributes();

        (new EncyclopediaFieldUpdatesOctober19Seeder)->run();

        $this->assertSame($categoryStamp, $category->fresh()->getAttributes());
        $this->assertSame($postStamp, $post->fresh()->getAttributes());
        $this->assertSame('Filled card text that is not the old value', $post->fresh()->description);
        $this->assertSame($blendBefore, $blend->fresh()->getAttributes());
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
     * @return array{cagri: EducationPost, ghk: EducationPost, bpc: EducationPost, cagriBefore: array<string, mixed>, ghkBefore: array<string, mixed>, bpcBefore: array<string, mixed>, category437: ProductCategory, category592: ProductCategory}
     */
    private function seedMatchingPosts(): array
    {
        $cagriPayload = $this->payload('cagrilintide.json');
        $ghkPayload = $this->payload('ghk-cu.json');
        $bpcPayload = $this->payload('bpc-157.json');
        $category = $this->categoryWithId(437, 'Cagrilintide', 'Cagrilintide', 'KEEP-437-DESCRIPTION');
        $blend = $this->categoryWithId(592, 'Semaglutide / Cagrilintide Blend', 'semaglutide-cagrilintide-blend', 'KEEP-592');

        $cagriColumns = $cagriPayload['columns'];
        $cagriApps = $cagriColumns['potential_applications']['replacements'];
        $cagri = $this->encyclopediaPost($category, 'Cagrilintide', [
            'overview' => $cagriColumns['overview']['old_value'],
            'background' => $cagriColumns['background']['old_value'],
            'half_life' => $cagriColumns['half_life']['old_value'],
            'molecular_formula' => $cagriColumns['molecular_formula']['old_value'],
            'molecular_weight' => $cagriColumns['molecular_weight']['old_value'],
            'cas_registry_number' => $cagriColumns['cas_registry_number']['old_value'],
            'human_use_intro' => $cagriColumns['human_use_intro']['old_value'],
            'human_use_subsections' => $cagriColumns['human_use_subsections']['old_value'],
            'regulatory_subsections' => $cagriColumns['regulatory_subsections']['old_value'],
            'regulatory_important_note' => $cagriColumns['regulatory_important_note']['old_value'],
            'potential_applications_important_context' => $cagriColumns['potential_applications_important_context']['old_value'],
            'potential_applications' => [
                $cagriApps[0]['old_item'],
                ['title' => 'KEEP-APP', 'description' => 'KEEP-APP-BODY'],
                $cagriApps[1]['old_item'],
            ],
            'areas_of_research' => [
                $cagriColumns['areas_of_research']['old_item'],
                ['name' => 'KEEP-AREA', 'description' => 'KEEP-AREA-BODY'],
            ],
            'conclusion' => $cagriColumns['conclusion']['old_value'],
            'references' => $cagriColumns['references']['old_value'],
            'faqs' => $cagriColumns['faqs']['old_value'],
            'key_points' => ['KEEP-POINT', $cagriColumns['key_points']['operations'][0]['old_item']],
            'description' => $cagriColumns['description']['old_value'],
            'preclinical_subsections' => [[
                'title' => 'Metabolic Effects',
                'findings' => [
                    ['title' => 'KEEP', 'content' => 'KEEP-FINDING'],
                    $cagriColumns['preclinical_subsections']['old_finding'],
                ],
            ], [
                'title' => 'KEEP-PRECLINICAL',
                'findings' => [['title' => 'Other', 'content' => 'KEEP-OTHER-FINDING']],
            ]],
            'mechanism_of_action_intro' => 'KEEP-MECHANISM',
        ]);

        $ghkColumns = $ghkPayload['columns'];
        $ghkHuman = $ghkColumns['human_use_subsections'];
        $ghkApps = $ghkColumns['potential_applications']['replacements'];
        $ghkCategory = ProductCategory::create([
            'name' => 'GHK-Cu',
            'slug' => 'catalog-ghk',
            'is_active' => true,
        ]);
        $ghk = $this->encyclopediaPost($ghkCategory, 'GHK-Cu', [
            'human_use_subsections' => [
                $ghkHuman['old_injectable_subsection'],
                ['title' => 'KEEP-HUMAN', 'entries' => [['type' => 'content', 'value' => 'KEEP-HUMAN-BODY']]],
                $ghkHuman['old_dermatological_subsection'],
            ],
            'regulatory_subsections' => $ghkColumns['regulatory_subsections']['current_value_for_reference'],
            'potential_applications' => [
                $ghkApps[0]['old_item'],
                ['title' => 'KEEP-GHK-APP', 'description' => 'KEEP-GHK-APP'],
                $ghkApps[1]['old_item'],
            ],
            'key_points' => ['KEEP-GHK-POINT'],
            'faqs' => $ghkColumns['faqs']['old_value'],
            'references' => $ghkColumns['references']['old_value'],
            'conclusion' => $ghkColumns['conclusion']['old_value'],
            'research_url' => $ghkColumns['research_url']['old_value'],
            'molecular_formula' => $ghkColumns['molecular_formula']['old_value'],
            'molecular_weight' => $ghkColumns['molecular_weight']['old_value'],
            'cas_registry_number' => $ghkColumns['cas_registry_number']['old_value'],
            'half_life' => '',
            'overview' => 'KEEP-GHK-OVERVIEW',
        ]);

        $bpcColumns = $bpcPayload['columns'];
        $bpcCategory = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'catalog-bpc',
            'is_active' => true,
        ]);
        $bpc = $this->encyclopediaPost($bpcCategory, 'BPC-157', [
            'regulatory_subsections' => [
                ['title' => 'KEEP-BEFORE', 'entries' => [['type' => 'content', 'value' => 'KEEP-BEFORE-BODY']]],
                ['title' => 'United States', 'entries' => [
                    ['type' => 'content', 'value' => $bpcColumns['regulatory_subsections']['old_united_states_value'], 'keep' => 'marker'],
                    ['type' => 'item', 'value' => 'KEEP-SECOND'],
                ]],
                $bpcColumns['regulatory_subsections']['old_compounding_subsection'],
                ['title' => 'KEEP-AFTER', 'entries' => [['type' => 'content', 'value' => 'KEEP-AFTER-BODY']]],
            ],
            'regulatory_important_note' => $bpcColumns['regulatory_important_note']['old_value'],
            'key_points' => ['KEEP-BPC', $bpcColumns['key_points']['old_item']],
            'overview' => 'KEEP-BPC-OVERVIEW',
        ]);

        $this->encyclopediaPost(null, 'retatrutide', [
            'overview' => 'LOCKED-RETATRUTIDE',
            'key_points' => ['Locked retatrutide point'],
            'regulatory_important_note' => 'LOCKED-RETA-NOTE',
        ]);
        $this->encyclopediaPost($blend, 'semaglutide-blend-note', [
            'overview' => 'LOCKED-UNRELATED',
        ]);

        return [
            'cagri' => $cagri,
            'ghk' => $ghk,
            'bpc' => $bpc,
            'cagriBefore' => $cagri->getAttributes(),
            'ghkBefore' => $ghk->getAttributes(),
            'bpcBefore' => $bpc->getAttributes(),
            'category437' => $category->fresh(),
            'category592' => $blend->fresh(),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function postWithNewValues(string $slug, string $file, array $overrides): EducationPost
    {
        $columns = $this->payload($file)['columns'];
        $attributes = [];
        foreach ($columns as $name => $spec) {
            if (! is_array($spec) || ! array_key_exists('new_value', $spec)) {
                continue;
            }
            $attributes[$name] = $spec['new_value'];
        }
        if ($file === 'ghk-cu.json') {
            $attributes['half_life'] = $columns['half_life']['new_value'];
            $attributes['human_use_subsections'] = [
                $columns['human_use_subsections']['new_injectable_subsection'],
                $columns['human_use_subsections']['new_dermatological_subsection'],
                $columns['human_use_subsections']['new_subsection'],
            ];
            $attributes['regulatory_subsections'] = array_merge(
                $columns['regulatory_subsections']['current_value_for_reference'],
                [$columns['regulatory_subsections']['new_subsection']]
            );
            $attributes['potential_applications'] = [
                $columns['potential_applications']['replacements'][0]['new_item'],
                ['title' => 'Middle', 'description' => 'Middle'],
                $columns['potential_applications']['replacements'][1]['new_item'],
            ];
            $attributes['key_points'] = [$columns['key_points']['new_item']];
        }
        if ($file === 'cagrilintide.json') {
            $attributes['potential_applications'] = [
                $columns['potential_applications']['replacements'][0]['new_item'],
                ['title' => 'Middle', 'description' => 'Middle'],
                $columns['potential_applications']['replacements'][1]['new_item'],
            ];
            $attributes['areas_of_research'] = [$columns['areas_of_research']['new_item']];
            $attributes['key_points'] = [
                $columns['key_points']['operations'][0]['new_item'],
                $columns['key_points']['operations'][1]['new_item'],
            ];
            $attributes['preclinical_subsections'] = [[
                'title' => 'Metabolic Effects',
                'findings' => [
                    ['title' => 'Kept', 'content' => 'Kept'],
                    $columns['preclinical_subsections']['new_finding'],
                ],
            ]];
        }
        if ($file === 'bpc-157.json') {
            $attributes['regulatory_subsections'] = [[
                'title' => 'United States',
                'entries' => [['type' => 'content', 'value' => $columns['regulatory_subsections']['new_united_states_value']]],
            ], $columns['regulatory_subsections']['new_compounding_subsection']];
            $attributes['key_points'] = [$columns['key_points']['new_item']];
        }

        return $this->encyclopediaPost(null, $slug, array_merge($attributes, $overrides));
    }

    private function categoryWithId(int $id, string $name, string $slug, string $description): ProductCategory
    {
        $category = new ProductCategory;
        $category->forceFill([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'is_active' => true,
        ]);
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

    /**
     * @return array<string, mixed>
     */
    private function payload(string $file): array
    {
        $decoded = json_decode((string) file_get_contents(database_path('seeders/data/encyclopedia-field-updates/2026-10-19/'.$file)), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function protectedSnapshots(): array
    {
        return EducationPost::query()
            ->whereRaw('LOWER(slug) NOT IN (?, ?, ?)', ['cagrilintide', 'ghk-cu', 'bpc-157'])
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
     * @param  array<string, mixed>  $before
     * @return list<string>
     */
    private function changedKeys(array $before, EducationPost|ProductCategory $model): array
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
