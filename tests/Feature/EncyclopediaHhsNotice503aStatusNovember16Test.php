<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\EncyclopediaHealthCanadaNovember16Seeder as Hc;
use Database\Seeders\EncyclopediaHhsNotice503aStatusNovember16Seeder as Hhs;
use Database\Seeders\EncyclopediaMotsC503aStatusNovember16Seeder as Motsc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 2026_11_16_240000: the HHS 503A notice paragraph on BPC-157, TB-500,
 * epitalon and KPV moves from "scheduled for publication" to published /
 * comment period closed, with the wording of 2026_11_16_220000 (MOTS-c).
 * Live values are re-encoded in production MySQL key order (shorter keys
 * first) because sqlite keeps insertion order.
 */
class EncyclopediaHhsNotice503aStatusNovember16Test extends TestCase
{
    use RefreshDatabase;

    private const SHA = '3092f1ead8b7bfd130651331c36633391430af172c5212258a3d66f0cde13507';

    private const M = '2026_11_16_240000';

    private const NEW_LEAD = 'Draft interim policy published for comment (Federal Register, 30 October 2026).';

    private const NAMES = ['bpc-157' => 'BPC-157', 'tb-500' => 'TB-500', 'epitalon' => 'Epitalon', 'kpv' => 'KPV'];

    private const KPV_CAS = '67727-97-3';

    /** Dated compounding heading per entry: [old, new]. */
    private const HEADINGS = [
        'bpc-157' => ['U.S. regulatory status — compounding policy (as of 29 October 2026)', 'U.S. regulatory status — compounding policy (FDA status as of 29 October 2026)'],
        'tb-500' => ['U.S. compounding policy (as of 29 October 2026)', 'U.S. compounding policy (FDA status as of 29 October 2026)'],
        'epitalon' => ['FDA / compounding (as of 29 October 2026)', 'FDA / compounding (FDA status as of 29 October 2026)'],
        'kpv' => ['FDA / compounding (as of 29 October 2026)', 'FDA / compounding (FDA status as of 29 October 2026)'],
    ];

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
        $this->assertCount(1, array_filter($files, fn ($f) => str_starts_with($f, '2026_11_16_240000')));
        $this->assertContains('2026_11_16_240000_encyclopedia_hhs_notice_503a_comment_period_closed.php', $files);
        $this->assertSame(self::SHA, Hhs::PAYLOAD_SHA256);
        $this->assertSame(self::SHA, hash_file('sha256', database_path(Hhs::PAYLOAD_PATH)));
        $this->assertSame(self::M, Hhs::MIGRATION);

        $posts = $this->payload()['posts'];
        $this->assertSame(Hhs::SLUGS, array_keys($posts));
        $this->assertSame(['bpc-157', 'tb-500', 'epitalon', 'kpv'], Hhs::SLUGS);
        foreach ($posts as $slug => $spec) {
            $this->assertSame('regulatory_subsections', $spec['field'], $slug);
            $this->assertSame(
                $slug === 'bpc-157' ? ['live-2026-11-16', 'after-2026_11_16_210000'] : ['live-2026-11-16'],
                array_column($spec['variants'], 'name'),
                $slug
            );
        }
    }

    public function test_only_the_paragraph_and_the_dated_heading_change_with_the_220000_wording(): void
    {
        $motscLead = $this->motscLead();
        foreach ($this->payload()['posts'] as $slug => $spec) {
            foreach ($spec['variants'] as $variant) {
                $label = $slug.' '.$variant['name'];
                $old = $this->flatten($variant['old_value']);
                $new = $this->flatten($variant['target_value']);
                $this->assertSame(array_keys($old), array_keys($new), $label);
                $changed = array_keys(array_diff_assoc($new, $old));

                // F1 convention: one heading per entry, only its parenthetical changes.
                $titles = array_values(array_filter($changed, fn ($k) => str_ends_with($k, '.title')));
                $this->assertCount(1, $titles, $label);
                $this->assertSame(self::HEADINGS[$slug][0], $old[$titles[0]], $label);
                $this->assertSame(self::HEADINGS[$slug][1], $new[$titles[0]], $label);
                $this->assertSame(
                    str_replace('(as of 29 October 2026)', '(FDA status as of 29 October 2026)', $old[$titles[0]]),
                    $new[$titles[0]],
                    $label
                );

                $paras = array_values(array_diff($changed, $titles));
                $this->assertCount(1, $paras, $label);
                $text = $new[$paras[0]];
                $this->assertStringStartsWith(self::NEW_LEAD, $text, $label);
                $this->assertStringStartsWith($motscLead.self::NAMES[$slug].' is one of six peptides in the policy’s Appendix A.', $text, $label);
                $this->assertStringContainsString('scheduled for publication on 30 October 2026', $old[$paras[0]], $label);
                $this->assertSame(
                    substr($old[$paras[0]], strpos($old[$paras[0]], self::NAMES[$slug].' is one of six')),
                    substr($text, strpos($text, self::NAMES[$slug].' is one of six')),
                    $label
                );

                $encoded = json_encode($variant['target_value'], JSON_UNESCAPED_UNICODE);
                foreach (['scheduled for publication', 'is scheduled', 'HHS/FDA', 'best-performing', 'superior'] as $banned) {
                    $this->assertStringNotContainsString($banned, $encoded, $label.': '.$banned);
                }
            }
        }
    }

    public function test_repo_shell_files_carry_the_same_wording(): void
    {
        $lead = $this->motscLead();
        foreach (['Semax' => 'Semax', 'kpv' => 'KPV', 'epitalon' => 'Epitalon'] as $shell => $name) {
            $body = (string) file_get_contents(database_path("seeders/data/encyclopedia-shells/{$shell}/body.md"));
            $this->assertStringNotContainsString('scheduled for publication', $body, $shell);
            $this->assertSame(1, substr_count($body, 'value: '.$lead.$name.' is one of six peptides in the policy’s Appendix A.'), $shell);
        }
        $semax = (string) file_get_contents(database_path('seeders/data/encyclopedia-shells/Semax/body.md'));
        $this->assertStringContainsString('title: U.S. compounding policy (FDA status as of 29 October 2026)', $semax);
        $this->assertStringNotContainsString('title: U.S. compounding policy (as of 29 October 2026)', $semax);
        foreach (['kpv', 'epitalon'] as $shell) {
            $body = (string) file_get_contents(database_path("seeders/data/encyclopedia-shells/{$shell}/body.md"));
            $this->assertStringContainsString('title: FDA / compounding (FDA status as of 29 October 2026)', $body, $shell);
            $this->assertStringNotContainsString('title: FDA / compounding (as of 29 October 2026)', $body, $shell);
        }
        $this->assertStringContainsString(self::KPV_CAS, (string) file_get_contents(app_path('Support/EncyclopediaShellParser.php')));
    }

    public function test_bpc157_variants_tie_to_the_210000_payload(): void
    {
        $hc = json_decode((string) file_get_contents(database_path(Hc::PAYLOAD_PATH)), true)['posts']['bpc-157'];
        [$live, $after] = $this->payload()['posts']['bpc-157']['variants'];
        $this->assertSame($hc['columns']['regulatory_subsections']['old_values'][0], $live['old_value']);
        $this->assertSame($hc['columns']['regulatory_subsections']['target_value'], $after['old_value']);
        $this->assertSame($hc['columns']['references']['old_values'][0], $live['drift']['references']['expected'][0]);
        $this->assertSame($hc['columns']['references']['target_value'], $after['drift']['references']['expected'][0]);
    }

    public function test_live_values_are_updated_then_a_second_run_writes_nothing(): void
    {
        $ids = $this->seedAll();
        $before = $this->rows($ids);

        $seeder = new Hhs;
        $seeder->run();

        $this->assertSame(Hhs::SLUGS, $seeder->transactions);
        foreach (Hhs::SLUGS as $slug) {
            $this->assertSame('updated', $seeder->results[$slug.'.regulatory_subsections'], $slug);
            $this->assertTrue($this->hasLog('info', self::M.' '.$slug.'.regulatory_subsections: updated'), $slug);
            $post = EducationPost::findOrFail($ids[$slug]);
            $this->assertSame($this->variant($slug, 'live-2026-11-16')['target_value'], $post->regulatory_subsections, $slug);
        }

        // Only regulatory_subsections (and updated_at) moved; KPV's CAS is untouched.
        $after = $this->rows($ids);
        foreach (Hhs::SLUGS as $slug) {
            $a = $before[$slug];
            $b = $after[$slug];
            unset($a['regulatory_subsections'], $b['regulatory_subsections']);
            $this->assertSame($a, $b, $slug);
        }
        $this->assertSame(self::KPV_CAS, EducationPost::findOrFail($ids['kpv'])->cas_registry_number);

        $frozen = $this->rows($ids);
        $again = new Hhs;
        $again->run();
        $this->assertSame([], $again->transactions);
        foreach (Hhs::SLUGS as $slug) {
            $this->assertSame('already current', $again->results[$slug.'.regulatory_subsections'], $slug);
        }
        $this->assertSame($frozen, $this->rows($ids));
    }

    public function test_rendered_pages_show_the_new_paragraph(): void
    {
        $this->seedAll();
        (new Hhs)->run();

        foreach (['BPC-157', 'TB-500', 'epitalon', 'kpv'] as $publicSlug) {
            $page = $this->get('/encyclopedia/'.$publicSlug)->assertOk()->assertDontSee('scheduled for publication', false);
            $page->assertInertia(fn ($p) => $p->where('regulatorySubsections', fn ($subs) => str_contains(json_encode($subs, JSON_UNESCAPED_UNICODE), self::NEW_LEAD)));
        }
        $this->get('/encyclopedia/kpv')->assertInertia(fn ($p) => $p->where('molecularInfo.casNumber', self::KPV_CAS));
    }

    public function test_bpc157_after_210000_is_updated_to_its_own_target(): void
    {
        $ids = $this->seedAll(['bpc-157' => 'after-2026_11_16_210000']);
        $seeder = new Hhs;
        $seeder->run();

        $this->assertSame('updated', $seeder->results['bpc-157.regulatory_subsections']);
        $stored = EducationPost::findOrFail($ids['bpc-157'])->regulatory_subsections;
        $this->assertSame($this->variant('bpc-157', 'after-2026_11_16_210000')['target_value'], $stored);
        $this->assertContains('Health Canada advisories (November 2026)', array_column($stored, 'title'));
    }

    public function test_210000_then_240000_end_to_end_for_bpc157(): void
    {
        $ids = $this->seedAll();
        (new Hc)->run();
        $this->assertSame($this->variant('bpc-157', 'after-2026_11_16_210000')['old_value'], EducationPost::findOrFail($ids['bpc-157'])->regulatory_subsections);

        $seeder = new Hhs;
        $seeder->run();
        foreach (Hhs::SLUGS as $slug) {
            $this->assertSame('updated', $seeder->results[$slug.'.regulatory_subsections'], $slug);
        }
        $this->assertSame($this->variant('bpc-157', 'after-2026_11_16_210000')['target_value'], EducationPost::findOrFail($ids['bpc-157'])->regulatory_subsections);
    }

    public function test_220000_and_240000_do_not_touch_each_others_entries(): void
    {
        $ids = $this->seedAll();
        $before = $this->rows($ids);
        $motsc = new Motsc;
        $motsc->run();
        $this->assertStringStartsWith('post lookup ', $motsc->results['mots-c.regulatory_subsections']);
        $this->assertSame($before, $this->rows($ids));
    }

    public function test_an_admin_edit_on_one_entry_skips_only_that_entry(): void
    {
        $ids = $this->seedAll();
        $post = EducationPost::findOrFail($ids['epitalon']);
        $edited = $post->regulatory_subsections;
        $edited[0]['entries'][0]['value'] .= ' Admin edit.';
        $post->regulatory_subsections = $edited;
        $post->saveQuietly();
        $frozen = $post->fresh()->getAttributes();

        $seeder = new Hhs;
        $seeder->run();

        $this->assertSame('guard mismatch', $seeder->results['epitalon.regulatory_subsections']);
        $this->assertSame($frozen, $post->fresh()->getAttributes());
        $this->assertContains(self::M.' epitalon.regulatory_subsections: guard mismatch — nothing written', $this->warnings());
        $this->assertSame(['bpc-157', 'tb-500', 'kpv'], $seeder->transactions);
    }

    public function test_sibling_column_drift_skips_the_entry(): void
    {
        foreach (['references', 'regulatory_important_note', 'human_use_intro', 'human_use_subsections'] as $column) {
            $ids = $this->seedAll();
            $post = EducationPost::findOrFail($ids['kpv']);
            $post->setAttribute($column, in_array($column, ['references', 'human_use_subsections'], true) ? [['title' => 'Admin edit']] : 'Admin edit.');
            $post->saveQuietly();
            $frozen = $post->fresh()->getAttributes();

            $seeder = new Hhs;
            $seeder->run();

            $this->assertSame('column drift: '.$column, $seeder->results['kpv.regulatory_subsections'], $column);
            $this->assertSame($frozen, $post->fresh()->getAttributes(), $column);
            $this->assertSame('updated', $seeder->results['tb-500.regulatory_subsections'], $column);
        }
    }

    public function test_bpc157_mixed_state_is_not_written(): void
    {
        $ids = $this->seedAll();
        $post = EducationPost::findOrFail($ids['bpc-157']);
        $post->references = $this->variant('bpc-157', 'after-2026_11_16_210000')['drift']['references']['expected'][0];
        $post->saveQuietly();

        $seeder = new Hhs;
        $seeder->run();
        $this->assertSame('column drift: references', $seeder->results['bpc-157.regulatory_subsections']);
    }

    public function test_missing_entries_are_warned_and_others_still_run(): void
    {
        $ids = $this->seedAll();
        EducationPost::whereKey($ids['tb-500'])->delete();
        ProductCategory::where('slug', 'TB-500')->delete();

        $seeder = new Hhs;
        $seeder->run();
        $this->assertStringStartsWith('post lookup ', $seeder->results['tb-500.regulatory_subsections']);
        $this->assertSame(['bpc-157', 'epitalon', 'kpv'], $seeder->transactions);
    }

    public function test_key_order_does_not_decide_the_guard(): void
    {
        $this->seedAll(reverseKeys: true);
        $seeder = new Hhs;
        $seeder->run();
        foreach (Hhs::SLUGS as $slug) {
            $this->assertSame('updated', $seeder->results[$slug.'.regulatory_subsections'], $slug);
        }
    }

    /**
     * @param  array<string, string>  $variantFor
     * @return array<string, int>
     */
    private function seedAll(array $variantFor = [], bool $reverseKeys = false): array
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $ids = [];
        foreach ($this->payload()['posts'] as $slug => $spec) {
            $variant = $this->variant($slug, $variantFor[$slug] ?? 'live-2026-11-16');
            $category = ProductCategory::create(['name' => self::NAMES[$slug], 'slug' => $spec['category_slug'], 'is_active' => true, 'description' => 'CATEGORY-'.$slug]);
            $attrs = ['title' => self::NAMES[$slug], 'slug' => $slug.'-linked', 'product_category_id' => $category->id,
                'status' => 'published', 'show_in_encyclopedia' => true, 'overview' => 'KEEP-overview-'.$slug,
                'cas_registry_number' => $slug === 'kpv' ? self::KPV_CAS : null,
                'regulatory_subsections' => self::reorder($variant['old_value'], $reverseKeys)];
            foreach ($variant['drift'] as $column => $col) {
                $attrs[$column] = self::reorder($col['expected'][0], $reverseKeys);
            }
            $ids[$slug] = EducationPost::create($attrs)->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $ids
     * @return array<string, array<string, mixed>>
     */
    private function rows(array $ids): array
    {
        $out = [];
        foreach ($ids as $slug => $id) {
            $a = EducationPost::findOrFail($id)->getAttributes();
            unset($a['updated_at']);
            $out[$slug] = $a;
        }

        return $out;
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

    private function motscLead(): string
    {
        $raw = json_decode((string) file_get_contents(database_path(Motsc::PAYLOAD_PATH)), true);
        foreach ($raw['posts']['mots-c']['variants'][0]['target_value'] as $sub) {
            foreach ($sub['entries'] as $entry) {
                if (str_starts_with($entry['value'], self::NEW_LEAD)) {
                    return explode('MOTS-c is one of six peptides', $entry['value'])[0];
                }
            }
        }
        $this->fail('no MOTS-c lead');
    }

    /** @return array<string, mixed> */
    private function variant(string $slug, string $name): array
    {
        foreach ($this->payload()['posts'][$slug]['variants'] as $variant) {
            if ($variant['name'] === $name) {
                return $variant;
            }
        }
        $this->fail('no variant '.$slug.' '.$name);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return json_decode((string) file_get_contents(database_path(Hhs::PAYLOAD_PATH)), true);
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
