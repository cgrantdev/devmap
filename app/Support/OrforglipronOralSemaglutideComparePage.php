<?php

namespace App\Support;

/**
 * Public URL for the orforglipron vs oral semaglutide educational
 * compare page. The article is stored as a blog row so the publisher,
 * FAQ JSON-LD, and cover path stay on the existing sync path. Oral
 * semaglutide is not a product category, so this is not a vendor
 * price pair. The stored slug is not the public path: that slug is
 * already the ACHIEVE-3 blog.
 */
class OrforglipronOralSemaglutideComparePage
{
    public const BLOG_SLUG = 'orforglipron-vs-oral-semaglutide-compare';

    public const PUBLIC_SLUG = 'orforglipron-vs-oral-semaglutide';

    public const PUBLIC_PATH = '/compare/orforglipron-vs-oral-semaglutide';

    public static function canonicalUrl(): string
    {
        return 'https://peptidemap.com'.self::PUBLIC_PATH;
    }

    public static function isBlogSlug(?string $slug): bool
    {
        return strtolower(trim((string) $slug)) === self::BLOG_SLUG;
    }

    public static function isCanonicalPublicSlug(?string $slug): bool
    {
        return strtolower(trim((string) $slug)) === self::PUBLIC_SLUG;
    }

    /**
     * The public slug and the alphabetical reverse. The reverse is not
     * a second page. CompareSlug does not reorder before this check.
     */
    public static function matchesRequestSlug(?string $slug): bool
    {
        $slug = strtolower(trim((string) $slug));

        return $slug === self::PUBLIC_SLUG
            || $slug === 'oral-semaglutide-vs-orforglipron';
    }
}
