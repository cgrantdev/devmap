<?php

namespace App\Support;

use App\Http\Controllers\Frontend\CompareController;
use App\Models\Blog;
use App\Models\EducationalGuide;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Schema;

/**
 * Which /compare/{a}-vs-{b} pages are indexable, and which belong in
 * sitemap.xml.
 *
 * Sitemap membership is the config allowlist union FEATURED_VS_PAIRS.
 * A pair is emitted once, and only when both sides are active categories.
 * The kill switch does not add thin pairs to the sitemap.
 *
 * A pair has real editorial content when any of these is true:
 *
 * 1. It is a FEATURED_VS_PAIRS entry. Those curated pairs count.
 * 2. Pair-specific copy is rendered on the compare page. That is
 *    ElamipretideMotsCCompareFaqs for elamipretide-vs-mots-c,
 *    EloralintideTirzepatideCompareCopy for eloralintide-vs-tirzepatide,
 *    and KlowCompareCopy for
 *    glow-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv and
 *    ghk-cu-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv. canonicalPair does
 *    not resolve compare-side aliases, so the copy class is keyed on
 *    those long slugs. The short side "klow" 301s in CompareController.
 * 3. A published blog or educational guide has a non-empty body and a
 *    slug of {a}-vs-{b}, the reversed {b}-vs-{a}, or either form plus
 *    the "-evidence" suffix. Those notes are markdown in
 *    resources/content/educational and are upserted by
 *    EducationalContentPublisher. sermorelin-vs-tesamorelin is stored
 *    as the blog tesamorelin-vs-sermorelin. bpc-157-vs-tb-500 is stored
 *    as bpc-157-vs-tb-500-evidence.
 *
 * Per-compound education_posts (mechanism, half-life, overview) do not
 * count. Every auto-generated vs page can pull those in.
 *
 * The short spelling glow-vs-klow is not an allowlist entry. It 301s
 * to glow-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv, which is allowlisted
 * with ghk-cu-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv. Also not on the
 * allowlist: cjc-1295-dac-vs-no-dac (one compound's DAC construct), and
 * eloralintide-tirzepatide-vs-retatrutide (a combination note; it does
 * not back eloralintide-vs-retatrutide).
 * A published article whose slug does match a reachable pair still
 * keeps that pair indexable. Sitemap membership stays allowlist union
 * featured.
 */
class VsPairIndexing
{
    public const NOINDEX = 'noindex,follow';

    /**
     * Robots meta for a canonical vs slug, or null when the page should
     * stay indexable (no robots meta).
     */
    public static function robotsDirective(string $pair): ?string
    {
        return self::isIndexable($pair) ? null : self::NOINDEX;
    }

    public static function isIndexable(string $pair): bool
    {
        $canonical = self::canonicalPair($pair);
        if ($canonical === null) {
            return true;
        }

        // Env and config caches often store booleans as the strings
        // "true" and "false". A strict === false leaves the string "false"
        // on, so thin pages stay noindexed when the kill switch was turned off.
        if (! filter_var(config('seo.vs_noindex_thin', true), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return self::isAllowlisted($canonical)
            || self::isFeatured($canonical)
            || self::hasEditorialContent($canonical);
    }

    public static function isAllowlisted(string $pair): bool
    {
        $canonical = self::canonicalPair($pair);

        return $canonical !== null && in_array($canonical, self::allowlist(), true);
    }

    public static function isFeatured(string $pair): bool
    {
        $canonical = self::canonicalPair($pair);

        return $canonical !== null && in_array($canonical, self::featuredSlugs(), true);
    }

    /**
     * Featured pairs count. So does on-page pair copy and a published
     * head-to-head article. See the class docblock.
     */
    public static function hasEditorialContent(string $pair): bool
    {
        $canonical = self::canonicalPair($pair);
        if ($canonical === null) {
            return false;
        }

        if (self::isFeatured($canonical)) {
            return true;
        }

        if (ElamipretideMotsCCompareFaqs::forSlug($canonical) !== null) {
            return true;
        }

        if (EloralintideTirzepatideCompareCopy::forSlug($canonical) !== null) {
            return true;
        }

        // Long slugs only. canonicalPair does not rewrite "klow".
        if (KlowCompareCopy::forSlug($canonical) !== null) {
            return true;
        }

        return self::publishedHeadToHeadExists($canonical);
    }

    /**
     * Allowlist slugs, folded into canonical alphabetical order.
     *
     * @return list<string>
     */
    public static function allowlist(): array
    {
        $raw = config('seo.vs_sitemap_allowlist', []);
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $slug) {
            if (! is_string($slug)) {
                continue;
            }
            $canonical = self::canonicalPair($slug);
            if ($canonical !== null) {
                $out[$canonical] = true;
            }
        }

        $list = array_keys($out);
        sort($list, SORT_STRING);

        return $list;
    }

    /**
     * @return list<string>
     */
    public static function featuredSlugs(): array
    {
        $out = [];
        foreach (CompareController::FEATURED_VS_PAIRS as $pair) {
            $slug = self::canonicalPair($pair['a'].'-vs-'.$pair['b']);
            if ($slug !== null) {
                $out[] = $slug;
            }
        }

        return $out;
    }

    /**
     * Allowlist union featured. Both sides must be active category slugs.
     * Lowercase the stored slug: MySQL matches case-insensitively but PHP
     * array keys do not, and production is not all lowercase
     * (CJC-1295, BPC-157, TB-500, Cagrilintide, Survodutide).
     *
     * @return list<string>
     */
    public static function sitemapSlugs(): array
    {
        $active = ProductCategory::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug))
            ->flip();

        $slugs = [];
        foreach (array_merge(self::allowlist(), self::featuredSlugs()) as $slug) {
            $pos = strpos($slug, '-vs-');
            if ($pos === false) {
                continue;
            }
            $a = substr($slug, 0, $pos);
            $b = substr($slug, $pos + 4);
            if (! isset($active[$a], $active[$b])) {
                continue;
            }
            $slugs[$slug] = true;
        }

        $out = array_keys($slugs);
        sort($out, SORT_STRING);

        return $out;
    }

    /**
     * Lowercase a-vs-b with a < b. Does not rewrite compare aliases
     * (reta, pt-141, ss-31, klow); those redirects stay in CompareController.
     */
    public static function canonicalPair(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        $pos = strpos($slug, '-vs-');
        if ($pos === false) {
            return null;
        }

        $a = substr($slug, 0, $pos);
        $b = substr($slug, $pos + 4);
        if ($a === '' || $b === '' || $a === $b) {
            return null;
        }
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $a) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $b)) {
            return null;
        }
        if (strcmp($a, $b) > 0) {
            return $b.'-vs-'.$a;
        }

        return $a.'-vs-'.$b;
    }

    /**
     * Slugs a published head-to-head may use for this pair.
     *
     * @return list<string>
     */
    public static function headToHeadSlugs(string $pair): array
    {
        $canonical = self::canonicalPair($pair);
        if ($canonical === null) {
            return [];
        }

        $pos = strpos($canonical, '-vs-');
        $a = substr($canonical, 0, $pos);
        $b = substr($canonical, $pos + 4);

        return array_values(array_unique([
            $a.'-vs-'.$b,
            $b.'-vs-'.$a,
            $a.'-vs-'.$b.'-evidence',
            $b.'-vs-'.$a.'-evidence',
        ]));
    }

    private static function publishedHeadToHeadExists(string $canonical): bool
    {
        $slugs = self::headToHeadSlugs($canonical);

        return self::publishedBody(Blog::class, $slugs)
            || self::publishedBody(EducationalGuide::class, $slugs);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @param  list<string>  $slugs
     */
    private static function publishedBody(string $model, array $slugs): bool
    {
        $table = (new $model)->getTable();
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'content')
            || ! Schema::hasColumn($table, 'status')
        ) {
            return false;
        }

        return $model::query()
            ->whereIn('slug', $slugs)
            ->where('status', 'published')
            ->whereNotNull('content')
            ->whereRaw('TRIM(content) <> ?', [''])
            ->exists();
    }
}
