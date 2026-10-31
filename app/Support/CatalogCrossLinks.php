<?php

namespace App\Support;

use App\Http\Controllers\Frontend\CompareController;
use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\EducationPost;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Schema;

/**
 * Internal links among /product/{slug}, /encyclopedia/{slug}, and
 * /compare/{slug} (including indexable /compare/{a}-vs-{b} pages).
 *
 * A compound is one ProductCategory. Products belong to it through
 * product_category_id — the catalog compound assigned at ingest.
 * CategoryAlias substring matching is not used here. That matcher is
 * longest-keyword-in-the-title, so a blend title that contains a
 * component keyword would be treated as the component. A blend page
 * links only to the blend category's own listing, encyclopedia entry,
 * and compare pages.
 *
 * Targets that 404, redirect to a different category, are noindex, or
 * are not a published encyclopedia entry are omitted. When two active
 * categories collapse to one compare or encyclopedia URL, the category
 * that does not own that URL is left unlinked and reported by
 * ambiguities().
 */
class CatalogCrossLinks
{
    public const CAP = 6;

    /**
     * @param  list<'product'|'encyclopedia'|'compare'>  $kinds
     * @return array{groups: list<array{label: string, links: list<array{href: string, label: string, kind: string}>}>}
     */
    public static function forCategory(ProductCategory $category, array $kinds): array
    {
        $published = in_array('encyclopedia', $kinds, true)
            ? self::publishedEncyclopediaIds()
            : [];
        $listed = in_array('compare', $kinds, true)
            ? self::listedCategoryIds()
            : [];
        $groups = [];

        if (in_array('encyclopedia', $kinds, true)) {
            $target = self::encyclopediaTarget($category, $published);
            if ($target['href'] !== null) {
                $groups[] = [
                    'label' => 'Encyclopedia',
                    'links' => [self::link(
                        $target['href'],
                        $category->name.' encyclopedia entry',
                        'encyclopedia'
                    )],
                ];
            }
        }

        if (in_array('product', $kinds, true)) {
            $target = self::productTarget($category);
            if ($target['href'] !== null) {
                $label = 'Products listed for '.$category->name;
                $groups[] = [
                    'label' => $label,
                    'links' => [self::link($target['href'], $label, 'product')],
                ];
            }
        }

        if (in_array('compare', $kinds, true)) {
            $links = self::cap(self::compareLinks($category, $listed));
            if ($links !== []) {
                $groups[] = [
                    'label' => 'Compare listings',
                    'links' => $links,
                ];
            }
        }

        return ['groups' => $groups];
    }

    /**
     * Encyclopedia entries and product listings for the two compounds on
     * a head-to-head page. Ordered by stored slug, then capped.
     *
     * @return array{groups: list<array{label: string, links: list<array{href: string, label: string, kind: string}>}>}
     */
    public static function forPair(ProductCategory $a, ProductCategory $b): array
    {
        $published = self::publishedEncyclopediaIds();
        $categories = [$a, $b];
        usort($categories, function (ProductCategory $left, ProductCategory $right): int {
            $bySlug = strcmp((string) $left->slug, (string) $right->slug);

            return $bySlug !== 0 ? $bySlug : ($left->id <=> $right->id);
        });

        $products = [];
        $encyclopedias = [];
        foreach ($categories as $category) {
            $product = self::productTarget($category);
            if ($product['href'] !== null) {
                $label = 'Products listed for '.$category->name;
                $products[$product['href']] = self::link($product['href'], $label, 'product');
            }
            $entry = self::encyclopediaTarget($category, $published);
            if ($entry['href'] !== null) {
                $encyclopedias[$entry['href']] = self::link(
                    $entry['href'],
                    $category->name.' encyclopedia entry',
                    'encyclopedia'
                );
            }
        }

        $groups = [];
        $productLinks = self::cap(array_values($products));
        if ($productLinks !== []) {
            $groups[] = [
                'label' => 'Products listed',
                'links' => $productLinks,
            ];
        }
        $entryLinks = self::cap(array_values($encyclopedias));
        if ($entryLinks !== []) {
            $groups[] = [
                'label' => 'Encyclopedia',
                'links' => $entryLinks,
            ];
        }

        return ['groups' => $groups];
    }

    /**
     * @param  array{groups: list<array{label: string, links: list<array{href: string, label: string, kind: string}>}>}  $payload
     * @return array{groups: list<array{label: string, links: list<array{href: string, label: string, kind: string}>}>}
     */
    public static function share(array $payload): array
    {
        view()->share('catalogCrossLinks', $payload);

        return $payload;
    }

    /**
     * Active categories whose compare or encyclopedia URL is owned by a
     * different category. Those targets are not linked.
     *
     * @return list<array{category_id: int, slug: string, name: string, kind: string, reason: string}>
     */
    public static function ambiguities(): array
    {
        $published = self::publishedEncyclopediaIds();
        $listed = self::listedCategoryIds();
        $notes = [];

        foreach (ProductCategory::query()->where('is_active', true)->orderBy('id')->get() as $category) {
            foreach ([
                'compare' => self::compareTarget($category, $listed),
                'encyclopedia' => self::encyclopediaTarget($category, $published),
            ] as $kind => $target) {
                if ($target['ambiguous'] === null) {
                    continue;
                }
                $notes[] = [
                    'category_id' => (int) $category->id,
                    'slug' => (string) $category->slug,
                    'name' => (string) $category->name,
                    'kind' => $kind,
                    'reason' => $target['ambiguous'],
                ];
            }
        }

        return $notes;
    }

    /**
     * How many live pages of each type gain at least one outbound catalog
     * link. Counts follow the same gates as the page render.
     *
     * @return array{product: int, encyclopedia: int, compare: int, compare_vs: int}
     */
    public static function coverage(): array
    {
        $published = self::publishedEncyclopediaIds();
        $listed = self::listedCategoryIds();
        $counts = [
            'product' => 0,
            'encyclopedia' => 0,
            'compare' => 0,
            'compare_vs' => 0,
        ];

        foreach (ProductCategory::query()->where('is_active', true)->orderBy('id')->get() as $category) {
            if (self::productTarget($category)['href'] !== null
                && self::hasLinks(self::forCategory($category, ['encyclopedia', 'compare']))) {
                $counts['product']++;
            }
            if (self::encyclopediaTarget($category, $published)['href'] !== null
                && self::hasLinks(self::forCategory($category, ['product', 'compare']))) {
                $counts['encyclopedia']++;
            }
            if (self::compareTarget($category, $listed)['href'] !== null
                && self::hasLinks(self::forCategory($category, ['encyclopedia', 'product']))) {
                $counts['compare']++;
            }
        }

        $seenPairs = [];
        foreach (self::indexableVsPages() as $page) {
            if (isset($seenPairs[$page['slug']])) {
                continue;
            }
            $seenPairs[$page['slug']] = true;
            if (self::hasLinks(self::forPair($page['a'], $page['b']))) {
                $counts['compare_vs']++;
            }
        }

        return $counts;
    }

    /**
     * @param  array{groups: list<array{label: string, links: list<array<string, string>>}>}  $payload
     */
    private static function hasLinks(array $payload): bool
    {
        foreach ($payload['groups'] as $group) {
            if ($group['links'] !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, true>  $listedIds
     * @return list<array{href: string, label: string, kind: string}>
     */
    private static function compareLinks(ProductCategory $category, array $listedIds): array
    {
        $links = [];
        $own = self::compareTarget($category, $listedIds);
        if ($own['href'] !== null) {
            $links[] = self::link(
                $own['href'],
                'Compare '.$category->name.' listings',
                'compare'
            );
        }

        $vs = [];
        foreach (self::indexableVsPages() as $page) {
            $featured = (int) $page['a']->id === (int) $category->id
                || (int) $page['b']->id === (int) $category->id;
            if (! $featured) {
                continue;
            }
            $vs[] = self::link(
                '/compare/'.$page['slug'],
                'Compare '.$page['a']->name.' and '.$page['b']->name.' listings',
                'vs'
            );
        }
        usort($vs, fn (array $left, array $right): int => strcmp($left['href'], $right['href']));

        return array_merge($links, $vs);
    }

    /**
     * @return array{href: ?string, ambiguous: ?string}
     */
    private static function productTarget(ProductCategory $category): array
    {
        if (! $category->is_active || ! ProductListingSeo::usesVendorTemplate($category->slug)) {
            return ['href' => null, 'ambiguous' => null];
        }

        return ['href' => '/product/'.$category->slug, 'ambiguous' => null];
    }

    /**
     * @param  array<int, true>  $publishedIds
     * @return array{href: ?string, ambiguous: ?string}
     */
    private static function encyclopediaTarget(ProductCategory $category, array $publishedIds): array
    {
        if (! $category->is_active) {
            return ['href' => null, 'ambiguous' => null];
        }

        $path = EncyclopediaSlug::path($category->slug);
        if ($path === null) {
            return ['href' => null, 'ambiguous' => null];
        }

        $publicSlug = substr($path, strlen('/encyclopedia/'));
        $resolved = self::resolveEncyclopediaCategory($publicSlug);
        if (! $resolved || (int) $resolved->id !== (int) $category->id) {
            if ($resolved && (int) $resolved->id !== (int) $category->id) {
                return [
                    'href' => null,
                    'ambiguous' => $path.' is served by '.$resolved->slug.', not '.$category->slug,
                ];
            }

            return ['href' => null, 'ambiguous' => null];
        }

        if (! isset($publishedIds[(int) $category->id])) {
            return ['href' => null, 'ambiguous' => null];
        }

        return ['href' => $path, 'ambiguous' => null];
    }

    /**
     * @param  array<int, true>  $listedIds
     * @return array{href: ?string, ambiguous: ?string}
     */
    private static function compareTarget(ProductCategory $category, array $listedIds): array
    {
        if (! $category->is_active) {
            return ['href' => null, 'ambiguous' => null];
        }

        $canonical = CompareSlug::canonical($category->slug);
        if ($canonical === null || str_contains($canonical, '-vs-')) {
            return ['href' => null, 'ambiguous' => null];
        }

        $resolved = CompareController::resolveCompareSide($canonical);
        $owner = ProductCategory::findForCompareSlug($resolved);
        if (! $owner || (int) $owner->id !== (int) $category->id) {
            $reason = $owner
                ? '/compare/'.$resolved.' is served by '.$owner->slug.', not '.$category->slug
                : '/compare/'.$resolved.' does not resolve to one category';

            return ['href' => null, 'ambiguous' => $reason];
        }

        if (! isset($listedIds[(int) $category->id])) {
            return ['href' => null, 'ambiguous' => null];
        }

        return ['href' => '/compare/'.$resolved, 'ambiguous' => null];
    }

    private static function resolveEncyclopediaCategory(string $publicSlug): ?ProductCategory
    {
        $category = ProductCategory::query()
            ->where('slug', $publicSlug)
            ->where('is_active', true)
            ->first();
        if ($category) {
            return $category;
        }

        return ProductCategory::findForPublicSlug(EncyclopediaSlug::publicSlug($publicSlug) ?? $publicSlug);
    }

    /**
     * @return array<int, true>
     */
    private static function publishedEncyclopediaIds(): array
    {
        if (! Schema::hasTable('education_posts') || ! Schema::hasColumn('education_posts', 'status')) {
            return [];
        }

        return array_fill_keys(
            EducationPost::query()
                ->where('status', 'published')
                ->whereNotNull('product_category_id')
                ->pluck('product_category_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            true
        );
    }

    /**
     * Categories with at least one priced, visible, active product.
     * Same price rule as the compare page and the sitemap, so an empty
     * noindex compare URL is not linked.
     *
     * @return array<int, true>
     */
    private static function listedCategoryIds(): array
    {
        return array_fill_keys(
            Product::visible()
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->where('discount_price', '>', 0)
                        ->orWhere(function ($inner) {
                            $inner->whereNull('discount_price')->where('price', '>', 0);
                        });
                })
                ->whereNotNull('product_category_id')
                ->distinct()
                ->pluck('product_category_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            true
        );
    }

    /**
     * @return list<array{slug: string, a: ProductCategory, b: ProductCategory}>
     */
    private static function indexableVsPages(): array
    {
        $pages = [];
        foreach (self::candidateVsSlugs() as $slug) {
            $live = CompareController::liveIndexableVsSlug($slug);
            if ($live === null || isset($pages[$live])) {
                continue;
            }
            $pos = strpos($live, '-vs-');
            if ($pos === false) {
                continue;
            }
            $a = ProductCategory::findForCompareSlug(substr($live, 0, $pos));
            $b = ProductCategory::findForCompareSlug(substr($live, $pos + 4));
            if (! $a || ! $b) {
                continue;
            }
            $pages[$live] = [
                'slug' => $live,
                'a' => $a,
                'b' => $b,
            ];
        }
        ksort($pages, SORT_STRING);

        return array_values($pages);
    }

    /**
     * @return list<string>
     */
    private static function candidateVsSlugs(): array
    {
        $set = [];
        foreach (VsPairIndexing::sitemapSlugs() as $slug) {
            $set[$slug] = true;
        }
        foreach ([
            KlowCompareCopy::SLUG_GLOW_KLOW,
            KlowCompareCopy::SLUG_GHK_KLOW,
            BpcTb500CompareCopy::SLUG,
            ElamipretideMotsCCompareFaqs::SLUG,
            EloralintideTirzepatideCompareCopy::SLUG,
            RetatrutideTirzepatideCompareCopy::SLUG,
        ] as $slug) {
            $canonical = VsPairIndexing::canonicalPair($slug);
            if ($canonical !== null) {
                $set[$canonical] = true;
            }
        }
        foreach (self::publishedHeadToHeadSlugs() as $slug) {
            $set[$slug] = true;
        }

        $slugs = array_keys($set);
        sort($slugs, SORT_STRING);

        return $slugs;
    }

    /**
     * @return list<string>
     */
    private static function publishedHeadToHeadSlugs(): array
    {
        $out = [];
        foreach ([Blog::class, EducationalGuide::class] as $model) {
            $table = (new $model)->getTable();
            if (
                ! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'content')
                || ! Schema::hasColumn($table, 'status')
                || ! Schema::hasColumn($table, 'slug')
            ) {
                continue;
            }

            $rows = $model::query()
                ->where('status', 'published')
                ->whereNotNull('slug')
                ->where('slug', 'like', '%-vs-%')
                ->whereNotNull('content')
                ->whereRaw('TRIM(content) <> ?', [''])
                ->pluck('slug');

            foreach ($rows as $slug) {
                $bare = preg_replace('/-evidence$/', '', (string) $slug) ?? (string) $slug;
                $pair = VsPairIndexing::canonicalPair($bare);
                if ($pair !== null) {
                    $out[$pair] = true;
                }
            }
        }

        return array_keys($out);
    }

    /**
     * @param  list<array{href: string, label: string, kind: string}>  $links
     * @return list<array{href: string, label: string, kind: string}>
     */
    private static function cap(array $links): array
    {
        return array_slice(array_values($links), 0, self::CAP);
    }

    /**
     * @return array{href: string, label: string, kind: string}
     */
    private static function link(string $href, string $label, string $kind): array
    {
        return [
            'href' => $href,
            'label' => $label,
            'kind' => $kind,
        ];
    }
}
