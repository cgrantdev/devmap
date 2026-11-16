<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\GuardedFieldWriter;
use Database\Seeders\EncyclopediaHealthCanadaNovember16Seeder as Hc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * 2026_11_16_210000: Health Canada advisories on 8 encyclopedia entries.
 * Eloralintide was dropped (16 Nov 2026 QA): human_use_subsections is not rendered
 * and the encyclopedia page carries vendor prices.
 * Live values are re-encoded in production MySQL key order (shorter keys
 * first) because sqlite keeps insertion order.
 */
class EncyclopediaHealthCanadaNovember16Test extends TestCase
{
    use RefreshDatabase;

    private const SHA = 'cb340c91c080e3ac5a342fac904fe890c5de918146d3102ce6630d9cf2a2690a';

    private const M = '2026_11_16_210000';

    private const TITLE = 'Health Canada advisories (November 2026)';

    private const RA_82930_URL = 'recalls-rappels.canada.ca/en/alert-recall/health-canada-warns-about-contaminated';

    private const RA_82944_URL = 'https://recalls-rappels.canada.ca/en/alert-recall/health-canada-warns-about-unauthorized-peptides-including-contaminated-injectable';

    /** Entries named in each advisory. */
    private const BOTH = ['hcg', 'mots-c', 'retatrutide', 'tesamorelin'];

    private const ONLY_82930 = ['nad', 'tirzepatide'];

    private const ONLY_82944 = ['bpc-157', 'selank'];

    /** Empty live shells dropped from this PR (16 Nov 2026); their lines are parked outside the repo. */
    private const DROPPED = ['bpc-157-tb-500', 'klow-blend-ghk-cu-bpc-157-tb-500-kpv', 'semax', 'melanotan-i'];

    /** Names and venues that must never be published (enforcement targets, seller sites, product labels). */
    private const BANNED = [
        'Shanghai', 'ERP Peptide', 'ERP', 'Sage & Summit', 'Sage &amp; Summit', 'Sage and Summit', 'Preston Street',
        'truenorthbiolabs', 'bcaxiompeptide', 'True North', 'Axiom', 'PURE BPC-157', 'Healing Blend', 'KLOW (',
        'Peptide Partners', 'best-performing', 'HHS/FDA',
    ];

    /** @var list<array{level: string, message: string}> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e): void {
            $this->logged[] = ['level' => $e->level, 'message' => $e->message];
        });
    }

    public function test_migration_slot_payload_sha_and_shape_are_pinned(): void
    {
        $files = array_map('basename', glob(database_path('migrations/2026_11_16_*.php')) ?: []);
        $this->assertContains('2026_11_16_210000_encyclopedia_health_canada_and_eloralintide_november_16.php', $files);
        $this->assertCount(1, array_filter($files, fn ($f) => str_starts_with($f, '2026_11_16_210000')));
        $this->assertSame(self::SHA, Hc::PAYLOAD_SHA256);
        $this->assertSame(self::SHA, hash_file('sha256', database_path(Hc::PAYLOAD_PATH)));
        $this->assertSame(self::M, Hc::MIGRATION);

        $payload = $this->payload();
        $this->assertSame(array_keys(Hc::COLUMNS), array_keys($payload['posts']));
        foreach (Hc::COLUMNS as $slug => $fields) {
            $this->assertSame($fields, array_keys($payload['posts'][$slug]['columns']), $slug);
            $this->assertNotEmpty($payload['posts'][$slug]['drift'], $slug);
            $this->assertSame([], array_intersect($fields, array_keys($payload['posts'][$slug]['drift'])), $slug);
        }
        $this->assertArrayNotHasKey('human_use_subsections', $payload['posts']['retatrutide']['columns']);
        $this->assertCount(8, Hc::COLUMNS);
        $this->assertArrayNotHasKey('eloralintide', Hc::COLUMNS);
        foreach (self::DROPPED as $slug) {
            $this->assertArrayNotHasKey($slug, Hc::COLUMNS, $slug);
            $this->assertArrayNotHasKey($slug, $payload['posts'], $slug);
        }
    }

    public function test_health_canada_lines_are_dated_attributed_and_generic(): void
    {
        foreach (array_merge(self::BOTH, self::ONLY_82930, self::ONLY_82944) as $slug) {
            $line = $this->hcLine($slug);
            $in82930 = in_array($slug, array_merge(self::BOTH, self::ONLY_82930), true);
            $in82944 = in_array($slug, array_merge(self::BOTH, self::ONLY_82944), true);

            $this->assertSame($in82930, str_contains($line, 'RA-82930 (6 November 2026)'), $slug);
            $this->assertSame($in82944, str_contains($line, 'RA-82944 (12 November 2026)'), $slug);
            $this->assertStringContainsString('Health Canada public advisor', $line, $slug);
            $this->assertMatchesRegularExpression('/(neither says which named product|does not say which listed product|without saying which product)/', $line, $slug);
            if ($in82930) {
                $this->assertStringContainsString('an overseas supplier', $line, $slug);
            }
            if ($in82944) {
                $this->assertStringContainsString('an Ottawa, Ontario retailer', $line, $slug);
            }
            // "Not legal or exempt" is RA-82944's statement only.
            $this->assertSame($in82944, str_contains($line, 'legal or exempt from regulatory requirements'), $slug);
            if (str_contains($line, 'legal or exempt')) {
                $this->assertMatchesRegularExpression('/(RA-82944 also states|It also states)/', $line, $slug);
            }
            $this->assertStringNotContainsString('“', $line, $slug);
            $this->assertDoesNotMatchRegularExpression('/\d\s?(mg|mcg|µg)\b/i', $line, $slug);
            $this->assertDoesNotMatchRegularExpression('/\b(superior|better|best|more effective)\b/i', $line, $slug);
            foreach (self::BANNED as $banned) {
                $this->assertStringNotContainsString($banned, $line, $slug.' / '.$banned);
            }
        }
        $this->assertStringContainsString('not authorized tirzepatide medicines', $this->hcLine('tirzepatide'));
        $this->assertStringContainsString('Retatrutide itself is investigational.', $this->hcLine('retatrutide'));
        $this->assertStringContainsString('both among unauthorized injectable peptides and as an unauthorized oral capsule product', $this->hcLine('bpc-157'));
    }

    public function test_ra_82930_is_never_linked_and_ra_82944_is(): void
    {
        $raw = (string) file_get_contents(database_path(Hc::PAYLOAD_PATH));
        $this->assertStringNotContainsString(self::RA_82930_URL, $raw);
        $this->assertStringNotContainsString('truenorthbiolabs', $raw);
        $this->assertStringNotContainsString('bcaxiompeptide', $raw);

        foreach (array_merge(self::BOTH, self::ONLY_82930, self::ONLY_82944) as $slug) {
            $refs = $this->target($slug, 'references');
            $a = $this->refByTitle($refs, 'Health Canada warns about contaminated unauthorized injectable peptides and non-peptide drugs');
            $b = $this->refByTitle($refs, 'Health Canada warns about unauthorized peptides, including contaminated injectable peptides …');
            $in82930 = ! in_array($slug, self::ONLY_82944, true);
            $in82944 = ! in_array($slug, self::ONLY_82930, true);
            $this->assertSame($in82930, $a !== null, $slug);
            $this->assertSame($in82944, $b !== null, $slug);
            if ($a !== null) {
                $this->assertSame([], $a['links'], $slug);
                $this->assertStringContainsString('(not linked)', $a['citation'], $slug);
            }
            if ($b !== null) {
                $this->assertSame([['label' => 'Health Canada', 'url' => self::RA_82944_URL]], $b['links'], $slug);
            }
            $this->assertCount(1, array_filter($this->target($slug, 'regulatory_subsections'), fn ($s) => ($s['title'] ?? null) === self::TITLE), $slug);
        }
    }

    public function test_live_values_in_prod_key_order_are_updated_then_a_second_run_writes_nothing(): void
    {
        $ids = $this->seedLive();
        $seeder = new Hc;
        $seeder->run();

        foreach (Hc::COLUMNS as $slug => $fields) {
            $post = EducationPost::findOrFail($ids[$slug]);
            foreach ($fields as $field) {
                $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results[$slug.'.'.$field], $slug.'.'.$field);
                $this->assertSame(GuardedFieldWriter::comparable($this->target($slug, $field)), GuardedFieldWriter::comparable($post->{$field}), $slug.'.'.$field);
                $this->assertTrue($this->hasLog('info', self::M.' '.$slug.'.'.$field.': updated'), $slug.'.'.$field);
            }
            $this->assertSame('KEEP-overview', $post->overview);
        }
        $this->assertSame(array_keys(Hc::COLUMNS), $seeder->transactions);
        $this->assertSame([], $this->warnings());

        $before = $this->snapshot();
        $this->logged = [];
        $again = new Hc;
        $again->run();
        foreach (Hc::COLUMNS as $slug => $fields) {
            foreach ($fields as $field) {
                $this->assertSame(GuardedFieldWriter::ALREADY, $again->results[$slug.'.'.$field], $slug.'.'.$field);
            }
        }
        $this->assertSame($before, $this->snapshot());
        $this->assertSame([], $this->warnings());
    }

    public function test_a_drifted_target_column_holds_the_whole_entry(): void
    {
        $ids = $this->seedLive();
        EducationPost::findOrFail($ids['nad'])->forceFill(['references' => [['title' => 'ADMIN', 'links' => []]]])->saveQuietly();
        $before = EducationPost::findOrFail($ids['nad'])->getAttributes();

        $seeder = new Hc;
        $seeder->run();

        $this->assertSame($before, EducationPost::findOrFail($ids['nad'])->getAttributes());
        $this->assertSame(Hc::GUARD_MISMATCH, $seeder->results['nad.references']);
        $this->assertSame(Hc::HELD, $seeder->results['nad.regulatory_subsections']);
        $this->assertTrue($this->hasLog('warning', self::M.' nad.references: guard mismatch — nothing written'));
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['tirzepatide.references']);
        $this->assertNotContains('nad', $seeder->transactions);
    }

    public function test_an_admin_edit_in_a_sibling_column_skips_the_entry(): void
    {
        $ids = $this->seedLive();
        EducationPost::findOrFail($ids['selank'])->forceFill(['regulatory_important_note' => 'ADMIN-NOTE'])->saveQuietly();
        EducationPost::findOrFail($ids['tirzepatide'])->forceFill(['human_use_subsections' => [['title' => 'ADMIN', 'entries' => []]]])->saveQuietly();
        $selank = EducationPost::findOrFail($ids['selank'])->getAttributes();
        $tirz = EducationPost::findOrFail($ids['tirzepatide'])->getAttributes();

        $seeder = new Hc;
        $seeder->run();

        $this->assertSame($selank, EducationPost::findOrFail($ids['selank'])->getAttributes());
        $this->assertSame($tirz, EducationPost::findOrFail($ids['tirzepatide'])->getAttributes());
        $this->assertSame(Hc::DRIFT_PREFIX.'regulatory_important_note', $seeder->results['selank.regulatory_subsections']);
        $this->assertSame(Hc::DRIFT_PREFIX.'human_use_subsections', $seeder->results['tirzepatide.regulatory_subsections']);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['bpc-157.regulatory_subsections']);
        $this->assertNotEmpty($this->warnings());
    }

    public function test_missing_category_is_warned_and_others_still_run(): void
    {
        $ids = $this->seedLive();
        EducationPost::findOrFail($ids['selank'])->delete();
        ProductCategory::whereRaw('LOWER(slug) = ?', ['selank'])->delete();

        $seeder = new Hc;
        $seeder->run();

        $this->assertStringStartsWith('post lookup ', $seeder->results['selank.regulatory_subsections']);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['bpc-157.references']);
    }

    // ----- helpers -----

    /** @return array<string, int> */
    private function seedLive(): array
    {
        EducationPost::query()->delete();
        ProductCategory::query()->delete();
        $ids = [];
        foreach ($this->payload()['posts'] as $slug => $spec) {
            $category = ProductCategory::create(['name' => $spec['category_slug'], 'slug' => $spec['category_slug'], 'is_active' => true, 'description' => 'CATEGORY-'.$slug]);
            $attrs = ['title' => $spec['category_slug'], 'slug' => $slug.'-linked', 'product_category_id' => $category->id,
                'status' => 'published', 'show_in_encyclopedia' => true, 'overview' => 'KEEP-overview'];
            foreach ($spec['columns'] as $field => $col) {
                $attrs[$field] = self::mysqlKeyOrder($col['old_values'][0]);
            }
            foreach ($spec['drift'] as $field => $col) {
                $attrs[$field] = self::mysqlKeyOrder($col['expected'][0]);
            }
            $ids[$slug] = EducationPost::create($attrs)->id;
        }

        return $ids;
    }

    private static function mysqlKeyOrder(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = self::mysqlKeyOrder($v);
        }
        if (! array_is_list($out)) {
            uksort($out, fn ($a, $b) => [strlen((string) $a), (string) $a] <=> [strlen((string) $b), (string) $b]);
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return json_decode((string) file_get_contents(database_path(Hc::PAYLOAD_PATH)), true);
    }

    private function target(string $slug, string $field): mixed
    {
        return $this->payload()['posts'][$slug]['columns'][$field]['target_value'];
    }

    private function hcLine(string $slug): string
    {
        foreach ($this->target($slug, 'regulatory_subsections') as $sub) {
            if (($sub['title'] ?? null) === self::TITLE) {
                return (string) $sub['entries'][0]['value'];
            }
        }
        $this->fail('No Health Canada subsection for '.$slug);
    }

    /** @param list<array<string, mixed>> $refs */
    private function refByTitle(array $refs, string $title): ?array
    {
        foreach ($refs as $ref) {
            if (($ref['title'] ?? null) === $title) {
                return $ref;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private function snapshot(): array
    {
        return EducationPost::orderBy('id')->get()->map(function ($p) {
            $a = $p->getAttributes();
            unset($a['updated_at']);

            return $a;
        })->all();
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
