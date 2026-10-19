<?php

namespace App\Support;

/**
 * Neutral TRIUMPH-2 context for /compare/retatrutide-vs-tirzepatide.
 *
 * Price page, so no per-arm results, no estimands, no mg figures and no
 * FAQ (house rule #31, commit 401ab59). Published results live in the
 * retatrutide encyclopedia entry. Checked 2026-10-19 against PubMed
 * (PMID 42810372) and ClinicalTrials.gov.
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
            // No FAQ block on this vs page (neutral price page).
            'faqs' => [],
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
