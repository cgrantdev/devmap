<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Encyclopedia post lookup that follows the page, not education_posts.slug.
 *
 * ProductCategory::educationPost() is a hasOne on product_category_id, and
 * EncyclopediaController::showArticle renders that row. Slug lookup can hit
 * a different row (the legacy unlinked EducationPostSeeder 'bpc-157' post)
 * or no row at all.
 *
 * Exactly one category and exactly one linked post are required. Any other
 * count is reported and nothing is returned for writing. A legacy unlinked
 * row is described and never selected.
 */
final class EncyclopediaPostMatch
{
    /**
     * @param  list<string>  $messages
     * @param  array{categories: int, posts: int, slug_matches: int}  $counts
     */
    public function __construct(
        public readonly ?EducationPost $post,
        public readonly string $outcome,
        public readonly array $counts,
        public readonly array $messages,
    ) {}

    public static function find(string $slug, ?Command $command, string $label): self
    {
        $needle = mb_strtolower(trim($slug));

        /** @var Collection<int, ProductCategory> $categories */
        $categories = ProductCategory::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', [$needle])
            ->orderBy('id')
            ->get();

        $categoryCount = $categories->count();

        /** @var Collection<int, EducationPost> $posts */
        $posts = collect();
        if ($categoryCount === 1) {
            $posts = EducationPost::query()
                ->where('product_category_id', $categories->first()->id)
                ->orderBy('id')
                ->get();
        } elseif ($categoryCount > 1) {
            $posts = EducationPost::query()
                ->whereIn('product_category_id', $categories->modelKeys())
                ->orderBy('id')
                ->get();
        }

        $postCount = $categoryCount === 0 ? 0 : $posts->count();

        /** @var Collection<int, EducationPost> $slugMatches */
        $slugMatches = EducationPost::query()
            ->whereRaw('LOWER(TRIM(slug)) = ?', [$needle])
            ->orderBy('id')
            ->get();

        $outcome = 'matched';
        $post = null;
        if ($categoryCount !== 1 || $postCount !== 1) {
            $outcome = ($categoryCount > 1 || $postCount > 1) ? 'collision' : 'missing';
        } else {
            $post = $posts->first();
        }

        $line = sprintf(
            '%s matched-count: %s outcome=%s category_count=%d post_count=%d slug_match_count=%d categories=%s posts=%s slug_matches=%s',
            $label,
            $slug,
            $outcome,
            $categoryCount,
            $postCount,
            $slugMatches->count(),
            self::describeCategories($categories),
            self::describePosts($categoryCount === 0 ? collect() : $posts),
            self::describePosts($slugMatches),
        );

        $messages = [$line];
        self::emit($line, $outcome === 'matched' ? 'info' : 'error', $command);

        foreach ($slugMatches as $row) {
            if ($row->product_category_id !== null) {
                continue;
            }
            $dump = sprintf(
                '%s LEGACY unlinked education_post id=%d slug=[%s] product_category_id=null status=%s regulatory_subsections=%s key_points=%s',
                $label,
                $row->id,
                $row->slug,
                $row->status,
                json_encode($row->regulatory_subsections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                json_encode($row->key_points, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
            $messages[] = $dump;
            self::emit($dump, 'warn', $command);
        }

        return new self($post, $outcome === 'matched' ? 'ok' : $outcome, [
            'categories' => $categoryCount,
            'posts' => $postCount,
            'slug_matches' => $slugMatches->count(),
        ], $messages);
    }

    /**
     * @param  Collection<int, ProductCategory>  $categories
     */
    private static function describeCategories(Collection $categories): string
    {
        $parts = $categories->map(fn (ProductCategory $category) => 'id='.$category->id.' slug=['.$category->slug.']')->all();

        return $parts === [] ? '[]' : '['.implode('; ', $parts).']';
    }

    /**
     * @param  Collection<int, EducationPost>  $posts
     */
    private static function describePosts(Collection $posts): string
    {
        $parts = $posts->map(function (EducationPost $post) {
            $categoryId = $post->product_category_id === null ? 'null' : (string) $post->product_category_id;

            return 'id='.$post->id.' slug=['.$post->slug.'] product_category_id='.$categoryId;
        })->all();

        return $parts === [] ? '[]' : '['.implode('; ', $parts).']';
    }

    private static function emit(string $message, string $level, ?Command $command): void
    {
        if ($level === 'info') {
            Log::info($message);
            $command?->info($message);
        } elseif ($level === 'error') {
            Log::warning($message);
            $command?->error($message);
        } else {
            Log::warning($message);
            $command?->warn($message);
        }

        fwrite(STDERR, $message.PHP_EOL);
    }
}
