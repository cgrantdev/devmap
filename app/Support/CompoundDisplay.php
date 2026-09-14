<?php

namespace App\Support;

/**
 * Compound display-name overrides. Split from raw category names so we
 * can show a friendlier (or, for the GLP entries, vendor-safer) label
 * on the visible page while keeping the raw name in URLs, meta title,
 * meta description, and schema.org fields for search engines.
 *
 * The GLP pseudonyms (Colin Sep 14) reduce optics scrutiny on vendors
 * whose listings we surface — they get pinged by regulators for showing
 * "Semaglutide" on public product pages, so peptidemap.com displays
 * "GLP1-S" instead. Google still sees Semaglutide in URL slugs, meta
 * title, and schema.org so search rank is preserved.
 */
class CompoundDisplay
{
    private const MAP = [
        'BPC-157 / TB-500' => 'BPC-157 / TB-500 Blend',
        'CJC-1295 / Ipamorelin' => 'CJC-1295 / Ipamorelin Blend',
        'GLOW' => 'GLOW — GHK-Cu/BPC-157/TB-500',
        'KLOW' => 'KLOW — GHK-Cu/BPC-157/TB-500/KPV',
        'Semaglutide' => 'GLP1-S',
        'Tirzepatide' => 'GLP2-T',
        'Retatrutide' => 'GLP3-R',
    ];

    public static function label(?string $rawName): ?string
    {
        if ($rawName === null) return null;
        return self::MAP[$rawName] ?? $rawName;
    }
}
