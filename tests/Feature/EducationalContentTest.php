<?php

namespace Tests\Feature;

use App\Content\EducationalContentPublisher;
use App\Content\ListingImageGuard;
use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EducationalContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_blog_index_redirects_and_new_pages_return_200_with_meta(): void
    {
        $this->get('/blog')->assertRedirect('/blogs');

        $evidence = $this->get('/blog/bpc-157-vs-tb-500-evidence');
        $evidence->assertOk();
        $evidence->assertSee('BPC-157 vs TB-500: What the Evidence Shows', false);
        $evidence->assertSee('What the Evidence Actually Shows', false);
        $evidence->assertSee('https://peptidemap.com/blog/bpc-157-vs-tb-500-evidence', false);
        $evidence->assertSee('https://peptidemap.com/blogs', false);
        $evidence->assertSee('property="og:type" content="article"', false);
        $evidence->assertSee('Are BPC-157 and TB-500 the same peptide?', false);
        $evidence->assertSee('/testing-labs', false);
        // Inertia JSON escapes slashes, so the body link is \/compare\/...
        $evidence->assertSee('compare\\/bpc-157-vs-tb-500', false);
        $evidence->assertSee('mdpi.com\\/2076-3417\\/16\\/12\\/6202', false);
        $evidence->assertDontSee('Note for Meta Optimizer', false);
        $evidence->assertDontSee('suggested_slug', false);
        $evidence->assertDontSee('thin placeholder', false);
        $evidence->assertDontSee('peptidemethods.com', false);
        $evidence->assertDontSee('kinetixatx.com', false);
        $evidence->assertDontSee('https://peptidemap.com/blog"', false);

        $this->get('/guides')->assertOk()->assertSee('Research Peptide Guides', false);
        $this->get('/guides/beginners-guide-to-research-peptides')->assertOk()
            ->assertSee('Beginner’s Guide to Research Peptides', false)
            ->assertSee('blog\\/how-to-verify-a-peptide-vendor-certificate-of-analysis', false)
            ->assertSee('/testing-labs', false)
            ->assertSee('https://peptidemap.com/guides', false)
            ->assertDontSee('peptides.so', false)
            ->assertDontSee('Note for Meta Optimizer', false);

        $legality = $this->get('/guides/peptide-legality-fda-ruo-compounding');
        $legality->assertOk();
        $legality->assertSee('Are Research Peptides Legal? FDA &amp; RUO Basics', false);
        $legality->assertSee('Last verified against FDA: 2026-09-30', false);
        $legality->assertSee('https://peptidemap.com/guides/peptide-legality-fda-ruo-compounding', false);
        $legality->assertDontSee('formblends', false);
        $legality->assertDontSee('FormBlends', false);
        $legality->assertDontSee('Note for Meta Optimizer', false);

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('https://peptidemap.com/guides/beginners-guide-to-research-peptides', false)
            ->assertSee('https://peptidemap.com/blog/bpc-157-vs-tb-500-evidence', false)
            ->assertSee('https://peptidemap.com/guides', false);
    }

    public function test_fda_correction_preserves_an_existing_byline(): void
    {
        Blog::where('slug', 'fda-peptide-reclassification-2026-what-researchers-need-to-know')
            ->update([
                'author_name' => 'Dr. Sarah Chen',
                'author_job' => 'Regulatory Affairs Editor',
                'is_featured' => true,
                'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b',
            ]);

        EducationalContentPublisher::sync();

        $blog = Blog::where('slug', 'fda-peptide-reclassification-2026-what-researchers-need-to-know')->first();
        $this->assertSame('Dr. Sarah Chen', $blog->author_name);
        $this->assertSame('Regulatory Affairs Editor', $blog->author_job);
        $this->assertTrue((bool) $blog->is_featured);
        $this->assertSame('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b', $blog->image);
        $this->assertSame('https://images.unsplash.com/photo-1582719478250-c89cae4dc85b', $blog->seo_og_image);
        $this->assertSame('2026-04-04', $blog->published_at->toDateString());
        $this->assertStringNotContainsString('back to Category 1', (string) $blog->content);
        $this->assertNull($blog->introduction);
        $this->assertNull($blog->detailed_analysis);
    }

    public function test_fda_reclassification_post_does_not_conflate_category_2_with_permission(): void
    {
        $response = $this->get('/blog/fda-peptide-reclassification-2026-what-researchers-need-to-know');
        $response->assertOk();
        $response->assertSee('FDA 2026: Category 2 Removal Is Not Permission', false);
        $response->assertSee('https://peptidemap.com/blogs', false);
        $response->assertSee('advisory', false);
        $response->assertDontSee('back to Category 1', false);
        $response->assertDontSee('can now produce', false);
        $response->assertDontSee('restored to Category 1', false);
        $response->assertDontSee('reopening compounding', false);
        $response->assertDontSee('PeptideMaps', false);
    }

    public function test_listing_cards_use_article_covers_and_indexes_are_chronological(): void
    {
        $evidencePath = '/images/educational/bpc-157-vs-tb-500-evidence.png';
        $evidence = Blog::where('slug', 'bpc-157-vs-tb-500-evidence')->firstOrFail();
        $this->assertSame($evidencePath, $evidence->image);
        $this->assertSame('https://peptidemap.com'.$evidencePath, $evidence->seo_og_image);
        $this->assertStringNotContainsString('og-default', $evidence->image);
        $this->assertStringNotContainsString('1.jpg', $evidence->image);
        $this->assertFileExists(public_path(ltrim($evidencePath, '/')));
        $this->assertFileExists(resource_path('content/educational/images/bpc-157-vs-tb-500-evidence.png'));

        $fda = Blog::where('slug', 'fda-peptide-reclassification-2026-what-researchers-need-to-know')->firstOrFail();
        $this->assertSame('/images/educational/fda-peptide-reclassification-2026.png', $fda->image);
        $this->assertStringNotContainsString('og-default', (string) $fda->image);
        $this->assertStringNotContainsString('1.jpg', (string) $fda->image);

        $beginner = EducationalGuide::where('slug', 'beginners-guide-to-research-peptides')->firstOrFail();
        $legality = EducationalGuide::where('slug', 'peptide-legality-fda-ruo-compounding')->firstOrFail();
        $this->assertSame('/images/educational/beginners-guide-to-research-peptides.png', $beginner->cover);
        $this->assertSame('/images/educational/peptide-legality-fda-ruo-compounding.png', $legality->cover);
        $this->assertNotSame($beginner->cover, $legality->cover);
        $this->assertSame('https://peptidemap.com'.$beginner->cover, $beginner->seo_og_image);
        $this->assertSame('https://peptidemap.com'.$legality->cover, $legality->seo_og_image);
        $this->assertFileExists(public_path(ltrim($beginner->cover, '/')));
        $this->assertFileExists(public_path(ltrim($legality->cover, '/')));

        $this->get('/blogs')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Frontend/BlogListing')
            ->missing('featured')
            ->where('blogs.data.0.slug', 'bpc-157-vs-tb-500-evidence')
            ->where('blogs.data.0.image', $evidencePath)
            ->where('blogs.data.1.slug', 'fda-peptide-reclassification-2026-what-researchers-need-to-know')
        );

        $this->get('/news')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Frontend/KnowledgeCenter')
            ->missing('featuredBlogs')
            ->where('latestBlogs.0.slug', 'bpc-157-vs-tb-500-evidence')
            ->where('latestBlogs.0.image', $evidencePath)
            ->where('latestBlogs.1.slug', 'fda-peptide-reclassification-2026-what-researchers-need-to-know')
        );

        $this->get('/guides')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Frontend/GuideListing')
            ->where('guides.0.slug', 'peptide-legality-fda-ruo-compounding')
            ->where('guides.1.slug', 'beginners-guide-to-research-peptides')
            ->where('guides', function ($guides) {
                $covers = collect($guides)->pluck('cover')->sort()->values()->all();

                return $covers === [
                    '/images/educational/beginners-guide-to-research-peptides.png',
                    '/images/educational/peptide-legality-fda-ruo-compounding.png',
                ];
            })
        );
    }

    public function test_listing_image_guard_rejects_empty_and_generic_placeholders(): void
    {
        $rejected = [
            ['blog', 'bpc-157-vs-tb-500-evidence', null],
            ['blog', 'bpc-157-vs-tb-500-evidence', ''],
            ['blog', 'bpc-157-vs-tb-500-evidence', '1.jpg'],
            ['blog', 'bpc-157-vs-tb-500-evidence', '/images/blogs/1.jpg'],
            ['blog', 'bpc-157-vs-tb-500-evidence', 'https://peptidemap.com/images/og-default-v7.png'],
            ['guide', 'beginners-guide-to-research-peptides', null],
            ['guide', 'peptide-legality-fda-ruo-compounding', '/images/og-default-v7.png'],
        ];

        foreach ($rejected as [$kind, $slug, $image]) {
            try {
                ListingImageGuard::assertArticleSpecific($kind, $slug, $image);
                $this->fail('Expected guard to reject '.$kind.' '.$slug.' image '.var_export($image, true));
            } catch (\RuntimeException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }

        ListingImageGuard::assertPresent('blog', 'legacy-demo', '1.jpg');
        ListingImageGuard::assertArticleSpecific(
            'blog',
            'bpc-157-vs-tb-500-evidence',
            '/images/educational/bpc-157-vs-tb-500-evidence.png'
        );
    }

    public function test_compare_pair_links_to_the_evidence_article(): void
    {
        ProductCategory::create(['name' => 'BPC-157', 'slug' => 'bpc-157', 'is_active' => true]);
        ProductCategory::create(['name' => 'TB-500', 'slug' => 'tb-500', 'is_active' => true]);

        $this->get('/compare/bpc-157-vs-tb-500')
            ->assertOk()
            ->assertSee('blog\\/bpc-157-vs-tb-500-evidence', false);
    }
}
