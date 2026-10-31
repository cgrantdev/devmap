<?php

namespace App\Support;

/**
 * Title and meta for /product/{slug} compound listing pages.
 *
 * {X} is the H1 name source, CompoundDisplay::label(), before the
 * visible heading uppercases it. Semaglutide, Tirzepatide, and
 * Retatrutide keep their raw names here. The visible H1 still shows
 * GLP1-S, GLP2-T, and GLP3-R.
 *
 * The suffix matches product detail pages. app.blade.php does not
 * append the site name again when the title already contains it, so
 * this string is the full <title>, the og:title, and the twitter:title.
 * Length limits are on those rendered strings.
 */
class ProductListingSeo
{
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 155;

    public const SITE_SUFFIX = ComparePageSeo::SITE_SUFFIX;

    /**
     * Same slug check SitemapController uses to skip the 8 categories
     * whose stored slug contains "/" or whitespace. Those rows 404 at
     * the route and keep the previous title and meta until the slug is
     * cleaned.
     */
    public static function slugIsRouteSafe(?string $slug): bool
    {
        return (bool) preg_match('#^[A-Za-z0-9._\-]+$#', (string) $slug);
    }

    /**
     * Broken slugs and the uncategorized bucket keep the previous title
     * and meta. uncategorized is a real route, so the slug check alone
     * does not exclude it.
     */
    public static function usesVendorTemplate(?string $slug): bool
    {
        if (strtolower(trim((string) $slug)) === 'uncategorized') {
            return false;
        }

        return self::slugIsRouteSafe($slug);
    }

    public static function compoundName(?string $raw): string
    {
        $raw = trim((string) $raw);
        $display = CompoundDisplay::label($raw) ?? $raw;
        if (in_array($display, ['GLP1-S', 'GLP2-T', 'GLP3-R'], true)) {
            return $raw;
        }

        return $display;
    }

    public static function title(string $compound, int $vendorCount): string
    {
        $compound = trim($compound);
        $count = max(0, $vendorCount);
        $n = (string) $count;
        $suffix = self::SITE_SUFFIX;

        $primary = self::primaryTitle($compound, $count, $n);
        $candidates = [$primary, "{$compound}: {$n} Research Vendors"];
        $short = self::shortName($compound);
        if ($short !== $compound) {
            $candidates[] = self::primaryTitle($short, $count, $n);
            $candidates[] = "{$short}: {$n} Research Vendors";
        }
        $candidates[] = $compound;

        foreach ($candidates as $candidate) {
            $full = $candidate.$suffix;
            if (self::chars($full) <= self::TITLE_LIMIT) {
                return $full;
            }
        }

        return ProductPageSeo::limitTitle($compound.$suffix);
    }

    public static function description(string $compound, int $vendorCount): string
    {
        $compound = trim($compound);
        $count = max(0, $vendorCount);
        $n = (string) $count;

        foreach ([false, true] as $compact) {
            [$prefix, $tail] = self::metaParts($count, $n, $compact);
            $meta = $prefix.$compound.$tail;
            if (self::chars($meta) <= self::DESCRIPTION_LIMIT) {
                return $meta;
            }
            if ($compact) {
                return $prefix.self::trimName($compound, self::DESCRIPTION_LIMIT - self::chars($prefix.$tail)).$tail;
            }
        }

        return $compound;
    }

    /**
     * Blend display names use " — " or " / " before the rest of the
     * label. The short token is only a title fallback; the meta keeps
     * the full name.
     */
    private static function shortName(string $compound): string
    {
        $cut = null;
        foreach ([' — ', ' / '] as $separator) {
            $at = mb_strpos($compound, $separator);
            if ($at !== false && ($cut === null || $at < $cut)) {
                $cut = $at;
            }
        }
        if ($cut === null || $cut < 1) {
            return $compound;
        }

        return trim(mb_substr($compound, 0, $cut));
    }

    private static function primaryTitle(string $compound, int $count, string $n): string
    {
        if ($count >= 2) {
            return "{$compound}: Compare {$n} Research Vendors";
        }
        if ($count === 1) {
            return "{$compound}: 1 Research Vendor Listed";
        }

        return "{$compound}: Research Compound Profile";
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function metaParts(int $count, string $n, bool $compact): array
    {
        if ($count >= 2) {
            return $compact
                ? ["Compare {$n} research vendors listing ", '. Research use only; Peptidemap sells nothing.']
                : ["Compare {$n} research vendors listing ", ': vial sizes, vendor details and listings side by side. Research use only; Peptidemap sells nothing.'];
        }
        if ($count === 1) {
            return $compact
                ? ['', ' has 1 research vendor listed on Peptidemap. Research use only; Peptidemap sells nothing.']
                : ['', ' has 1 research vendor listed on Peptidemap. See vial sizes and vendor details. Research use only; Peptidemap sells nothing.'];
        }

        return $compact
            ? ['', ' research-use-only profile on Peptidemap. No vendors list it yet. We sell nothing.']
            : ['', ' research-use-only profile. No vendors currently list it on Peptidemap; compared listings appear here when they do. We sell nothing.'];
    }

    private static function trimName(string $name, int $budget): string
    {
        if ($budget < 1) {
            return '';
        }
        if (self::chars($name) <= $budget) {
            return $name;
        }

        $slice = rtrim(mb_substr($name, 0, $budget));
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space > 0) {
            $slice = rtrim(mb_substr($slice, 0, $space));
        }
        $slice = (string) preg_replace('/[\s\-–—\/+&,|·:]+$/u', '', $slice);

        return $slice !== '' ? $slice : rtrim(mb_substr($name, 0, $budget));
    }

    private static function chars(string $value): int
    {
        return mb_strlen($value);
    }
}
