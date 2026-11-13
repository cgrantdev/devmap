<?php

namespace App\Support;

use App\Models\ProductCategory;

/**
 * Nineteen category slugs held spaces, "/" or "+" until 2026-11-13
 * ("Selank/Semax", "HGH 191AA", "Tesamorlin / Ipamorelin"). Slash slugs
 * 404ed on /product/{slug}; space slugs loaded but were noindexed and
 * kept out of the sitemap. Migration 2026_11_13_160000 rewrote them to
 * hyphen slugs.
 *
 * This map keeps every old address working. The old stored slug, its
 * old compare canonical (Str::slug deleted "/", so "Selank/Semax" was
 * /compare/selanksemax) and letter-case variants all resolve to the
 * renamed row, and the controllers 301 to the new slug.
 */
final class LegacyCategorySlug
{
    /**
     * Category id => [slug before 2026-11-13, slug after].
     * Keep in step with the 2026_11_13_160000 migration.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    public const RENAMED = [
        595 => ['Tesamorlin / Ipamorelin', 'tesamorelin-ipamorelin'],
        564 => ['HGH 191AA', 'hgh-191aa'],
        596 => ['Selank/Semax', 'selank-semax'],
        591 => ['Retatrutide / Cagrilintide Blend', 'retatrutide-cagrilintide-blend'],
        547 => ['Vitamin B12', 'vitamin-b12'],
        546 => ['MIC + B12', 'mic-b12'],
        581 => ['Phosphate Buffered Saline', 'phosphate-buffered-saline'],
        577 => ['BPC-157 / TB500 / Cartalax', 'bpc-157-tb500-cartalax'],
        593 => ['Retatrutide / Tirzepatide Blend', 'retatrutide-tirzepatide-blend'],
        592 => ['Semaglutide / Cagrilintide Blend', 'semaglutide-cagrilintide-blend'],
        607 => ['AM Shred', 'am-shred'],
        605 => ['PM Matrix', 'pm-matrix'],
        594 => ['Adalank /  Adamax', 'adalank-adamax'],
        475 => ['Palmitoyl Pentapeptide-4', 'palmitoyl-pentapeptide-4'],
        580 => ['Sterile Water', 'sterile-water'],
        602 => ['Alpha-Klotho LR', 'alpha-klotho-lr'],
        601 => ['GHK-CU/BPC 157/KPV', 'ghk-cu-bpc-157-kpv'],
        588 => ['Thymosin Beta-4 Fragment 1-4', 'thymosin-beta-4-fragment-1-4'],
        587 => ['N-Acetyl Larazotide', 'n-acetyl-larazotide'],
    ];

    /** @var array<int, ?string>|null old compare canonical per id */
    private static ?array $oldCanonicals = null;

    /**
     * Active renamed row for an old slug, or null. Returns null until the
     * row actually carries its new slug, so before the migration runs the
     * old slug keeps resolving directly and nothing redirects.
     */
    public static function find(?string $requested): ?ProductCategory
    {
        $id = self::idFor($requested);
        if ($id === null) {
            return null;
        }

        $category = ProductCategory::query()->where('is_active', true)->find($id);
        if (! $category || (string) $category->slug !== self::RENAMED[$id][1]) {
            return null;
        }

        return $category;
    }

    /**
     * Category id whose old slug (exact, any letter case, or its old
     * compare canonical) matches the request.
     */
    public static function idFor(?string $requested): ?int
    {
        $requested = (string) $requested;
        if (trim($requested) === '') {
            return null;
        }

        $lower = strtolower($requested);
        $canonical = CompareSlug::canonical($requested);
        self::$oldCanonicals ??= array_map(
            fn (array $pair) => CompareSlug::canonical($pair[0]),
            self::RENAMED
        );

        foreach (self::RENAMED as $id => [$old]) {
            if ($lower === strtolower($old)) {
                return $id;
            }
            if ($canonical !== null && $canonical === self::$oldCanonicals[$id]) {
                return $id;
            }
        }

        return null;
    }
}
