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

        if ($count >= 2) {
            $primary = "{$compound}: Compare {$n} Research Vendors";
        } elseif ($count === 1) {
            $primary = "{$compound}: 1 Research Vendor Listed";
        } else {
            $primary = "{$compound}: Research Compound Profile";
        }

        foreach ([$primary, "{$compound}: {$n} Research Vendors", $compound] as $candidate) {
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

        if ($count >= 2) {
            $meta = "Compare {$n} research vendors listing {$compound}: vial sizes, vendor details and listings side by side. Research use only; Peptidemap sells nothing.";
        } elseif ($count === 1) {
            $meta = "{$compound} has 1 research vendor listed on Peptidemap. See vial sizes and vendor details. Research use only; Peptidemap sells nothing.";
        } else {
            $meta = "{$compound} research-use-only profile. No vendors currently list it on Peptidemap; compared listings appear here when they do. We sell nothing.";
        }

        return self::fitMeta($meta);
    }

    private static function fitMeta(string $meta): string
    {
        $meta = trim(preg_replace('/\s+/u', ' ', $meta) ?? '');
        if ($meta === '' || self::chars($meta) <= self::DESCRIPTION_LIMIT) {
            return $meta;
        }

        $slice = rtrim(mb_substr($meta, 0, self::DESCRIPTION_LIMIT));
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space > 0) {
            $slice = rtrim(mb_substr($slice, 0, $space));
        }

        return $slice;
    }

    private static function chars(string $value): int
    {
        return mb_strlen($value);
    }
}
