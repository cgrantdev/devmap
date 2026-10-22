<?php

namespace App\Support;

/**
 * Site-wide default description.
 *
 * The live value is the settings.site_description row (Admin → Settings),
 * not a migration. When that row is empty or matches the product
 * claim/promo filter, the public fallback below is what pages and
 * the Inertia payload use. The stored row is not rewritten.
 */
class SiteCopy
{
    public const DESCRIPTION = 'Compare research-use-only (RUO) peptide vendor listings and prices. Catalog data only; Peptidemap sells nothing.';

    public static function description(?string $stored = null): string
    {
        $stored = trim((string) $stored);
        if ($stored === '' || ProductPageSeo::hasClaim($stored)) {
            return self::DESCRIPTION;
        }

        return $stored;
    }
}
