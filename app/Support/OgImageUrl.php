<?php

namespace App\Support;

/**
 * Public /og/* URLs. The route only serves a lowercase slug, so every
 * emitter folds case here and keeps the ?v= cache revision.
 */
class OgImageUrl
{
    public static function compound(string $slug, string $version): string
    {
        return self::withVersion('og.compound', $slug, $version);
    }

    public static function brand(string $slug, string $version): string
    {
        return self::withVersion('og.brand', $slug, $version);
    }

    /**
     * Fold a generated /og/compound|brand/{slug}.png URL to a lowercase slug.
     * Other image URLs, including the ?v= query, are left as they are.
     */
    /** Section card (home, products, vendors, deals, compare, calculator, research, encyclopedia). */
    public static function page(string $key): string
    {
        return route('og.page', ['key' => $key]).'?v='.OgImageRevision::COPY;
    }

    public static function coupon(string $slug): string
    {
        return route('og.coupon', ['slug' => strtolower($slug)]).'?v='.OgImageRevision::COPY;
    }

    public static function calculator(string $slug): string
    {
        return route('og.calculator', ['slug' => strtolower($slug)]).'?v='.OgImageRevision::COPY;
    }

    public static function fold(string $url): string
    {
        $folded = preg_replace_callback(
            '#(/og/(?:compound|brand)/)([A-Za-z0-9-]+)(\.png)#',
            static fn (array $match): string => $match[1].strtolower($match[2]).$match[3],
            $url
        );

        return is_string($folded) ? $folded : $url;
    }

    private static function withVersion(string $route, string $slug, string $version): string
    {
        return route($route, ['slug' => strtolower($slug)]).'?v='.$version;
    }
}
