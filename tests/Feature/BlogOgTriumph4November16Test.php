<?php

namespace Tests\Feature;

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use Database\Seeders\BlogOgTriumph4November16Seeder as Og;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2026_11_16_230000: retatrutide-side-effects-trial-data og_description
 * names the NEJM knee-OA paper (TRIUMPH-4 + TRIUMPH-1-OA). Meta Optimizer
 * signed off on this wording.
 */
class BlogOgTriumph4November16Test extends TestCase
{
    use RefreshDatabase;

    public function test_target_is_the_catalog_value_within_limits_and_neutral(): void
    {
        $entry = collect((new \ReflectionMethod(EducationalContentPublisher::class, 'catalog'))->invoke(null))
            ->firstWhere('slug', Og::SLUG);
        $this->assertSame(Og::TARGET, $entry['og_description']);
        $this->assertSame(Og::SIBLINGS['seo_description'], $entry['seo_description']);
        $this->assertSame(Og::SIBLINGS['seo_og_title'], $entry['og_title']);
        $this->assertLessThanOrEqual(155, strlen(Og::TARGET));
        $this->assertStringContainsString('NEJM knee-OA paper (TRIUMPH-4 + TRIUMPH-1-OA)', Og::TARGET);
        $this->assertDoesNotMatchRegularExpression('/\b(price|prices|pricing|coupon|buy|cheap|deal|best|better|superior)\b|\$|\bmg\b/i', Og::TARGET);
        $this->assertFileExists(database_path('migrations/2026_11_16_230000_blog_og_triumph_4_side_effects_november_16.php'));
    }

    public function test_live_value_is_updated_once_and_other_columns_stay(): void
    {
        $blog = $this->liveRow();
        $before = $blog->getAttributes();

        $seeder = new Og;
        $seeder->run();
        $this->assertSame('updated', $seeder->results[Og::SLUG.'.seo_og_description']);
        $after = $blog->fresh()->getAttributes();
        $this->assertSame(Og::TARGET, $after['seo_og_description']);
        unset($before['seo_og_description'], $before['updated_at'], $after['seo_og_description'], $after['updated_at']);
        $this->assertSame($before, $after);

        $again = new Og;
        $again->run();
        $this->assertSame('already current', $again->results[Og::SLUG.'.seo_og_description']);
    }

    public function test_admin_edit_is_not_overwritten(): void
    {
        $blog = $this->liveRow();
        $blog->seo_og_description = 'Admin wording.';
        $blog->saveQuietly();
        $seeder = new Og;
        $seeder->run();
        $this->assertSame('guard mismatch — nothing written', $seeder->results[Og::SLUG.'.seo_og_description']);
        $this->assertSame('Admin wording.', $blog->fresh()->seo_og_description);
    }

    public function test_sibling_drift_skips(): void
    {
        $blog = $this->liveRow();
        $blog->seo_description = 'Edited by the meta team.';
        $blog->saveQuietly();
        $seeder = new Og;
        $seeder->run();
        $this->assertSame('column drift: seo_description', $seeder->results[Og::SLUG.'.seo_og_description']);
        $this->assertSame(Og::OLD, $blog->fresh()->seo_og_description);
    }

    public function test_duplicate_slug_is_skipped(): void
    {
        $blog = $this->liveRow();
        $copy = $blog->replicate();
        $copy->slug = ' '.strtoupper(Og::SLUG).' ';
        $copy->saveQuietly();
        $seeder = new Og;
        $seeder->run();
        $this->assertSame('duplicate blog slug', $seeder->results[Og::SLUG.'.seo_og_description']);
        $this->assertSame(Og::OLD, $blog->fresh()->seo_og_description);
    }

    private function liveRow(): Blog
    {
        $blog = Blog::query()->where('slug', Og::SLUG)->firstOrFail();
        $blog->seo_og_description = Og::OLD;
        $blog->saveQuietly();

        return $blog->fresh();
    }
}
