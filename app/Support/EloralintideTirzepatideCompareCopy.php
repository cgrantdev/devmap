<?php

namespace App\Support;

/**
 * Dated status note for /compare/eloralintide-vs-tirzepatide.
 *
 * Primary source is Lilly's 30 September 2026 investor release.
 * Figures are company-reported. Dose arms are named by position.
 * Checked 22 October 2026: no peer-reviewed paper of NCT06603571
 * was found on PubMed. No dosing instructions.
 */
class EloralintideTirzepatideCompareCopy
{
    public const SLUG = 'eloralintide-vs-tirzepatide';

    public const NOTE_HEADING = 'As of 22 October 2026';

    /**
     * @return array{evidence: array<string, mixed>, link: null, faqs: list<array{q: string, a: string}>, references: list<array{title: string, url: string}>}|null
     */
    public static function forSlug(?string $slug): ?array
    {
        if (strtolower(trim((string) $slug)) !== self::SLUG) {
            return null;
        }

        return [
            'evidence' => self::evidence(),
            'link' => null,
            'faqs' => [],
            'references' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function evidence(): array
    {
        return [
            'title' => self::NOTE_HEADING,
            'paragraphs' => self::paragraphs(),
            'sources' => [
                [
                    'title' => 'Eli Lilly and Company. “Lilly\'s EloraTZP (combination of eloralintide and tirzepatide) delivered greater weight loss and A1C reduction vs. tirzepatide 15 mg in adults with obesity and type 2 diabetes.” 30 September 2026.',
                    'url' => 'https://investor.lilly.com/news-releases/news-release-details/lillys-eloratzp-combination-eloralintide-and-tirzepatide',
                ],
                [
                    'title' => 'ClinicalTrials.gov. NCT06603571. A study of LY3841136 and tirzepatide, alone or in combination, in adults with obesity or overweight and type 2 diabetes. Eli Lilly and Company.',
                    'url' => 'https://clinicaltrials.gov/study/NCT06603571',
                ],
                [
                    'title' => 'PubMed search for NCT06603571 or EloraTZP, checked 22 October 2026. No peer-reviewed paper of this study was found.',
                    'url' => 'https://pubmed.ncbi.nlm.nih.gov/?term=NCT06603571+OR+EloraTZP',
                ],
            ],
        ];
    }

    /**
     * Visible status-note body. The heading is evidence.title.
     *
     * @return list<string>
     */
    public static function paragraphs(): array
    {
        return [
            'On 30 September 2026, Eli Lilly and Company reported Phase 2b results for EloraTZP, an investigational combination of eloralintide and tirzepatide, in a sponsor press release. The figures in this note are company-reported. A PubMed search on 22 October 2026 found no peer-reviewed paper of this study (NCT06603571).',
            'The trial randomized 367 adults with obesity or overweight and type 2 diabetes. Assigned treatment lasted 48 weeks. Lilly said the primary comparison was percent change in body weight for the combination versus placebo. On the efficacy estimand, which Lilly defines as the effect had participants remained on study intervention for 48 weeks, with possible interruptions or modifications, company-reported mean weight change was −13.2% in the lowest combination dose arm and −23.3% in the highest combination dose arm, −14.8% in the tirzepatide-only arm, and −3.0% with placebo. Company-reported mean A1C change on that estimand was −2.2% in the lowest combination dose arm and −2.9% in the highest combination dose arm, −2.4% in the tirzepatide-only arm, and −0.3% with placebo. Other combination dose arms in the release are not repeated here.',
            'Lilly said the most common adverse events were gastrointestinal and were seen more often in the combination arms than with either medicine alone. Company-reported discontinuation because of adverse events was 10.8% to 27.0% with the combination, 0% to 10.8% with eloralintide alone, 2.9% with tirzepatide alone, and 16.7% with placebo. In this study the two medicines were given as separate injections. Lilly said it plans to start Phase 3 studies of a co-formulated product by the end of 2026. EloraTZP is not an approved medicine.',
        ];
    }
}
