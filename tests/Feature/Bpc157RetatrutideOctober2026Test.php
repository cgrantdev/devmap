<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\Bpc157RetatrutideOctober2026Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Bpc157RetatrutideOctober2026Test extends TestCase
{
    use RefreshDatabase;

    public function test_updates_matching_posts_and_leaves_every_other_post_unchanged(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_10_16_120000_update_bpc_157_and_retatrutide_encyclopedia_fields.php'));
        $this->assertStringContainsString('Bpc157RetatrutideOctober2026Seeder', $migration);

        $posts = $this->seedReplaceablePosts();
        $protected = $this->protectedSnapshots();
        $this->assertArrayHasKey(
            EducationPost::query()->whereRaw('LOWER(slug) = ?', ['pemvidutide'])->firstOrFail()->id,
            $protected
        );
        $this->assertArrayHasKey(
            EducationPost::query()->whereRaw('LOWER(slug) = ?', ['hexarelin'])->firstOrFail()->id,
            $protected
        );

        $seeder = new Bpc157RetatrutideOctober2026Seeder;
        $seeder->run();

        $this->assertSame('updated', $seeder->report[Bpc157RetatrutideOctober2026Seeder::BPC_SLUG]);
        $this->assertSame('updated', $seeder->report[Bpc157RetatrutideOctober2026Seeder::RETA_SLUG]);
        $this->assertProtectedUnchanged($protected);

        $bpc = $posts['bpc']->fresh();
        $this->assertSame(
            ['key_points', 'regulatory_important_note', 'regulatory_subsections', 'updated_at'],
            $this->changedKeys($posts['bpcBefore'], $bpc)
        );
        $this->assertSame('BPC-157', $bpc->slug);
        $this->assertSame('KEEP-BPC-OVERVIEW', $bpc->overview);
        $this->assertSame('KEEP-HUMAN', $bpc->human_use_subsections[0]['title']);
        $this->assertSame('KEEP-FAQ', $bpc->faqs[0]['answer']);
        $this->assertSame('KEEP-CURRENT', $bpc->regulatory_subsections[0]['entries'][0]['value']);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::newBpcUnitedStates(), $bpc->regulatory_subsections[1]['entries'][0]['value']);
        $this->assertSame('marker', $bpc->regulatory_subsections[1]['entries'][0]['keep']);
        $this->assertSame('content', $bpc->regulatory_subsections[1]['entries'][0]['type']);
        $this->assertSame('KEEP-SECOND', $bpc->regulatory_subsections[1]['entries'][1]['value']);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::bpcCompoundingSubsection(), $bpc->regulatory_subsections[2]);
        $this->assertSame('KEEP-WADA', $bpc->regulatory_subsections[3]['entries'][0]['value']);
        $this->assertCount(4, $bpc->regulatory_subsections);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::newBpcNote(), $bpc->regulatory_important_note);
        $this->assertSame([
            'Healing research context',
            'Not an approved drug',
            Bpc157RetatrutideOctober2026Seeder::bpcKeyPoint(),
        ], $bpc->key_points);
        $compounding = json_encode($bpc->regulatory_subsections[2], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('OIRA listing as indexed; live page unavailable 16 Oct 2026', $compounding);
        $this->assertStringContainsString('RIN: 0910-ZD94', $compounding);
        $this->assertStringContainsString('Reuters reported on 15 October 2026', $compounding);
        $this->assertStringContainsString('gives no vote counts', $compounding);

        $reta = $posts['reta']->fresh();
        $this->assertSame(
            ['human_use_subsections', 'key_points', 'regulatory_important_note', 'updated_at'],
            $this->changedKeys($posts['retaBefore'], $reta)
        );
        $this->assertSame('Retatrutide', $reta->slug);
        $this->assertSame('LOCKED-OVERVIEW −25.0% and −28.3%', $reta->overview);
        $this->assertSame('KEEP-FDA', $reta->faqs[0]['answer']);
        $this->assertSame('LOCKED Q1 2027 filing plan −25.0% −28.3%', $reta->regulatory_subsections[0]['entries'][0]['value']);
        $this->assertSame('LOCKED −25.0% vs efficacy −28.3%.', $reta->human_use_subsections[0]['entries'][0]['value']);
        $this->assertSame('KEEP-PHASE-2', $reta->human_use_subsections[1]['entries'][0]['value']);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::retaSubsection(), $reta->human_use_subsections[2]);
        $this->assertCount(3, $reta->human_use_subsections);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::newRetaNote(), $reta->regulatory_important_note);
        $this->assertSame([
            'Investigational triple agonist',
            Bpc157RetatrutideOctober2026Seeder::retaKeyPoint(),
        ], $reta->key_points);
        $signals = json_encode($reta->human_use_subsections[2], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString('44 reports in which retatrutide appears as a drug name', $signals);
        $this->assertStringContainsString('doi:10.1001/jamanetworkopen.2026.38929', $signals);
        $this->assertStringContainsString('862 retatrutide exposures', $signals);
    }

    public function test_second_run_is_a_no_op(): void
    {
        $posts = $this->seedReplaceablePosts();
        (new Bpc157RetatrutideOctober2026Seeder)->run();

        $bpc = $posts['bpc']->fresh();
        $reta = $posts['reta']->fresh();
        $bpc->updated_at = now()->subDay();
        $reta->updated_at = now()->subDay();
        $bpc->saveQuietly();
        $reta->saveQuietly();
        $bpcStamp = $bpc->fresh()->updated_at;
        $retaStamp = $reta->fresh()->updated_at;
        $bpcSections = $bpc->fresh()->regulatory_subsections;
        $bpcPoints = $bpc->fresh()->key_points;
        $bpcNote = $bpc->fresh()->regulatory_important_note;
        $retaSections = $reta->fresh()->human_use_subsections;
        $retaPoints = $reta->fresh()->key_points;
        $retaNote = $reta->fresh()->regulatory_important_note;
        $protected = $this->protectedSnapshots();

        $again = new Bpc157RetatrutideOctober2026Seeder;
        $again->run();

        $this->assertSame('unchanged', $again->report[Bpc157RetatrutideOctober2026Seeder::BPC_SLUG]);
        $this->assertSame('unchanged', $again->report[Bpc157RetatrutideOctober2026Seeder::RETA_SLUG]);
        $this->assertProtectedUnchanged($protected);
        $bpc->refresh();
        $reta->refresh();
        $this->assertTrue($bpc->updated_at->equalTo($bpcStamp));
        $this->assertTrue($reta->updated_at->equalTo($retaStamp));
        $this->assertSame($bpcSections, $bpc->regulatory_subsections);
        $this->assertSame($bpcPoints, $bpc->key_points);
        $this->assertSame($bpcNote, $bpc->regulatory_important_note);
        $this->assertSame($retaSections, $reta->human_use_subsections);
        $this->assertSame($retaPoints, $reta->key_points);
        $this->assertSame($retaNote, $reta->regulatory_important_note);
        $this->assertCount(1, array_filter(
            $bpc->regulatory_subsections,
            fn ($section) => is_array($section) && str_starts_with((string) ($section['title'] ?? ''), Bpc157RetatrutideOctober2026Seeder::BPC_COMPOUNDING_TITLE_PREFIX)
        ));
        $this->assertCount(1, array_filter(
            $bpc->key_points,
            fn ($point) => is_string($point) && str_starts_with($point, Bpc157RetatrutideOctober2026Seeder::BPC_KEY_POINT_PREFIX)
        ));
        $this->assertCount(1, array_filter(
            $reta->human_use_subsections,
            fn ($section) => is_array($section) && str_starts_with((string) ($section['title'] ?? ''), Bpc157RetatrutideOctober2026Seeder::RETA_SUBSECTION_TITLE_PREFIX)
        ));
        $this->assertCount(1, array_filter(
            $reta->key_points,
            fn ($point) => is_string($point) && str_starts_with($point, Bpc157RetatrutideOctober2026Seeder::RETA_KEY_POINT_PREFIX)
        ));
    }

    public function test_skips_when_the_guarded_content_is_already_present(): void
    {
        $bpcCategory = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'catalog-bpc',
            'is_active' => true,
        ]);
        $bpc = $this->encyclopediaPost($bpcCategory, 'BPC-157', [
            'regulatory_subsections' => [
                [
                    'title' => 'United States',
                    'entries' => [[
                        'type' => 'content',
                        'value' => Bpc157RetatrutideOctober2026Seeder::oldBpcUnitedStates(),
                    ]],
                ],
                [
                    'title' => 'U.S. regulatory status — compounding review (as of 16 October 2026)',
                    'entries' => [['type' => 'content', 'value' => 'ALREADY-PRESENT-BPC']],
                ],
            ],
            'regulatory_important_note' => 'Custom note that already cites RIN 0910-ZD94 and must stay.',
            'key_points' => ['Regulatory status (as of 16 Oct 2026): already written in shorter form.'],
            'overview' => 'KEEP-BPC-OVERVIEW',
        ]);
        $retaCategory = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'catalog-reta',
            'is_active' => true,
        ]);
        $reta = $this->encyclopediaPost($retaCategory, 'retatrutide', [
            'human_use_subsections' => [
                [
                    'title' => 'TRIUMPH-1',
                    'entries' => [['type' => 'content', 'value' => 'LOCKED −25.0% −28.3%']],
                ],
                [
                    'title' => 'Reported safety signals outside clinical trials — already filed',
                    'entries' => [['type' => 'content', 'value' => 'ALREADY-PRESENT-RETA']],
                ],
            ],
            'regulatory_important_note' => 'ALREADY-RETA-NOTE',
            'key_points' => ['Safety reports outside trials (Oct 2026): already summarized.'],
            'overview' => 'LOCKED-OVERVIEW',
        ]);
        $bpcBefore = $bpc->getAttributes();
        $retaBefore = $reta->getAttributes();
        $protected = $this->protectedSnapshots();

        $seeder = new Bpc157RetatrutideOctober2026Seeder;
        $seeder->run();

        $this->assertSame('updated', $seeder->report[Bpc157RetatrutideOctober2026Seeder::BPC_SLUG]);
        $this->assertSame('unchanged', $seeder->report[Bpc157RetatrutideOctober2026Seeder::RETA_SLUG]);
        $this->assertSame($retaBefore, $reta->fresh()->getAttributes());
        $this->assertProtectedUnchanged($protected);
        $bpc->refresh();
        $this->assertSame(['regulatory_subsections', 'updated_at'], $this->changedKeys($bpcBefore, $bpc));
        $this->assertCount(2, $bpc->regulatory_subsections);
        $this->assertSame('ALREADY-PRESENT-BPC', $bpc->regulatory_subsections[1]['entries'][0]['value']);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::newBpcUnitedStates(), $bpc->regulatory_subsections[0]['entries'][0]['value']);
        $this->assertSame('Custom note that already cites RIN 0910-ZD94 and must stay.', $bpc->regulatory_important_note);
        $this->assertSame(['Regulatory status (as of 16 Oct 2026): already written in shorter form.'], $bpc->key_points);
        $this->assertCount(2, $reta->fresh()->human_use_subsections);
        $this->assertSame('ALREADY-PRESENT-RETA', $reta->fresh()->human_use_subsections[1]['entries'][0]['value']);
        $this->assertSame('LOCKED −25.0% −28.3%', $reta->fresh()->human_use_subsections[0]['entries'][0]['value']);
        $this->assertSame('ALREADY-RETA-NOTE', $reta->fresh()->regulatory_important_note);
        $this->assertSame(['Safety reports outside trials (Oct 2026): already summarized.'], $reta->fresh()->key_points);
    }

    public function test_does_nothing_when_the_slug_is_missing(): void
    {
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'BPC-157',
            'is_active' => true,
        ]);
        $decoy = $this->encyclopediaPost($category, 'bpc-157-notes', [
            'regulatory_subsections' => $this->bpcSections(Bpc157RetatrutideOctober2026Seeder::oldBpcUnitedStates()),
            'regulatory_important_note' => Bpc157RetatrutideOctober2026Seeder::oldBpcNote(),
            'key_points' => ['Would update if the post slug matched'],
            'human_use_subsections' => $this->retaSections(),
            'overview' => 'DECOY-OVERVIEW',
        ]);
        $before = $decoy->getAttributes();
        $protected = $this->protectedSnapshots();
        $postsBefore = EducationPost::query()->count();

        $seeder = new Bpc157RetatrutideOctober2026Seeder;
        $seeder->run();

        $this->assertSame('missing', $seeder->report[Bpc157RetatrutideOctober2026Seeder::BPC_SLUG]);
        $this->assertSame('missing', $seeder->report[Bpc157RetatrutideOctober2026Seeder::RETA_SLUG]);
        $this->assertSame(0, EducationPost::query()->whereRaw('LOWER(slug) = ?', ['bpc-157'])->count());
        $this->assertSame(0, EducationPost::query()->whereRaw('LOWER(slug) = ?', ['retatrutide'])->count());
        $this->assertSame($postsBefore, EducationPost::query()->count());
        $this->assertSame($before, $decoy->fresh()->getAttributes());
        $this->assertSame('BPC-157', $category->fresh()->slug);
        $this->assertProtectedUnchanged($protected);
    }

    public function test_old_value_mismatch_leaves_that_field_unchanged(): void
    {
        $posts = $this->seedReplaceablePosts([
            'bpc_united_states' => 'NOT THE STORED UNITED STATES PARAGRAPH',
            'bpc_note' => 'NOT THE STORED BPC NOTE',
            'reta_note' => 'NOT THE STORED RETA NOTE',
        ]);
        $protected = $this->protectedSnapshots();

        $seeder = new Bpc157RetatrutideOctober2026Seeder;
        $seeder->run();

        $this->assertSame('updated', $seeder->report[Bpc157RetatrutideOctober2026Seeder::BPC_SLUG]);
        $this->assertSame('updated', $seeder->report[Bpc157RetatrutideOctober2026Seeder::RETA_SLUG]);
        $this->assertProtectedUnchanged($protected);

        $bpc = $posts['bpc']->fresh();
        $this->assertSame(
            ['key_points', 'regulatory_subsections', 'updated_at'],
            $this->changedKeys($posts['bpcBefore'], $bpc)
        );
        $this->assertSame('NOT THE STORED UNITED STATES PARAGRAPH', $bpc->regulatory_subsections[1]['entries'][0]['value']);
        $this->assertSame('marker', $bpc->regulatory_subsections[1]['entries'][0]['keep']);
        $this->assertSame('KEEP-SECOND', $bpc->regulatory_subsections[1]['entries'][1]['value']);
        $this->assertSame('NOT THE STORED BPC NOTE', $bpc->regulatory_important_note);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::bpcCompoundingSubsection(), $bpc->regulatory_subsections[2]);
        $this->assertSame('KEEP-WADA', $bpc->regulatory_subsections[3]['entries'][0]['value']);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::bpcKeyPoint(), $bpc->key_points[2]);

        $reta = $posts['reta']->fresh();
        $this->assertSame(
            ['human_use_subsections', 'key_points', 'updated_at'],
            $this->changedKeys($posts['retaBefore'], $reta)
        );
        $this->assertSame('NOT THE STORED RETA NOTE', $reta->regulatory_important_note);
        $this->assertSame('LOCKED −25.0% vs efficacy −28.3%.', $reta->human_use_subsections[0]['entries'][0]['value']);
        $this->assertSame('KEEP-PHASE-2', $reta->human_use_subsections[1]['entries'][0]['value']);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::retaSubsection(), $reta->human_use_subsections[2]);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::retaKeyPoint(), $reta->key_points[1]);
        $this->assertSame('LOCKED-OVERVIEW −25.0% and −28.3%', $reta->overview);
        $this->assertSame('LOCKED Q1 2027 filing plan −25.0% −28.3%', $reta->regulatory_subsections[0]['entries'][0]['value']);
    }

    public function test_skips_when_more_than_one_post_matches_the_slug(): void
    {
        $first = $this->encyclopediaPost(null, 'BPC-157', [
            'regulatory_subsections' => $this->bpcSections(Bpc157RetatrutideOctober2026Seeder::oldBpcUnitedStates()),
            'regulatory_important_note' => Bpc157RetatrutideOctober2026Seeder::oldBpcNote(),
            'key_points' => ['First BPC point'],
        ]);
        $second = $this->encyclopediaPost(null, 'bpc-157', [
            'regulatory_subsections' => $this->bpcSections(Bpc157RetatrutideOctober2026Seeder::oldBpcUnitedStates()),
            'regulatory_important_note' => Bpc157RetatrutideOctober2026Seeder::oldBpcNote(),
            'key_points' => ['Second BPC point'],
        ]);
        $retaCategory = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'catalog-reta',
            'is_active' => true,
        ]);
        $reta = $this->encyclopediaPost($retaCategory, 'retatrutide', [
            'human_use_subsections' => $this->retaSections(),
            'regulatory_important_note' => Bpc157RetatrutideOctober2026Seeder::oldRetaNote(),
            'key_points' => ['Investigational triple agonist'],
            'overview' => 'LOCKED-OVERVIEW −25.0% and −28.3%',
        ]);
        $firstBefore = $first->getAttributes();
        $secondBefore = $second->getAttributes();
        $protected = $this->protectedSnapshots();

        $seeder = new Bpc157RetatrutideOctober2026Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report[Bpc157RetatrutideOctober2026Seeder::BPC_SLUG]);
        $this->assertSame('updated', $seeder->report[Bpc157RetatrutideOctober2026Seeder::RETA_SLUG]);
        $this->assertSame($firstBefore, $first->fresh()->getAttributes());
        $this->assertSame($secondBefore, $second->fresh()->getAttributes());
        $this->assertProtectedUnchanged($protected);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::oldBpcUnitedStates(), $first->fresh()->regulatory_subsections[1]['entries'][0]['value']);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::oldBpcNote(), $second->fresh()->regulatory_important_note);
        $this->assertCount(3, $first->fresh()->regulatory_subsections);
        $this->assertSame(Bpc157RetatrutideOctober2026Seeder::retaSubsection(), $reta->fresh()->human_use_subsections[2]);
        $this->assertSame('LOCKED-OVERVIEW −25.0% and −28.3%', $reta->fresh()->overview);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array{bpc: EducationPost, reta: EducationPost, other: EducationPost, bpcBefore: array<string, mixed>, retaBefore: array<string, mixed>}
     */
    private function seedReplaceablePosts(array $overrides = []): array
    {
        $bpcCategory = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'catalog-bpc',
            'is_active' => true,
        ]);
        $bpc = $this->encyclopediaPost($bpcCategory, 'BPC-157', [
            'regulatory_subsections' => $this->bpcSections($overrides['bpc_united_states'] ?? Bpc157RetatrutideOctober2026Seeder::oldBpcUnitedStates()),
            'regulatory_important_note' => $overrides['bpc_note'] ?? Bpc157RetatrutideOctober2026Seeder::oldBpcNote(),
            'key_points' => ['Healing research context', 'Not an approved drug'],
            'human_use_subsections' => [[
                'title' => 'KEEP-HUMAN',
                'entries' => [['type' => 'content', 'value' => 'KEEP-HUMAN-BODY']],
            ]],
            'faqs' => [['question' => 'KEEP?', 'answer' => 'KEEP-FAQ']],
            'overview' => 'KEEP-BPC-OVERVIEW',
        ]);
        $retaCategory = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'catalog-reta',
            'is_active' => true,
        ]);
        $reta = $this->encyclopediaPost($retaCategory, 'Retatrutide', [
            'human_use_subsections' => $this->retaSections(),
            'regulatory_important_note' => $overrides['reta_note'] ?? Bpc157RetatrutideOctober2026Seeder::oldRetaNote(),
            'key_points' => ['Investigational triple agonist'],
            'regulatory_subsections' => [[
                'title' => 'Filing literacy',
                'entries' => [['type' => 'content', 'value' => 'LOCKED Q1 2027 filing plan −25.0% −28.3%']],
            ]],
            'faqs' => [['question' => 'Is retatrutide FDA-approved?', 'answer' => 'KEEP-FDA']],
            'overview' => 'LOCKED-OVERVIEW −25.0% and −28.3%',
        ]);
        $other = $this->encyclopediaPost(null, 'semaglutide', [
            'regulatory_subsections' => $this->bpcSections(Bpc157RetatrutideOctober2026Seeder::oldBpcUnitedStates()),
            'regulatory_important_note' => Bpc157RetatrutideOctober2026Seeder::oldBpcNote(),
            'key_points' => ['Other point'],
            'human_use_subsections' => $this->retaSections(),
            'overview' => 'OTHER-OVERVIEW',
        ]);

        return [
            'bpc' => $bpc,
            'reta' => $reta,
            'other' => $other,
            'bpcBefore' => $bpc->getAttributes(),
            'retaBefore' => $reta->getAttributes(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function bpcSections(string $unitedStates): array
    {
        return [
            [
                'title' => 'Current Approval Status',
                'entries' => [['type' => 'content', 'value' => 'KEEP-CURRENT']],
            ],
            [
                'title' => 'United States',
                'entries' => [
                    ['type' => 'content', 'value' => $unitedStates, 'keep' => 'marker'],
                    ['type' => 'item', 'value' => 'KEEP-SECOND'],
                ],
            ],
            [
                'title' => 'World Anti-Doping Agency (WADA)',
                'entries' => [['type' => 'content', 'value' => 'KEEP-WADA']],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function retaSections(): array
    {
        return [
            [
                'title' => 'TRIUMPH-1 Phase 3 (NEJM, 29 Sep 2026)',
                'entries' => [['type' => 'content', 'value' => 'LOCKED −25.0% vs efficacy −28.3%.']],
            ],
            [
                'title' => 'Phase 2 Results',
                'entries' => [['type' => 'content', 'value' => 'KEEP-PHASE-2']],
            ],
        ];
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
            'conclusion' => 'CONCLUSION-'.$slug,
            'background' => 'BACKGROUND-'.$slug,
        ], $attributes));
        $post->updated_at = now()->subDays(4);
        $post->saveQuietly();

        return $post->fresh();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function protectedSnapshots(): array
    {
        return EducationPost::query()
            ->whereRaw('LOWER(slug) NOT IN (?, ?)', [
                Bpc157RetatrutideOctober2026Seeder::BPC_SLUG,
                Bpc157RetatrutideOctober2026Seeder::RETA_SLUG,
            ])
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
    private function changedKeys(array $before, EducationPost $post): array
    {
        $after = $post->getAttributes();
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
