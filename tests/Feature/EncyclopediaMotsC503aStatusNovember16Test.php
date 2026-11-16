<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaHealthCanadaNovember16Seeder as Hc;
use Database\Seeders\EncyclopediaMotsC503aStatusNovember16Seeder as Motsc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 2026_11_16_220000: the MOTS-c 503A notice paragraph moves from
 * "scheduled for publication" to published / comment period closed.
 * Live values are re-encoded in production MySQL key order (shorter keys
 * first) because sqlite keeps insertion order.
 */
class EncyclopediaMotsC503aStatusNovember16Test extends TestCase
{
    use RefreshDatabase;

    private const SHA = '161980f2b3204a08730529085919190c482c0fb4d7a5252074ea4b56d517102d';

    private const M = '2026_11_16_220000';

    private const NEW_LEAD = 'Draft interim policy published for comment (Federal Register, 30 October 2026).';

    /** @var list<array{level: string, message: string}> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e): void {
            $this->logged[] = ['level' => $e->level, 'message' => $e->message];
        });
    }

    public function test_slot_payload_sha_and_shape_are_pinned(): void
    {
        $files = array_map('basename', glob(database_path('migrations/2026_11_16_*.php')) ?: []);
        $this->assertCount(1, array_filter($files, fn ($f) => str_starts_with($f, '2026_11_16_220000')));
        $this->assertContains('2026_11_16_220000_encyclopedia_mots_c_503a_comment_period_closed.php', $files);
        $this->assertSame(self::SHA, Motsc::PAYLOAD_SHA256);
        $this->assertSame(self::SHA, hash_file('sha256', database_path(Motsc::PAYLOAD_PATH)));
        $this->assertSame(self::M, Motsc::MIGRATION);

        $posts = $this->payload()['posts'];
        $this->assertSame(['mots-c'], array_keys($posts));
        $this->assertSame('regulatory_subsections', $posts['mots-c']['field']);
        $this->assertSame(['live-2026-11-16', 'after-2026_11_16_210000'], array_column($posts['mots-c']['variants'], 'name'));
    }

    public function test_variant_two_starts_from_the_210000_target_and_only_the_paragraph_changes(): void
    {
        [$live, $after] = $this->payload()['posts']['mots-c']['variants'];
        $hc = json_decode((string) file_get_contents(database_path(Hc::PAYLOAD_PATH)), true)['posts']['mots-c'];

        $this->assertSame($hc['columns']['regulatory_subsections']['old_values'][0], $live['old_value']);
        $this->assertSame($hc['columns']['regulatory_subsections']['target_value'], $after['old_value']);
        $this->assertSame($hc['columns']['references']['old_values'][0], $live['drift']['references']['expected'][0]);
        $this->assertSame($hc['columns']['references']['target_value'], $after['drift']['references']['expected'][0]);

        foreach ([$live, $after] as $variant) {
            $old = $this->flatten($variant['old_value']);
            $new = $this->flatten($variant['target_value']);
            $this->assertCount(count($old), $new);
            $changed = array_keys(array_diff_assoc($new, $old));
            $this->assertCount(1, $changed, $variant['name']);
            $text = $new[$changed[0]];
            $this->assertStringStartsWith(self::NEW_LEAD, $text);
            $this->assertStringContainsString('FR Doc. 2026-22192; 91 FR 68526–68527; Docket No. HHS-OASH-2026-0496', $text);
            $this->assertStringContainsString('The HHS Office of the Secretary published', $text);
            $this->assertStringContainsString('closed at 11:59 pm ET on 13 November 2026', $text);
            $this->assertStringContainsString('As of 16 November 2026, no final version of the policy has been published', $text);
            $this->assertStringContainsString('MOTS-c is one of six peptides in the policy’s Appendix A.', $text);
            $encoded = json_encode($variant['target_value'], JSON_UNESCAPED_UNICODE);
            foreach (['scheduled for publication', 'is scheduled', 'HHS/FDA', 'best', 'superior', ' mg'] as $banned) {
                $this->assertStringNotContainsString($banned, $encoded, $banned);
            }
            $this->assertStringContainsString('scheduled for publication on 30 October 2026', $old[$changed[0]]);
        }
    }

    public function test_live_value_is_updated_then_a_second_run_writes_nothing(): void
    {
        $post = $this->seedVariant('live-2026-11-16');
        $seeder = new Motsc;
        $seeder->run();

        $this->assertSame('updated', $seeder->results['mots-c.regulatory_subsections']);
        $this->assertSame(['mots-c'], $seeder->transactions);
        $this->assertSame($this->variant('live-2026-11-16')['target_value'], $post->fresh()->regulatory_subsections);
        $this->assertTrue($this->hasLog('info', self::M.' mots-c.regulatory_subsections: updated'));

        $before = $post->fresh()->getAttributes();
        $again = new Motsc;
        $again->run();
        $this->assertSame('already current', $again->results['mots-c.regulatory_subsections']);
        $this->assertSame([], $again->transactions);
        $this->assertSame($before, $post->fresh()->getAttributes());

        $this->get('/encyclopedia/mots-c')->assertOk()
            ->assertDontSee('scheduled for publication', false)
            ->assertInertia(fn ($page) => $page->where('regulatorySubsections', fn ($subs) => str_contains(json_encode($subs, JSON_UNESCAPED_UNICODE), self::NEW_LEAD)));
    }

    public function test_value_after_210000_is_updated_to_its_own_target(): void
    {
        $post = $this->seedVariant('after-2026_11_16_210000');
        $seeder = new Motsc;
        $seeder->run();

        $this->assertSame('updated', $seeder->results['mots-c.regulatory_subsections']);
        $stored = $post->fresh()->regulatory_subsections;
        $this->assertSame($this->variant('after-2026_11_16_210000')['target_value'], $stored);
        $this->assertContains('Health Canada advisories (November 2026)', array_column($stored, 'title'));
    }

    public function test_210000_then_220000_end_to_end(): void
    {
        $post = $this->seedVariant('live-2026-11-16');
        // Other 210000 entries are missing here; it warns for them and still runs mots-c.
        (new Hc)->run();
        $this->assertSame($this->variant('after-2026_11_16_210000')['old_value'], $post->fresh()->regulatory_subsections);

        $seeder = new Motsc;
        $seeder->run();
        $this->assertSame('updated', $seeder->results['mots-c.regulatory_subsections']);
        $this->assertSame($this->variant('after-2026_11_16_210000')['target_value'], $post->fresh()->regulatory_subsections);
    }

    public function test_drifted_column_is_not_written(): void
    {
        $post = $this->seedVariant('live-2026-11-16');
        $edited = $post->regulatory_subsections;
        $edited[0]['entries'][0]['value'] .= ' Admin edit.';
        $post->regulatory_subsections = $edited;
        $post->saveQuietly();
        $before = $post->fresh()->getAttributes();

        $seeder = new Motsc;
        $seeder->run();

        $this->assertSame('guard mismatch', $seeder->results['mots-c.regulatory_subsections']);
        $this->assertSame([], $seeder->transactions);
        $this->assertSame($before, $post->fresh()->getAttributes());
        $this->assertContains(self::M.' mots-c.regulatory_subsections: guard mismatch — nothing written', $this->warnings());
    }

    public function test_sibling_column_drift_skips_the_write(): void
    {
        foreach (['references', 'regulatory_important_note', 'human_use_intro'] as $column) {
            EducationPost::query()->delete();
            ProductCategory::query()->delete();
            $post = $this->seedVariant('live-2026-11-16');
            $post->setAttribute($column, $column === 'references' ? [] : 'Admin edit.');
            $post->saveQuietly();
            $before = $post->fresh()->getAttributes();

            $seeder = new Motsc;
            $seeder->run();

            $this->assertSame('column drift: '.$column, $seeder->results['mots-c.regulatory_subsections'], $column);
            $this->assertSame($before, $post->fresh()->getAttributes(), $column);
        }
    }

    public function test_mixed_state_is_not_written(): void
    {
        // Live regulatory_subsections but references already at the 210000 target: not a state either variant expects.
        $post = $this->seedVariant('live-2026-11-16');
        $post->references = $this->variant('after-2026_11_16_210000')['drift']['references']['expected'][0];
        $post->saveQuietly();

        $seeder = new Motsc;
        $seeder->run();
        $this->assertSame('column drift: references', $seeder->results['mots-c.regulatory_subsections']);
    }

    public function test_missing_post_is_warned(): void
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $seeder = new Motsc;
        $seeder->run();
        $this->assertStringStartsWith('post lookup ', $seeder->results['mots-c.regulatory_subsections']);
        $this->assertNotEmpty($this->warnings());
    }

    public function test_key_order_does_not_decide_the_guard(): void
    {
        $post = $this->seedVariant('live-2026-11-16', reverseKeys: true);
        $seeder = new Motsc;
        $seeder->run();
        $this->assertSame('updated', $seeder->results['mots-c.regulatory_subsections']);
    }

    private function seedVariant(string $variantName, bool $reverseKeys = false): EducationPost
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $variant = $this->variant($variantName);
        $category = ProductCategory::create(['name' => 'MOTS-c', 'slug' => 'mots-c', 'is_active' => true, 'description' => 'CATEGORY']);
        $attrs = ['title' => 'MOTS-c', 'slug' => 'mots-c-linked', 'product_category_id' => $category->id,
            'status' => 'published', 'show_in_encyclopedia' => true, 'overview' => 'KEEP-overview',
            'regulatory_subsections' => self::reorder($variant['old_value'], $reverseKeys)];
        foreach ($variant['drift'] as $column => $spec) {
            $attrs[$column] = self::reorder($spec['expected'][0], $reverseKeys);
        }

        return EducationPost::create($attrs);
    }

    private static function reorder(mixed $value, bool $reverse): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = self::reorder($v, $reverse);
        }
        if (! array_is_list($out)) {
            $reverse
                ? krsort($out, SORT_STRING)
                : uksort($out, fn ($a, $b) => [strlen((string) $a), (string) $a] <=> [strlen((string) $b), (string) $b]);
        }

        return $out;
    }

    /** @return array<string, string> */
    private function flatten(array $subs): array
    {
        $out = [];
        foreach ($subs as $i => $sub) {
            $out["$i.title"] = (string) ($sub['title'] ?? '');
            foreach ($sub['entries'] ?? [] as $j => $entry) {
                $out["$i.$j.".($entry['type'] ?? '')] = (string) ($entry['value'] ?? '');
            }
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function variant(string $name): array
    {
        foreach ($this->payload()['posts']['mots-c']['variants'] as $variant) {
            if ($variant['name'] === $name) {
                return $variant;
            }
        }
        $this->fail('no variant '.$name);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return json_decode((string) file_get_contents(database_path(Motsc::PAYLOAD_PATH)), true);
    }

    private function hasLog(string $level, string $message): bool
    {
        foreach ($this->logged as $line) {
            if ($line['level'] === $level && $line['message'] === $message) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function warnings(): array
    {
        return array_values(array_map(fn ($l) => $l['message'], array_filter($this->logged, fn ($l) => $l['level'] === 'warning')));
    }
}
