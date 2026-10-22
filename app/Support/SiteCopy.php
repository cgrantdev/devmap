<?php

namespace App\Support;

/**
 * Site-wide default description.
 *
 * The live value is the settings.site_description row (Admin → Settings),
 * not a migration. When that row is empty or still says "verified", the
 * public fallback below is what pages and the Inertia payload use.
 */
class SiteCopy
{
    public const DESCRIPTION = 'Compare research-use-only (RUO) peptide vendor listings and prices. Catalog data only; Peptidemap sells nothing.';

    public static function description(?string $stored = null): string
    {
        $stored = trim((string) $stored);
        if ($stored === '' || preg_match('/\bverified\b/i', $stored)) {
            return self::DESCRIPTION;
        }

        return $stored;
    }
}
