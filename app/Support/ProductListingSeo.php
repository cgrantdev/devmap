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

    public const DESCRIPTION_MIN = 140;

    public static function title(string $compound, int $vendorCount): string
    {
        $compound = trim($compound);
        $count = max(0, $vendorCount);
        $n = (string) $count;
        $suffix = self::SITE_SUFFIX;
        $blend = self::isBlend($compound);

        $candidates = [self::primaryTitle($compound, $count, $n, $blend)];
        if ($count >= 2) {
            $candidates[] = "{$compound}: {$n} Research Vendors";
        }
        $short = self::shortName($compound);
        if ($short !== $compound) {
            $candidates[] = self::primaryTitle($short, $count, $n, $blend);
            if ($count >= 2) {
                $candidates[] = "{$short}: {$n} Research Vendors";
            }
        }
        $candidates[] = $compound;

        foreach ($candidates as $candidate) {
            $full = $candidate.$suffix;
            if (self::chars($full) <= self::TITLE_LIMIT) {
                return $full;
            }
        }

        // Keep the suffix so app.blade.php does not append it again and
        // push the rendered <title> past 60 characters.
        return self::trimName($compound, self::TITLE_LIMIT - self::chars($suffix)).$suffix;
    }

    /**
     * Picks the first template between DESCRIPTION_MIN and
     * DESCRIPTION_LIMIT. Every template ends "Peptidemap sells nothing."
     * Only a name too long for the shortest template is trimmed.
     */
    public static function description(string $compound, int $vendorCount): string
    {
        $compound = trim($compound);
        $count = max(0, $vendorCount);
        $n = (string) $count;
        $blend = self::isBlend($compound);

        $fallback = null;
        foreach (self::metaTemplates($count, $n, $blend) as [$prefix, $tail]) {
            $meta = $prefix.$compound.$tail;
            $length = self::chars($meta);
            if ($length >= self::DESCRIPTION_MIN && $length <= self::DESCRIPTION_LIMIT) {
                return $meta;
            }
            if ($length <= self::DESCRIPTION_LIMIT && $fallback === null) {
                $fallback = $meta;
            }
        }
        if ($fallback !== null) {
            return $fallback;
        }

        $templates = self::metaTemplates($count, $n, $blend);
        [$prefix, $tail] = end($templates);

        $meta = $prefix.self::trimName($compound, self::DESCRIPTION_LIMIT - self::chars($prefix.$tail)).$tail;

        return str_replace('….', '…', $meta);
    }

    public static function isBlend(string $compound): bool
    {
        return (bool) preg_match('#\s(?:—|/)\s|\bblend\b#iu', $compound);
    }

    /**
     * Title fallback name. "KLOW — GHK-Cu/…" becomes "KLOW". A " / "
     * blend keeps both compounds and only drops a trailing "Blend", so
     * "CJC-1295 / Ipamorelin Blend" never shortens to "CJC-1295" and
     * collides with the single-compound page.
     */
    private static function shortName(string $compound): string
    {
        $at = mb_strpos($compound, ' — ');
        if ($at !== false && $at > 0) {
            return trim(mb_substr($compound, 0, $at));
        }
        $stripped = trim((string) preg_replace('/\s+blend$/iu', '', $compound));

        return $stripped !== '' ? $stripped : $compound;
    }

    private static function primaryTitle(string $compound, int $count, string $n, bool $blend): string
    {
        if ($count >= 2) {
            return "{$compound}: {$n} Research Vendors Listed";
        }
        if ($count === 1) {
            return "{$compound}: 1 Research Vendor Listed";
        }

        return $blend ? "{$compound}: Research Blend Profile" : "{$compound}: Research Compound Profile";
    }

    /**
     * Longest first. Adjacent templates differ by at most 15
     * characters, so any name up to the longest fit lands in 140–155.
     *
     * @return list<array{0: string, 1: string}>
     */
    private static function metaTemplates(int $count, string $n, bool $blend): array
    {
        $tails = ['. Research use only (RUO); Peptidemap sells nothing.', '. Research use only; Peptidemap sells nothing.'];
        if ($count >= 2) {
            $prefix = "Compare {$n} research vendors listing ";
            $middles = [
                ': vial sizes, product formats, catalog names and vendor details side by side',
                ': vial sizes, product formats, catalog names and vendor details',
                ': vial sizes, product formats and vendor details side by side',
                ': vial sizes, vendor details and listings side by side',
                ': vial sizes and vendor details side by side',
                ', with vial sizes and vendor details',
                ', with vendor details',
                '',
            ];
        } elseif ($count === 1) {
            $prefix = '';
            $middles = [
                ' has 1 research vendor listed on Peptidemap. See its vial sizes, product formats, catalog names and vendor details',
                ' has 1 research vendor listed on Peptidemap. See vial sizes, product formats and vendor details',
                ' has 1 research vendor listed on Peptidemap. See vial sizes and vendor details',
                ' has 1 research vendor listed on Peptidemap. See vendor details',
                ' has 1 research vendor listed on Peptidemap',
                ': 1 research vendor listed',
            ];
        } else {
            $kind = $blend ? 'blend' : 'compound';
            $prefix = '';
            $middles = [
                ": {$kind} profile. No research vendors currently list it on Peptidemap; vendor listings and vial sizes appear here when they do",
                ": {$kind} profile. No research vendors currently list it on Peptidemap; vendor listings appear here when they do",
                ": {$kind} profile. No research vendors currently list it on Peptidemap; listings appear here when they do",
                ": {$kind} profile. No research vendors list it on Peptidemap yet; listings appear here when they do",
                ": {$kind} profile. No research vendors list it yet; listings appear here when they do",
                ": {$kind} profile. No research vendors currently list it on Peptidemap",
                ": {$kind} profile. No research vendors list it yet",
                ": {$kind} profile, not yet listed",
                ": {$kind} profile",
            ];
        }

        $templates = [];
        foreach ($middles as $middle) {
            foreach ($tails as $tail) {
                $templates[] = [$prefix, $middle.$tail];
            }
        }

        return $templates;
    }

    /**
     * Cuts at a word boundary and marks the cut with "…". A name with
     * no space in its first half is cut mid-token, still with "…".
     */
    private static function trimName(string $name, int $budget): string
    {
        if ($budget < 2) {
            return '…';
        }
        if (self::chars($name) <= $budget) {
            return $name;
        }

        $keep = $budget - 1;
        $slice = mb_substr($name, 0, $keep);
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space >= (int) floor($keep / 2)) {
            $slice = mb_substr($slice, 0, $space);
        }
        $slice = (string) preg_replace('/[\s\-–—\/+&,|·:]+$/u', '', $slice);
        if ($slice === '') {
            $slice = mb_substr($name, 0, $keep);
        }

        return $slice.'…';
    }

    private static function chars(string $value): int
    {
        return mb_strlen($value);
    }
}
