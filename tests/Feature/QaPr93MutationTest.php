<?php
namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\GuardedFieldWriter;
use Database\Seeders\EncyclopediaHealthCanadaNovember16Seeder as Hc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaPr93MutationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array { return json_decode(file_get_contents(database_path(Hc::PAYLOAD_PATH)), true); }

    private static function order(mixed $v, bool $rev): mixed
    {
        if (! is_array($v)) return $v;
        $o = [];
        foreach ($v as $k => $x) $o[$k] = self::order($x, $rev);
        if (! array_is_list($o)) { $rev ? krsort($o) : ksort($o); }
        return $o;
    }

    private function seedRows(bool $rev): array
    {
        $ids = [];
        foreach ($this->payload()['posts'] as $slug => $spec) {
            $c = ProductCategory::create(['name' => $spec['category_slug'], 'slug' => $spec['category_slug'], 'is_active' => true]);
            $a = ['title' => $slug, 'slug' => $slug.'-x', 'product_category_id' => $c->id, 'status' => 'published', 'show_in_encyclopedia' => true];
            foreach ($spec['columns'] as $f => $col) $a[$f] = self::order($col['old_values'][0], $rev);
            foreach ($spec['drift'] as $f => $col) $a[$f] = self::order($col['expected'][0], $rev);
            $ids[$slug] = EducationPost::create($a)->id;
        }
        return $ids;
    }

    public function test_reverse_key_order_everywhere_still_updates_all(): void
    {
        $this->seedRows(true);
        $s = new Hc; $s->run();
        foreach (Hc::COLUMNS as $slug => $fields) foreach ($fields as $f)
            $this->assertSame(GuardedFieldWriter::UPDATED, $s->results[$slug.'.'.$f], $slug.'.'.$f);
    }

    public function test_content_value_edit_in_target_column_skips_entry(): void
    {
        $ids = $this->seedRows(false);
        $p = EducationPost::findOrFail($ids['hcg']);
        $rs = $p->regulatory_subsections; $rs[0]['entries'][0]['value'] .= ' ADMIN';
        $p->forceFill(['regulatory_subsections' => $rs])->saveQuietly();
        $before = EducationPost::findOrFail($ids['hcg'])->getAttributes();
        $s = new Hc; $s->run();
        $this->assertSame($before, EducationPost::findOrFail($ids['hcg'])->getAttributes());
        $this->assertNotSame(GuardedFieldWriter::UPDATED, $s->results['hcg.references']);
        $this->assertNotSame(GuardedFieldWriter::UPDATED, $s->results['hcg.regulatory_subsections']);
        $this->assertSame(GuardedFieldWriter::UPDATED, $s->results['mots-c.references']);
    }

    public function test_sibling_column_edits_skip_entry(): void
    {
        $ids = $this->seedRows(false);
        EducationPost::findOrFail($ids['mots-c'])->forceFill(['human_use_intro' => 'ADMIN'])->saveQuietly();
        $p = EducationPost::findOrFail($ids['retatrutide']); $h = $p->human_use_subsections ?? [];
        $h[] = ['title' => 'ADMIN', 'entries' => []];
        $p->forceFill(['human_use_subsections' => $h])->saveQuietly();
        $m = EducationPost::findOrFail($ids['mots-c'])->getAttributes();
        $r = EducationPost::findOrFail($ids['retatrutide'])->getAttributes();
        $s = new Hc; $s->run();
        $this->assertSame($m, EducationPost::findOrFail($ids['mots-c'])->getAttributes());
        $this->assertSame($r, EducationPost::findOrFail($ids['retatrutide'])->getAttributes());
        $this->assertSame(Hc::DRIFT_PREFIX.'human_use_intro', $s->results['mots-c.references']);
        $this->assertSame(Hc::DRIFT_PREFIX.'human_use_subsections', $s->results['retatrutide.regulatory_subsections']);
        $this->assertSame(GuardedFieldWriter::UPDATED, $s->results['nad.references']);
    }
}
