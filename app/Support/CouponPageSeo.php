<?php

namespace App\Support;

/**
 * Default title, meta description and H1 for /coupon/{slug}. The coupon
 * page uses these unless the SEO agency has set its own (Coupon SEO);
 * the agency editor shows them as placeholders.
 */
class CouponPageSeo
{
    public static function percentOff(mixed $raw): ?int
    {
        return $raw ? (int) round((float) $raw) : null;
    }

    public static function defaultTitle(string $brand, string $code, ?int $percentOff): string
    {
        return $percentOff
            ? "{$brand} Coupon Code {$code} — {$percentOff}% Off"
            : "{$brand} Coupon Code {$code}";
    }

    public static function defaultDescription(string $brand, string $code, ?int $percentOff, int $productCount): string
    {
        return $percentOff
            ? "Peptidemap code {$code}. Save {$percentOff}% at {$brand}. "
              ."Code supplied by {$brand}. Applies across {$productCount} products."
            : "Peptidemap code {$code}. "
              ."Code supplied by {$brand}. Applies across {$productCount} products.";
    }

    public static function defaultH1(string $brand): string
    {
        return "{$brand} Coupon Code";
    }
}
