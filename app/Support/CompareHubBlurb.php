<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Copy for a /compare hub card, the line rendered beside "lowest listed".
 *
 * Encyclopedia descriptions can quote trial outcomes, doses, and
 * superlatives. Those stay on the encyclopedia page. The hub card
 * substitutes a class line when the rendered blurb matches that pattern.
 */
class CompareHubBlurb
{
    /**
     * Percent outcomes, efficacy or healing results, superlatives, and
     * per-dose figures. Matched against the blurb the hub would render.
     */
    private const OUTCOME_PATTERN = '/\d+(?:\.\d+)?\s*%|\b(?:proven|clinically\s+shown|clinically\s+proven|healing|fat[-\s]?burning|fat[-\s]?loss|weight[-\s]?(?:loss|reduction)|superior|remarkable|highest|best|most[-\s]searched|most\s+selective|most\s+effective|exercise\s+mimetic|master\s+upstream)\b|\breduc(?:e|es|ing)\s+(?:visceral\s+)?fat\b|\bpromotes?\s+(?:systemic\s+)?recovery\b|\bincreas(?:e|es|ing)\s+sexual\s+desire\b|\btransformed\b|\bhas\s+shown\b|\bstimulat(?:e|es|ing)\s+(?:natural\s+)?(?:gh|growth\s+hormone)\s+release\b|\bimprov(?:e|es|ing)\s+metabolic\b|\b\d+(?:\.\d+)?\s*(?:mg|mcg|µg|μg|iu)\b|\b(?:once|twice)[-\s](?:daily|weekly)\b|\bper\s+dose\b/iu';

    /**
     * Class lines for compounds whose stored hub blurb is an outcome claim.
     * Structural facts already on the card stay; nothing here is a result.
     *
     * @var array<string, string>
     */
    private const NEUTRAL = [
        'retatrutide' => 'Retatrutide is a triple agonist (GLP-1, GIP, and glucagon) studied in obesity research. Research use only.',
        'tirzepatide' => 'Tirzepatide is a dual GIP and GLP-1 receptor agonist studied in metabolic research. Research use only.',
        'semaglutide' => 'Semaglutide is a GLP-1 receptor agonist studied in metabolic research. Research use only.',
        'tesamorelin' => 'Tesamorelin is a GHRH analog studied in lipodystrophy research. Research use only.',
        'mots-c' => 'MOTS-c is a mitochondrial-derived peptide studied in metabolic research. Research use only.',
        'bpc-157' => 'BPC-157 is a synthetic peptide fragment studied in tissue-repair research. Research use only.',
        'ipamorelin' => 'Ipamorelin is a growth hormone secretagogue studied in endocrine research. Research use only.',
        'aod-9604' => 'AOD-9604 is a modified fragment of human growth hormone (amino acids 176-191) studied in metabolic research. Research use only.',
        'tb-500' => 'TB-500 is a synthetic fragment of thymosin beta-4 studied in tissue-repair research. Research use only.',
        'sermorelin' => 'Sermorelin is a GHRH(1-29) analog studied in endocrine research. Research use only.',
        'cagrilintide' => 'Cagrilintide is an amylin analog studied in obesity research. Research use only.',
        'kisspeptin' => 'Kisspeptin is a neuropeptide studied in reproductive endocrinology research. Research use only.',
        'bremelanotide' => 'Bremelanotide is a melanocortin receptor agonist studied in sexual-function research. Research use only.',
        'pt-141' => 'Bremelanotide is a melanocortin receptor agonist studied in sexual-function research. Research use only.',
    ];

    private const FALLBACK = 'Listed for research use only.';

    public static function render(?string $slug, ?string $raw): ?string
    {
        $plain = trim(strip_tags((string) $raw));
        if ($plain === '') {
            return null;
        }

        $limited = Str::limit($plain, 200);
        if (! self::hasOutcomeClaim($limited)) {
            return $limited;
        }

        $key = strtolower(trim((string) $slug));

        return self::NEUTRAL[$key] ?? self::FALLBACK;
    }

    public static function hasOutcomeClaim(?string $text): bool
    {
        $text = trim(strip_tags((string) $text));
        if ($text === '') {
            return false;
        }

        return preg_match(self::OUTCOME_PATTERN, $text) === 1;
    }
}
