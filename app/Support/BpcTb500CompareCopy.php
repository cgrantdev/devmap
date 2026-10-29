<?php

namespace App\Support;

/**
 * Compact evidence block and Wolverine-stack FAQ for /compare/bpc-157-vs-tb-500.
 *
 * The page stays a price page. Grades describe published human evidence,
 * not products. Depth lives in /blog/bpc-157-vs-tb-500-evidence. No dosing,
 * no combination instructions. Sources checked 2026-10-19. The 28 Oct 2026
 * regulatory note is the HHS draft text, kept as supplied.
 */
class BpcTb500CompareCopy
{
    public const SLUG = 'bpc-157-vs-tb-500';

    /**
     * @return array{evidence: array<string, mixed>, link: array{before: string, text: string, href: string, after: string}|null, faqs: list<array{q: string, a: string}>, references: list<array{title: string, url: string}>}|null
     */
    public static function forSlug(?string $slug): ?array
    {
        if (strtolower(trim((string) $slug)) !== self::SLUG) {
            return null;
        }

        return [
            'evidence' => self::evidence(),
            'link' => null,
            'faqs' => self::faqs(),
            // Sources render inside the evidence block, so the FAQ
            // references list stays empty and nothing is listed twice.
            'references' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function evidence(): array
    {
        return [
            'title' => 'Evidence at a glance',
            'paragraphs' => [
                'This page compares listings and prices. The grades below describe published human evidence, not the products listed here. Detail and sources are in the evidence comparison.',
            ],
            'grades' => [
                [
                    'label' => 'BPC-157',
                    'grade' => 'Human RCTs: none',
                    'rationale' => 'A 2026 systematic review found three uncontrolled human studies (30 participants in total) and rated the certainty of evidence very low.',
                ],
                [
                    'label' => 'Full-length thymosin beta-4 (Tβ4, 43 amino acids)',
                    'grade' => 'Human RCTs: some (eye and skin only)',
                    'rationale' => 'Randomized trials tested RGN-259 eye drops and topical Tβ4 for skin wounds, with mixed results. None tested tendon, ligament, or muscle repair. It is a different molecule from TB-500.',
                ],
                [
                    'label' => 'TB-500 (Ac-LKKTETQ, Tβ4 residues 17–23)',
                    'grade' => 'Human RCTs: none',
                    'rationale' => 'FDA’s July 2026 briefing defines TB-500 as this fragment; vendor labels vary. No published human study was identified. One Phase 1/2 trial is registered (NCT07487363) with no results posted.',
                ],
                [
                    'label' => 'BPC-157 with TB-500 (“Wolverine stack”)',
                    'grade' => 'Human RCTs: none',
                    'rationale' => 'No human trial has studied the pair. A 2026 rat study found no added benefit from combining them.',
                ],
            ],
            'notes' => [
                'FDA’s July 2026 advisory committee briefing states: “The nomination did not include, and FDA has not identified, any clinical studies or human exposure data using TB-500 via any ROA.” ROA means route of administration.',
                'The committee’s July 2026 votes to recommend BPC-157 and TB-500 for the 503A bulks list are advisory. They are not FDA approval.',
                '**Regulatory note (Oct 2026).** BPC-157 and TB-500 are both named in a draft, interim, non-binding enforcement-discretion policy from the HHS Office of the Secretary. The policy was announced on 28 Oct 2026, with Federal Register publication on 30 Oct (FR Doc. 2026-22192). It covers only specific acetate forms and listed routes. It applies only to single-ingredient, patient-specific compounding under section 503A, so a BPC-157 + TB-500 blend is not covered. It is not FDA approval, neither peptide is on the 503A Bulks List, and it makes no safety or efficacy finding. RUO-labelled products are outside its terms. See [Compounded vs RUO Peptides](/guides/compounded-vs-ruo-peptides) and [the six-peptide explainer](/blog/fda-503a-peptide-enforcement-discretion-2026).',
            ],
            'sources' => [
                [
                    'title' => 'U.S. FDA, Pharmacy Compounding Advisory Committee briefing document, 23–24 July 2026 (TB-500 memo dated 15 May 2026; quote on PDF p. 31).',
                    'url' => 'https://www.fda.gov/media/193349/download',
                ],
                [
                    'title' => 'Poolton JT, et al. Human evidence for BPC-157 in orthopaedic and regenerative medicine: a systematic review. Acta Orthop Belg 2026;92(3). PMID 42885452.',
                    'url' => 'https://pubmed.ncbi.nlm.nih.gov/42885452/',
                ],
                [
                    'title' => 'McGuire F, et al. Thymosin beta-4 and TB-500 in tissue healing: a scoping review. Applied Sciences 2026;16(12):6202.',
                    'url' => 'https://www.mdpi.com/2076-3417/16/12/6202',
                ],
                [
                    'title' => 'Biçer O, et al. BPC-157 and TB-500 on Achilles tendon healing in rats. Jt Dis Relat Surg 2026;37(3):822-837. PMID 42542926.',
                    'url' => 'https://www.jointdrs.org/full-text/1851',
                ],
                [
                    'title' => 'ClinicalTrials.gov, NCT07487363 (TB-500, Phase 1/2, recruiting, no results).',
                    'url' => 'https://clinicaltrials.gov/study/NCT07487363',
                ],
            ],
        ];
    }

    /**
     * Visible FAQ text. CompareController::showVs builds the FAQPage
     * schema from this same array, so schema and visible text match.
     *
     * @return list<array{q: string, a: string}>
     */
    public static function faqs(): array
    {
        return [
            [
                'q' => 'What is the “Wolverine stack”?',
                'a' => 'It is a nickname vendors and online communities use for pairing BPC-157 with TB-500. It is not a trial name or a protocol, and listings on this page are for research use only. No human trial has studied the pair. A 2026 rat Achilles study found no added benefit from combining them. FDA advisory committee votes in July 2026 recommending each substance for the 503A bulks list are advisory and are not FDA approval. Peptidemap does not give dosing or combination instructions.',
            ],
        ];
    }
}
