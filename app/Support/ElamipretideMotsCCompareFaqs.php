<?php

namespace App\Support;

/**
 * Publisher-comparison FAQs for /compare/elamipretide-vs-mots-c.
 *
 * Compare FAQs are assembled in the controller, not stored on a CMS row.
 * This pair has no compare-FAQ table, so the copy lives here the same way
 * other compare FAQs live in CompareController. Other vs pages get nothing.
 */
class ElamipretideMotsCCompareFaqs
{
    public const SLUG = 'elamipretide-vs-mots-c';

    /**
     * @return array{faqs: list<array{q: string, a: string}>, references: list<array{title: string, url: string}>}|null
     */
    public static function forSlug(?string $slug): ?array
    {
        if (strtolower(trim((string) $slug)) !== self::SLUG) {
            return null;
        }

        return [
            'faqs' => self::faqs(),
            'references' => self::references(),
        ];
    }

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function faqs(): array
    {
        return [
            [
                'q' => 'Does SS-31 need to come before MOTS-c?',
                'a' => 'The literature does not establish that. There is no validated stacking or sequencing protocol for SS-31 (elamipretide) and MOTS-c. SS-31 is a research alias for elamipretide, the same molecule. The only FDA approval involving elamipretide is Forzinity (elamipretide) injection for Barth syndrome in patients weighing at least 30 kg (accelerated approval, 19 September 2025), and research-chemical “SS-31” listings are not that product. MOTS-c is a mitochondrial-derived research peptide that is not FDA-approved. Peptidemap is a publisher comparison resource, not medical guidance, and it does not give dosing, sequencing, or combination advice.',
            ],
            [
                'q' => 'Are “SS-31” and “elamipretide” listings on this page different compounds?',
                'a' => 'No. SS-31 is a research code for elamipretide. Vendors use either name, and neither name makes a research listing the FDA-approved Forzinity product.',
            ],
        ];
    }

    /**
     * @return list<array{title: string, url: string}>
     */
    public static function references(): array
    {
        return [
            [
                'title' => 'FDA press announcement, 19 Sep 2025, “FDA Grants Accelerated Approval to First Treatment for Barth Syndrome”',
                'url' => 'https://www.fda.gov/news-events/press-announcements/fda-grants-accelerated-approval-first-treatment-barth-syndrome',
            ],
            [
                'title' => 'Tung C, et al. Elamipretide: A Review of Its Structure, Mechanism of Action, and Therapeutic Potential. Int J Mol Sci. 2025;26(3):944. PMID 39940712 (SS-31 / MTP-131 / Bendavia aliases)',
                'url' => 'https://pubmed.ncbi.nlm.nih.gov/39940712/',
            ],
        ];
    }
}
