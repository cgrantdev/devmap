<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\FaqPage;
use Database\Seeders\Cjc1295DacIdentitySeeder;
use Database\Seeders\EncyclopediaFaqAuditBatch1Seeder;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class EncyclopediaFaqAuditBatch1Test extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private const SLUGS = ['tb-500', 'ipamorelin', 'cjc-1295', 'aod-9604', 'kisspeptin'];

    private const DOSE_PATTERN = '/\d+(?:[.·]\d+)?\s?(mg|mcg|µg|IU|mL|nmol)\b|\/kg/u';

    private const COMMERCE_PATTERN = '/(?i)\bbuy\b|coupon|discount|\$\s?\d|in stock|\bCOA\b|per day|daily dose|\bcycle\b|reconstitut|best time|loading phase|users report|stack(ed|ing)? with/u';

    private const PERCENT_PATTERN = '/\d+(?:\.\d+)?\s?%/u';

    /** @var list<string> */
    private const SOURCE_TOKENS = [
        'PMID ',
        'fda.gov',
        'FDA',
        'WADA',
        'PubChem CID',
        'NCT',
        'doi:',
        'et al.',
        'PubMed',
        'ClinicalTrials.gov',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_writes_each_target_and_keeps_items_reduce_drops(): void
    {
        $seeded = $this->seedTargets();
        $tb = $seeded['tb-500'];
        $faqs = $tb->faqs;
        $faqs[0]['answer'] = '<em>'.$faqs[0]['answer'].'</em>';
        array_splice($faqs, 1, 0, [['question' => '', 'answer' => 'KEEP-EMPTY']]);
        $tb->faqs = $faqs;
        $tb->saveQuietly();

        $seeder = new EncyclopediaFaqAuditBatch1Seeder;
        $seeder->run();

        $total = 0;
        foreach (self::SLUGS as $slug) {
            $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::UPDATED, $seeder->report[$slug], $slug);
            $fresh = $seeded[$slug]->fresh();
            $newFaqs = $this->payload($slug)['new_faqs'];
            $this->assertSame($newFaqs, $this->reduce($fresh->faqs), $slug);
            $total += count($newFaqs);
            if ($slug === 'tb-500') {
                $this->assertSame('KEEP-EMPTY', $fresh->faqs[1]['answer']);
                $this->assertSame('', $fresh->faqs[1]['question']);
                $this->assertTrue($this->logContains($seeder, 'tb-500: dropped empty q/a items=1'));
            } else {
                $this->assertSame($newFaqs, $fresh->faqs, $slug);
            }
        }
        $this->assertSame(19, $total);
    }

    public function test_protected_rows_stay_byte_identical_and_only_faqs_change_on_targets(): void
    {
        $protected = $this->seedProtected();
        $protectedBefore = [];
        foreach ($protected as $key => $post) {
            $protectedBefore[$key] = $post->fresh()->getAttributes();
        }
        $seeded = $this->seedTargets();
        $before = [];
        foreach ($seeded as $slug => $post) {
            $fresh = $post->fresh();
            $fresh->updated_at = now()->subDay();
            $fresh->saveQuietly();
            $before[$slug] = $fresh->fresh()->getAttributes();
        }

        (new EncyclopediaFaqAuditBatch1Seeder)->run();

        foreach ($protected as $key => $post) {
            $this->assertSame($protectedBefore[$key], $post->fresh()->getAttributes(), $key);
        }
        foreach (self::SLUGS as $slug) {
            $after = $seeded[$slug]->fresh()->getAttributes();
            $this->assertNotSame($before[$slug]['faqs'], $after['faqs'], $slug);
            $this->assertNotSame($before[$slug]['updated_at'], $after['updated_at'], $slug);
            unset($before[$slug]['faqs'], $before[$slug]['updated_at'], $after['faqs'], $after['updated_at']);
            $this->assertSame($before[$slug], $after, $slug);
        }
    }

    public function test_legacy_unlinked_rows_stay_byte_identical_and_are_named_in_the_diagnostic(): void
    {
        $seeded = $this->seedTargets();
        $tbLegacy = $this->seedPost(null, 'tb-500', ['faqs' => $this->payload('tb-500')['old_faqs_guard'], 'status' => 'draft']);
        $ipaLegacy = $this->seedPost(null, 'Ipamorelin', ['faqs' => $this->payload('ipamorelin')['old_faqs_guard'], 'status' => 'draft']);
        $tbBefore = $tbLegacy->fresh()->getAttributes();
        $ipaBefore = $ipaLegacy->fresh()->getAttributes();

        $seeder = new EncyclopediaFaqAuditBatch1Seeder;
        $seeder->run();

        $this->assertSame($tbBefore, $tbLegacy->fresh()->getAttributes());
        $this->assertSame($ipaBefore, $ipaLegacy->fresh()->getAttributes());
        $blob = implode("\n", $seeder->log);
        $this->assertStringContainsString('id='.$tbLegacy->id, $blob);
        $this->assertStringContainsString('slug=[tb-500]', $blob);
        $this->assertStringContainsString('product_category_id=null', $blob);
        $this->assertStringContainsString('id='.$ipaLegacy->id, $blob);
        $this->assertStringContainsString('slug=[Ipamorelin]', $blob);
        foreach (self::SLUGS as $slug) {
            $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::UPDATED, $seeder->report[$slug], $slug);
        }
        $this->assertSame($this->payload('tb-500')['new_faqs'], $this->reduce($seeded['tb-500']->fresh()->faqs));
    }

    public function test_case_and_trimmed_category_slugs_match(): void
    {
        foreach (['TB-500', 'tb-500', ' TB-500 '] as $categorySlug) {
            $this->wipe();
            $category = ProductCategory::create([
                'name' => 'TB-500',
                'slug' => $categorySlug,
                'is_active' => true,
            ]);
            $post = $this->seedPost($category, 'linked-tb-'.md5($categorySlug), [
                'faqs' => $this->payload('tb-500')['old_faqs_guard'],
            ]);
            $seeder = new EncyclopediaFaqAuditBatch1Seeder;
            $seeder->run();
            $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::UPDATED, $seeder->report['tb-500'], $categorySlug);
            $this->assertSame($this->payload('tb-500')['new_faqs'], $post->fresh()->faqs, $categorySlug);
        }

        foreach (['Kisspeptin', 'KISSPEPTIN'] as $categorySlug) {
            $this->wipe();
            $category = ProductCategory::create([
                'name' => 'Kisspeptin',
                'slug' => $categorySlug,
                'is_active' => true,
            ]);
            $post = $this->seedPost($category, 'linked-kiss-'.md5($categorySlug), [
                'faqs' => $this->payload('kisspeptin')['old_faqs_guard'],
            ]);
            $seeder = new EncyclopediaFaqAuditBatch1Seeder;
            $seeder->run();
            $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::UPDATED, $seeder->report['kisspeptin'], $categorySlug);
            $this->assertSame($this->payload('kisspeptin')['new_faqs'], $post->fresh()->faqs, $categorySlug);
        }
    }

    public function test_missing_or_collision_writes_nothing_and_opens_no_transaction(): void
    {
        $first = ProductCategory::create(['name' => 'Ipamorelin', 'slug' => 'Ipamorelin', 'is_active' => true]);
        $second = ProductCategory::create(['name' => 'Ipamorelin other', 'slug' => 'ipamorelin', 'is_active' => true]);
        $left = $this->seedPost($first, 'ipa-dup-a', ['faqs' => $this->payload('ipamorelin')['old_faqs_guard']]);
        $right = $this->seedPost($second, 'ipa-dup-b', ['faqs' => $this->payload('ipamorelin')['old_faqs_guard']]);
        $before = [$left->fresh()->getAttributes(), $right->fresh()->getAttributes()];
        $warnings = $this->captureWarnings();
        $began = $this->countTransactions();

        $seeder = new EncyclopediaFaqAuditBatch1Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report['ipamorelin']);
        $this->assertSame([], $seeder->transactions);
        $this->assertSame(0, $began());
        $this->assertSame($before[0], $left->fresh()->getAttributes());
        $this->assertSame($before[1], $right->fresh()->getAttributes());
        $this->assertTrue($this->warningsContain($warnings, 'ipamorelin', '2'), json_encode($warnings->getArrayCopy()));

        $this->wipe();
        $category = ProductCategory::create(['name' => 'Ipamorelin', 'slug' => 'ipamorelin', 'is_active' => true]);
        Schema::table('education_posts', function (Blueprint $table) {
            $table->dropUnique(['product_category_id']);
        });
        $one = $this->seedPost($category, 'ipa-post-a', ['faqs' => $this->payload('ipamorelin')['old_faqs_guard'], 'overview' => 'DUP-A']);
        $two = $this->seedPost($category, 'ipa-post-b', ['faqs' => $this->payload('ipamorelin')['old_faqs_guard'], 'overview' => 'DUP-B']);
        $postBefore = [$one->fresh()->getAttributes(), $two->fresh()->getAttributes()];
        $warnings = $this->captureWarnings();
        $began = $this->countTransactions();

        $seeder = new EncyclopediaFaqAuditBatch1Seeder;
        $seeder->run();

        $this->assertSame('collision', $seeder->report['ipamorelin']);
        $this->assertSame([], $seeder->transactions);
        $this->assertSame(0, $began());
        $this->assertSame($postBefore[0], $one->fresh()->getAttributes());
        $this->assertSame($postBefore[1], $two->fresh()->getAttributes());
        $this->assertTrue($this->warningsContain($warnings, 'ipamorelin', '2'), json_encode($warnings->getArrayCopy()));

        $this->wipe();
        try {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->unique('product_category_id');
            });
        } catch (\Throwable) {
            // The unique index is already present.
        }
        $warnings = $this->captureWarnings();
        $began = $this->countTransactions();
        $seeder = new EncyclopediaFaqAuditBatch1Seeder;
        $seeder->run();
        $this->assertSame('missing', $seeder->report['ipamorelin']);
        $this->assertSame([], $seeder->transactions);
        $this->assertSame(0, $began());
        $this->assertTrue($this->warningsContain($warnings, 'ipamorelin', 'missing'), json_encode($warnings->getArrayCopy()));
    }

    public function test_second_run_is_already_at_target_and_identity_seeder_does_not_restore_old_faqs(): void
    {
        $seeded = $this->seedTargets();
        (new EncyclopediaFaqAuditBatch1Seeder)->run();

        foreach ($seeded as $slug => $post) {
            $fresh = $post->fresh();
            $fresh->updated_at = now()->subDay();
            $fresh->saveQuietly();
            $seeded[$slug] = $fresh;
        }
        $snapshots = [];
        foreach ($seeded as $slug => $post) {
            $snapshots[$slug] = $post->fresh()->getAttributes();
        }

        $again = new EncyclopediaFaqAuditBatch1Seeder;
        $again->run();

        foreach (self::SLUGS as $slug) {
            $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::AT_TARGET, $again->report[$slug], $slug);
            $this->assertSame($snapshots[$slug], $seeded[$slug]->fresh()->getAttributes(), $slug);
        }
        $this->assertNotContains(EncyclopediaFaqAuditBatch1Seeder::UPDATED, $again->report);

        (new Cjc1295DacIdentitySeeder)->run();
        $faqs = $seeded['cjc-1295']->fresh()->faqs;
        $newFaqs = $this->payload('cjc-1295')['new_faqs'];
        $this->assertCount(7, $faqs);
        $this->assertSame($newFaqs, $faqs);
        $blob = json_encode($faqs, JSON_UNESCAPED_UNICODE);
        $this->assertIsString($blob);
        $this->assertStringNotContainsString('What should a blend CoA name?', $blob);

        $expectedPairs = [];
        foreach (array_slice($newFaqs, 3, 4) as $faq) {
            $expectedPairs[$faq['question']] = $faq['answer'];
        }
        $this->assertSame($expectedPairs, Cjc1295DacIdentitySeeder::replacementFaqPairs());
    }

    public function test_whitespace_drift_matches_and_a_changed_word_does_not(): void
    {
        $seeded = [];
        foreach (self::SLUGS as $slug) {
            $faqs = $this->payload($slug)['old_faqs_guard'];
            foreach ($faqs as &$faq) {
                $faq['question'] = " \r\n".$faq['question']."  \r\n";
                $faq['answer'] = "\u{00A0}".str_replace(' ', "\u{00A0}", $faq['answer'])." \r\n";
            }
            unset($faq);
            $category = ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'is_active' => true,
            ]);
            $seeded[$slug] = $this->seedPost($category, $slug.'-drift', ['faqs' => $faqs]);
        }

        $seeder = new EncyclopediaFaqAuditBatch1Seeder;
        $seeder->run();
        foreach (self::SLUGS as $slug) {
            $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::UPDATED, $seeder->report[$slug], $slug);
            $this->assertSame($this->payload($slug)['new_faqs'], $seeded[$slug]->fresh()->faqs, $slug);
        }

        $this->wipe();
        $guard = $this->payload('aod-9604')['old_faqs_guard'];
        $guard[0]['answer'] .= ' mismatch';
        $category = ProductCategory::create([
            'name' => 'AOD-9604',
            'slug' => 'aod-9604',
            'is_active' => true,
        ]);
        $post = $this->seedPost($category, 'aod-mismatch', ['faqs' => $guard]);
        $before = $post->fresh()->getAttributes();
        $warnings = $this->captureWarnings();
        $seeder = new EncyclopediaFaqAuditBatch1Seeder;
        $seeder->run();

        $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::MISMATCH, $seeder->report['aod-9604']);
        $this->assertSame($before, $post->fresh()->getAttributes());
        $this->assertTrue($this->warningsContain($warnings, 'aod-9604', 'old mismatch'), json_encode($warnings->getArrayCopy()));
        $warning = $this->warningLine($warnings, 'aod-9604', 'excerpt=');
        $this->assertNotNull($warning);
        $this->assertLessThanOrEqual(200, mb_strlen((string) preg_replace('/^.*excerpt=/', '', (string) $warning)));
    }

    public function test_a_failure_during_aod_save_rolls_back_only_that_slug(): void
    {
        $seeded = $this->seedTargets();
        $aodBefore = $seeded['aod-9604']->fresh()->getAttributes();
        $seeder = new class extends EncyclopediaFaqAuditBatch1Seeder
        {
            protected function beforeSave(string $slug): void
            {
                if ($slug === 'aod-9604') {
                    throw new RuntimeException('forced write failure');
                }
            }
        };

        $seeder->run();

        $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::FAILED, $seeder->report['aod-9604']);
        $this->assertSame($aodBefore, $seeded['aod-9604']->fresh()->getAttributes());
        foreach (['tb-500', 'ipamorelin', 'cjc-1295', 'kisspeptin'] as $slug) {
            $this->assertSame(EncyclopediaFaqAuditBatch1Seeder::UPDATED, $seeder->report[$slug], $slug);
            $this->assertSame($this->payload($slug)['new_faqs'], $seeded[$slug]->fresh()->faqs, $slug);
        }
        $this->assertTrue($this->logContains($seeder, 'aod-9604', 'forced write failure'));
        $this->assertContains('aod-9604', $seeder->transactions);
    }

    public function test_seed_text_no_longer_contains_the_replaced_answers(): void
    {
        $needles = ['What should a blend CoA name?'];
        foreach (self::SLUGS as $slug) {
            foreach ($this->payload($slug)['old_faqs_guard'] as $faq) {
                $needles[] = $faq['answer'];
            }
        }

        foreach ([database_path(), app_path(), resource_path()] as $root) {
            foreach (File::allFiles($root) as $file) {
                $path = str_replace('\\', '/', $file->getPathname());
                if (str_contains($path, 'encyclopedia-field-updates/2026-10-22d/')) {
                    continue;
                }
                $contents = $file->getContents();
                foreach ($needles as $needle) {
                    $this->assertStringNotContainsString($needle, $contents, $path);
                }
            }
        }
    }

    public function test_rendered_faqpage_main_entity_equals_new_faqs(): void
    {
        $this->seedTargets();
        (new EncyclopediaFaqAuditBatch1Seeder)->run();

        foreach (self::SLUGS as $slug) {
            $response = $this->get('/encyclopedia/'.$slug);
            $response->assertOk();
            $faqPage = $this->faqPage($response->getContent());
            $this->assertIsArray($faqPage, $slug);
            $newFaqs = $this->payload($slug)['new_faqs'];
            $this->assertCount(count($newFaqs), $faqPage['mainEntity'], $slug);
            foreach ($newFaqs as $index => $faq) {
                $this->assertSame($faq['question'], $faqPage['mainEntity'][$index]['name'], $slug.' '.$index);
                $this->assertSame($faq['answer'], $faqPage['mainEntity'][$index]['acceptedAnswer']['text'], $slug.' '.$index);
            }
        }
    }

    public function test_new_items_match_the_dose_commerce_and_percent_patterns_only_for_the_cjc_half_life_exception(): void
    {
        foreach (self::SLUGS as $slug) {
            foreach ($this->payload($slug)['new_faqs'] as $index => $faq) {
                $text = $faq['question']."\n".$faq['answer'];
                if ($slug === 'cjc-1295' && $index === 4) {
                    $text = str_replace('5.8–8.1 days', '', $text);
                }
                $this->assertDoesNotMatchRegularExpression(self::DOSE_PATTERN, $text, $slug.' '.$index);
                $this->assertDoesNotMatchRegularExpression(self::COMMERCE_PATTERN, $text, $slug.' '.$index);
                $this->assertDoesNotMatchRegularExpression(self::PERCENT_PATTERN, $text, $slug.' '.$index);
            }
        }
    }

    public function test_every_new_answer_names_a_source_and_aod_faq_2_is_not_an_fda_gras_claim(): void
    {
        foreach (self::SLUGS as $slug) {
            foreach ($this->payload($slug)['new_faqs'] as $index => $faq) {
                $hit = false;
                foreach (self::SOURCE_TOKENS as $token) {
                    if (str_contains($faq['answer'], $token)) {
                        $hit = true;
                        break;
                    }
                }
                $this->assertTrue($hit, $slug.' FAQ '.$index.' has no source token');
            }
        }

        $aod = $this->payload('aod-9604')['new_faqs'][1]['answer'];
        $this->assertStringContainsString('self-affirmed', $aod);
        $this->assertStringNotContainsString('GRAS status from the FDA', $aod);
    }

    public function test_inertia_faqs_prop_equals_faqpage_main_entity(): void
    {
        $this->seedTargets();
        (new EncyclopediaFaqAuditBatch1Seeder)->run();

        foreach (self::SLUGS as $slug) {
            $response = $this->get('/encyclopedia/'.$slug);
            $response->assertOk();
            $faqPage = $this->faqPage($response->getContent());
            $this->assertIsArray($faqPage, $slug);
            $response->assertInertia(function ($page) use ($faqPage, $slug) {
                $faqs = $page->toArray()['props']['faqs'];
                $this->assertIsArray($faqs, $slug);
                $this->assertCount(count($faqPage['mainEntity']), $faqs, $slug);
                foreach ($faqPage['mainEntity'] as $index => $entity) {
                    $this->assertSame($entity['name'], $faqs[$index]['question'], $slug.' '.$index);
                    $this->assertSame($entity['acceptedAnswer']['text'], $faqs[$index]['answer'], $slug.' '.$index);
                }

                return $page;
            });
        }
    }

    public function test_allowlist_includes_the_batch_and_render_allowed_is_case_insensitive(): void
    {
        $this->assertSame([
            'bpc-157',
            'tb-500',
            'ipamorelin',
            'cjc-1295',
            'aod-9604',
            'kisspeptin',
        ], config('peptidemap.faq_render_allowlist.encyclopedia'));

        foreach (['bpc-157', 'tb-500', 'ipamorelin', 'cjc-1295', 'aod-9604', 'kisspeptin', 'TB-500'] as $slug) {
            $this->assertTrue(FaqPage::renderAllowed('encyclopedia', $slug), $slug);
        }
    }

    /**
     * @return array<string, EducationPost>
     */
    private function seedTargets(): array
    {
        $posts = [];
        foreach (self::SLUGS as $slug) {
            $category = ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'is_active' => true,
            ]);
            $posts[$slug] = $this->seedPost($category, $slug.'-post', [
                'faqs' => $this->payload($slug)['old_faqs_guard'],
                'overview' => 'KEEP-'.$slug,
            ]);
        }

        return $posts;
    }

    /**
     * @return array<string, EducationPost>
     */
    private function seedProtected(): array
    {
        $unrelated = ProductCategory::create(['name' => 'Unrelated', 'slug' => 'unrelated-peptide', 'is_active' => true]);
        $blend = ProductCategory::create(['name' => 'CJC-1295 / Ipamorelin', 'slug' => 'CJC-1295-Ipamorelin', 'is_active' => true]);
        $bpc = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'bpc-157', 'is_active' => true]);
        $ghrp = ProductCategory::create(['name' => 'GHRP-6', 'slug' => 'ghrp-6', 'is_active' => true]);

        return [
            'unrelated' => $this->seedPost($unrelated, 'unrelated-peptide', ['faqs' => [['question' => 'Protected?', 'answer' => 'Yes.']]]),
            'blend' => $this->seedPost($blend, 'cjc-blend', ['faqs' => [['question' => 'Blend?', 'answer' => 'Protected blend.']]]),
            'bpc-157' => $this->seedPost($bpc, 'bpc-157-protected', ['faqs' => [['question' => 'BPC?', 'answer' => 'Protected BPC.']]]),
            'ghrp-6' => $this->seedPost($ghrp, 'ghrp-6', ['faqs' => [['question' => 'GHRP?', 'answer' => 'Protected GHRP.']]]),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedPost(?ProductCategory $category, string $slug, array $overrides = []): EducationPost
    {
        return EducationPost::create(array_merge([
            'title' => $slug,
            'slug' => $slug,
            'product_category_id' => $category?->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'Overview '.$slug,
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $slug): array
    {
        $path = database_path('seeders/data/encyclopedia-field-updates/2026-10-22d/'.$slug.'.json');
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @param  list<mixed>  $stored
     * @return list<array{question: string, answer: string}>
     */
    private function reduce(array $stored): array
    {
        $reduced = [];
        foreach ($stored as $item) {
            if (! is_array($item)) {
                continue;
            }
            $question = trim((string) ($item['question'] ?? $item['q'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? $item['a'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            $reduced[] = [
                'question' => $question,
                'answer' => strip_tags($answer),
            ];
        }

        return $reduced;
    }

    private function wipe(): void
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
    }

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
        return $this->warningLine($warnings, $slug, $needle) !== null;
    }

    private function warningLine(\ArrayObject $warnings, string $slug, string $needle): ?string
    {
        foreach ($warnings as $warning) {
            if (is_string($warning) && str_contains($warning, $slug) && str_contains($warning, $needle)) {
                return $warning;
            }
        }

        return null;
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

    private function logContains(EncyclopediaFaqAuditBatch1Seeder $seeder, string $left, ?string $right = null): bool
    {
        foreach ($seeder->log as $line) {
            if (! str_contains($line, $left)) {
                continue;
            }
            if ($right === null || str_contains($line, $right)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function faqPage(string $html): ?array
    {
        preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches);
        foreach ($matches[1] as $json) {
            $data = json_decode(html_entity_decode($json), true);
            if (is_array($data) && ($data['@type'] ?? null) === 'FAQPage') {
                return $data;
            }
        }

        return null;
    }
}
