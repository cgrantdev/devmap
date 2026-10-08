<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use App\Support\EncyclopediaShellParser;
use App\Support\RetatrutideCompareNarrative;
use Database\Seeders\EncyclopediaEmptyShellsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncyclopediaEmptyShellsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_seeder_fills_empty_shells_once_and_leaves_retatrutide_encyclopedia_alone(): void
    {
        $this->assertSame(
            EncyclopediaEmptyShellsSeeder::SLUGS,
            $this->draftDirectories(),
            'Draft folders must match the 22 queue slugs.'
        );

        foreach (EncyclopediaEmptyShellsSeeder::SLUGS as $slug) {
            $category = ProductCategory::create([
                'name' => $slug,
                'slug' => $slug,
                'description' => 'peptide encyclopedia stub.',
                'is_active' => true,
            ]);
            if ($slug === 'kpv') {
                EducationPost::create([
                    'title' => 'KPV',
                    'slug' => 'kpv',
                    'product_category_id' => $category->id,
                    'status' => 'draft',
                    'overview' => 'short',
                    'show_in_encyclopedia' => false,
                ]);
            }
        }

        $retatrutide = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'description' => 'Leave this encyclopedia overview alone.',
            'is_active' => true,
        ]);
        $originalOverview = 'Original retatrutide encyclopedia overview that must survive the compare refresh.';
        EducationPost::create([
            'title' => 'Retatrutide',
            'slug' => 'retatrutide',
            'product_category_id' => $retatrutide->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => $originalOverview,
        ]);

        $categoryCount = ProductCategory::count();
        $seeder = new EncyclopediaEmptyShellsSeeder;
        $seeder->run();

        $this->assertSame(EncyclopediaEmptyShellsSeeder::SLUGS, $seeder->report['filled']);
        $this->assertSame([], $seeder->report['missing_category']);
        $this->assertSame([], $seeder->report['missing_draft']);
        $this->assertSame([], $seeder->report['skipped_slug_collision']);
        $this->assertNotContains('kpv', $seeder->report['created_posts']);
        $this->assertContains('glow', $seeder->report['created_posts']);
        $this->assertSame($categoryCount, ProductCategory::count());

        $kpv = EducationPost::query()->where('slug', 'kpv')->firstOrFail();
        $this->assertSame('67727-97-3', $kpv->cas_registry_number);
        $this->assertStringNotContainsString('67724-34-9', json_encode($kpv->getAttributes()));
        $this->assertSame('/images/encyclopedia/kpv-featured.png', $kpv->seo_og_image);
        $this->assertSame('KPV', $kpv->seo_h1);
        $this->assertGreaterThanOrEqual(20, mb_strlen(trim(strip_tags($kpv->overview))));

        $glutathione = EducationPost::query()->where('slug', 'glutathione')->firstOrFail();
        $this->assertSame('307.33 g/mol', $glutathione->molecular_weight);
        $this->assertStringNotContainsString('307.32', (string) $glutathione->overview);
        $this->assertStringContainsString('307.33', (string) $glutathione->overview);

        $epitalon = EducationPost::query()->where('slug', 'epitalon')->firstOrFail();
        $this->assertSame('C14H22N4O9', $epitalon->molecular_formula);
        $this->assertSame('390.35 g/mol', $epitalon->molecular_weight);
        $this->assertSame('307297-39-8', $epitalon->cas_registry_number);
        $this->assertSame('Ala-Glu-Asp-Gly', $epitalon->amino_acid_sequence);
        $this->assertStringContainsString('219042', (string) $epitalon->peptide_full_name);

        $blend = EducationPost::query()->where('slug', 'BPC-157-TB-500')->firstOrFail();
        $this->assertStringContainsString('Ac-LKKTETQ', (string) $blend->peptide_full_name);
        $this->assertStringContainsString('LKKTETQ', (string) $blend->overview);
        $this->assertNull($blend->cas_registry_number);

        $klow = EducationPost::query()->where('slug', 'klow-blend-ghk-cu-bpc-157-tb-500-kpv')->firstOrFail();
        $this->assertStringContainsString('KPV', (string) $klow->peptide_full_name);
        $this->assertStringContainsString('fourth component', (string) $klow->overview);
        $this->assertStringContainsString('not extra GHK', (string) $klow->overview);

        $mt2 = EducationPost::query()->where('slug', 'Melanotan-II')->firstOrFail();
        $this->assertStringContainsString('not final 503A Bulks List permission to compound', (string) $mt2->regulatory_important_note);

        foreach (EncyclopediaEmptyShellsSeeder::SLUGS as $slug) {
            $post = EducationPost::query()->where('product_category_id', ProductCategory::where('slug', $slug)->value('id'))->firstOrFail();
            $this->assertSame(1, EducationPost::query()->where('product_category_id', $post->product_category_id)->count(), $slug);
            $this->assertStringStartsWith('/images/encyclopedia/', (string) $post->seo_og_image);
            $this->assertFileExists(public_path(ltrim((string) $post->seo_og_image, '/')));
            $this->assertSame("\x89PNG", substr((string) file_get_contents(public_path(ltrim((string) $post->seo_og_image, '/'))), 0, 4));
            $blob = json_encode($post->only(['overview', 'background', 'conclusion', 'seo_og_image']));
            $this->assertDoesNotMatchRegularExpression('/picsum|unsplash|placeholder\.com/i', (string) $blob);
        }

        $retatrutide->refresh();
        $retaPost = $retatrutide->educationPost()->first();
        $this->assertSame($originalOverview, $retaPost->overview);
        $this->assertSame('Leave this encyclopedia overview alone.', $retatrutide->description);

        $snapshots = EducationPost::query()
            ->whereIn('slug', EncyclopediaEmptyShellsSeeder::SLUGS)
            ->get()
            ->mapWithKeys(fn (EducationPost $post) => [$post->slug => [$post->overview, $post->updated_at?->toJSON(), $post->seo_og_image]])
            ->all();

        $again = new EncyclopediaEmptyShellsSeeder;
        $again->run();
        $this->assertSame([], $again->report['filled']);
        $this->assertEqualsCanonicalizing(EncyclopediaEmptyShellsSeeder::SLUGS, $again->report['skipped_already_filled']);
        $this->assertSame($categoryCount, ProductCategory::count());

        foreach ($snapshots as $slug => [$overview, $updatedAt, $image]) {
            $post = EducationPost::query()->where('slug', $slug)->firstOrFail();
            $this->assertSame($overview, $post->overview, $slug);
            $this->assertSame($updatedAt, $post->updated_at?->toJSON(), $slug);
            $this->assertSame($image, $post->seo_og_image, $slug);
            $this->assertSame(1, EducationPost::query()->where('slug', $slug)->count(), $slug);
        }

        $this->get('/encyclopedia/kpv')
            ->assertOk()
            ->assertSee('67727-97-3', false)
            ->assertDontSee('67724-34-9', false)
            ->assertSee('/images/encyclopedia/kpv-featured.png', false)
            ->assertDontSee('unsplash.com', false)
            ->assertDontSee('picsum.photos', false)
            ->assertInertia(fn ($page) => $page
                ->where('featuredImage', '/images/encyclopedia/kpv-featured.png')
                ->where('seo.h1', 'KPV')
                ->where('molecularInfo.casNumber', '67727-97-3')
            );

        $this->get('/encyclopedia/glutathione')
            ->assertOk()
            ->assertSee('307.33', false)
            ->assertDontSee('307.32', false);

        $this->get('/encyclopedia/epitalon')
            ->assertOk()
            ->assertSee('219042', false)
            ->assertSee('C14H22N4O9', false)
            ->assertSee('390.35', false)
            ->assertSee('307297-39-8', false);

        $this->get('/encyclopedia/BPC-157-TB-500')
            ->assertOk()
            ->assertSee('LKKTETQ', false);

        $this->get('/encyclopedia/klow-blend-ghk-cu-bpc-157-tb-500-kpv')
            ->assertOk()
            ->assertSee('not extra GHK', false)
            ->assertSee('fourth component', false);

        $this->get('/encyclopedia/Melanotan-II')
            ->assertOk()
            ->assertSee('not final 503A Bulks List permission to compound', false);

        $this->get('/encyclopedia/retatrutide')
            ->assertOk()
            ->assertSee($originalOverview, false)
            ->assertDontSee('10.1056/NEJMoa2604169', false);
    }

    public function test_seeder_skips_filled_overviews_missing_categories_taken_slugs_and_collisions(): void
    {
        ProductCategory::create([
            'name' => 'KPV',
            'slug' => 'KPV',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'GHRP-2',
            'slug' => 'ghrp-2',
            'is_active' => true,
        ]);
        ProductCategory::create([
            'name' => 'GHRP-2 alt',
            'slug' => 'GHRP-2',
            'is_active' => true,
        ]);
        $snap = ProductCategory::create([
            'name' => 'SNAP-8',
            'slug' => 'snap-8',
            'description' => 'A real catalog description that should stay.',
            'is_active' => true,
        ]);
        $filled = 'This overview is already long enough to keep.';
        EducationPost::create([
            'title' => 'SNAP-8',
            'slug' => 'snap-8',
            'product_category_id' => $snap->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => $filled,
            'seo_og_image' => null,
        ]);
        $other = ProductCategory::create([
            'name' => 'Other',
            'slug' => 'other-holding-ll-37',
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => 'Held',
            'slug' => 'll-37',
            'product_category_id' => $other->id,
            'status' => 'draft',
            'overview' => null,
        ]);
        ProductCategory::create([
            'name' => 'LL-37',
            'slug' => 'll-37',
            'is_active' => true,
        ]);

        $before = ProductCategory::count();
        $seeder = new EncyclopediaEmptyShellsSeeder;
        $seeder->run();

        $this->assertSame($before, ProductCategory::count());
        $this->assertContains('kpv', $seeder->report['filled']);
        $this->assertTrue(collect($seeder->report['skipped_slug_collision'])->contains(fn ($row) => str_starts_with($row, 'ghrp-2 ')));
        $this->assertContains('snap-8', $seeder->report['skipped_already_filled']);
        $this->assertContains('dihexa', $seeder->report['missing_category']);
        $this->assertContains('ll-37', $seeder->report['skipped_slug_taken']);
        $kpv = EducationPost::query()->where('product_category_id', ProductCategory::where('slug', 'KPV')->value('id'))->firstOrFail();
        $this->assertSame('KPV', $kpv->slug);
        $this->assertSame('67727-97-3', $kpv->cas_registry_number);
        $this->assertSame('KPV', ProductCategory::query()->where('id', $kpv->product_category_id)->value('slug'));
        $this->assertNull(EducationPost::query()->where('product_category_id', ProductCategory::where('slug', 'ghrp-2')->value('id'))->first());
        $this->assertNull(EducationPost::query()->where('product_category_id', ProductCategory::where('slug', 'GHRP-2')->value('id'))->first());
        $this->assertNull(ProductCategory::query()->where('slug', 'dihexa')->first());
        $this->assertSame($filled, EducationPost::query()->where('slug', 'snap-8')->firstOrFail()->overview);
        $this->assertNull(EducationPost::query()->where('slug', 'snap-8')->firstOrFail()->seo_og_image);
        $this->assertSame('A real catalog description that should stay.', $snap->fresh()->description);
        $this->assertSame($other->id, EducationPost::query()->where('slug', 'll-37')->firstOrFail()->product_category_id);
        $this->assertNull(EducationPost::query()->where('product_category_id', ProductCategory::where('slug', 'll-37')->value('id'))->first());
    }

    public function test_seeder_fills_elamipretide_from_title_case_draft_when_category_slug_is_lowercase(): void
    {
        $this->assertDirectoryExists(database_path('seeders/data/encyclopedia-shells/Elamipretide'));

        $category = ProductCategory::create([
            'name' => 'Elamipretide',
            'slug' => 'elamipretide',
            'description' => 'peptide encyclopedia stub.',
            'is_active' => true,
        ]);
        $categoryCount = ProductCategory::count();

        $seeder = new EncyclopediaEmptyShellsSeeder;
        $seeder->run();

        $this->assertSame(['Elamipretide'], $seeder->report['filled']);
        $this->assertSame(['Elamipretide'], $seeder->report['created_posts']);
        $this->assertSame([], $seeder->report['skipped_slug_collision']);
        $this->assertNotContains('Elamipretide', $seeder->report['missing_category']);
        $this->assertSame($categoryCount, ProductCategory::count());
        $this->assertSame('elamipretide', $category->fresh()->slug);

        $post = EducationPost::query()->where('product_category_id', $category->id)->firstOrFail();
        $this->assertSame('elamipretide', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->show_in_encyclopedia);
        $this->assertSame('Elamipretide', $post->seo_h1);
        $this->assertSame('C32H49N9O5', $post->molecular_formula);
        $this->assertSame('736992-21-5', $post->cas_registry_number);
        $this->assertStringContainsString('Forzinity', (string) $post->overview);
        $this->assertStringContainsString('research-chemical listings', (string) $post->overview);
        $this->assertGreaterThanOrEqual(20, mb_strlen(trim(strip_tags((string) $post->overview))));
        $this->assertSame('/images/encyclopedia/elamipretide-featured.png', $post->seo_og_image);
        $this->assertSame(1, EducationPost::query()->where('product_category_id', $category->id)->count());

        $again = new EncyclopediaEmptyShellsSeeder;
        $again->run();
        $this->assertSame([], $again->report['filled']);
        $this->assertContains('Elamipretide', $again->report['skipped_already_filled']);
        $this->assertSame($categoryCount, ProductCategory::count());
        $this->assertSame('elamipretide', $category->fresh()->slug);
        $post->refresh();
        $this->assertSame('736992-21-5', $post->cas_registry_number);
        $this->assertSame(1, EducationPost::query()->where('slug', 'elamipretide')->count());

        $this->get('/encyclopedia/elamipretide')
            ->assertOk()
            ->assertSee('736992-21-5', false)
            ->assertSee('Forzinity', false)
            ->assertSee('/images/encyclopedia/elamipretide-featured.png', false)
            ->assertInertia(fn ($page) => $page
                ->where('slug', 'elamipretide')
                ->where('seo.h1', 'Elamipretide')
                ->where('molecularInfo.casNumber', '736992-21-5')
                ->where('molecularInfo.formula', 'C32H49N9O5')
                ->where('featuredImage', '/images/encyclopedia/elamipretide-featured.png')
            );
    }

    public function test_retatrutide_compare_leads_with_triumph_1_treatment_regimen(): void
    {
        $lead = RetatrutideCompareNarrative::payload()['lead'];
        $this->assertLessThanOrEqual(260, mb_strlen($lead));
        $this->assertStringContainsString('treatment-regimen', $lead);
        $this->assertStringContainsString('−25.0%', $lead);
        $this->assertStringContainsString('efficacy estimand', $lead);
        $this->assertStringContainsString('−28.3%', $lead);
        $this->assertLessThan(mb_strpos($lead, '−28.3%'), mb_strpos($lead, '−25.0%'));

        $blob = json_encode(RetatrutideCompareNarrative::payload(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('10.1056/NEJMoa2604169', $blob);
        $this->assertStringContainsString('29 Sep 2026', $blob);
        $this->assertStringContainsString('10.1016/S0140-6736(26)01861-1', $blob);
        $this->assertStringContainsString('prnewswire.com/news-releases/lillys-triple-agonist-retatrutide', $blob);
        $this->assertStringContainsString('−11.9%', $blob);
        $this->assertStringContainsString('−20.8%', $blob);
        $this->assertStringNotContainsString('Condor', $blob);
        $this->assertStringNotContainsString('Peptidemap lists Retatrutide', $blob);
        $this->assertStringNotContainsString('research-vendor synonym', $blob);

        $category = ProductCategory::create([
            'name' => 'Retatrutide',
            'slug' => 'retatrutide',
            'is_active' => true,
        ]);
        $overview = 'Original encyclopedia overview stays on the encyclopedia URL only.';
        EducationPost::create([
            'title' => 'Retatrutide',
            'slug' => 'retatrutide',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => $overview,
        ]);

        $this->get('/compare/retatrutide')
            ->assertOk()
            ->assertSee('<h1 class="ssr-seo-h1">Retatrutide Price Comparison</h1>', false)
            ->assertSee('<title>Retatrutide Price per mg: 0 Vendors Compared — Peptidemap</title>', false)
            ->assertSee('efficacy estimand', false)
            ->assertSee('treatment-regimen', false)
            ->assertSee('29 Sep 2026', false)
            ->assertDontSee('Condor', false)
            ->assertDontSee('Peptidemap lists Retatrutide', false)
            ->assertDontSee('research-vendor synonym', false)
            ->assertInertia(fn ($page) => $page
                ->where('seo.h1', 'Retatrutide Price Comparison')
                ->where('seo.title', 'Retatrutide Price per mg: 0 Vendors Compared')
                ->where('compound.summary', $lead)
                ->where('compound.research.lead', $lead)
                ->where('compound.research.references.0.url', 'https://doi.org/10.1056/NEJMoa2604169')
                ->where('compound.research.references.1.url', 'https://www.prnewswire.com/news-releases/lillys-triple-agonist-retatrutide-delivered-substantial-weight-loss-and-a1c-reduction-underscoring-its-potential-promise-for-people-with-obesity-and-type-2-diabetes-302891798.html')
                ->where('compound.research.references.2.url', 'https://www.thelancet.com/journals/lancet/article/PIIS0140-6736(26)01861-1/abstract')
                ->has('compound.faqs', 0)
            );

        $this->get('/encyclopedia/retatrutide')
            ->assertOk()
            ->assertSee($overview, false)
            ->assertDontSee('NEJMoa2604169', false);

        ProductCategory::create(['name' => 'Tirzepatide', 'slug' => 'tirzepatide', 'is_active' => true]);
        $this->get('/compare/retatrutide-vs-tirzepatide')
            ->assertOk()
            ->assertSee('efficacy estimand', false)
            ->assertInertia(fn ($page) => $page->where('a.summary', $lead));
    }

    public function test_parser_rejects_forbidden_kpv_cas_and_stock_images(): void
    {
        $parser = new EncyclopediaShellParser;
        $this->expectException(\RuntimeException::class);
        $parser->enforceLocks('kpv', [
            'overview' => 'KPV',
            'cas_registry_number' => '67724-34-9',
        ]);
    }

    /**
     * @return list<string>
     */
    private function draftDirectories(): array
    {
        $root = database_path('seeders/data/encyclopedia-shells');
        $dirs = array_values(array_filter(scandir($root) ?: [], function (string $name) use ($root) {
            return $name !== '.' && $name !== '..' && is_dir($root.'/'.$name);
        }));
        sort($dirs);
        $expected = EncyclopediaEmptyShellsSeeder::SLUGS;
        sort($expected);

        return $expected === $dirs ? EncyclopediaEmptyShellsSeeder::SLUGS : $dirs;
    }
}
