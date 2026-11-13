<?php

namespace App\Support;

/**
 * Encyclopedia compounds that are also the active ingredient of FDA-approved
 * branded medicines. Their Research Use Only box says so instead of the
 * default "not approved for human use by any regulatory agency" line, which
 * is false for them (Advantage Collective request, Nov 2026).
 *
 * Keys are lowercased public encyclopedia slugs. The brand names record why
 * each entry is listed; they are not rendered. Elamipretide (Forzinity,
 * accelerated approval) keeps its own notice in EncyclopediaController.
 */
final class ApprovedMedicines
{
    private const BRANDS = [
        'semaglutide' => ['Ozempic', 'Wegovy', 'Rybelsus'],
        'tirzepatide' => ['Mounjaro', 'Zepbound'],
        'tesamorelin' => ['Egrifta SV', 'Egrifta WR'],
        'bremelanotide' => ['Vyleesi'],
        'setmelanotide' => ['Imcivree'],
        'teriparatide' => ['Forteo'],
        'teduglutide' => ['Gattex'],
        'oxytocin' => ['Pitocin'],
        'hcg' => ['Novarel', 'Pregnyl'],
        'hmg' => ['Menopur'],
        'triptorelin' => ['Trelstar', 'Triptodur'],
        'hgh-191aa' => ['Genotropin', 'Humatrope', 'Norditropin', 'Omnitrope'],
        'melanotan-i' => ['Scenesse'],
        'methylene-blue' => ['ProvayBlue'],
        'l-carnitine' => ['Carnitor'],
    ];

    public static function has(?string $slug): bool
    {
        return isset(self::BRANDS[self::key($slug)]);
    }

    /**
     * @return list<string>
     */
    public static function brands(?string $slug): array
    {
        return self::BRANDS[self::key($slug)] ?? [];
    }

    /**
     * The agency's wording, with the entry name at the start of the first
     * sentence and in running text in the second.
     */
    public static function notice(string $name): string
    {
        $name = trim($name);

        return $name.' is FDA-approved as branded medicines. Research-grade '
            .self::inSentence($name)
            .' listed by vendors is a different product and is for laboratory research use only.'
            .' This article is for informational purposes and is not medical advice.';
    }

    /**
     * "Tirzepatide" becomes "tirzepatide" and "Methylene Blue" becomes
     * "methylene blue". Codes and acronyms (PT-141, hCG, HGH 191AA,
     * Melanotan-I, L-Carnitine) keep their letter case.
     */
    private static function inSentence(string $name): string
    {
        $words = explode(' ', $name);
        foreach ($words as $i => $word) {
            if (preg_match('/^\p{Lu}\p{Ll}+$/u', $word) === 1) {
                $words[$i] = mb_strtolower($word);
            }
        }

        return implode(' ', $words);
    }

    private static function key(?string $slug): string
    {
        return mb_strtolower(trim((string) $slug));
    }
}
