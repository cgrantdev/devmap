<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\EducationPost;
use Illuminate\Console\Command;

/**
 * Audit which blogs + encyclopedia articles are missing images
 * (both the hero `image` and the SEO `seo_og_image`). Reports counts
 * + lists the biggest offenders so Colin / Claude can prioritize
 * backfill.
 */
class BlogImageAudit extends Command
{
    protected $signature = 'blogs:image-audit';
    protected $description = 'Report blog + encyclopedia article image coverage.';

    public function handle(): int
    {
        $this->line('=== BLOGS ===');
        $blogs = Blog::query()->where('status', 'published')->get();
        $blogTotal = $blogs->count();
        $blogsNoImg = $blogs->whereNull('image')->count() + $blogs->where('image', '')->count();
        $blogsNoOg = $blogs->whereNull('seo_og_image')->count() + $blogs->where('seo_og_image', '')->count();
        $this->line("  Total published:    {$blogTotal}");
        $this->line("  Missing hero image: {$blogsNoImg}");
        $this->line("  Missing seo_og_image: {$blogsNoOg}");
        $this->newLine();
        $this->line('  Published blogs missing hero image:');
        foreach ($blogs->filter(fn ($b) => empty($b->image)) as $b) {
            $this->line("    [{$b->id}] {$b->slug} — {$b->title}");
        }

        $this->newLine();
        $this->line('=== ENCYCLOPEDIA ARTICLES (education_posts) ===');
        $eps = EducationPost::query()
            ->where('status', 'published')
            ->where('show_in_encyclopedia', true)
            ->get();
        $epTotal = $eps->count();
        $this->line("  Total published:    {$epTotal}");
        $this->line("  Missing seo_og_image: " . $eps->whereNull('seo_og_image')->count());

        return self::SUCCESS;
    }
}
