<?php

namespace App\Support;

/**
 * Neutral title and meta for /compare/{slug}.
 *
 * app.blade.php appends " — Peptidemap" to the document <title> only.
 * og:title and twitter:title stay the unsuffixed title, and the social
 * descriptions match the meta description.
 *
 * Length limits are on the rendered strings: 60 for the full <title>,
 * 155 for the meta. A long compound name falls back to a shorter
 * pattern, then the compound name itself is shortened so the limit
 * still holds. Copy never includes a price, a coupon claim, or a
 * "verified" claim.
 */
class ComparePageSeo
{
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 155;

    /**
     * Must match the suffix app.blade.php appends: " — " plus the
     * default site name. The stored title omits it.
     */
    public const SITE_SUFFIX = ' — Peptidemap';

    /**
     * @return array{title: string, description: string, og_title: string, og_description: string}
     */
    public static function forCompound(string $compound, int $vendorCount): array
    {
        $title = self::title($compound, $vendorCount);
        $description = self::description($compound, $vendorCount);

        return [
            'title' => $title,
            'description' => $description,
            'og_title' => $title,
            'og_description' => $description,
        ];
    }

    public static function title(string $compound, int $vendorCount): string
    {
        $compound = trim($compound);
        $count = max(0, $vendorCount);
        $n = (string) $count;
        $vendors = self::vendorLabel($count, true);

        $candidates = [
            "{$compound} Price per mg: {$n} {$vendors} Compared",
            "{$compound} Prices: {$n} {$vendors}",
            "{$compound} Price Comparison",
        ];

        foreach ($candidates as $candidate) {
            if (self::chars($candidate.self::SITE_SUFFIX) <= self::TITLE_LIMIT) {
                return $candidate;
            }
        }

        $tail = ' Price Comparison';
        $budget = self::TITLE_LIMIT - self::chars($tail.self::SITE_SUFFIX);

        return self::shortenTo($compound, $budget).$tail;
    }

    public static function renderedTitle(string $compound, int $vendorCount): string
    {
        return self::title($compound, $vendorCount).self::SITE_SUFFIX;
    }

    public static function description(string $compound, int $vendorCount): string
    {
        $compound = trim($compound);
        $count = max(0, $vendorCount);
        $n = (string) $count;
        $vendors = self::vendorLabel($count, false);

        $candidates = [
            "Compare {$compound} research listings from {$n} {$vendors} by vial price and price per mg. Catalog data only: RUO, and Peptidemap sells nothing.",
            "Compare {$compound} listings from {$n} {$vendors} by vial price and price per mg. Catalog data only: RUO, and Peptidemap sells nothing.",
            "Compare {$compound} listings from {$n} {$vendors} by vial price and price per mg. RUO only. Peptidemap sells nothing.",
        ];

        foreach ($candidates as $candidate) {
            if (self::chars($candidate) <= self::DESCRIPTION_LIMIT) {
                return $candidate;
            }
        }

        $tail = " from {$n} {$vendors}, by vial price and price per mg. RUO. Peptidemap sells nothing.";
        $budget = self::DESCRIPTION_LIMIT - self::chars($tail);

        return self::shortenTo($compound, $budget).$tail;
    }

    private static function vendorLabel(int $count, bool $titleCase): string
    {
        $word = $count === 1 ? 'vendor' : 'vendors';

        return $titleCase ? ucfirst($word) : $word;
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
        $slice = rtrim(mb_substr($value, 0, $keep));
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space >= (int) floor($keep / 2)) {
            $slice = rtrim(mb_substr($slice, 0, $space));
        }
        $slice = rtrim($slice, " \t-/");
        if ($slice === '') {
            $slice = rtrim(mb_substr($value, 0, $keep));
        }

        return $slice.$ellipsis;
    }

    private static function chars(string $value): int
    {
        return mb_strlen($value);
    }
}
