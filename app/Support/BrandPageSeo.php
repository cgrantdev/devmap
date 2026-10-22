<?php

namespace App\Support;

/**
 * Brand-page title. Keeps Colin's "Coupon Codes" wording and the em
 * dash before the site name. Long vendor names drop "Reviews" first,
 * then the vendor name is cut at the last space, so the rendered title
 * stays within 60 characters. "Coupon Codes" is never removed.
 */
class BrandPageSeo
{
    public const TITLE_LIMIT = 60;

    public static function title(string $vendor, string $siteName = 'Peptidemap'): string
    {
        $vendor = trim($vendor) !== '' ? trim($vendor) : 'Vendor';
        $site = trim($siteName) !== '' ? trim($siteName) : 'Peptidemap';
        $suffix = ' — '.$site;
        $withReviews = ': Coupon Codes, Reviews & Prices';
        $pricesOnly = ': Coupon Codes & Prices';

        $full = $vendor.$withReviews.$suffix;
        if (mb_strlen($full) <= self::TITLE_LIMIT) {
            return $full;
        }

        $short = $vendor.$pricesOnly.$suffix;
        if (mb_strlen($short) <= self::TITLE_LIMIT) {
            return $short;
        }

        $tail = $pricesOnly.$suffix;
        $name = self::cutAtLastSpace($vendor, self::TITLE_LIMIT - mb_strlen($tail));
        if ($name === '') {
            $name = 'Vendor';
        }

        $cut = $name.$tail;
        if (mb_strlen($cut) <= self::TITLE_LIMIT) {
            return $cut;
        }

        return self::cutAtLastSpace($cut, self::TITLE_LIMIT);
    }

    private static function cutAtLastSpace(string $value, int $limit): string
    {
        $value = trim($value);
        if ($limit < 1) {
            return '';
        }
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        $slice = rtrim(mb_substr($value, 0, $limit));
        $space = mb_strrpos($slice, ' ');
        if ($space !== false && $space > 0) {
            $slice = rtrim(mb_substr($slice, 0, $space));
        }

        return rtrim($slice, " \t-—,");
    }
}
