<?php

namespace Tests\Feature;

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\GuardedFieldWriter;
use Database\Seeders\EncyclopediaCompoundingPolicyOctober29Seeder as Policy;
use Database\Seeders\EncyclopediaProdDriftReconcileOctober29Seeder as Reconcile;
use Database\Seeders\EncyclopediaRetatrutideOsaLitigationSeeder as Osa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Production's MySQL JSON columns return object keys in MySQL's own order
 * (shorter keys first, then bytewise: links, title, authors, citation,
 * description; answer before question), not in the order the payloads or
 * the Inertia props use. sqlite keeps insertion order, so these tests
 * re-encode every JSON column in MySQL order before each migration runs,
 * then replay the filename-order chain 100000 -> 110000 -> 120000 -> 200000.
 *
 * Primary scenario (b): production, where 100000, 110000 and 200000 already
 * ran (100000 with its original key-order-sensitive compare, so it skipped
 * bpc-157 faqs and the legality guide seo_schema), then 120000 runs and
 * catches those two columns up. Scenario (a): a fresh chain in filename
 * order.
 */
class ProdKeyOrderMigrationChainOctober29Test extends TestCase
{
    use RefreshDatabase;

    private const GUIDE = 'peptide-legality-fda-ruo-compounding';

    private const POLICY_CATEGORY = [
        'bpc-157' => 'BPC-157', 'tb-500' => 'TB-500', 'kpv' => 'kpv', 'epitalon' => 'epitalon', 'mots-c' => 'mots-c',
        'semax' => 'Semax', 'BPC-157-TB-500' => 'BPC-157-TB-500', 'glow' => 'glow',
        'klow-blend-ghk-cu-bpc-157-tb-500-kpv' => 'klow-blend-ghk-cu-bpc-157-tb-500-kpv',
    ];

    /** 100000 slugs whose live page renders no published post. */
    private const NO_LIVE_POST = ['semax', 'BPC-157-TB-500', 'klow-blend-ghk-cu-bpc-157-tb-500-kpv'];

    private const GUIDE_MISSING = '2026_10_29_120000 guide:peptide-legality-fda-ruo-compounding.seo_schema: guide lookup missing — nothing written';

    private const PAGES_200000 = ['compounded-vs-ruo-peptides', 'hhs-503a-peptide-enforcement-discretion-2026'];

    private const MIGRATIONS = [
        '2026_10_29_100000_compounding_policy_october_29.php',
        '2026_10_29_110000_retatrutide_osa_and_litigation_update.php',
        '2026_10_29_120000_reconcile_encyclopedia_prod_drift_october_29.php',
        '2026_10_29_200000_publish_503a_peptide_policy_pages.php',
    ];

    /** @var list<string> */
    private array $warnings = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->warnings = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e): void {
            if ($e->level === 'warning') {
                $this->warnings[] = $e->message;
            }
        });
    }

    public function test_b_prod_after_100000_110000_200000_with_strict_skips_then_120000_reaches_main(): void
    {
        $policy = Policy::payload();
        $posts = [];
        foreach ($policy['posts'] as $slug => $spec) {
            if (in_array($slug, self::NO_LIVE_POST, true)) {
                continue;
            }
            $attrs = [];
            foreach ($spec['columns'] as $column => $colSpec) {
                $attrs[$column] = $slug === 'bpc-157' && $column === 'faqs'
                    ? $colSpec['old_values'][0]           // skipped by strict 100000
                    : $colSpec['target_values'][0];       // written by 100000
            }
            $posts[$slug] = $this->linkedPost($this->category($slug, self::POLICY_CATEGORY[$slug]), strtolower($slug).'-linked', $attrs);
        }
        // semax / blends: live renders [] because no published post is linked.
        // One prod shape each: no post, a draft with NULL, a draft with [].
        $this->category('semax', 'Semax');
        $this->linkedPost($this->category('BPC-157-TB-500', 'BPC-157-TB-500'), 'bpc-157-tb-500-draft', ['status' => 'draft', 'regulatory_subsections' => null]);
        $this->linkedPost($this->category('klow', 'klow-blend-ghk-cu-bpc-157-tb-500-kpv'), 'klow-draft', ['status' => 'draft', 'regulatory_subsections' => []]);

        $live = $this->liveFixture();
        $after110000 = json_decode((string) file_get_contents(base_path('tests/Fixtures/prod-drift-2026-10-29/live-after-110000.json')), true)['posts']['retatrutide'];
        $ids = [];
        foreach ($live['posts'] as $slug => $fields) {
            if ($slug === 'retatrutide') {
                $fields = array_merge($fields, $after110000, ['regulatory_subsections' => $this->osaColumns()['regulatory_subsections']['target_values'][0]]);
            }
            $category = $this->category($live['categories'][$slug]['name'], $live['categories'][$slug]['slug']);
            $post = EducationPost::query()->where('product_category_id', $category->id)->first();
            if ($post) {
                // bpc-157: keep what 100000 wrote; take the rest from live.
                $fields = array_diff_key($fields, $policy['posts']['bpc-157']['columns']);
                $post->forceFill($fields)->saveQuietly();
            } else {
                $post = $this->linkedPost($category, $slug.'-linked', $fields);
            }
            $ids[$slug] = $post->id;
        }
        $this->assertNotSame([], $this->dateHits(EducationPost::findOrFail($ids['retatrutide'])->getAttributes()));

        $this->runMigration('2026_10_29_200000_publish_503a_peptide_policy_pages.php');
        $guideSpec = $policy['guides'][self::GUIDE]['columns'];
        $guideAttrs = [];
        foreach ($guideSpec as $column => $colSpec) {
            $guideAttrs[$column] = $column === 'seo_schema' ? $colSpec['old_values'][0] : $colSpec['target_values'][0];
        }
        $this->guide()->forceFill($guideAttrs)->saveQuietly();
        $this->reencodeAll();
        $untouched = EducationPost::query()->where('status', 'draft')->orderBy('id')->get()->map->getAttributes()->all();
        $this->warnings = [];

        $this->runMigration('2026_10_29_120000_reconcile_encyclopedia_prod_drift_october_29.php');
        $this->reencodeAll();

        $this->assertSame([], $this->warnings);
        $this->assertChainFinal($posts, $ids, skipNoLivePost: true);
        $this->assertSame($untouched, EducationPost::query()->where('status', 'draft')->orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame(0, EducationPost::query()->whereHas('category', fn ($q) => $q->where('slug', 'Semax'))->count());

        // Re-migrate: 120000 again changes nothing and warns about nothing.
        $snapshot = $this->contentSnapshot();
        $seeder = new Reconcile;
        $seeder->run();
        $this->assertSame([], $this->warnings);
        foreach ($seeder->results as $key => $status) {
            $this->assertSame(GuardedFieldWriter::ALREADY, $status, $key);
        }
        $this->assertCount(14, $seeder->results);
        $this->assertSame($snapshot, $this->contentSnapshot());
    }

    public function test_b_catch_up_guards_leave_unknown_values_alone(): void
    {
        $policy = Policy::payload();
        $faqs = $policy['posts']['bpc-157']['columns']['faqs']['old_values'][0];
        $faqs[0]['answer'] .= ' (admin edit)';
        $live = $this->liveFixture();
        $ids = [];
        foreach ($live['posts'] as $slug => $fields) {
            if ($slug === 'bpc-157') {
                $fields['faqs'] = $faqs;
            }
            $ids[$slug] = $this->linkedPost($this->category($live['categories'][$slug]['name'], $live['categories'][$slug]['slug']), $slug.'-linked', $fields)->id;
        }
        $schema = $policy['guides'][self::GUIDE]['columns']['seo_schema']['old_values'][0];
        $schema[0]['@id'] .= '-edited';
        $this->guide()->forceFill(['seo_schema' => self::mysqlKeyOrder($schema)])->saveQuietly();
        $guideBefore = $this->guide()->getAttributes()['seo_schema'];
        $this->warnings = [];

        $seeder = new Reconcile;
        $seeder->run();

        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results['bpc-157.faqs']);
        $this->assertSame(GuardedFieldWriter::MISMATCH, $seeder->results['guide:'.self::GUIDE.'.seo_schema']);
        $this->assertSame(GuardedFieldWriter::UPDATED, $seeder->results['bpc-157.references']);
        $this->assertSame($faqs, EducationPost::findOrFail($ids['bpc-157'])->faqs);
        $this->assertSame($guideBefore, $this->guide()->getAttributes()['seo_schema']);
        $this->assertCount(2, $this->warnings);

        // No guide row: a WARNING, nothing written, the posts still reconcile.
        EducationalGuide::where('slug', self::GUIDE)->delete();
        $this->warnings = [];
        $again = new Reconcile;
        $again->run();
        $this->assertSame([self::GUIDE_MISSING], array_values(array_filter($this->warnings, fn ($w) => str_contains($w, 'guide:'))));
    }

    public function test_100000_writes_on_prod_key_order_and_reruns_clean(): void
    {
        $posts = $this->seedPolicyPosts();
        $this->seedGuideAtPolicyOld();

        // The strict, key-ordered compare 100000 shipped with misses these.
        $faqsOld = Policy::payload()['posts']['bpc-157']['columns']['faqs']['old_values'][0];
        $this->assertNotSame(Policy::normalize($faqsOld), Policy::normalize($posts['bpc-157']->fresh()->faqs));
        $schemaOld = Policy::payload()['guides'][self::GUIDE]['columns']['seo_schema']['old_values'][0];
        $this->assertNotSame(Policy::normalize($schemaOld), Policy::normalize($this->guide()->seo_schema));

        $seeder = new Policy;
        $seeder->run();

        $this->assertSame([], $seeder->warnings);
        foreach (Policy::payload()['posts'] as $slug => $spec) {
            foreach ($spec['columns'] as $column => $colSpec) {
                $this->assertSame(Policy::UPDATED, $seeder->columns[$slug][$column], "{$slug}.{$column}");
                $this->assertSame(GuardedFieldWriter::comparable($colSpec['target_values'][0]), GuardedFieldWriter::comparable($posts[$slug]->fresh()->{$column}), "{$slug}.{$column}");
            }
        }
        foreach (Policy::payload()['guides'][self::GUIDE]['columns'] as $column => $colSpec) {
            $this->assertSame(Policy::UPDATED, $seeder->columns['guide:'.self::GUIDE][$column], $column);
        }

        $this->reencodeAll();
        $again = new Policy;
        $again->run();
        $this->assertSame([], $again->warnings);
        foreach ($again->columns as $key => $columns) {
            foreach ($columns as $column => $status) {
                $this->assertSame(Policy::CURRENT, $status, "{$key}.{$column}");
            }
        }
    }

    public function test_110000_writes_on_prod_key_order_and_reruns_clean(): void
    {
        $category = ProductCategory::create(['name' => 'Retatrutide', 'slug' => 'retatrutide', 'is_active' => true]);
        $columns = $this->osaColumns();
        $attrs = [];
        foreach ($columns as $column => $spec) {
            $attrs[$column] = self::mysqlKeyOrder($spec['old_values'][0]);
        }
        $post = $this->linkedPost($category, 'retatrutide-linked', $attrs);

        $seeder = new Osa;
        $seeder->run();
        $this->assertSame([], $this->warnings);
        foreach ($columns as $column => $spec) {
            $this->assertSame(Osa::UPDATED, $seeder->columnReport[$column], $column);
            $this->assertSame(GuardedFieldWriter::comparable($spec['target_values'][0]), GuardedFieldWriter::comparable($post->fresh()->{$column}), $column);
        }

        // Stored round trip: MySQL reorders the appended references' keys.
        $this->reencodeAll();
        $stored = $post->fresh()->references;
        $this->assertNotSame(Osa::normalize($columns['references']['target_values'][0]), Osa::normalize($stored));

        $again = new Osa;
        $again->run();
        $this->assertSame([], $this->warnings);
        foreach (array_keys($columns) as $column) {
            $this->assertSame(Osa::ALREADY, $again->columnReport[$column], $column);
        }
        $this->assertSame($stored, $post->fresh()->references);
    }

    public function test_200000_sync_only_has_no_guard_and_is_unaffected_by_key_order(): void
    {
        $this->runMigration('2026_10_29_200000_publish_503a_peptide_policy_pages.php');
        $this->reencodeAll();
        $before = [
            Blog::where('slug', self::PAGES_200000[1])->firstOrFail()->seo_schema,
            EducationalGuide::where('slug', self::PAGES_200000[0])->firstOrFail()->seo_schema,
        ];

        $this->runMigration('2026_10_29_200000_publish_503a_peptide_policy_pages.php');

        $this->assertSame(1, Blog::where('slug', self::PAGES_200000[1])->count());
        $this->assertSame(1, EducationalGuide::where('slug', self::PAGES_200000[0])->count());
        $this->assertSame(GuardedFieldWriter::comparable($before[0]), GuardedFieldWriter::comparable(Blog::where('slug', self::PAGES_200000[1])->firstOrFail()->seo_schema));
        $this->assertSame(GuardedFieldWriter::comparable($before[1]), GuardedFieldWriter::comparable(EducationalGuide::where('slug', self::PAGES_200000[0])->firstOrFail()->seo_schema));
        $this->assertSame([], $this->warnings);
    }

    public function test_a_fresh_filename_order_chain_from_prod_like_values_reaches_main_with_no_warnings_and_no_21_oct(): void
    {
        $posts = $this->seedPolicyPosts();
        $live = $this->liveFixture();
        $ids = [];
        foreach ($live['posts'] as $slug => $fields) {
            $category = $this->category($live['categories'][$slug]['name'], $live['categories'][$slug]['slug']);
            $post = EducationPost::query()->where('product_category_id', $category->id)->first();
            $fields = array_map(fn ($v) => self::mysqlKeyOrder($v), $fields);
            if ($post) {
                foreach ($fields as $column => $value) {
                    if (array_key_exists($column, Policy::payload()['posts']['bpc-157']['columns'])) {
                        // Same prod value in both fixtures.
                        $this->assertSame(GuardedFieldWriter::comparable($value), GuardedFieldWriter::comparable($post->{$column}), "{$slug}.{$column}");
                    }
                }
                $post->forceFill($fields)->saveQuietly();
            } else {
                $post = $this->linkedPost($category, $slug.'-linked', $fields);
            }
            $ids[$slug] = $post->id;
        }
        $this->seedGuideAtPolicyOld();
        Blog::whereIn('slug', self::PAGES_200000)->delete();
        EducationalGuide::whereIn('slug', self::PAGES_200000)->delete();
        $this->reencodeAll();
        $this->warnings = [];

        foreach (self::MIGRATIONS as $file) {
            $this->runMigration($file);
            $this->reencodeAll();
        }

        $this->assertSame([], $this->warnings);
        $this->assertChainFinal($posts, $ids);

        // Second migrate: nothing written, nothing warned.
        $snapshot = EducationPost::orderBy('id')->get()->map->getAttributes()->all();
        foreach (self::MIGRATIONS as $file) {
            if (! str_contains($file, '200000')) {
                $this->runMigration($file);
            }
        }
        $this->assertSame([], $this->warnings);
        $this->assertSame($snapshot, EducationPost::orderBy('id')->get()->map->getAttributes()->all());
    }

    /**
     * @param  array<string, EducationPost>  $policyPosts
     * @param  array<string, int>  $ids
     */
    private function assertChainFinal(array $policyPosts, array $ids, bool $skipNoLivePost = false): void
    {
        foreach (Policy::payload()['posts'] as $slug => $spec) {
            if ($skipNoLivePost && in_array($slug, self::NO_LIVE_POST, true)) {
                continue;
            }
            foreach ($spec['columns'] as $column => $colSpec) {
                $this->assertSame(GuardedFieldWriter::comparable($colSpec['target_values'][0]), GuardedFieldWriter::comparable($policyPosts[$slug]->fresh()->{$column}), "100000 {$slug}.{$column}");
            }
        }
        foreach (Policy::payload()['guides'][self::GUIDE]['columns'] as $column => $colSpec) {
            $this->assertSame(GuardedFieldWriter::comparable($colSpec['target_values'][0]), GuardedFieldWriter::comparable($this->guide()->{$column}), "100000 guide.{$column}");
        }

        $reta = EducationPost::findOrFail($ids['retatrutide']);
        $this->assertSame(GuardedFieldWriter::comparable($this->osaColumns()['regulatory_subsections']['target_values'][0]), GuardedFieldWriter::comparable($reta->regulatory_subsections));
        $reconcile = json_decode((string) file_get_contents(database_path(Reconcile::PAYLOAD_PATH)), true)['posts'];
        foreach ($reconcile as $slug => $post) {
            $row = EducationPost::findOrFail($slug === 'bpc-157' && isset($policyPosts['bpc-157']) ? $policyPosts['bpc-157']->id : $ids[$slug]);
            foreach ($post['columns'] as $column => $spec) {
                $value = $column === 'description' && trim((string) $row->description) === ''
                    ? ProductCategory::findOrFail($row->product_category_id)->description
                    : $row->{$column};
                $this->assertSame(GuardedFieldWriter::comparable($spec['target_value']), GuardedFieldWriter::comparable($value), "120000 {$slug}.{$column}");
            }
        }
        foreach (['references', 'human_use_subsections'] as $column) {
            $this->assertSame(GuardedFieldWriter::comparable($this->osaColumns()[$column]['target_values'][1]), GuardedFieldWriter::comparable($reta->{$column}), $column);
        }

        foreach (self::PAGES_200000 as $slug) {
            $this->assertSame(1, Blog::where('slug', $slug)->count() + EducationalGuide::where('slug', $slug)->count(), $slug);
        }

        // No 21 October date anywhere in the content tables.
        foreach ([EducationPost::class, ProductCategory::class, EducationalGuide::class, Blog::class] as $model) {
            foreach ($model::query()->get() as $row) {
                $this->assertSame([], $this->dateHits($row->getAttributes()), $model.' #'.$row->getKey());
            }
        }
        $osaSection = collect($reta->human_use_subsections)->first(fn ($s) => str_starts_with((string) ($s['title'] ?? ''), 'Obstructive sleep apnea trials'));
        $this->assertSame('Obstructive sleep apnea trials (NEJM, 20 Oct 2026) — developer-affiliated (Lilly-funded)', $osaSection['title']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function dateHits(array $attributes): array
    {
        $blob = json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        preg_match_all('/\b21 Oct(?:ober)?\b|October 21\b/', $blob, $m);

        return $m[0];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function contentSnapshot(): array
    {
        return [
            'posts' => EducationPost::orderBy('id')->get()->map->getAttributes()->all(),
            'categories' => ProductCategory::orderBy('id')->get()->map->getAttributes()->all(),
            'guides' => EducationalGuide::orderBy('id')->get()->map->getAttributes()->all(),
            'blogs' => Blog::orderBy('id')->get()->map->getAttributes()->all(),
        ];
    }

    private function runMigration(string $file): void
    {
        $migration = require database_path('migrations/'.$file);
        $migration->up();
    }

    /**
     * @return array<string, EducationPost>
     */
    private function seedPolicyPosts(): array
    {
        $out = [];
        foreach (Policy::payload()['posts'] as $slug => $spec) {
            $attrs = [];
            foreach ($spec['columns'] as $column => $colSpec) {
                $attrs[$column] = self::mysqlKeyOrder($colSpec['old_values'][0]);
            }
            $out[$slug] = $this->linkedPost($this->category($slug, self::POLICY_CATEGORY[$slug]), strtolower($slug).'-linked', $attrs);
        }

        return $out;
    }

    private function seedGuideAtPolicyOld(): void
    {
        if (! EducationalGuide::query()->where('slug', self::GUIDE)->exists()) {
            EducationalContentPublisher::sync();
        }
        $attrs = [];
        foreach (Policy::payload()['guides'][self::GUIDE]['columns'] as $column => $spec) {
            $attrs[$column] = self::mysqlKeyOrder($spec['old_values'][0]);
        }
        $this->guide()->forceFill($attrs)->saveQuietly();
    }

    /**
     * Simulate a MySQL JSON round trip for every array-cast column.
     */
    private function reencodeAll(): void
    {
        foreach ([EducationPost::class, EducationalGuide::class, Blog::class] as $model) {
            foreach ($model::query()->get() as $row) {
                foreach ($row->getCasts() as $column => $cast) {
                    if (in_array($cast, ['array', 'json'], true) && is_array($row->{$column})) {
                        $row->{$column} = self::mysqlKeyOrder($row->{$column});
                    }
                }
                if ($row->isDirty()) {
                    $row->saveQuietly();
                }
            }
        }
    }

    /**
     * MySQL JSON object key order: shorter keys first, then bytewise.
     */
    public static function mysqlKeyOrder(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        $value = array_map(fn ($v) => self::mysqlKeyOrder($v), $value);
        if (! array_is_list($value)) {
            uksort($value, fn ($a, $b) => [strlen((string) $a), (string) $a] <=> [strlen((string) $b), (string) $b]);
        }

        return $value;
    }

    private function category(string $name, string $slug): ProductCategory
    {
        return ProductCategory::query()->whereRaw('LOWER(TRIM(slug)) = ?', [strtolower(trim($slug))])->first()
            ?? ProductCategory::create(['name' => $name, 'slug' => $slug, 'is_active' => true, 'description' => 'CATEGORY-'.$slug]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function linkedPost(ProductCategory $category, string $slug, array $attributes): EducationPost
    {
        return EducationPost::create(array_merge([
            'title' => $slug, 'slug' => $slug, 'product_category_id' => $category->id,
            'status' => 'published', 'show_in_encyclopedia' => true,
        ], $attributes))->fresh();
    }

    private function guide(): EducationalGuide
    {
        return EducationalGuide::query()->where('slug', self::GUIDE)->firstOrFail();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function osaColumns(): array
    {
        return json_decode((string) file_get_contents(database_path(Osa::PAYLOAD_PATH)), true)['columns'];
    }

    /**
     * @return array<string, mixed>
     */
    private function liveFixture(): array
    {
        return json_decode((string) file_get_contents(base_path('tests/Fixtures/prod-drift-2026-10-29/live.json')), true);
    }
}
