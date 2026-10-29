<?php

namespace App\Support;

/**
 * Single source of truth for the peptide calculator's preset library.
 *
 * Consumed by:
 *  - routes/web.php /calculator and /calculator/{slug} — passes presets
 *    to the Vue component + resolves a slug into prefilled state and
 *    compound-specific SEO.
 *  - resources/js/Pages/Frontend/Calculator.vue — renders the grouped
 *    dropdown + applies a prefilled preset on mount.
 *
 * Each entry carries:
 *  - slug: lowercase-dashed, used in /calculator/{slug}
 *  - name: display label in the dropdown + hero title
 *  - group: category for the grouped dropdown
 *  - mg / water / dose: the preset values (mg of peptide, mL of BAC
 *    water, mcg starting aliquot)
 *  - common search queries this compound page targets (seo helper)
 *
 * Adding a compound here automatically makes /calculator/{slug} live
 * and appears in the dropdown next deploy.
 */
class CalculatorPresets
{
    /**
     * Flat list of every preset. Grouping is derived from the `group` key.
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            // GLP-1 / Weight management
            ['slug' => 'semaglutide-3mg', 'name' => 'Semaglutide (3mg)', 'group' => 'GLP-1 / Weight', 'mg' => 3, 'water' => 1.5, 'dose' => 250],
            ['slug' => 'semaglutide-5mg', 'name' => 'Semaglutide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 500],
            ['slug' => 'semaglutide-10mg', 'name' => 'Semaglutide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 250],
            ['slug' => 'tirzepatide-5mg', 'name' => 'Tirzepatide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 2500],
            ['slug' => 'tirzepatide-10mg', 'name' => 'Tirzepatide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 2500],
            ['slug' => 'tirzepatide-15mg', 'name' => 'Tirzepatide (15mg)', 'group' => 'GLP-1 / Weight', 'mg' => 15, 'water' => 3, 'dose' => 2500],
            ['slug' => 'tirzepatide-30mg', 'name' => 'Tirzepatide (30mg)', 'group' => 'GLP-1 / Weight', 'mg' => 30, 'water' => 3, 'dose' => 2500],
            ['slug' => 'retatrutide-5mg', 'name' => 'Retatrutide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 1000],
            ['slug' => 'retatrutide-10mg', 'name' => 'Retatrutide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 1000],
            ['slug' => 'liraglutide-5mg', 'name' => 'Liraglutide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 600],
            ['slug' => 'cagrilintide-5mg', 'name' => 'Cagrilintide (5mg)', 'group' => 'GLP-1 / Weight', 'mg' => 5, 'water' => 2, 'dose' => 600],
            ['slug' => 'survodutide-10mg', 'name' => 'Survodutide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 800],
            ['slug' => 'pemvidutide-10mg', 'name' => 'Pemvidutide (10mg)', 'group' => 'GLP-1 / Weight', 'mg' => 10, 'water' => 2, 'dose' => 1000],

            // Growth hormone / GH secretagogues
            ['slug' => 'cjc-1295-no-dac-2mg', 'name' => 'CJC-1295 no DAC (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 100],
            ['slug' => 'cjc-1295-no-dac-5mg', 'name' => 'CJC-1295 no DAC (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2.5, 'dose' => 100],
            ['slug' => 'cjc-1295-dac-2mg', 'name' => 'CJC-1295 w/ DAC (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 1000],
            ['slug' => 'ipamorelin-2mg', 'name' => 'Ipamorelin (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 200],
            ['slug' => 'ipamorelin-5mg', 'name' => 'Ipamorelin (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2.5, 'dose' => 200],
            ['slug' => 'cjc-ipamorelin-blend-10mg', 'name' => 'CJC/Ipamorelin Blend (10mg)', 'group' => 'Growth / GH', 'mg' => 10, 'water' => 3, 'dose' => 300],
            ['slug' => 'tesamorelin-2mg', 'name' => 'Tesamorelin (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 2000],
            ['slug' => 'tesamorelin-5mg', 'name' => 'Tesamorelin (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2, 'dose' => 2000],
            ['slug' => 'sermorelin-2mg', 'name' => 'Sermorelin (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 200],
            ['slug' => 'sermorelin-5mg', 'name' => 'Sermorelin (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2.5, 'dose' => 300],
            ['slug' => 'hexarelin-2mg', 'name' => 'Hexarelin (2mg)', 'group' => 'Growth / GH', 'mg' => 2, 'water' => 2, 'dose' => 100],
            ['slug' => 'hexarelin-5mg', 'name' => 'Hexarelin (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2.5, 'dose' => 200],
            ['slug' => 'ghrp-2-5mg', 'name' => 'GHRP-2 (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2, 'dose' => 150],
            ['slug' => 'ghrp-6-5mg', 'name' => 'GHRP-6 (5mg)', 'group' => 'Growth / GH', 'mg' => 5, 'water' => 2, 'dose' => 150],

            // Healing / repair
            ['slug' => 'bpc-157-5mg', 'name' => 'BPC-157 (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 250],
            ['slug' => 'bpc-157-10mg', 'name' => 'BPC-157 (10mg)', 'group' => 'Healing / Repair', 'mg' => 10, 'water' => 3, 'dose' => 500],
            ['slug' => 'tb-500-5mg', 'name' => 'TB-500 (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 2500],
            ['slug' => 'tb-500-10mg', 'name' => 'TB-500 (10mg)', 'group' => 'Healing / Repair', 'mg' => 10, 'water' => 3, 'dose' => 2500],
            ['slug' => 'bpc-tb500-blend-10mg', 'name' => 'BPC-157 / TB-500 Blend (10mg)', 'group' => 'Healing / Repair', 'mg' => 10, 'water' => 3, 'dose' => 500],
            ['slug' => 'kpv-5mg', 'name' => 'KPV (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 500],
            ['slug' => 'll-37-5mg', 'name' => 'LL-37 (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 500],
            ['slug' => 'thymosin-alpha-1-5mg', 'name' => 'Thymosin Alpha-1 (5mg)', 'group' => 'Healing / Repair', 'mg' => 5, 'water' => 2, 'dose' => 1600],
            ['slug' => 'ghk-cu-50mg', 'name' => 'GHK-Cu (50mg)', 'group' => 'Healing / Repair', 'mg' => 50, 'water' => 2, 'dose' => 500],
            ['slug' => 'ghk-cu-100mg', 'name' => 'GHK-Cu (100mg)', 'group' => 'Healing / Repair', 'mg' => 100, 'water' => 3, 'dose' => 1000],

            // Longevity / mitochondrial
            ['slug' => 'mots-c-5mg', 'name' => 'MOTS-c (5mg)', 'group' => 'Longevity', 'mg' => 5, 'water' => 2.5, 'dose' => 5000],
            ['slug' => 'mots-c-10mg', 'name' => 'MOTS-c (10mg)', 'group' => 'Longevity', 'mg' => 10, 'water' => 2, 'dose' => 5000],
            ['slug' => 'humanin-5mg', 'name' => 'Humanin (5mg)', 'group' => 'Longevity', 'mg' => 5, 'water' => 2, 'dose' => 1500],
            ['slug' => 'ss-31-elamipretide-10mg', 'name' => 'SS-31 / Elamipretide (10mg)', 'group' => 'Longevity', 'mg' => 10, 'water' => 2, 'dose' => 1000],
            ['slug' => 'epitalon-10mg', 'name' => 'Epitalon (10mg)', 'group' => 'Longevity', 'mg' => 10, 'water' => 2, 'dose' => 1000],
            ['slug' => 'epitalon-50mg', 'name' => 'Epitalon (50mg)', 'group' => 'Longevity', 'mg' => 50, 'water' => 2, 'dose' => 5000],
            ['slug' => 'nad-100mg', 'name' => 'NAD+ (100mg)', 'group' => 'Longevity', 'mg' => 100, 'water' => 2, 'dose' => 5000],
            ['slug' => 'nad-500mg', 'name' => 'NAD+ (500mg)', 'group' => 'Longevity', 'mg' => 500, 'water' => 3, 'dose' => 10000],
            ['slug' => 'foxo4-dri-5mg', 'name' => 'FOXO4-DRI (5mg)', 'group' => 'Longevity', 'mg' => 5, 'water' => 2, 'dose' => 1000],

            // Cognitive / nootropic
            ['slug' => 'semax-5mg', 'name' => 'Semax (5mg)', 'group' => 'Cognitive', 'mg' => 5, 'water' => 2, 'dose' => 300],
            ['slug' => 'selank-5mg', 'name' => 'Selank (5mg)', 'group' => 'Cognitive', 'mg' => 5, 'water' => 2, 'dose' => 300],
            ['slug' => 'dihexa-10mg', 'name' => 'Dihexa (10mg)', 'group' => 'Cognitive', 'mg' => 10, 'water' => 2, 'dose' => 2000],
            ['slug' => 'dsip-5mg', 'name' => 'DSIP (5mg)', 'group' => 'Cognitive', 'mg' => 5, 'water' => 2, 'dose' => 300],

            // Sexual function / cosmetic
            ['slug' => 'pt-141-bremelanotide-10mg', 'name' => 'PT-141 / Bremelanotide (10mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 10, 'water' => 2, 'dose' => 1750],
            ['slug' => 'melanotan-ii-10mg', 'name' => 'Melanotan II (10mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 10, 'water' => 2, 'dose' => 250],
            ['slug' => 'melanotan-i-10mg', 'name' => 'Melanotan I (10mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 10, 'water' => 2, 'dose' => 500],
            ['slug' => 'kisspeptin-10-5mg', 'name' => 'Kisspeptin-10 (5mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 5, 'water' => 2, 'dose' => 100],
            ['slug' => 'kisspeptin-10-10mg', 'name' => 'Kisspeptin-10 (10mg)', 'group' => 'Sexual / Cosmetic', 'mg' => 10, 'water' => 2, 'dose' => 100],

            // Metabolic / lipolytic
            ['slug' => 'aod-9604-5mg', 'name' => 'AOD-9604 (5mg)', 'group' => 'Metabolic', 'mg' => 5, 'water' => 2, 'dose' => 300],
            ['slug' => '5-amino-1mq-50mg', 'name' => '5-Amino-1MQ (50mg)', 'group' => 'Metabolic', 'mg' => 50, 'water' => 2, 'dose' => 10000],
            ['slug' => 'slu-pp-332-10mg', 'name' => 'SLU-PP-332 (10mg)', 'group' => 'Metabolic', 'mg' => 10, 'water' => 2, 'dose' => 500],
        ];
    }

    /**
     * O(1) lookup of a preset by its slug.
     */
    public static function findBySlug(string $slug): ?array
    {
        foreach (self::all() as $p) {
            if ($p['slug'] === $slug) return $p;
        }
        return null;
    }

    /**
     * Compound-level root label (e.g. "Tirzepatide" from "Tirzepatide (10mg)")
     * used for SEO titles.
     */
    public static function compoundName(array $preset): string
    {
        $name = $preset['name'];
        // Strip the "(5mg)" / "(10mg)" tail for the search-engine title.
        return trim(preg_replace('/\s*\([^)]*\)\s*$/', '', $name));
    }
}
