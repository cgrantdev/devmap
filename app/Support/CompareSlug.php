<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * /compare/{slug} only matches [a-z0-9-]+. Category slugs in the database
 * sometimes keep display casing ("BPC-157") or spaces and slashes
 * ("Vitamin B12", "BPC-157 / TB500"). Those strings 404 on the compare
 * route, so the sitemap and internal links must use this form instead.
 */
class CompareSlug
{
    public static function canonical(?string $slug): ?string
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return null;
        }

        $normalized = Str::slug($slug);
        if ($normalized === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalized)) {
            return null;
        }

        return $normalized;
    }
}
