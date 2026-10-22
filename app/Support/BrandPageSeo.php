<?php

namespace App\Support;

/**
 * Brand-page title. Keeps the coupon / reviews / prices wording and
 * switches the site separator to an em dash. Long vendor names drop
 * phrases until the rendered title is 60 characters or fewer.
 */
class BrandPageSeo
{
    public const TITLE_LIMIT = 60;

    public static function title(string $vendor, string $siteName = 'Peptidemap'): string
    {
        $vendor = trim($vendor) !== '' ? trim($vendor) : 'Vendor';
        $site = trim($siteName) !== '' ? trim($siteName) : 'Peptidemap';
        $suffix = ' — '.$site;
        $candidates = [
            $vendor.': Coupon Codes, Reviews & Prices',
            $vendor.': Reviews & Prices',
            $vendor.': Prices',
        ];

        foreach ($candidates as $candidate) {
            if (mb_strlen($candidate.$suffix) <= self::TITLE_LIMIT) {
                return $candidate.$suffix;
            }
        }

        $tail = ': Prices'.$suffix;
        $budget = self::TITLE_LIMIT - mb_strlen($tail);
        if ($budget < 1) {
            return mb_substr($site, 0, self::TITLE_LIMIT);
        }

        return rtrim(mb_substr($vendor, 0, $budget)).$tail;
    }
}
