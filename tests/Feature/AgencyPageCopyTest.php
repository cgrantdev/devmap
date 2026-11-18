<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\PageContent;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\VendorSetting;
use App\Support\SafeHtml;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jake (Advantage Collective), Nov 17 2026: the agency login had no field
 * for a blog article body, blog FAQs or (visibly) a featured image; vendor
 * pages had no H1, main copy or FAQs; calculator pages nothing on the page;
 * coupon pages no editor at all. Agency copy replaces the page default,
 * and the FAQPage JSON-LD is always the FAQ list the page shows.
 */
class AgencyPageCopyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_safe_html_removes_script_and_keeps_article_structure(): void
    {
        $out = SafeHtml::clean(
            '<h1>Title</h1><p onclick="x()" style="color:red">Text — 5µg <a href="javascript:alert(1)">bad</a> '
            .'<a href="/compare/bpc-157" target="_blank" rel="noopener noreferrer">compare</a> '
            .'<a href="https://example.com/x" target="_blank">out</a></p>'
            .'<script>alert(1)</script><iframe src="https://evil.test"></iframe>'
            .'<img src="x" onerror="alert(1)"><img src="javascript:alert(1)">'
            .'<table><tr><th>A</th></tr><tr><td>1</td></tr></table>'
            .'<ul><li>one</li></ul><font>kept text</font>'
        );

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('<iframe', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringNotContainsString('onerror', $out);
        $this->assertStringNotContainsString('style=', $out);
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringNotContainsString('<h1', $out);
        $this->assertStringContainsString('<h2>Title</h2>', $out);
        // Internal links open in the same tab; external ones keep noopener.
        $this->assertStringContainsString('<a href="/compare/bpc-157">compare</a>', $out);
        $this->assertMatchesRegularExpression('#<a href="https://example.com/x" target="_blank" rel="noopener">out</a>#', $out);
        $this->assertStringContainsString('<div class="edu-table-wrap"><table>', $out);
        $this->assertStringContainsString('<li>one</li>', $out);
        $this->assertStringContainsString('kept text', $out);
        $this->assertStringNotContainsString('<font', $out);
        $text = html_entity_decode(strip_tags($out), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertStringContainsString('Text — 5µg', $text);
    }

    public function test_safe_html_keeps_existing_article_markup_and_empties_blank_editor(): void
    {
        $article = '<p>Intro with <strong>bold</strong> and <em>em</em>.</p><h2>Section</h2><h3>Sub</h3>'
            .'<div class="edu-table-wrap"><table><thead><tr><th>Peptide</th></tr></thead><tbody><tr><td>Selank</td></tr></tbody></table></div>'
            .'<ol><li>first</li></ol><p><a href="/encyclopedia/Semax">Semax</a></p>';

        $out = SafeHtml::clean($article);

        $this->assertSame(1, substr_count($out, 'edu-table-wrap'));
        foreach (['<strong>bold</strong>', '<em>em</em>', '<h2>Section</h2>', '<h3>Sub</h3>', '<thead>', '<td>Selank</td>', '<ol><li>first</li></ol>', '<a href="/encyclopedia/Semax">Semax</a>'] as $piece) {
            $this->assertStringContainsString($piece, $out);
        }
        $this->assertNull(SafeHtml::clean('<p><br></p>'));
        $this->assertNull(SafeHtml::clean('   '));
    }

    public function test_agency_saves_a_blog_body_and_faqs_and_the_post_shows_them(): void
    {
        $blog = Blog::create([
            'title' => 'BPC-157 vs TB-500',
            'slug' => 'bpc-157-vs-tb-500',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'content' => '<p>Old body.</p>',
        ]);

        $this->actingAs($this->agency())
            ->post('/admin/blogs/'.$blog->id, [
                'title' => 'BPC-157 vs TB-500',
                'status' => 'published',
                'content' => '<h2>Overview</h2><p>New body.</p><script>alert(1)</script>',
                'faqs_sent' => true,
                'faqs' => [
                    ['question' => 'Are they the same?', 'answer' => 'No.'],
                    ['question' => 'Can they be compared?', 'answer' => 'Yes, side by side.'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $blog->refresh();
        $this->assertStringContainsString('<h2>Overview</h2>', $blog->content);
        $this->assertStringNotContainsString('<script', $blog->content);
        $this->assertCount(2, $blog->faqs);

        $props = $this->get('/blog/bpc-157-vs-tb-500')->assertOk()->viewData('page')['props'];
        $this->assertStringContainsString('New body.', $props['blog']['content']);
        $this->assertCount(2, $props['faqs']);
        $this->assertSame(['Are they the same?', 'Can they be compared?'], $this->faqQuestions($props['seo']['schema']));
    }

    public function test_saved_blog_faqs_replace_the_stored_schema_faqs_and_the_editor_starts_from_them(): void
    {
        $blog = Blog::create([
            'title' => 'Retatrutide guide',
            'slug' => 'retatrutide-guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'content' => '<p>Body.</p>',
            'seo_schema' => [[
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => [
                    ['@type' => 'Question', 'name' => 'Old question?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Old answer.']],
                ],
            ]],
        ]);

        $editor = $this->actingAs($this->admin())->get('/admin/blogs/'.$blog->id.'/edit')->assertOk()->viewData('page')['props'];
        $this->assertSame([['question' => 'Old question?', 'answer' => 'Old answer.']], $editor['blog']['faqs']);
        $this->assertFalse($editor['blog']['faqs_saved']);

        // Saving with every FAQ removed clears them from page and schema.
        $this->post('/admin/blogs/'.$blog->id, [
            'title' => 'Retatrutide guide',
            'status' => 'published',
            'content' => '<p>Body.</p>',
            'faqs_sent' => true,
        ])->assertSessionHasNoErrors();

        $props = $this->get('/blog/retatrutide-guide')->assertOk()->viewData('page')['props'];
        $this->assertSame([], $props['faqs']);
        $this->assertSame([], $this->faqQuestions($props['seo']['schema']));
    }

    public function test_vendor_page_shows_agency_h1_copy_and_faqs_and_schema_matches(): void
    {
        $brand = $this->vendor('glow-aminos', 'Glow Aminos');

        $this->actingAs($this->agency())
            ->put('/admin/seo/brands/'.$brand->id, [
                'copy_sent' => true,
                'h1' => 'Glow Aminos Research Peptides',
                'body_html' => '<h2>About Glow Aminos</h2><p>Long copy.</p>',
                'faqs' => [
                    ['question' => 'Where is Glow Aminos based?', 'answer' => 'In the US.'],
                    ['question' => 'Does it ship fast?', 'answer' => 'Usually within two days.'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $props = $this->get('/brand/glow-aminos')->assertOk()->viewData('page')['props'];
        $this->assertSame('Glow Aminos Research Peptides', $props['brand']['h1']);
        $this->assertStringContainsString('<h2>About Glow Aminos</h2>', $props['brand']['body_html']);
        $this->assertCount(2, $props['brand']['faqs']);
        $this->assertSame(['Where is Glow Aminos based?', 'Does it ship fast?'], $this->faqQuestions($props['seo']['schema']));
        $this->assertSame('Glow Aminos Research Peptides', $props['seo']['h1']);
    }

    public function test_vendor_page_shows_its_automatic_faqs_when_the_agency_has_none(): void
    {
        $brand = $this->vendor('amino-club', 'Amino Club');

        $props = $this->get('/brand/amino-club')->assertOk()->viewData('page')['props'];

        $visible = array_column($props['brand']['faqs'], 'question');
        $this->assertContains('How many products does Amino Club sell?', $visible);
        $this->assertSame($visible, $this->faqQuestions($props['seo']['schema']));
        $this->assertNull($props['brand']['h1']);
    }

    public function test_vendor_page_shows_why_choose_bullets_from_the_agency_editor(): void
    {
        $brand = $this->vendor('hydro-research', 'Hydro Research');

        $this->actingAs($this->agency())
            ->put('/admin/seo/brands/'.$brand->id, [
                'why_choose_bullets' => ['US warehouse', ''],
            ])
            ->assertSessionHasNoErrors();

        $props = $this->get('/brand/hydro-research')->assertOk()->viewData('page')['props'];
        $this->assertSame(['US warehouse'], $props['brand']['why_choose_bullets']);
    }

    public function test_calculator_page_copy_replaces_heading_intro_guides_and_faqs(): void
    {
        $this->actingAs($this->agency())
            ->put('/admin/seo/calculators/semaglutide-5mg/content', [
                'h1' => 'Semaglutide Dosage Calculator',
                'intro_html' => '<p>Work out a semaglutide dose.</p>',
                'body_html' => '<h2>How semaglutide is mixed</h2><p>Steps.</p>',
                'faqs' => [
                    ['question' => 'How much water for 5mg?', 'answer' => '2 mL is common.'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $props = $this->get('/calculator/semaglutide-5mg')->assertOk()->viewData('page')['props'];
        $this->assertSame('Semaglutide Dosage Calculator', $props['heading']);
        $this->assertSame('Semaglutide Dosage Calculator', $props['seo']['h1']);
        $this->assertStringContainsString('Work out a semaglutide dose.', $props['copy']['intro_html']);
        $this->assertCount(1, $props['copy']['faqs']);
        $this->assertSame(['How much water for 5mg?'], $this->faqQuestions($props['seo']['schema']));
        $this->assertFalse($this->hasType($props['seo']['schema'], 'HowTo'));

        // Another preset keeps the defaults.
        $other = $this->get('/calculator/tirzepatide-5mg')->assertOk()->viewData('page')['props'];
        $this->assertNull($other['copy']);
        $this->assertTrue($this->hasType($other['seo']['schema'], 'HowTo'));
        $this->assertCount(12, $this->faqQuestions($other['seo']['schema']));
    }

    public function test_main_calculator_page_takes_its_own_copy_and_unknown_slugs_404(): void
    {
        $this->actingAs($this->agency())
            ->put('/admin/seo/calculators/index/content', ['h1' => 'Peptide Reconstitution Calculator'])
            ->assertSessionHasNoErrors();

        $props = $this->get('/calculator')->assertOk()->viewData('page')['props'];
        $this->assertSame('Peptide Reconstitution Calculator', $props['heading']);

        $this->get('/admin/seo/calculators/not-a-preset/content')->assertNotFound();
    }

    public function test_coupon_page_takes_agency_title_meta_h1_copy_and_faqs(): void
    {
        $brand = $this->vendor('peptide-labs', 'Peptide Labs', 'PMAP', 15);

        $default = $this->get('/coupon/peptide-labs')->assertOk()->viewData('page')['props'];
        $this->assertSame('Peptide Labs Coupon Code PMAP — 15% Off', $default['seo']['title']);
        $this->assertNull($default['copy']);
        $this->assertSame([], $this->faqQuestions($default['seo']['schema']));

        $this->actingAs($this->agency())
            ->get('/admin/seo/coupons')
            ->assertOk();

        $this->put('/admin/seo/coupons/'.$brand->id, [
            'title' => 'Peptide Labs Discount Code: 15% Off',
            'meta_description' => 'Save 15% at Peptide Labs with code PMAP.',
            'h1' => 'Peptide Labs Discount Code',
            'intro_html' => '<p>Use PMAP at checkout.</p>',
            'body_html' => '<h2>About the code</h2><p>Details.</p>',
            'faqs' => [
                ['question' => 'Does PMAP stack?', 'answer' => 'Usually not.'],
                ['question' => 'Is the code free?', 'answer' => 'Yes.'],
            ],
        ])->assertSessionHasNoErrors();

        $props = $this->get('/coupon/peptide-labs')->assertOk()->viewData('page')['props'];
        $this->assertSame('Peptide Labs Discount Code: 15% Off', $props['seo']['title']);
        $this->assertSame('Save 15% at Peptide Labs with code PMAP.', $props['seo']['description']);
        $this->assertSame('Peptide Labs Discount Code', $props['copy']['h1']);
        $this->assertSame('Peptide Labs Discount Code', $props['seo']['h1']);
        $this->assertSame(['Does PMAP stack?', 'Is the code free?'], $this->faqQuestions($props['seo']['schema']));
    }

    public function test_clearing_every_field_removes_the_page_copy_row(): void
    {
        $brand = $this->vendor('lab-one', 'Lab One', 'PMAP', 10);
        $this->actingAs($this->admin())
            ->put('/admin/seo/coupons/'.$brand->id, ['h1' => 'Lab One Code'])
            ->assertSessionHasNoErrors();
        $this->assertNotNull(PageContent::forPage(PageContent::COUPON, $brand->id));

        $this->put('/admin/seo/coupons/'.$brand->id, ['h1' => '', 'faqs' => []])->assertSessionHasNoErrors();
        $this->assertNull(PageContent::forPage(PageContent::COUPON, $brand->id));
    }

    public function test_faq_rows_need_both_a_question_and_an_answer(): void
    {
        $brand = $this->vendor('lab-two', 'Lab Two', 'PMAP', 10);

        $this->actingAs($this->admin())
            ->put('/admin/seo/coupons/'.$brand->id, [
                'faqs' => [['question' => 'Only a question?', 'answer' => '']],
            ])
            ->assertSessionHasErrors('faqs.0.answer');
    }

    private function vendor(string $slug, string $name, ?string $code = null, ?int $percent = null): Brand
    {
        $brand = Brand::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
        VendorSetting::create([
            'brand_id' => $brand->id,
            'approval_status' => 'approved',
            'status' => 1,
            'coupon_code' => $code,
            'coupon_discount_percent' => $percent,
        ]);
        $category = ProductCategory::firstOrCreate(['slug' => 'bpc-157'], ['name' => 'BPC-157', 'is_active' => true]);
        Product::create([
            'name' => 'BPC-157 5mg '.$slug,
            'slug' => 'bpc-157-5mg-'.$slug,
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => 40,
            'status' => 'active',
            'hidden' => false,
        ]);

        return $brand;
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'QA Admin',
            'email' => 'qa-copy-admin@example.test',
            'password' => bcrypt('x'),
            'role' => 'admin',
        ]);
    }

    private function agency(): User
    {
        $user = User::create([
            'name' => 'QA Agency',
            'email' => 'qa-copy-agency@example.test',
            'password' => bcrypt('x'),
            'role' => 'seo_agency',
        ]);
        $user->markEmailAsVerified();

        return $user;
    }

    /** @return list<string> */
    private function faqQuestions(array $schema): array
    {
        foreach ($schema as $node) {
            if (($node['@type'] ?? null) === 'FAQPage') {
                return array_map(fn ($entity) => $entity['name'], $node['mainEntity']);
            }
        }

        return [];
    }

    private function hasType(array $schema, string $type): bool
    {
        foreach ($schema as $node) {
            if (($node['@type'] ?? null) === $type) {
                return true;
            }
        }

        return false;
    }
}
