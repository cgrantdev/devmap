<?php

namespace App\Support;

/**
 * Neutral title and meta for a product page.
 *
 * app.blade.php does not append the site name when the stored title
 * already contains it, so the stored title is the full <title>.
 * The rendered title stays within 60 characters. The meta stays within
 * 155 and does not include a price, a coupon percentage, or a buy,
 * best, or cheapest claim.
 */
class ProductPageSeo
{
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 155;

    public const SITE_SUFFIX = ComparePageSeo::SITE_SUFFIX;

    private const TRIM_CHARS = " \t-/+&,|·";

    public static function title(string $product, ?string $vendor = null): string
    {
        $product = self::label($product, 'Product');
        $vendor = trim((string) $vendor);

        $candidates = $vendor !== ''
            ? [
                "{$product} — {$vendor} Listing",
                "{$product} — {$vendor}",
                "{$product} Listing",
                $product,
            ]
            : [
                "{$product} Listing",
                $product,
            ];

        foreach ($candidates as $candidate) {
            if (self::chars($candidate.self::SITE_SUFFIX) <= self::TITLE_LIMIT) {
                return $candidate.self::SITE_SUFFIX;
            }
        }

        return self::shortenTo($product, self::TITLE_LIMIT - self::chars(self::SITE_SUFFIX)).self::SITE_SUFFIX;
    }

    public static function description(string $product, ?string $vendor = null, int $vendorCount = 0): string
    {
        $product = self::label($product, 'This product');
        $vendor = trim((string) $vendor);
        $count = max(0, $vendorCount);
        $from = $vendor !== '' ? " from {$vendor}" : '';
        $candidates = [];

        if ($count > 0) {
            $word = $count === 1 ? 'vendor' : 'vendors';
            $candidates[] = "{$product}{$from}. Compare listings from {$count} {$word}. Catalog data only: RUO, and Peptidemap sells nothing.";
        }

        $candidates[] = "{$product}{$from}. Compare research peptide listings. Catalog data only: RUO, and Peptidemap sells nothing.";
        $candidates[] = "Compare {$product} listings. Catalog data only: RUO, and Peptidemap sells nothing.";
        $candidates[] = "Compare {$product} listings. RUO. Peptidemap sells nothing.";

        foreach ($candidates as $candidate) {
            if (self::chars($candidate) <= self::DESCRIPTION_LIMIT) {
                return $candidate;
            }
        }

        $prefix = 'Compare ';
        $tail = ' listings. RUO. Peptidemap sells nothing.';
        $budget = self::DESCRIPTION_LIMIT - self::chars($prefix.$tail);

        return $prefix.self::shortenTo($product, max(1, $budget)).$tail;
    }

    private static function label(string $value, string $fallback): string
    {
        $value = trim($value);

        return $value !== '' ? $value : $fallback;
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
