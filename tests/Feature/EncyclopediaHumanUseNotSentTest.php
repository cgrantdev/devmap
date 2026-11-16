<?php

namespace Tests\Feature;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Human Use & Evidence" section has been hidden site-wide since the
 * 7 Oct 2026 decision (EncyclopediaArticleDetail.vue). Its text must not
 * reach the Inertia payload or the page source either. The columns stay
 * in the database for the admin CMS.
 */
class EncyclopediaHumanUseNotSentTest extends TestCase
{
    use RefreshDatabase;

    private const INTRO_MARKER = 'HUMANUSE-INTRO-MARKER-7Q2X';

    private const SUB_TITLE_MARKER = 'HUMANUSE-SUBTITLE-MARKER-9K4P';

    private const SUB_BODY_MARKER = 'HUMANUSE-SUBBODY-MARKER-3T8M';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_human_use_text_is_absent_from_the_rendered_html_and_props(): void
    {
        $category = ProductCategory::create([
            'name' => 'Markerpeptide',
            'slug' => 'markerpeptide',
            'description' => 'Catalog description.',
            'is_active' => true,
        ]);
        $post = EducationPost::create([
            'title' => 'Markerpeptide',
            'slug' => 'markerpeptide',
            'product_category_id' => $category->id,
            'status' => 'published',
            'show_in_encyclopedia' => true,
            'overview' => 'VISIBLE-OVERVIEW-MARKER-5R1D',
            'human_use_intro' => self::INTRO_MARKER.' intro text.',
            'human_use_subsections' => [[
                'title' => self::SUB_TITLE_MARKER,
                'entries' => [['type' => 'content', 'value' => self::SUB_BODY_MARKER.' body text.']],
            ]],
        ]);

        $response = $this->get('/encyclopedia/markerpeptide');
        $response->assertOk();

        // Control: ordinary article text does reach the page source.
        $response->assertSee('VISIBLE-OVERVIEW-MARKER-5R1D', false);

        $html = (string) $response->getContent();
        foreach ([self::INTRO_MARKER, self::SUB_TITLE_MARKER, self::SUB_BODY_MARKER] as $marker) {
            $this->assertStringNotContainsString($marker, $html, $marker);
        }
        $this->assertStringNotContainsString('humanUseIntro', $html);
        $this->assertStringNotContainsString('humanUseSubsections', $html);

        $response->assertInertia(fn ($page) => $page
            ->component('Frontend/EncyclopediaArticleDetail')
            ->missing('humanUseIntro')
            ->missing('humanUseSubsections')
            ->missing('humanUseEvidence'));

        // The stored columns are untouched.
        $fresh = $post->fresh();
        $this->assertStringContainsString(self::INTRO_MARKER, (string) $fresh->human_use_intro);
        $this->assertSame(self::SUB_TITLE_MARKER, $fresh->human_use_subsections[0]['title']);
    }
}
