<?php

namespace App\Content;

use RuntimeException;

/**
 * Fails content migrations and seeds when a post that appears on a listing
 * card has no real cover. Blog cards read blogs.image. Guide cards read cover.
 */
class ListingImageGuard
{
    public static function assertPresent(string $kind, string $slug, ?string $image): void
    {
        if ($image === null || trim($image) === '') {
            $field = $kind === 'guide' ? 'cover' : 'blogs.image';
            throw new RuntimeException(
                "Listing {$kind} [{$slug}] is missing {$field}. Cards would render a placeholder."
            );
        }
    }

    public static function assertArticleSpecific(string $kind, string $slug, ?string $image): void
    {
        self::assertPresent($kind, $slug, $image);

        if (self::isGenericPlaceholder((string) $image)) {
            throw new RuntimeException(
                "Listing {$kind} [{$slug}] uses a generic placeholder ({$image}). Use an article-specific cover."
            );
        }

        if (str_starts_with((string) $image, '/')) {
            $path = public_path(ltrim((string) $image, '/'));
            if (! is_file($path)) {
                throw new RuntimeException(
                    "Listing {$kind} [{$slug}] cover file is missing: {$path}"
                );
            }
        }
    }

    public static function isGenericPlaceholder(string $image): bool
    {
        $value = strtolower(trim($image));

        return str_contains($value, 'og-default')
            || str_contains($value, '/images/blogs/1.jpg')
            || $value === '1.jpg';
    }
}
