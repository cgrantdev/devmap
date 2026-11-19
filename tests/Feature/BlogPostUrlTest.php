<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Redirect;
use App\Models\User;
use App\Support\OrforglipronOralSemaglutideComparePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jake, Nov 18 2026: "is there a way for us to set the URL on each post,
 * or does it come from the title?" It came from the title on every save,
 * so editing a title moved the post. The editor now has a URL field.
 */
class BlogPostUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::create([
            'name' => 'QA Admin',
            'email' => 'qa-blog-url@example.test',
            'password' => bcrypt('x'),
            'role' => 'admin',
        ]));
    }

    public function test_new_post_takes_the_typed_url_normalised(): void
    {
        $this->post('/admin/blogs', [
            'title' => 'Retatrutide vs Tirzepatide: What Trials Show',
            'slug' => 'Retatrutide vs Tirzepatide',
            'status' => 'draft',
        ])->assertSessionHasNoErrors();

        $this->assertSame('retatrutide-vs-tirzepatide', Blog::first()->slug);
    }

    public function test_new_post_without_a_url_uses_the_title(): void
    {
        $this->post('/admin/blogs', ['title' => 'BPC-157 Storage Guide', 'status' => 'draft'])->assertSessionHasNoErrors();

        $this->assertSame('bpc-157-storage-guide', Blog::first()->slug);
    }

    public function test_editing_the_title_keeps_the_url(): void
    {
        $blog = $this->post_('ghk-cu-guide', 'GHK-Cu Guide');

        $this->post('/admin/blogs/'.$blog->id, [
            'title' => 'GHK-Cu: The Complete Guide',
            'slug' => 'ghk-cu-guide',
            'status' => 'published',
        ])->assertSessionHasNoErrors();

        $this->assertSame('ghk-cu-guide', $blog->fresh()->slug);
        $this->assertSame(0, Redirect::count());
    }

    public function test_changing_a_published_posts_url_redirects_the_old_one(): void
    {
        $blog = $this->post_('old-url', 'Semax Guide');
        // An older redirect into the old URL is re-pointed, not chained.
        Redirect::create(['from_path' => '/blog/older-url', 'to_path' => '/blog/old-url', 'status_code' => 301]);

        $this->post('/admin/blogs/'.$blog->id, [
            'title' => 'Semax Guide',
            'slug' => 'semax-guide',
            'status' => 'published',
        ])->assertSessionHasNoErrors();

        $this->assertSame('semax-guide', $blog->fresh()->slug);
        $this->assertSame('/blog/semax-guide', Redirect::where('from_path', '/blog/old-url')->value('to_path'));
        $this->assertSame('/blog/semax-guide', Redirect::where('from_path', '/blog/older-url')->value('to_path'));

        $this->get('/blog/old-url')->assertRedirect('/blog/semax-guide');
        $this->get('/blog/semax-guide')->assertOk();
    }

    public function test_a_url_another_post_uses_is_refused(): void
    {
        $this->post_('selank-guide', 'Selank Guide');
        $blog = $this->post_('selank-notes', 'Selank Notes');

        $this->post('/admin/blogs/'.$blog->id, [
            'title' => 'Selank Notes',
            'slug' => 'Selank Guide',
            'status' => 'published',
        ])->assertSessionHasErrors('slug');

        $this->assertSame('selank-notes', $blog->fresh()->slug);
    }

    public function test_the_compare_page_post_keeps_its_url(): void
    {
        $blog = $this->post_(OrforglipronOralSemaglutideComparePage::BLOG_SLUG, 'Orforglipron vs Oral Semaglutide');

        $this->post('/admin/blogs/'.$blog->id, [
            'title' => 'Orforglipron vs Oral Semaglutide',
            'slug' => 'something-else',
            'status' => 'published',
        ])->assertSessionHasNoErrors();

        $this->assertSame(OrforglipronOralSemaglutideComparePage::BLOG_SLUG, $blog->fresh()->slug);
        $this->assertSame(0, Redirect::count());
    }

    private function post_(string $slug, string $title): Blog
    {
        return Blog::create([
            'title' => $title,
            'slug' => $slug,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'content' => '<p>Body.</p>',
        ]);
    }
}
