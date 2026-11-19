<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use Database\Seeders\RuoCopyCleanupNovember20Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 2026_11_20_100000 strict RUO language pass.
 */
class RuoCopyCleanupNovember20Test extends TestCase
{
    use RefreshDatabase;

    private const BANNED = '/gr[ae]y[- ]?(area|market|channel)|black[- ]market|lawsuit|court case|seiz/i';

    public function test_migration_runs_the_seeder_and_the_data_files_hold(): void
    {
        $migration = (string) file_get_contents(database_path('migrations/2026_11_20_100000_ruo_copy_cleanup.php'));
        $this->assertStringContainsString('RuoCopyCleanupNovember20Seeder', $migration);

        $rules = $this->rules();
        $this->assertNotEmpty($rules);
        foreach ($rules as $rule) {
            $this->assertCount(2, $rule);
            $this->assertNotFalse(@preg_match('~'.$rule[0].'~u', ''), $rule[0]);
        }

        $spec = $this->encyclopedia();
        $this->assertCount(30, $spec);
        foreach ($spec as $slug => $fields) {
            foreach ($fields as $column => $change) {
                $label = $slug.'.'.$column;
                $this->assertIsBool($change['json'], $label);
                $this->assertNotEquals($change['old'], $change['new'], $label);
                $this->assertDoesNotMatchRegularExpression(self::BANNED, (string) json_encode($change['new'], JSON_UNESCAPED_UNICODE), $label);
            }
        }
    }

    public function test_blog_and_guide_wording_is_rewritten_and_a_second_run_changes_nothing(): void
    {
        $blog = Blog::where('slug', 'retatrutide-vs-semaglutide')->firstOrFail();
        $blog->forceFill([
            'title' => 'Retatrutide Testing and COA Limits: Grey-Market Labels',
            'content' => '<p>Peptidemap is an RUO / grey-area peptide comparison publisher, not a clinic.</p><p>Peptidemap compares research-use and grey-area peptide listings.</p>',
            'tags' => ['GLP-1', 'Grey market', 'Retatrutide'],
            'key_points' => ['Peptidemap is an RUO / grey-area peptide comparison publisher.', 'KEEP-POINT'],
        ])->saveQuietly();

        $guide = EducationalGuide::where('slug', 'compounded-vs-ruo-peptides')->firstOrFail();
        $guide->forceFill([
            'content' => '<p>Grey-channel sellers list it in grey-area catalogs.</p>',
            'seo_schema' => [[
                '@type' => 'FAQPage',
                'mainEntity' => [[
                    '@type' => 'Question',
                    'name' => 'Who publishes this?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Peptidemap compares research-use and grey-area peptide listings.'],
                ]],
            ]],
        ])->saveQuietly();

        $seeder = new RuoCopyCleanupNovember20Seeder;
        $seeder->run();

        $blog->refresh();
        $this->assertSame('Retatrutide Testing and COA Limits: Research-Use Labels', $blog->title);
        $this->assertSame('<p>Peptidemap is a research-use-only (RUO) peptide comparison publisher, not a clinic.</p><p>Peptidemap compares research-use-only (RUO) peptide listings.</p>', $blog->content);
        $this->assertSame(['GLP-1', 'Retatrutide'], $blog->tags);
        $this->assertSame(['Peptidemap is a research-use-only (RUO) peptide comparison publisher.', 'KEEP-POINT'], $blog->key_points);

        $guide->refresh();
        $this->assertSame('<p>Unregulated sellers list it in research-peptide catalogs.</p>', $guide->content);
        $this->assertSame('Peptidemap compares research-use-only (RUO) peptide listings.', $guide->seo_schema[0]['mainEntity'][0]['acceptedAnswer']['text']);
        $this->assertGreaterThanOrEqual(1, $seeder->counts['blog_rows']);
        $this->assertGreaterThanOrEqual(1, $seeder->counts['guide_rows']);

        $before = [$this->raw('blogs', $blog->id), $this->raw('educational_guides', $guide->id)];
        $again = new RuoCopyCleanupNovember20Seeder;
        $again->run();
        $this->assertSame($before, [$this->raw('blogs', $blog->id), $this->raw('educational_guides', $guide->id)]);
        $this->assertSame(0, $again->counts['blog_rows']);
        $this->assertSame(0, $again->counts['guide_rows']);
    }

    public function test_encyclopedia_fields_are_written_only_while_they_hold_the_20_nov_value(): void
    {
        $spec = $this->encyclopedia()['BPC-157'];
        $this->assertSame(['key_points', 'regulatory_subsections', 'regulatory_important_note', 'references'], array_keys($spec));

        $category = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'BPC-157', 'is_active' => true]);
        $attributes = array_map(fn ($change) => $change['old'], $spec);
        $attributes['regulatory_important_note'] = 'ADMIN-EDIT';
        $post = EducationPost::create(array_merge([
            'title' => 'BPC-157',
            'slug' => 'bpc-157',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
        ], $attributes));
        $overview = $post->overview;

        $seeder = new RuoCopyCleanupNovember20Seeder;
        $seeder->run();
        $post->refresh();

        foreach (['key_points', 'regulatory_subsections', 'references'] as $column) {
            $this->assertSame($spec[$column]['new'], $post->{$column}, $column);
        }
        $this->assertSame('ADMIN-EDIT', $post->regulatory_important_note);
        $this->assertSame($overview, $post->overview);
        $this->assertSame(3, $seeder->counts['fields_written']);
        $this->assertSame(0, $seeder->counts['fields_already']);

        $raw = $this->raw('education_posts', $post->id);
        $again = new RuoCopyCleanupNovember20Seeder;
        $again->run();
        $this->assertSame($raw, $this->raw('education_posts', $post->id));
        $this->assertSame(0, $again->counts['fields_written']);
        $this->assertSame(3, $again->counts['fields_already']);
    }

    public function test_the_encyclopedia_section_heading_is_research_wording(): void
    {
        foreach (['js/Pages/Frontend/EncyclopediaArticleDetail.vue', 'js/Pages/Admin/EncyclopediaEntryEdit.vue'] as $file) {
            $source = (string) preg_replace('/<!--.*?-->/s', '', (string) file_get_contents(resource_path($file)));
            $this->assertStringNotContainsString('Therapeutic', $source, $file);
            $this->assertStringContainsString('Further Areas of Research', $source, $file);
        }
    }

    /** @return list<array{0: string, 1: string}> */
    private function rules(): array
    {
        $rules = json_decode((string) file_get_contents(database_path('seeders/data/ruo-cleanup/2026-11-20/copy-rules.json')), true);
        $this->assertIsArray($rules);

        return $rules;
    }

    /** @return array<string, array<string, array{json: bool, old: mixed, new: mixed}>> */
    private function encyclopedia(): array
    {
        $spec = json_decode((string) file_get_contents(database_path('seeders/data/ruo-cleanup/2026-11-20/encyclopedia.json')), true);
        $this->assertIsArray($spec);

        return $spec;
    }

    /** @return array<string, mixed> */
    private function raw(string $table, int $id): array
    {
        return (array) DB::table($table)->where('id', $id)->first();
    }
}
