<?php

namespace App\Support;

/**
 * Neutral title and meta for /compare/{slug}.
 *
 * app.blade.php appends " — Peptidemap" to the document <title> only.
 * og:title and twitter:title stay the unsuffixed title, and the social
 * descriptions match the meta description.
 *
 * $perMg is true only when the page actually shows a price-per-mg table
 * (retatrutide). $perMl is true only for bacteriostatic water, which
 * compares vial size and listed price per mL. Other compounds compare
 * vial size and listed price.
 * An empty catalog uses "{Compound} Vendor Comparison" and does not
 * promise a per-mg figure.
 *
 * Length limits are on the rendered strings: 60 for the full <title>,
 * 155 for the meta. A long compound name falls back to a shorter
 * pattern. "{Compound} Prices" is tried before an ellipsis so a blend
 * name stays whole. Copy never includes a dollar amount, a coupon
 * claim, or a "verified" claim.
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

    private const TRIM_CHARS = " \t-/+&,|·";

    /**
     * @return array{title: string, description: string, og_title: string, og_description: string}
     */
    public static function forCompound(string $compound, int $vendorCount, bool $perMg = false, bool $perMl = false): array
    {
        $title = self::title($compound, $vendorCount, $perMg);
        $description = self::description($compound, $vendorCount, $perMg, $perMl);

        return [
            'title' => $title,
            'description' => $description,
            'og_title' => $title,
            'og_description' => $description,
        ];
    }

    public static function title(string $compound, int $vendorCount, bool $perMg = false): string
    {
        $compound = trim($compound);
        $count = max(0, $vendorCount);

        if ($count === 0) {
            return self::fit("{$compound} Vendor Comparison", $compound, ' Vendor Comparison');
        }

        $n = (string) $count;
        $vendors = self::vendorLabel($count, true);
        $candidates = $perMg
            ? [
                "{$compound} Price per mg: {$n} {$vendors} Compared",
                "{$compound} Prices: {$n} {$vendors}",
                "{$compound} Price Comparison",
                "{$compound} Prices",
            ]
            : [
                "{$compound} Prices: {$n} {$vendors} Compared",
                "{$compound} Prices: {$n} {$vendors}",
                "{$compound} Prices",
            ];

        foreach ($candidates as $candidate) {
            if (self::chars($candidate.self::SITE_SUFFIX) <= self::TITLE_LIMIT) {
                return $candidate;
            }
        }

        return self::fit($candidates[array_key_last($candidates)], $compound, ' Prices');
    }

    public static function renderedTitle(string $compound, int $vendorCount, bool $perMg = false): string
    {
        return self::title($compound, $vendorCount, $perMg).self::SITE_SUFFIX;
    }

    public static function description(string $compound, int $vendorCount, bool $perMg = false, bool $perMl = false): string
    {
        $compound = trim($compound);
        $count = max(0, $vendorCount);

        if ($count === 0) {
            return self::emptyDescription($compound);
        }

        $n = (string) $count;
        $vendors = self::vendorLabel($count, false);
        $basis = $perMl
            ? 'vial size and listed price per mL'
            : ($perMg ? 'vial price and price per mg' : 'vial size and listed price');

        $candidates = [
            "Compare {$compound} research listings from {$n} {$vendors} by {$basis}. Catalog data only: RUO, and Peptidemap sells nothing.",
            "Compare {$compound} listings from {$n} {$vendors} by {$basis}. Catalog data only: RUO, and Peptidemap sells nothing.",
            "Compare {$compound} listings from {$n} {$vendors} by {$basis}. RUO only. Peptidemap sells nothing.",
        ];

        foreach ($candidates as $candidate) {
            if (self::chars($candidate) <= self::DESCRIPTION_LIMIT) {
                return $candidate;
            }
        }

        return self::fitDescription($compound, $n, $vendors, $basis, $perMg, $perMl);
    }

    private static function emptyDescription(string $compound): string
    {
        $candidates = [
            "There are no priced {$compound} listings on Peptidemap right now. Catalog data only: RUO, and Peptidemap sells nothing.",
            "There are no priced {$compound} listings on Peptidemap right now. Peptidemap sells nothing.",
        ];

        foreach ($candidates as $candidate) {
            if (self::chars($candidate) <= self::DESCRIPTION_LIMIT) {
                return $candidate;
            }
        }

        $prefix = 'There are no priced ';
        $tail = ' listings on Peptidemap right now.';
        $budget = self::DESCRIPTION_LIMIT - self::chars($prefix.$tail);

        return $prefix.self::shortenTo($compound, $budget).$tail;
    }

    private static function fitDescription(string $compound, string $countLabel, string $vendors, string $basis, bool $perMg, bool $perMl = false): string
    {
        $prefix = 'Compare ';
        $bases = $perMl
            ? [$basis, 'price per mL']
            : ($perMg
                ? [$basis, 'price per mg']
                : [$basis, 'listed price']);

        foreach ($bases as $shortBasis) {
            $tail = " listings from {$countLabel} {$vendors} by {$shortBasis}. RUO. Peptidemap sells nothing.";
            $budget = self::DESCRIPTION_LIMIT - self::chars($prefix.$tail);
            if ($budget >= 4) {
                return $prefix.self::shortenTo($compound, $budget).$tail;
            }
        }

        $tail = " listings from {$countLabel} {$vendors}. RUO. Peptidemap sells nothing.";
        $budget = self::DESCRIPTION_LIMIT - self::chars($prefix.$tail);

        return $prefix.self::shortenTo($compound, max(1, $budget)).$tail;
    }

    private static function fit(string $candidate, string $compound, string $tail): string
    {
        if (self::chars($candidate.self::SITE_SUFFIX) <= self::TITLE_LIMIT) {
            return $candidate;
        }

        $budget = self::TITLE_LIMIT - self::chars($tail.self::SITE_SUFFIX);

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
