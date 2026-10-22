<?php

namespace App\Support;

/**
 * Neutral title and meta for /compare/{a}-vs-{b}.
 *
 * Display names are passed in already. Retatrutide, tirzepatide, and
 * semaglutide stay on their CompoundDisplay code names (GLP3-R, GLP2-T,
 * GLP1-S). This class only shortens for length.
 *
 * app.blade.php appends " — Peptidemap" to the document <title> only.
 * The stored title omits that suffix. The rendered title stays within
 * 60 characters. Featured-pair metas land between 140 and 155
 * characters and do not name a dollar amount, a coupon, or a best vendor.
 *
 * KlowCompareCopy::seoOverride replaces title, H1, and description for
 * a canonical pair slug when the display labels would not fit.
 */
class VsPageSeo
{
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 155;

    public const SITE_SUFFIX = ComparePageSeo::SITE_SUFFIX;

    private const TRIM_CHARS = " \t-/+&,|·";

    /**
     * @return array{title: string, description: string, og_title: string, og_description: string, h1: string}
     */
    public static function forPair(string $a, string $b, ?string $slug = null): array
    {
        $override = KlowCompareCopy::seoOverride($slug);
        $title = $override['title'] ?? self::title($a, $b);
        $description = $override['description'] ?? self::description($a, $b);
        $h1 = $override['h1'] ?? self::pair($a, $b);

        return [
            'title' => $title,
            'description' => $description,
            'og_title' => $title,
            'og_description' => $description,
            'h1' => $h1,
        ];
    }

    public static function title(string $a, string $b): string
    {
        $pair = self::pair($a, $b);
        $candidates = [
            "{$pair} — Vendor & Price Comparison",
            "{$pair} — Price Comparison",
            "{$pair} Comparison",
            $pair,
        ];

        foreach ($candidates as $candidate) {
            if (self::chars($candidate.self::SITE_SUFFIX) <= self::TITLE_LIMIT) {
                return $candidate;
            }
        }

        return self::fitPair(trim($a), trim($b));
    }

    public static function renderedTitle(string $a, string $b): string
    {
        return self::title($a, $b).self::SITE_SUFFIX;
    }

    public static function description(string $a, string $b): string
    {
        $pair = self::pair($a, $b);
        $candidates = [
            "{$pair} side-by-side: mechanism, half-life, and research context. Compare vendor listings. Catalog data only: RUO, and Peptidemap sells nothing.",
            // Bremelanotide vs Kisspeptin is 164 on the vendor-listings
            // line and 157 on the catalog line below. The following line
            // is 144, still inside 140–155. Shorter pairs keep an earlier line.
            "{$pair} side-by-side: mechanism, half-life, and research context. Compare listings. Catalog data only: RUO, and Peptidemap sells nothing.",
            "{$pair} side-by-side: mechanism, half-life, and research context. Compare listings. RUO data only; Peptidemap sells nothing.",
            "{$pair} side-by-side: mechanism, half-life, and research context. Catalog data only: RUO, and Peptidemap sells nothing.",
            "{$pair} side-by-side: mechanism, half-life, and research context. RUO only. Peptidemap sells nothing.",
            "Compare {$pair} listings. Catalog data only: RUO, and Peptidemap sells nothing.",
            "Compare {$pair} listings. RUO. Peptidemap sells nothing.",
        ];

        foreach ($candidates as $candidate) {
            if (self::chars($candidate) <= self::DESCRIPTION_LIMIT) {
                return $candidate;
            }
        }

        return self::fitDescription(trim($a), trim($b));
    }

    private static function pair(string $a, string $b): string
    {
        return trim($a).' vs '.trim($b);
    }

    private static function fitPair(string $a, string $b): string
    {
        [$left, $right] = self::shortenPair(
            $a,
            $b,
            self::TITLE_LIMIT - self::chars(' vs '.self::SITE_SUFFIX)
        );

        return $left.' vs '.$right;
    }

    private static function fitDescription(string $a, string $b): string
    {
        $prefix = 'Compare ';
        $tail = ' listings. RUO. Peptidemap sells nothing.';
        [$left, $right] = self::shortenPair(
            $a,
            $b,
            self::DESCRIPTION_LIMIT - self::chars($prefix.' vs '.$tail)
        );

        return $prefix.$left.' vs '.$right.$tail;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function shortenPair(string $a, string $b, int $budget): array
    {
        $budget = max(3, $budget);
        if (self::chars($a.' vs '.$b) <= $budget + self::chars(' vs ')) {
            return [$a, $b];
        }

        if (self::chars($a) >= self::chars($b)) {
            $keep = $b;
            $trimBudget = $budget - self::chars($keep);
            if ($trimBudget >= 4) {
                return [self::shortenTo($a, $trimBudget), $keep];
            }
        } else {
            $keep = $a;
            $trimBudget = $budget - self::chars($keep);
            if ($trimBudget >= 4) {
                return [$keep, self::shortenTo($b, $trimBudget)];
            }
        }

        $half = intdiv($budget, 2);
        $left = self::shortenTo($a, max(1, $half));

        return [$left, self::shortenTo($b, max(1, $budget - self::chars($left)))];
    }

    private static function shortenTo(string $value, int $budget): string
    {
        if ($budget < 1) {
            return '…';
        }
        if (self::chars($value) <= $budget) {
            return $value;
        }

        $ellipsis = '…';
        $keep = max(1, $budget - self::chars($ellipsis));
        $slice = self::trimSeparators(mb_substr($value, 0, $keep));
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space >= (int) floor($keep / 2)) {
            $slice = self::trimSeparators(mb_substr($slice, 0, $space));
        }
        if ($slice === '') {
            $slice = self::trimSeparators(mb_substr($value, 0, $keep));
        }
        if ($slice === '') {
            $slice = mb_substr($value, 0, $keep);
        }

        return $slice.$ellipsis;
    }

    private static function trimSeparators(string $value): string
    {
        return rtrim($value, self::TRIM_CHARS);
    }

    private static function chars(string $value): int
    {
        return mb_strlen($value);
    }
}
