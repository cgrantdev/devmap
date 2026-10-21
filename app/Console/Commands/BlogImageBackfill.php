<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Console\Command;

/**
 * Backfill seo_og_image for blogs + encyclopedia articles.
 *
 * Encyclopedia articles already have an auto-rendered compound OG
 * at /og/compound/{slug}.png — we just need to point seo_og_image
 * at that URL so Google and social share scrapers find it.
 *
 * Blogs: fall back to /images/og-default-v8.png when nothing else
 * fits. If the blog is linked to a product_category, prefer that
 * compound's OG card so the share preview matches the topic.
 *
 * Idempotent and non-destructive. Won't overwrite anything an admin
 * manually set.
 */
class BlogImageBackfill extends Command
{
    protected $signature = 'blogs:backfill-images {--dry-run : Report counts without writing}';
    protected $description = 'Set seo_og_image on blogs + encyclopedia articles that are missing one.';

    public function handle(): int
    {
        $base = 'https://peptidemap.com';
        $default = $base . '/images/og-default-v8.png';
        $isDry = $this->option('dry-run');

        $epFixed = 0;
        $epSkipped = 0;
        $eps = EducationPost::query()
            ->where('status', 'published')
            ->where('show_in_encyclopedia', true)
            ->whereNull('seo_og_image')
            ->get();
        foreach ($eps as $ep) {
            $cat = ProductCategory::find($ep->product_category_id);
            if (!$cat || !$cat->slug) {
                $epSkipped++;
                continue;
            }
            $url = $base . '/og/compound/' . $cat->slug . '.png';
            if (!$isDry) $ep->forceFill(['seo_og_image' => $url])->save();
            $epFixed++;
        }

        $blogFixed = 0;
        $blogs = Blog::query()->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('seo_og_image')->orWhere('seo_og_image', ''))
            ->get();
        foreach ($blogs as $b) {
            // Prefer a compound card when the blog is linked to a category.
            if ($b->product_category_id) {
                $cat = ProductCategory::find($b->product_category_id);
                if ($cat && $cat->slug) {
                    $url = $base . '/og/compound/' . $cat->slug . '.png';
                } else {
                    $url = $default;
                }
            } else {
                $url = $default;
            }
            if (!$isDry) $b->forceFill(['seo_og_image' => $url])->save();
            $blogFixed++;
        }

        // Also set `image` (hero) on blogs when it's missing — same
        // fallback. Hero on the listing page is a different field but
        // same visual target.
        $heroFixed = 0;
        $heroBlogs = Blog::query()->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('image')->orWhere('image', ''))
            ->get();
        foreach ($heroBlogs as $b) {
            if ($b->product_category_id) {
                $cat = ProductCategory::find($b->product_category_id);
                $url = ($cat && $cat->slug)
                    ? $base . '/og/compound/' . $cat->slug . '.png'
                    : $default;
            } else {
                $url = $default;
            }
            if (!$isDry) $b->forceFill(['image' => $url])->save();
            $heroFixed++;
        }

        $tag = $isDry ? '[dry-run] ' : '';
        $this->info("{$tag}Encyclopedia OG filled: {$epFixed} (skipped {$epSkipped} with no category slug).");
        $this->info("{$tag}Blog OG filled: {$blogFixed}");
        $this->info("{$tag}Blog hero filled: {$heroFixed}");
        return self::SUCCESS;
    }
}
