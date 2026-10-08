<?php

namespace App\Support;

/**
 * /encyclopedia/{slug} is one path segment. A category slug that contains
 * "/" ("Selank/Semax", "BPC-157 / TB500 / Cartalax") is split by the router
 * and 404s, so it must not be advertised as an encyclopedia loc.
 *
 * Canonical form is the stored ProductCategory.slug, byte for byte.
 * Production is not all lowercase: retatrutide, 5-amino-1mq, and
 * slu-pp-332 are lowercase, while CJC-1295, BPC-157, TB-500, Cagrilintide,
 * and Survodutide keep mixed case. The sitemap and internal links emit
 * that stored string.
 * A request that differs only by letter case is not a second page; it
 * 301s to the stored slug. Stored slugs are not rewritten.
 *
 * The families below are the exception. Their public URL is the hyphen
 * slug, and the space, short, and letter-case aliases 301 there. Research
 * aliases use the same map (ss-31 / ss31 → elamipretide). Stored slugs
 * are not rewritten. Other space slugs stay as stored.
 */
class EncyclopediaSlug
{
    /**
     * Public hyphen slug => accepted request aliases (already normalized).
     * Case variants are folded by normalize() before lookup.
     *
     * @var array<string, list<string>>
     */
    private const FAMILIES = [
        'hgh-191aa' => ['hgh-191aa', 'hgh 191aa'],
        'vitamin-b12' => ['vitamin-b12', 'vitamin b12'],
        'phosphate-buffered-saline' => ['phosphate-buffered-saline', 'phosphate buffered saline', 'pbs'],
        'sterile-water' => ['sterile-water', 'sterile water'],
        'thymosin-beta-4-fragment-1-4' => [
            'thymosin-beta-4-fragment-1-4',
            'thymosin beta-4 fragment 1-4',
            'thymosin beta 4 fragment 1-4',
        ],
        'alpha-klotho-lr' => ['alpha-klotho-lr', 'alpha-klotho lr', 'alpha klotho lr'],
        'n-acetyl-larazotide' => ['n-acetyl-larazotide', 'n-acetyl larazotide', 'n acetyl larazotide'],
        // Live encyclopedia slug is lowercase elamipretide. SS-31 is the
        // research alias; do not advertise a separate encyclopedia page.
        'elamipretide' => ['elamipretide', 'ss-31', 'ss31'],
    ];

    public static function isResolvable(?string $slug): bool
    {
        $slug = (string) $slug;

        return $slug !== '' && ! str_contains($slug, '/');
    }

    /**
     * Hyphen canonical for a forced family, or null when this slug is not
     * one of those aliases.
     */
    public static function publicSlug(?string $slug): ?string
    {
        $key = self::normalize($slug);
        if ($key === '') {
            return null;
        }

        foreach (self::FAMILIES as $canonical => $aliases) {
            if (in_array($key, $aliases, true)) {
                return $canonical;
            }
        }

        return null;
    }

    /**
     * Stored-slug aliases that should resolve to a public hyphen URL,
     * including the public slug itself.
     *
     * @return list<string>
     */
    public static function aliasesFor(string $publicSlug): array
    {
        return self::FAMILIES[$publicSlug] ?? [$publicSlug];
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

        $public = self::publicSlug($slug);

        return '/encyclopedia/'.($public ?? $slug);
    }

    /**
     * Append the current request query string so a 301 keeps utm and
     * filter parameters. An empty query is left off.
     */
    public static function withRequestQuery(string $path): string
    {
        $query = request()->getQueryString();
        if (! is_string($query) || $query === '') {
            return $path;
        }

        return $path.(str_contains($path, '?') ? '&' : '?').$query;
    }

    public static function normalize(?string $slug): string
    {
        $slug = strtolower(trim((string) $slug));
        $slug = str_replace('_', ' ', $slug);
        $slug = preg_replace('/\s+/', ' ', $slug) ?? $slug;

        return $slug;
    }
}
