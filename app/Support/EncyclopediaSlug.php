<?php

namespace App\Support;

/**
 * /encyclopedia/{slug} is one path segment. A category slug that contains
 * "/" ("Selank/Semax", "BPC-157 / TB500 / Cartalax") is split by the router
 * and 404s, so it must not be advertised as an encyclopedia loc.
 * Spaces are fine: "Vitamin B12" is one segment and already returns 200.
 */
class EncyclopediaSlug
{
    public static function isResolvable(?string $slug): bool
    {
        $slug = (string) $slug;

        return $slug !== '' && ! str_contains($slug, '/');
    }

    /**
     * Public encyclopedia path for a stored slug, or null when that slug
     * cannot be served (slash blends have no encyclopedia page).
     */
    public static function path(?string $slug): ?string
    {
        if (! self::isResolvable($slug)) {
            return null;
        }

        return '/encyclopedia/'.$slug;
    }
}
