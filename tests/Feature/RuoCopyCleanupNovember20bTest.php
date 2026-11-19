<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\RuoCopyCleanupNovember20bSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 2026_11_20_120000 strict RUO wording, pass 2.
 */
class RuoCopyCleanupNovember20bTest extends TestCase
{
    use RefreshDatabase;

    private const KENNEDY_REFS = '<li>U.S. FDA. Letter to Eli Lilly and Company on issues remanded to FDA (retatrutide product classification), 2 October 2026, filed in <em>Eli Lilly and Co. v. Kennedy</em>, 7th Cir. No. 26-1301, 8 October 2026. https://example.test/a.pdf</li>'
        .'<li>U.S. District Court, S.D. Indiana. <em>Eli Lilly and Co. v. Kennedy et al.</em>, No. 1:24-cv-01503, amended order, 3 October 2025. https://example.test/b.pdf</li>';

    public function test_migration_runs_the_seeder_and_every_pattern_compiles(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_11_20_120000_ruo_copy_cleanup_dosing_wording.php'));
        $this->assertStringContainsString('RuoCopyCleanupNovember20bSeeder', $migration);

        $spec = json_decode((string) file_get_contents(database_path('seeders/data/ruo-cleanup/2026-11-20b/copy-rules.json')), true);
        $this->assertIsArray($spec);
        $patterns = array_column($spec['rules'], 'find');
        $patterns = array_merge($patterns, array_column($spec['cleanup'], 'find'));
        foreach ($spec['groups'] as $group) {
            $patterns[] = $group['when'];
            $patterns = array_merge($patterns, array_column($group['rules'], 'find'));
        }
        $this->assertGreaterThan(100, count($patterns));
        foreach ($patterns as $pattern) {
            $this->assertNotFalse(@preg_match('~'.$pattern.'~u', ''), $pattern);
        }
    }

    public function test_encyclopedia_disclaimers_go_and_a_bullet_that_was_only_one_is_dropped(): void
    {
        $post = EducationPost::create([
            'title' => 'Sample',
            'slug' => 'sample-ruo-entry',
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'Sample is a small molecule. It is not a peptide. No dose is given. No CAS is stated.',
            'regulatory_important_note' => 'Informational only. Not medical advice, and not a dosing guide.',
            'key_points' => ['Sample is a small molecule.', 'No dose is given.', 'No dosing/protocols here.'],
            'potential_applications' => [['title' => 'Combination Protocols', 'description' => 'Assays run side-by-side under controlled lab protocols']],
            'preclinical_subsections' => [['title' => 'Dose-Response Studies', 'findings' => [['description' => 'GH release rose in a dose-dependent manner.']]]],
        ]);

        $seeder = new RuoCopyCleanupNovember20bSeeder;
        $seeder->run();
        $post->refresh();

        $this->assertSame('Sample is a small molecule. It is not a peptide. No CAS is stated.', $post->overview);
        $this->assertSame('Informational only. Not medical advice.', $post->regulatory_important_note);
        $this->assertSame(['Sample is a small molecule.'], $post->key_points);
        $this->assertSame([['title' => 'Combination Research', 'description' => 'Assays run side-by-side under controlled lab conditions']], $post->potential_applications);
        // Trial and study reporting keeps its dose words.
        $this->assertSame([['title' => 'Dose-Response Studies', 'findings' => [['description' => 'GH release rose in a dose-dependent manner.']]]], $post->preclinical_subsections);
        $this->assertGreaterThanOrEqual(1, $seeder->counts['education_posts']);

        $raw = $this->raw('education_posts', $post->id);
        $again = new RuoCopyCleanupNovember20bSeeder;
        $again->run();
        $this->assertSame($raw, $this->raw('education_posts', $post->id));
        $this->assertSame(0, $again->counts['education_posts']);
    }

    public function test_court_references_go_and_citations_are_renumbered_once_on_that_post_only(): void
    {
        $content = '<p>Peptidemap is a research-use-only (RUO) peptide comparison publisher. It is not medical advice, gives no dosing, and does not say which drug is better.</p>'
            .'<p>Topline results [23]. TRIUMPH-1 [25][26]. The highest dose arm is reported as published.</p>'
            .'<ol><li>Reference twelve.</li>'.self::KENNEDY_REFS.'<li>Reference fifteen.</li></ol>';

        $blog = Blog::where('slug', 'retatrutide-vs-semaglutide')->firstOrFail();
        $blog->forceFill([
            'content' => $content,
            'faqs' => [['question' => 'Is retatrutide FDA-approved?', 'answer' => 'No. Lilly plans to file in 2027. The FDA has determined that retatrutide is a drug rather than a biological product, a classification Lilly is challenging in court.']],
            'key_points' => ['This page does not rank the forms and does not give dosing or reconstitution steps.'],
        ])->saveQuietly();

        $other = Blog::where('slug', 'bpc-157-vs-ghk-cu')->firstOrFail();
        $otherContent = '<p>Unrelated post [23].</p><ol>'.self::KENNEDY_REFS.'</ol>';
        $other->forceFill(['content' => $otherContent])->saveQuietly();

        (new RuoCopyCleanupNovember20bSeeder)->run();
        $blog->refresh();

        $this->assertSame(
            '<p>Peptidemap is a research-use-only (RUO) peptide comparison publisher. It is not medical advice and does not say which drug is better.</p>'
            .'<p>Topline results [21]. TRIUMPH-1 [23][24]. The highest dose arm is reported as published.</p>'
            .'<ol><li>Reference twelve.</li><li>Reference fifteen.</li></ol>',
            $blog->content
        );
        $this->assertSame([['question' => 'Is retatrutide FDA-approved?', 'answer' => 'No. Lilly plans to file in 2027.']], $blog->faqs);
        $this->assertSame(['This page does not rank the forms and does not give reconstitution steps.'], $blog->key_points);
        // The renumbering group is scoped to retatrutide-vs-semaglutide.
        $this->assertSame($otherContent, $other->fresh()->content);

        $raw = $this->raw('blogs', $blog->id);
        (new RuoCopyCleanupNovember20bSeeder)->run();
        $this->assertSame($raw, $this->raw('blogs', $blog->id));
    }

    public function test_category_descriptions_and_page_and_encyclopedia_metas_are_cleaned(): void
    {
        $category = ProductCategory::create([
            'name' => 'Sample Category',
            'slug' => 'sample-category',
            'is_active' => true,
            'description' => 'Sample is a small molecule. It is not a peptide. No dosing is given here.',
        ]);
        $post = EducationPost::create([
            'title' => 'Sample Meta',
            'slug' => 'sample-meta',
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'seo_description' => 'A hypothalamic research peptide. No dosing.',
            'seo_og_description' => 'Synthetic peptide. Not an FDA-approved U.S. therapeutic; prohibited in sport (WADA S2).',
        ]);
        DB::table('seo_pages')->insert([
            'key' => 'ruo-test-page',
            'description' => 'Every listing. Filter by compound, purity, price, and dose.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new RuoCopyCleanupNovember20bSeeder)->run();

        $this->assertSame('Sample is a small molecule. It is not a peptide.', $category->fresh()->description);
        $post->refresh();
        $this->assertSame('A hypothalamic research peptide.', $post->seo_description);
        $this->assertSame('Synthetic peptide. Not an FDA-approved U.S. drug; prohibited in sport (WADA S2).', $post->seo_og_description);
        $this->assertSame('Every listing. Filter by compound, purity, price, and vial size.', DB::table('seo_pages')->where('key', 'ruo-test-page')->value('description'));
    }

    public function test_guide_wording_is_rewritten(): void
    {
        $guide = EducationalGuide::where('slug', 'bacteriostatic-water-literacy')->firstOrFail();
        $guide->forceFill(['content' => '<p>That keeps educational pages from doubling as protocols.</p><p>Not medical advice, not legal advice, and not instructions for reconstitution, dosing, or human administration.</p>'])->saveQuietly();

        (new RuoCopyCleanupNovember20bSeeder)->run();

        $this->assertSame(
            '<p>That keeps educational pages from doubling as instructions.</p><p>Not medical advice, not legal advice, and not instructions for reconstitution or human administration.</p>',
            $guide->fresh()->content
        );
    }

    /** @return array<string, mixed> */
    private function raw(string $table, int $id): array
    {
        return (array) DB::table($table)->where('id', $id)->first();
    }
}
