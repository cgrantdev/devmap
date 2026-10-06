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

        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $sitemap->assertSee('https://peptidemap.com/guides/beginners-guide-to-research-peptides', false);
        $sitemap->assertSee('https://peptidemap.com/blog/bpc-157-vs-tb-500-evidence', false);
        $sitemap->assertSee('https://peptidemap.com/guides', false);
        foreach ([
            'glow-vs-klow',
            'orforglipron-vs-tirzepatide',
            'retatrutide-vs-tirzepatide',
            'retatrutide-cagrilintide-blend',
            'cagrilintide-vs-eloralintide',
            'eloralintide-tirzepatide-vs-retatrutide',
        ] as $slug) {
            $sitemap->assertSee('https://peptidemap.com/blog/'.$slug, false);
        }
    }

    public function test_six_evidence_notes_are_published_once_with_same_origin_covers(): void
    {
        $slugs = [
            'glow-vs-klow',
            'orforglipron-vs-tirzepatide',
            'retatrutide-vs-tirzepatide',
            'retatrutide-cagrilintide-blend',
            'cagrilintide-vs-eloralintide',
            'eloralintide-tirzepatide-vs-retatrutide',
        ];

        $ids = [];
        foreach ($slugs as $slug) {
            $blog = Blog::where('slug', $slug)->firstOrFail();
            $this->assertSame(1, Blog::where('slug', $slug)->count());
            $this->assertSame('/images/educational/'.$slug.'.png', $blog->image);
            $this->assertSame('https://peptidemap.com/images/educational/'.$slug.'.png', $blog->seo_og_image);
            $this->assertSame('2026-10-05', $blog->published_at->toDateString());
            $this->assertFalse((bool) $blog->is_featured);
            $this->assertSame('Research', $blog->blog_type);
            $this->assertSame('published', $blog->status);
            $this->assertStringNotContainsString('/workspace/', (string) $blog->content);
            $this->assertStringNotContainsString('/workspace/', (string) $blog->image);
            $this->assertStringNotContainsString('CMS paste', (string) $blog->content);
            $this->assertStringNotContainsString('67724-34-9', (string) $blog->content);
            $this->assertStringNotContainsString('picsum.photos', (string) $blog->image);
            $this->assertStringNotContainsString('unsplash.com', (string) $blog->image);
            $this->assertFileExists(public_path('images/educational/'.$slug.'.png'));
            $this->assertFileExists(resource_path('content/educational/'.$slug.'.md'));
            $ids[$slug] = $blog->id;
        }

        $before = Blog::count();
        EducationalContentPublisher::sync();

        $this->assertSame($before, Blog::count());
        foreach ($slugs as $slug) {
            $blog = Blog::where('slug', $slug)->firstOrFail();
            $this->assertSame($ids[$slug], $blog->id);
            $this->assertSame('/images/educational/'.$slug.'.png', $blog->image);
            $this->assertSame('2026-10-05', $blog->published_at->toDateString());
            $this->assertFalse((bool) $blog->is_featured);
        }

        $glow = $this->get('/blog/glow-vs-klow');
        $glow->assertOk();
        $glow->assertSee('GLOW vs KLOW: What the Blend Labels Contain', false);
        $glow->assertSee('67727-97-3', false);
        $glow->assertSee('/images/educational/glow-vs-klow.png', false);
        $glow->assertDontSee('67724-34-9', false);
        $glow->assertDontSee('/workspace/', false);
        $glow->assertDontSee('CMS paste', false);
        $glow->assertDontSee('picsum.photos', false);
        $glow->assertDontSee('unsplash.com', false);

        $orforBlog = Blog::where('slug', 'orforglipron-vs-tirzepatide')->firstOrFail();
        $this->assertStringContainsString('−11.2%', $orforBlog->content);
        $this->assertStringContainsString('−12.4%', $orforBlog->content);
        $this->assertStringContainsString('−20.9%', $orforBlog->content);
        $this->assertStringContainsString('−22.5%', $orforBlog->content);
        $this->assertStringContainsString('Foundayo', $orforBlog->content);
        $orfor = $this->get('/blog/orforglipron-vs-tirzepatide');
        $orfor->assertOk();
        $orfor->assertSee('Foundayo', false);
        $orfor->assertSee('\u221211.2%', false);
        $orfor->assertSee('\u221212.4%', false);
        $orfor->assertSee('\u221220.9%', false);
        $orfor->assertSee('\u221222.5%', false);
        $orfor->assertDontSee('/workspace/', false);

        $retaBlog = Blog::where('slug', 'retatrutide-vs-tirzepatide')->firstOrFail();
        $this->assertStringContainsString('−25.0%', $retaBlog->content);
        $this->assertStringContainsString('−28.3%', $retaBlog->content);
        $this->assertStringContainsString('−18.8%', $retaBlog->content);
        $reta = $this->get('/blog/retatrutide-vs-tirzepatide');
        $reta->assertOk();
        $reta->assertSee('\u221225.0%', false);
        $reta->assertSee('\u221228.3%', false);
        $reta->assertSee('\u221218.8%', false);
        $reta->assertSee('TRIUMPH-5', false);
        $reta->assertDontSee('/workspace/', false);

        $blendBlog = Blog::where('slug', 'retatrutide-cagrilintide-blend')->firstOrFail();
        $this->assertStringContainsString('−20.4%', $blendBlog->content);
        $this->assertStringContainsString('−22.7%', $blendBlog->content);
        $blend = $this->get('/blog/retatrutide-cagrilintide-blend');
        $blend->assertOk();
        $blend->assertSee('\u221220.4%', false);
        $blend->assertSee('\u221222.7%', false);
        $blend->assertSee('not a published clinical trial', false);
        $blend->assertDontSee('/workspace/', false);

        $amylinBlog = Blog::where('slug', 'cagrilintide-vs-eloralintide')->firstOrFail();
        $this->assertStringContainsString('−11.5%', $amylinBlog->content);
        $this->assertStringContainsString('−23.3%', $amylinBlog->content);
        $this->assertStringContainsString('−20.1%', $amylinBlog->content);
        $amylin = $this->get('/blog/cagrilintide-vs-eloralintide');
        $amylin->assertOk();
        $amylin->assertSee('\u221211.5%', false);
        $amylin->assertSee('\u221223.3%', false);
        $amylin->assertSee('\u221220.1%', false);
        $amylin->assertDontSee('/workspace/', false);

        $comboBlog = Blog::where('slug', 'eloralintide-tirzepatide-vs-retatrutide')->firstOrFail();
        $this->assertStringContainsString('−23.3%', $comboBlog->content);
        $this->assertStringContainsString('−25.0%', $comboBlog->content);
        $this->assertStringContainsString('−28.3%', $comboBlog->content);
        $this->assertStringContainsString('−18.8%', $comboBlog->content);
        $this->assertStringContainsString('−20.8%', $comboBlog->content);
        $combo = $this->get('/blog/eloralintide-tirzepatide-vs-retatrutide');
        $combo->assertOk();
        $combo->assertSee('\u221223.3%', false);
        $combo->assertSee('\u221225.0%', false);
        $combo->assertSee('\u221228.3%', false);
        $combo->assertSee('\u221218.8%', false);
        $combo->assertSee('\u221220.8%', false);
        $combo->assertSee('images\\/educational\\/eloralintide-tirzepatide-vs-retatrutide.png', false);
        $combo->assertDontSee('/workspace/', false);
        $combo->assertDontSee('CMS paste', false);
        $combo->assertDontSee('67724-34-9', false);
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
        $this->assertSame('/images/educational/fda-peptide-reclassification-2026.png', $blog->image);
        $this->assertSame(
            'https://peptidemap.com/images/educational/fda-peptide-reclassification-2026.png',
            $blog->seo_og_image
        );
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

        $newestSlug = 'eloralintide-tirzepatide-vs-retatrutide';
        $newestImage = '/images/educational/'.$newestSlug.'.png';

        $this->get('/blogs')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Frontend/BlogListing')
            ->missing('featured')
            ->where('blogs.data.0.slug', $newestSlug)
            ->where('blogs.data.0.image', $newestImage)
            ->where('blogs.data.1.slug', 'cagrilintide-vs-eloralintide')
            ->where('blogs.data.6.slug', 'bpc-157-vs-tb-500-evidence')
            ->where('blogs.data.6.image', $evidencePath)
            ->where('blogs.data.7.slug', 'fda-peptide-reclassification-2026-what-researchers-need-to-know')
        );

        $this->get('/news')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Frontend/KnowledgeCenter')
            ->missing('featuredBlogs')
            ->where('latestBlogs.0.slug', $newestSlug)
            ->where('latestBlogs.0.image', $newestImage)
            ->where('latestBlogs.1.slug', 'cagrilintide-vs-eloralintide')
            ->where('latestBlogs.6.slug', 'bpc-157-vs-tb-500-evidence')
            ->where('latestBlogs.7.slug', 'fda-peptide-reclassification-2026-what-researchers-need-to-know')
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
            ['blog', 'bpc-157-vs-tb-500-evidence', 'https://images.unsplash.com/photo-1'],
            ['blog', 'bpc-157-vs-tb-500-evidence', 'https://picsum.photos/seed/x/800/500'],
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
