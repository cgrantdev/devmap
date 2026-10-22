<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqSchemaParityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Hardcoded strings that used to ship from EncyclopediaController::getFAQs().
     *
     * @var list<string>
     */
    private const REMOVED_DOSING_STRINGS = [
        'How long does it take to see results from BPC-157?',
        'Should I inject BPC-157 near the injury site?',
        'What is the recommended dosage?',
        'Most users report noticeable improvements within 2-4 weeks of consistent use.',
        'some users prefer to inject near the injury site',
        'Dosage varies by individual and condition.',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_blog_with_faq_renders_the_faqpage_strings_and_drops_the_markdown_copy(): void
    {
        $question = 'What does this sample record?';
        $answer = 'It records a catalog label only.';
        $secondQuestion = 'Where does the answer text come from?';
        $secondAnswer = 'It comes from the FAQPage entity, character for character.';

        $blog = Blog::create([
            'title' => 'FAQ parity sample',
            'slug' => 'faq-parity-sample',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'description' => 'A sample post used to check FAQ parity.',
            'content' => <<<'HTML'
<h2>Overview</h2>
<p>Body paragraph stays.</p>
<h2>FAQs</h2>
<h3>What does this sample record?</h3>
<p>It records a catalog label only.</p>
<h3>Where does the answer text come from?</h3>
<p>It comes from the FAQPage entity, <strong>character for character</strong>.</p>
<h2>Sources</h2>
<p>Source paragraph stays.</p>
HTML,
            'seo_schema' => [[
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                '@id' => 'https://peptidemap.com/blog/faq-parity-sample#faq',
                'mainEntity' => [
                    [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
                    ],
                    [
                        '@type' => 'Question',
                        'name' => $secondQuestion,
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $secondAnswer],
                    ],
                ],
            ]],
        ]);

        $response = $this->get('/blog/'.$blog->slug);
        $response->assertOk();

        $faqPage = $this->faqPage($response->getContent());
        $this->assertNotNull($faqPage);
        $this->assertCount(2, $faqPage['mainEntity']);

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 2)
            ->where('faqs.0.question', $faqPage['mainEntity'][0]['name'])
            ->where('faqs.0.answer', $faqPage['mainEntity'][0]['acceptedAnswer']['text'])
            ->where('faqs.1.question', $faqPage['mainEntity'][1]['name'])
            ->where('faqs.1.answer', $faqPage['mainEntity'][1]['acceptedAnswer']['text'])
            ->where('faqs.0.question', $question)
            ->where('faqs.0.answer', $answer)
            ->where('faqs.1.question', $secondQuestion)
            ->where('faqs.1.answer', $secondAnswer)
            ->where('blog.content', function ($content) {
                $this->assertIsString($content);
                $this->assertStringContainsString('Body paragraph stays.', $content);
                $this->assertStringContainsString('Source paragraph stays.', $content);
                $this->assertStringNotContainsString('<h2>FAQs</h2>', $content);
                $this->assertStringNotContainsString('<h3>What does this sample record?</h3>', $content);
                $this->assertStringNotContainsString('<strong>character for character</strong>', $content);

                return true;
            })
        );

        $this->assertSame($question, $faqPage['mainEntity'][0]['name']);
        $this->assertSame($answer, $faqPage['mainEntity'][0]['acceptedAnswer']['text']);
        $this->assertSame($secondQuestion, $faqPage['mainEntity'][1]['name']);
        $this->assertSame($secondAnswer, $faqPage['mainEntity'][1]['acceptedAnswer']['text']);
    }

    public function test_blog_without_faq_emits_no_faqpage_and_no_faq_section(): void
    {
        $content = <<<'HTML'
<h2>Notes</h2>
<p>This post has no question block.</p>
HTML;

        $blog = Blog::create([
            'title' => 'No FAQ sample',
            'slug' => 'no-faq-sample',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'description' => 'A sample post with no FAQ.',
            'content' => $content,
            'seo_schema' => [[
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                '@id' => 'https://peptidemap.com/blog/no-faq-sample#faq',
                'mainEntity' => [],
            ]],
        ]);

        $response = $this->get('/blog/'.$blog->slug);
        $response->assertOk();
        $response->assertDontSee('FAQPage', false);
        $response->assertDontSee('<h2>FAQ</h2>', false);
        $response->assertDontSee('<h2>FAQs</h2>', false);
        $this->assertNull($this->faqPage($response->getContent()));

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 0)
            ->where('blog.content', $content)
            ->where('seo.schema', function ($schema) {
                foreach ($schema as $block) {
                    $this->assertNotSame('FAQPage', is_array($block) ? ($block['@type'] ?? null) : null);
                }

                return true;
            })
        );
    }

    public function test_bpc_157_allowlist_renders_visible_faq_matching_json_ld(): void
    {
        $question = 'What does this bpc-157 entry record?';
        $answer = 'It records a catalog label only.';
        $stored = [[
            'question' => '  '.$question.'  ',
            'answer' => '<p>'.$answer.'</p>',
        ]];

        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        $post = EducationPost::create([
            'title' => 'BPC-157',
            'slug' => 'bpc-157',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'Overview paragraph stays.',
            'faqs' => $stored,
        ]);

        $response = $this->get('/encyclopedia/bpc-157');
        $response->assertOk();

        $faqPage = $this->faqPage($response->getContent());
        $this->assertNotNull($faqPage);
        $this->assertCount(1, $faqPage['mainEntity']);
        $this->assertSame($question, $faqPage['mainEntity'][0]['name']);
        $this->assertSame($answer, $faqPage['mainEntity'][0]['acceptedAnswer']['text']);

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 1)
            ->where('faqs.0.question', $question)
            ->where('faqs.0.answer', $answer)
            ->where('faqs.0.question', $faqPage['mainEntity'][0]['name'])
            ->where('faqs.0.answer', $faqPage['mainEntity'][0]['acceptedAnswer']['text'])
        );

        $this->assertSame($stored, $post->fresh()->faqs);
    }

    public function test_ipamorelin_stored_faqs_are_not_rendered_or_marked_up(): void
    {
        $stored = [[
            'question' => 'What catalog label does this ipamorelin entry use?',
            'answer' => 'It uses the stored encyclopedia label only.',
        ]];

        $category = ProductCategory::create([
            'name' => 'Ipamorelin',
            'slug' => 'ipamorelin',
            'is_active' => true,
        ]);
        $post = EducationPost::create([
            'title' => 'Ipamorelin',
            'slug' => 'ipamorelin',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'Ipamorelin overview stays.',
            'faqs' => $stored,
        ]);

        $response = $this->get('/encyclopedia/ipamorelin');
        $response->assertOk();
        $response->assertSee('Ipamorelin overview stays.', false);
        $response->assertDontSee('FAQPage', false);
        $response->assertDontSee($stored[0]['question'], false);
        $response->assertDontSee($stored[0]['answer'], false);
        $this->assertNull($this->faqPage($response->getContent()));

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 0)
            ->where('seo.schema', function ($schema) {
                foreach ($schema as $block) {
                    $this->assertNotSame('FAQPage', is_array($block) ? ($block['@type'] ?? null) : null);
                }

                return true;
            })
        );

        $this->assertSame($stored, $post->fresh()->faqs);
    }

    public function test_encyclopedia_without_faq_emits_no_faqpage_and_no_faq_section(): void
    {
        $category = ProductCategory::create([
            'name' => 'No Faq Sample',
            'slug' => 'no-faq-sample',
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => 'No Faq Sample',
            'slug' => 'no-faq-sample',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'Overview paragraph stays.',
            'faqs' => [],
        ]);

        $response = $this->get('/encyclopedia/no-faq-sample');
        $response->assertOk();
        $response->assertDontSee('FAQPage', false);
        $response->assertSee('Overview paragraph stays.', false);
        $this->assertNull($this->faqPage($response->getContent()));

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 0)
            ->where('seo.schema', function ($schema) {
                foreach ($schema as $block) {
                    $this->assertNotSame('FAQPage', is_array($block) ? ($block['@type'] ?? null) : null);
                }

                return true;
            })
        );
    }

    public function test_responses_do_not_contain_removed_getfaqs_dosing_strings(): void
    {
        $category = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'BPC-157',
            'is_active' => true,
        ]);
        EducationPost::create([
            'title' => 'BPC-157',
            'slug' => 'BPC-157',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'Catalog label only.',
            'faqs' => [],
        ]);

        Blog::create([
            'title' => 'Plain note',
            'slug' => 'plain-note',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'content' => '<p>Catalog label only.</p>',
        ]);

        foreach (['/encyclopedia/BPC-157', '/blog/plain-note'] as $path) {
            $response = $this->get($path);
            $response->assertOk();
            foreach (self::REMOVED_DOSING_STRINGS as $needle) {
                $response->assertDontSee($needle, false);
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function faqPage(string $html): ?array
    {
        preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches);
        foreach ($matches[1] as $json) {
            $data = json_decode($json, true);
            if (is_array($data) && ($data['@type'] ?? null) === 'FAQPage') {
                return $data;
            }
        }

        return null;
    }
}
