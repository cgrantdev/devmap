<?php

namespace App\Support;

/**
 * Trial context for /compare/retatrutide-vs-tirzepatide.
 *
 * Price page, so no per-arm percentages and no milligram dose strings.
 * TRIUMPH-2 weight figures stay on the retatrutide encyclopedia entry.
 * The OSA section states what the separate placebo-controlled trials
 * report, without those figures. Its two Q&As are this page's FAQ;
 * the visible questions and the FAQPage JSON-LD are this same list.
 * Checked 2026-10-19 against PubMed (PMID 42810372) and ClinicalTrials.gov.
 * OSA sources checked against the NEJM paper (published online
 * 20 October 2026) and the 20 December 2024 FDA announcement.
 */
class RetatrutideTirzepatideCompareCopy
{
    public const SLUG = 'retatrutide-vs-tirzepatide';

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
            'link' => self::link(),
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
            'title' => 'TRIUMPH-2: retatrutide vs placebo',
            'paragraphs' => [
                'TRIUMPH-2 (NCT05929079; Bellido et al., The Lancet, published online 29 September 2026) was an 80-week Phase 3 trial that compared once-weekly retatrutide with placebo in 1,152 adults with type 2 diabetes and a BMI of 27 kg/m² or higher. It did not include tirzepatide, so it does not show how the two compounds compare. Retatrutide is investigational and not FDA-approved.',
            ],
            'notes' => [
                'TRIUMPH-2 compared retatrutide with placebo, not with tirzepatide. Tirzepatide’s SURMOUNT trials are separate trials with their own populations, durations, and placebo groups, so results from the two programmes are not a ranking of the two compounds.',
                'We found no published trial of retatrutide and tirzepatide used in combination. TRIUMPH-5 (NCT06662383) is a head-to-head comparison of retatrutide versus tirzepatide in adults with obesity, not a combination. ClinicalTrials.gov lists it as active, not recruiting, with no results posted (record last updated 20 July 2026; checked 19 October 2026). A vendor blend containing both is two molecules in one vial, not one compound.',
            ],
            'sections' => [
                self::osaSection(),
            ],
            'sources' => [
                [
                    'title' => 'Bellido V, et al. Retatrutide in adults with obesity and type 2 diabetes (TRIUMPH-2): a double-blind, parallel-group, randomised, placebo-controlled, phase 3 trial. The Lancet, published online 29 Sep 2026. DOI 10.1016/S0140-6736(26)01861-1. PMID 42810372.',
                    'url' => 'https://doi.org/10.1016/S0140-6736(26)01861-1',
                ],
                [
                    'title' => 'ClinicalTrials.gov, NCT05929079 (TRIUMPH-2).',
                    'url' => 'https://clinicaltrials.gov/study/NCT05929079',
                ],
                [
                    'title' => 'ClinicalTrials.gov, NCT06662383 (TRIUMPH-5, retatrutide compared with tirzepatide).',
                    'url' => 'https://clinicaltrials.gov/study/NCT06662383',
                ],
                [
                    'title' => 'Grunstein RR, Somers VK, Azarbarzin A, et al. Retatrutide Once Weekly for Treatment of Obstructive Sleep Apnea. N Engl J Med. Published online October 20, 2026. Funded by Eli Lilly. DOI: 10.1056/NEJMoa2611477.',
                    'url' => 'https://doi.org/10.1056/NEJMoa2611477',
                ],
                [
                    'title' => 'PubMed record, PMID 42904878.',
                    'url' => 'https://pubmed.ncbi.nlm.nih.gov/42904878/',
                ],
                [
                    'title' => 'ClinicalTrials.gov, TRIUMPH-1 master protocol (NCT05929066).',
                    'url' => 'https://clinicaltrials.gov/study/NCT05929066',
                ],
                [
                    'title' => 'ClinicalTrials.gov, TRIUMPH-2 master protocol (NCT05929079).',
                    'url' => 'https://clinicaltrials.gov/study/NCT05929079',
                ],
                [
                    'title' => 'Eli Lilly and Company (company-reported). News release, June 6, 2026: "Lilly\'s triple agonist, retatrutide, drove substantial improvements in weight, A1C, knee osteoarthritis pain, and obstructive sleep apnea…"',
                    'url' => 'https://investor.lilly.com/news-releases/news-release-details/lillys-triple-agonist-retatrutide-drove-substantial-improvements',
                ],
                [
                    'title' => 'Malhotra A, Grunstein RR, Fietze I, et al. Tirzepatide for the Treatment of Obstructive Sleep Apnea and Obesity. N Engl J Med. 2024;391(13):1193-1205 (online June 21, 2024). Funded by Eli Lilly. DOI: 10.1056/NEJMoa2404881. PMID 38912654. (open full text: https://pmc.ncbi.nlm.nih.gov/articles/PMC11598664/)',
                    'url' => 'https://doi.org/10.1056/NEJMoa2404881',
                ],
                [
                    'title' => 'ClinicalTrials.gov, SURMOUNT-OSA (NCT05412004).',
                    'url' => 'https://clinicaltrials.gov/study/NCT05412004',
                ],
                [
                    'title' => 'U.S. FDA. "FDA Approves First Medication for Obstructive Sleep Apnea." Press announcement, December 20, 2024.',
                    'url' => 'https://www.fda.gov/news-events/press-announcements/fda-approves-first-medication-obstructive-sleep-apnea',
                ],
            ],
        ];
    }

    /**
     * OSA trial note. Citation numbers continue the evidence source list:
     * the attached note's [1]–[8] are sources [4]–[11] here.
     *
     * @return array{title: string, paragraphs: list<string|array{lead: string, text: string}>}
     */
    private static function osaSection(): array
    {
        return [
            'title' => 'Obstructive sleep apnea (OSA): what the trials report',
            'paragraphs' => [
                'Both compounds have been studied in adults with moderate-to-severe OSA and excess weight (obesity or overweight in the retatrutide trials; obesity in the tirzepatide trials), using the same primary endpoint: change in the apnea-hypopnea index (AHI), the number of breathing pauses or shallow-breathing events per hour of sleep. The two programs were separate, placebo-controlled trials. No head-to-head trial has compared retatrutide with tirzepatide in OSA, and differences in populations, duration and estimands mean the results should not be ranked against each other.',
                [
                    'lead' => 'Retatrutide.',
                    'text' => 'TRIUMPH-1-OSA (participants without type 2 diabetes) and TRIUMPH-2-OSA (participants with type 2 diabetes) were reported together in a single developer-affiliated NEJM paper (Grunstein et al.), published online in October 2026 [4][5]. The OSA trials were nested within the larger TRIUMPH-1 and TRIUMPH-2 basket trials [6][7]. The primary endpoint was tested in the middle and highest dose arms; in each trial, AHI fell more in all three dose arms (lowest, middle and highest) than with placebo at week 80 under a hybrid-treatment estimand. The paper also reports intention-to-treat results. The most common adverse events were gastrointestinal [4]. An earlier company-reported Lilly release (June 2026) covered only the TRIUMPH-1 OSA basket and used an efficacy estimand [8]. Retatrutide is investigational and has no approved indication.',
                ],
                [
                    'lead' => 'Tirzepatide.',
                    'text' => 'SURMOUNT-OSA (Malhotra et al., NEJM 2024) was a developer-affiliated pair of trials: one in people not using positive airway pressure (PAP) therapy and one in people using it [9][10]. In each, AHI fell more with tirzepatide than with placebo at week 52 under the treatment-regimen estimand, the estimand the paper uses for its reported results; the paper also reports an efficacy estimand [9]. On 20 December 2024 the FDA approved Zepbound (tirzepatide) for moderate-to-severe OSA in adults with obesity [11].',
                ],
            ],
        ];
    }

    /**
     * Same strings the page renders and the FAQPage JSON-LD repeats.
     *
     * @return list<array{q: string, a: string}>
     */
    private static function faqs(): array
    {
        return [
            [
                'q' => 'Has retatrutide been compared directly with tirzepatide for sleep apnea?',
                'a' => 'No. TRIUMPH-1-OSA, TRIUMPH-2-OSA and SURMOUNT-OSA each compared one compound with placebo. They differed in population (with or without type 2 diabetes, PAP use), length (80 vs 52 weeks) and estimand, so they cannot be used to rank the two compounds against each other.',
            ],
            [
                'q' => 'Is either compound approved for OSA?',
                'a' => 'Tirzepatide, as Zepbound, has an FDA-approved OSA indication (December 2024). Retatrutide is not approved for any use; Lilly states it is legally available only to participants in its clinical trials [8].',
            ],
        ];
    }

    /**
     * One sentence with an inline link. Visible text is before . text . after.
     *
     * @return array{before: string, text: string, href: string, after: string}
     */
    public static function link(): array
    {
        return [
            'before' => 'Published TRIUMPH-2 results, with each analysis method labelled, are summarized in the ',
            'text' => 'retatrutide encyclopedia entry',
            'href' => '/encyclopedia/retatrutide',
            'after' => '. This page compares listings and prices only.',
        ];
    }
}
