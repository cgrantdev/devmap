<?php

namespace App\Support;

/**
 * Pair copy for /compare/glow-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv
 * and /compare/ghk-cu-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv.
 *
 * canonicalVsSlug rewrites the short side "klow" before this lookup.
 * VsPairIndexing::canonicalPair does not, so the keys stay on the long
 * slugs. Price pages with a contents comparison. No dosing, no
 * reconstitution, no ranking, no percentages, no per-dose trial outcomes.
 * Sources checked 2026-10-19. KPV CAS is 67727-97-3.
 */
class KlowCompareCopy
{
    public const SLUG_GLOW_KLOW = 'glow-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv';

    public const SLUG_GHK_KLOW = 'ghk-cu-vs-klow-blend-ghk-cu-bpc-157-tb-500-kpv';

    /**
     * Stored titles omit the " — Peptidemap" suffix app.blade.php appends.
     * Rendered titles and these H1s stay within 60 characters. Display
     * labels for GLOW and KLOW are longer than that, so the short names
     * are set here.
     *
     * @return array{title: string, h1: string, description: string}|null
     */
    public static function seoOverride(?string $slug): ?array
    {
        $slug = strtolower(trim((string) $slug));

        if ($slug === self::SLUG_GLOW_KLOW) {
            return [
                'title' => 'GLOW vs KLOW — Vendor & Price Comparison',
                'h1' => 'GLOW vs KLOW',
                'description' => 'GLOW vs KLOW research blend labels: KLOW adds KPV, not more GHK-Cu. Component table, evidence limits, and listings. RUO only; Peptidemap sells nothing.',
            ];
        }

        if ($slug === self::SLUG_GHK_KLOW) {
            return [
                'title' => 'GHK-Cu vs KLOW — Vendor & Price Comparison',
                'h1' => 'GHK-Cu vs KLOW',
                'description' => 'GHK-Cu vs KLOW: one copper peptide vs a four-peptide blend label. Component table, evidence limits, and listings. RUO only; Peptidemap sells nothing.',
            ];
        }

        return null;
    }

    /**
     * @return array{evidence: array<string, mixed>, link: array{before: string, text: string, href: string, after: string}|null, faqs: list<array{q: string, a: string}>, references: list<array{title: string, url: string}>}|null
     */
    public static function forSlug(?string $slug): ?array
    {
        $slug = strtolower(trim((string) $slug));

        if ($slug === self::SLUG_GLOW_KLOW) {
            return [
                'evidence' => self::glowKlowEvidence(),
                // Set to the /blog/glow-vs-klow link once that URL returns 200.
                'link' => null,
                'faqs' => self::glowKlowFaqs(),
                'references' => [],
            ];
        }

        if ($slug === self::SLUG_GHK_KLOW) {
            return [
                'evidence' => self::ghkKlowEvidence(),
                'link' => null,
                'faqs' => self::ghkKlowFaqs(),
                'references' => [],
            ];
        }

        return null;
    }

    /** @return array<string, mixed> */
    public static function glowKlowEvidence(): array
    {
        return [
            'title' => 'Evidence at a glance',
            'paragraphs' => [
                'GLOW and KLOW are marketplace names for multi-peptide research blends. They are not single molecules, and they are not approved medicines. On Peptidemap\'s catalog labels, GLOW names three components: GHK-Cu, BPC-157, and TB-500. KLOW names the same three plus a fourth, KPV (Lys-Pro-Val). The added piece is KPV, not more GHK-Cu.',
                'This page compares catalog listings and what each label names. It does not rank the blends, it does not pick a winner, and it is not medical advice. Listings are research use only (RUO), and Peptidemap sells nothing.',
                'Listings label these blends in different ways. Some print one total fill for the vial and others print a separate fill for each component, and the totals differ from listing to listing. The component order in product titles varies, and TB-500 is sometimes written as TB500. A few GLOW-titled entries are capsules or kits rather than single vials. Peptidemap does not publish an official ratio for either blend. A stated fill belongs on that lot\'s certificate of analysis.',
                'This page is for readers checking what research-catalog labels name: lab teams comparing listings, researchers confirming which components a blend title covers, and analysts tracking how vendors label these products. It is a contents and listings comparison. It does not suggest a blend for any condition, goal, or experiment, and it does not give dosing or preparation instructions.',
            ],
            'table' => [
                'caption' => 'What the GLOW and KLOW catalog labels name',
                'head' => ['Component', 'GLOW label', 'KLOW label', 'What it is'],
                'rows' => [
                    ['GHK-Cu', 'Named', 'Named', 'Copper(II) complex of the tripeptide glycyl-L-histidyl-L-lysine'],
                    ['BPC-157', 'Named', 'Named', 'Synthetic 15-amino-acid peptide'],
                    ['TB-500', 'Named', 'Named', 'Marketplace name. FDA describes it as the thymosin beta-4 fragment LKKTETQ, not the full-length protein'],
                    ['KPV', 'Not named', 'Named', 'Tripeptide Lys-Pro-Val, residues 11–13 of alpha-MSH, CAS 67727-97-3'],
                ],
            ],
            'grades' => [
                ['label' => 'GLOW or KLOW as named products', 'grade' => 'Human trials: none identified', 'rationale' => 'A PubMed search on 19 October 2026 found no indexed record for the phrases "GLOW blend", "KLOW blend", or "GHK-Cu/BPC-157/TB-500", and a title search for KLOW with peptide returned nothing. No study of either full blend was found.'],
                ['label' => 'KPV (the only component that differs)', 'grade' => 'Human trials: none identified', 'rationale' => 'Published KPV work is cell and rodent research, for example a 2008 study of KPV uptake through the PepT1 transporter in intestinal cell lines and mouse colitis models. FDA says it has not identified any human exposure data on drug products containing KPV by any route.'],
                ['label' => 'GHK-Cu (in both)', 'grade' => 'Human evidence: limited, topical and cosmetic only', 'rationale' => 'Small studies of topical copper tripeptide exist. None tested a research vial or either blend. '.self::ghkGradeLine()],
                ['label' => 'BPC-157 (in both)', 'grade' => 'Human RCTs: none', 'rationale' => 'A 2026 systematic review found three uncontrolled human studies with 30 participants in total and rated the certainty of evidence very low.'],
                ['label' => 'TB-500 (in both)', 'grade' => 'Human studies: none identified', 'rationale' => 'FDA\'s July 2026 briefing states that it has not identified any clinical studies or human exposure data for TB-500 by any route.'],
                ['label' => 'BPC-157 with TB-500 (the pair inside both blends)', 'grade' => 'Human trials: none', 'rationale' => 'A 2026 rat Achilles-tendon study reported no added benefit from combining the two compared with either alone.'],
            ],
            'notes' => [
                'A study of one component is a study of that molecule. It is not a test of a vial that contains three or four peptides, and work on KPV alone does not show what KLOW does.',
                self::categoryNote('None of the four is an FDA-approved drug.'),
                'Vendors sometimes use "TB-500" loosely. FDA\'s documents describe the LKKTETQ fragment, and a 2012 analytical paper found the acetylated 17–23 fragment in material sold as TB-500. A lot\'s certificate of analysis shows which sequence was tested.',
            ],
            'sources' => array_merge(self::regulatorySources(), [
                ['title' => 'Dalmasso G, et al. PepT1-mediated tripeptide KPV uptake reduces intestinal inflammation. Gastroenterology 2008;134(1):166-78. PMID 18061177.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/18061177/'],
                ['title' => 'Pickart L, Margolina A. Regenerative and protective actions of the GHK-Cu peptide in the light of the new gene data. Int J Mol Sci 2018. PMID 29986520.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/29986520/'],
                self::millerSource(),
                ['title' => 'Poolton JT, et al. Human evidence for BPC-157 in orthopaedic and regenerative medicine: a systematic review of efficacy and safety. Acta Orthop Belg 2026;92(3). PMID 42885452.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/42885452/'],
                ['title' => 'Biçer O, et al. Effects of BPC-157 and TB-500 on Achilles tendon healing in rats: A histopathological and biomechanical study. Jt Dis Relat Surg 2026;37(3):822-837. PMID 42542926.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/42542926/'],
                ['title' => 'Esposito S, et al. Synthesis and characterization of the N-terminal acetylated 17-23 fragment of thymosin beta 4 identified in TB-500. Drug Test Anal 2012. PMID 22962027.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/22962027/'],
                self::pubchemSource(),
                ['title' => 'PubMed searches run 19 October 2026: "GLOW blend" OR "KLOW blend" OR "GHK-Cu/BPC-157/TB-500" (all three phrases returned as not found) and "KLOW"[Title] AND peptide (0 records).', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/?term=KLOW[Title]+AND+peptide'],
            ]),
        ];
    }

    /** @return list<array{q: string, a: string}> */
    public static function glowKlowFaqs(): array
    {
        return [
            ['q' => 'What is the difference between GLOW and KLOW?', 'a' => 'On Peptidemap\'s catalog labels, GLOW names GHK-Cu, BPC-157, and TB-500. KLOW names those same three plus KPV (Lys-Pro-Val). KPV is the only component that differs.'],
            ['q' => 'Is KLOW just GLOW with more GHK-Cu?', 'a' => 'No. The added component is KPV, a different tripeptide. KPV is not a copper peptide and is not a fragment of GHK. Adding it to a label does not mean the vial holds more GHK-Cu.'],
            ['q' => 'What is KPV?', 'a' => 'KPV is the tripeptide lysine-proline-valine, residues 11–13 of alpha-melanocyte-stimulating hormone (alpha-MSH). Its CAS number is 67727-97-3. Published research on it is cell and rodent work. FDA says it has not identified human exposure data on drug products containing KPV.'],
            ['q' => 'Has GLOW or KLOW been tested in people?', 'a' => 'No human trial of either named blend was identified. A PubMed search on 19 October 2026 found no indexed study of either blend. Each component has its own literature, and none of it is a test of the blends.'],
            ['q' => 'Does this page say which blend is better?', 'a' => 'This page does not rank them. No published study has compared GLOW with KLOW, and a paper on one component does not show what a three- or four-peptide mixture does.'],
            ['q' => 'Why do GLOW and KLOW listings show different fills?', 'a' => 'Vendors label these blends differently. Some print a single total and some print a fill for each component, and totals vary. Peptidemap does not publish an official ratio, so the certificate of analysis for a given lot is the place to check what was tested.'],
            ['q' => 'Who is this comparison for?', 'a' => 'Readers comparing research-catalog listings and checking what each blend label names. It is not a guide to choosing a blend for any use, and it is not medical advice.'],
            ['q' => 'Does Peptidemap sell GLOW or KLOW, or give dosing information?', 'a' => 'No. Peptidemap is a comparison publisher. It lists research-use-only (RUO) products from other sellers and does not give dosing, preparation, or combination instructions.'],
        ];
    }

    /** @return array<string, mixed> */
    public static function ghkKlowEvidence(): array
    {
        return [
            'title' => 'Evidence at a glance',
            'paragraphs' => [
                'GHK-Cu is a single peptide: the copper(II) complex of the tripeptide glycyl-L-histidyl-L-lysine. KLOW is a marketplace name for a four-peptide research blend. On Peptidemap\'s catalog labels, KLOW names GHK-Cu, BPC-157, TB-500, and KPV (Lys-Pro-Val). GHK-Cu is one of KLOW\'s four named components. A GHK-Cu listing is not a KLOW listing, and KLOW is not a stronger form of GHK-Cu.',
                'This page compares catalog listings and what each label names. It does not rank them, it does not pick a winner, and it is not medical advice. Listings are research use only (RUO), and Peptidemap sells nothing.',
                'Labels vary on both sides. GHK-Cu listings are sold in several fill sizes. KLOW listings print either one total fill for the vial or a separate fill for each component, and the totals differ from listing to listing. Component order in KLOW titles varies, and TB-500 is sometimes written as TB500. Peptidemap does not publish an official ratio for KLOW. A stated fill belongs on that lot\'s certificate of analysis.',
                'This page is for readers checking what research-catalog labels name: lab teams comparing a single-peptide listing with a blend listing, researchers confirming that a blend title includes GHK-Cu alongside other components, and analysts tracking how vendors label these products. It is a contents and listings comparison. It does not suggest either listing for any condition, goal, or experiment, and it does not give dosing or preparation instructions.',
            ],
            'table' => [
                'caption' => 'What GHK-Cu and KLOW catalog labels name',
                'head' => ['Component', 'GHK-Cu label', 'KLOW label', 'What it is'],
                'rows' => [
                    ['GHK-Cu', 'Named (sole component)', 'Named', 'Copper(II) complex of the tripeptide glycyl-L-histidyl-L-lysine, also listed as copper tripeptide-1 or prezatide copper'],
                    ['BPC-157', 'Not named', 'Named', 'Synthetic 15-amino-acid peptide'],
                    ['TB-500', 'Not named', 'Named', 'Marketplace name. FDA describes it as the thymosin beta-4 fragment LKKTETQ, not the full-length protein'],
                    ['KPV', 'Not named', 'Named', 'Tripeptide Lys-Pro-Val, residues 11–13 of alpha-MSH, CAS 67727-97-3'],
                ],
            ],
            'grades' => [
                ['label' => 'GHK-Cu', 'grade' => 'Human evidence: limited, topical and cosmetic only', 'rationale' => 'Small studies of topical copper tripeptide exist, and the wider literature is mostly cell, animal, and gene-expression work. None of it tested a research vial. '.self::ghkGradeLine()],
                ['label' => 'KLOW as a named product', 'grade' => 'Human trials: none identified', 'rationale' => 'A PubMed search on 19 October 2026 found no indexed record for the phrase "KLOW blend", and a title search for KLOW with peptide returned nothing.'],
                ['label' => 'KPV (in KLOW only)', 'grade' => 'Human trials: none identified', 'rationale' => 'Published KPV work is cell and rodent research, for example a 2008 study of KPV uptake through the PepT1 transporter in intestinal cell lines and mouse colitis models. FDA says it has not identified any human exposure data on drug products containing KPV by any route.'],
                ['label' => 'BPC-157 (in KLOW only)', 'grade' => 'Human RCTs: none', 'rationale' => 'A 2026 systematic review found three uncontrolled human studies with 30 participants in total and rated the certainty of evidence very low.'],
                ['label' => 'TB-500 (in KLOW only)', 'grade' => 'Human studies: none identified', 'rationale' => 'FDA\'s July 2026 briefing states that it has not identified any clinical studies or human exposure data for TB-500 by any route.'],
            ],
            'notes' => [
                'GHK-Cu research is research on GHK-Cu. It does not describe a vial that also contains BPC-157, TB-500, and KPV, and it is not evidence for KLOW.',
                self::categoryNote('None of them is an FDA-approved drug.'),
                'Vendors sometimes use "TB-500" loosely. FDA\'s documents describe the LKKTETQ fragment, and a 2012 analytical paper found the acetylated 17–23 fragment in material sold as TB-500. A lot\'s certificate of analysis shows which sequence was tested.',
            ],
            'sources' => array_merge(self::regulatorySources(), [
                ['title' => 'Pickart L, Margolina A. Regenerative and protective actions of the GHK-Cu peptide in the light of the new gene data. Int J Mol Sci 2018. PMID 29986520.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/29986520/'],
                self::millerSource(),
                ['title' => 'Dalmasso G, et al. PepT1-mediated tripeptide KPV uptake reduces intestinal inflammation. Gastroenterology 2008;134(1):166-78. PMID 18061177.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/18061177/'],
                ['title' => 'Poolton JT, et al. Human evidence for BPC-157 in orthopaedic and regenerative medicine: a systematic review of efficacy and safety. Acta Orthop Belg 2026;92(3). PMID 42885452.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/42885452/'],
                ['title' => 'Esposito S, et al. Synthesis and characterization of the N-terminal acetylated 17-23 fragment of thymosin beta 4 identified in TB-500. Drug Test Anal 2012. PMID 22962027.', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/22962027/'],
                self::pubchemSource(),
                ['title' => 'PubMed searches run 19 October 2026: "KLOW blend" (returned as not found) and "KLOW"[Title] AND peptide (0 records).', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/?term=KLOW[Title]+AND+peptide'],
            ]),
        ];
    }

    /** @return list<array{q: string, a: string}> */
    public static function ghkKlowFaqs(): array
    {
        return [
            ['q' => 'What is the difference between GHK-Cu and KLOW?', 'a' => 'GHK-Cu is one peptide, the copper complex of glycyl-L-histidyl-L-lysine. KLOW is a four-name research blend. On Peptidemap\'s labels it names GHK-Cu, BPC-157, TB-500, and KPV.'],
            ['q' => 'Is KLOW a stronger version of GHK-Cu?', 'a' => 'No. KLOW adds three other peptides to GHK-Cu. Its label does not describe a higher GHK-Cu strength, and a GHK-Cu listing is not a KLOW listing.'],
            ['q' => 'Does KLOW contain GHK-Cu?', 'a' => 'GHK-Cu is one of the four components named on KLOW catalog labels. How much is stated, and whether a fill is given for each component, varies by listing. The certificate of analysis for a lot is the place to check what was tested.'],
            ['q' => 'What is KPV?', 'a' => 'KPV is the tripeptide lysine-proline-valine, residues 11–13 of alpha-melanocyte-stimulating hormone (alpha-MSH). Its CAS number is 67727-97-3. It is not a copper peptide and not a fragment of GHK.'],
            ['q' => 'Has GHK-Cu or KLOW been tested in people?', 'a' => 'GHK-Cu has small topical and cosmetic human studies, none of them on research-vial material. No human trial of KLOW as a named blend was identified in a PubMed search on 19 October 2026.'],
            ['q' => 'Does this page say which blend is better?', 'a' => 'This page does not rank them. No published study has compared GHK-Cu with KLOW, and GHK-Cu research does not show what a four-peptide mixture does.'],
            ['q' => 'Who is this comparison for?', 'a' => 'Readers comparing research-catalog listings and checking what each label names. It is not a guide to choosing a product for any use, and it is not medical advice.'],
            ['q' => 'Does Peptidemap sell GHK-Cu or KLOW, or give dosing information?', 'a' => 'No. Peptidemap is a comparison publisher. It lists research-use-only (RUO) products from other sellers and does not give dosing, preparation, or combination instructions.'],
        ];
    }

    private static function categoryNote(string $closing): string
    {
        return 'FDA\'s page on compounding bulk substances that may present significant safety risks (content current as of 22 April 2026) lists BPC-157, GHK-Cu (injectable routes), KPV, and the TB-500 fragment among substances previously in Category 2 whose nominations were withdrawn, and it keeps its safety concerns for each. FDA\'s 503A categories list (updated 14 May 2026) places GHK-Cu, except for injectable routes, in Category 1, under evaluation. In July 2026 an FDA advisory committee narrowly recommended adding BPC-157, KPV, and TB-500 to the 503A bulks list; that vote is not binding. On 14 October 2026 FDA sent an interim access policy for compounding certain peptides to the White House for review; its details were not public as of 19 October 2026. '.$closing;
    }

    private static function ghkGradeLine(): string
    {
        return 'FDA\'s safety-risks page lists GHK-Cu for injectable routes among substances previously in Category 2 whose nominations were withdrawn, and keeps its note on immunogenicity risk and limited human data. Non-injectable GHK-Cu is in Category 1 (under evaluation).';
    }

    /**
     * @return list<array{title: string, url: string}>
     */
    private static function regulatorySources(): array
    {
        return [
            [
                'title' => 'U.S. FDA. Certain bulk drug substances for use in compounding that may present significant safety risks (Category 2 and substances previously in Category 2 whose nominations were withdrawn). Content current as of 22 April 2026.',
                'url' => 'https://www.fda.gov/drugs/human-drug-compounding/certain-bulk-drug-substances-use-compounding-may-present-significant-safety-risks',
            ],
            [
                'title' => 'U.S. FDA. 503A categories list (updated 14 May 2026).',
                'url' => 'https://www.fda.gov/media/94155/download',
            ],
            [
                'title' => 'U.S. FDA. Pharmacy Compounding Advisory Committee briefing document, 23–24 July 2026 (TB-500 memo dated 15 May 2026; quote on PDF p. 31).',
                'url' => 'https://www.fda.gov/media/193349/download',
            ],
            [
                'title' => 'Office of Information and Regulatory Affairs (OIRA), RIN 0910-ZD94. Interim Access Policy for Compounding Using Certain Peptides. Received 14 October 2026.',
                'url' => 'https://www.reginfo.gov/public/do/eoDetails?rrid=1568964',
            ],
            [
                'title' => 'Reuters. FDA advisers back first four of seven unapproved peptides under review for looser rules. 23 July 2026. The committee voted 8-6, with one abstention, to recommend adding BPC-157, KPV, and TB-500 to the 503A bulks list, as reported by Reuters.',
                'url' => 'https://www.reuters.com/legal/litigation/us-fda-advisers-weigh-restrictions-seven-peptides-industry-presses-expansion-2026-07-23/',
            ],
        ];
    }

    /**
     * @return array{title: string, url: string}
     */
    private static function millerSource(): array
    {
        return [
            'title' => 'Miller TR, et al. Effects of topical copper tripeptide complex on CO2 laser-resurfaced skin. Arch Facial Plast Surg 2006;8(4):252-9. PMID 16847171.',
            'url' => 'https://pubmed.ncbi.nlm.nih.gov/16847171/',
        ];
    }

    /**
     * @return array{title: string, url: string}
     */
    private static function pubchemSource(): array
    {
        return [
            'title' => 'PubChem. Msh (11-13), Lys-Pro-Val, CID 125672 (CAS 67727-97-3; C16H30N4O4).',
            'url' => 'https://pubchem.ncbi.nlm.nih.gov/compound/125672',
        ];
    }
}
