<?php

namespace App\Support;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Resolve the encyclopedia post EncyclopediaController::showArticle renders.
 *
 * product_categories WHERE LOWER(TRIM(slug)) = ? must be exactly one row,
 * then education_posts WHERE product_category_id = that id must be exactly
 * one row. education_posts.slug is only a diagnostic. A legacy row whose
 * product_category_id is null is logged and never returned.
 *
 * Missing and collision counts are logged and are not thrown, so a later
 * migration still runs. The sibling AU/TGA migration uses this same class.
 */
final class EncyclopediaPostMatch
{
    /**
     * @param  list<string>  $lines
     */
    private function __construct(
        public readonly string $status,
        public readonly ?ProductCategory $category,
        public readonly ?EducationPost $post,
        public readonly int $categoryCount,
        public readonly int $linkedPostCount,
        public readonly int $slugPostCount,
        public readonly ?string $warning,
        public readonly array $lines,
    ) {}

    public static function find(string $slug, ?Command $command, string $label): self
    {
        $needle = strtolower(trim($slug));

        /** @var Collection<int, ProductCategory> $categories */
        $categories = ProductCategory::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', [$needle])
            ->orderBy('id')
            ->get();

        $categoryCount = $categories->count();
        /** @var ProductCategory|null $category */
        $category = $categoryCount === 1 ? $categories->first() : null;

        /** @var Collection<int, EducationPost> $linked */
        $linked = $categoryCount === 0
            ? collect()
            : EducationPost::query()
                ->whereIn('product_category_id', $categories->pluck('id'))
                ->orderBy('id')
                ->get();
        $linkedCount = $linked->count();

        /** @var Collection<int, EducationPost> $slugPosts */
        $slugPosts = EducationPost::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', [$needle])
            ->orderBy('id')
            ->get();

        $lines = [];
        foreach ($slugPosts as $row) {
            $lines[] = sprintf(
                'diagnostic %s education_post id=%d slug=[%s] product_category_id=%s status=%s',
                $label,
                $row->id,
                (string) $row->slug,
                $row->product_category_id === null ? 'null' : (string) $row->product_category_id,
                (string) $row->status,
            );
        }

        $lines[] = sprintf(
            'matched-count %s %s: categories=%d linked_posts=%d slug_posts=%d',
            $label,
            $slug,
            $categoryCount,
            $linkedCount,
            $slugPosts->count(),
        );

        foreach ($lines as $line) {
            Log::info($line);
            $command?->info($line);
            self::emit(STDOUT, $line);
        }

        $warning = null;
        $post = null;
        if ($categoryCount !== 1 || $linkedCount !== 1) {
            $status = ($categoryCount > 1 || $linkedCount > 1) ? 'collision' : 'missing';
            $category = null;
            $warning = sprintf(
                '%s %s: %s (categories %d, linked posts %d, slug-matching posts %d). %s',
                $label,
                $slug,
                $status,
                $categoryCount,
                $linkedCount,
                $slugPosts->count(),
                self::stored($categories, $linked, $slugPosts),
            );
            Log::warning($warning);
            $command?->error($warning);
            self::emit(STDERR, $warning);
        } else {
            $status = 'ok';
            /** @var EducationPost $post */
            $post = $linked->first();
        }

        return new self(
            $status,
            $status === 'ok' ? $category : null,
            $status === 'ok' ? $post : null,
            $categoryCount,
            $linkedCount,
            $slugPosts->count(),
            $warning,
            $lines,
        );
    }

    /**
     * @param  Collection<int, ProductCategory>  $categories
     * @param  Collection<int, EducationPost>  $linked
     * @param  Collection<int, EducationPost>  $slugPosts
     */
    private static function stored(Collection $categories, Collection $linked, Collection $slugPosts): string
    {
        $categoryText = $categories
            ->map(fn (ProductCategory $category) => 'id='.$category->id.' slug=['.$category->slug.']')
            ->implode('; ');
        $linkedText = $linked
            ->map(fn (EducationPost $post) => 'id='.$post->id.' slug=['.$post->slug.'] product_category_id='.(string) $post->product_category_id.' status='.(string) $post->status)
            ->implode('; ');
        $slugText = $slugPosts
            ->map(fn (EducationPost $post) => 'id='.$post->id.' slug=['.$post->slug.'] product_category_id='.($post->product_category_id === null ? 'null' : (string) $post->product_category_id).' status='.(string) $post->status)
            ->implode('; ');

        return 'Stored categories: '.($categoryText !== '' ? $categoryText : 'none')
            .'. Stored linked posts: '.($linkedText !== '' ? $linkedText : 'none')
            .'. Stored slug-matching posts: '.($slugText !== '' ? $slugText : 'none')
            .'.';
    }

    /**
     * @param  resource  $stream
     */
    private static function emit($stream, string $line): void
    {
        if (is_resource($stream)) {
            fwrite($stream, $line.PHP_EOL);
        }
    }
}
