<?php

namespace App\Support;

/**
 * Company-reported topline note for /compare/retatrutide-vs-semaglutide.
 *
 * Price page. No per-arm figures and no milligram dose strings.
 * The 28 Oct 2026 Lilly announcement is marked company-reported and
 * is not peer-reviewed. Peptidemap is a research-use-only comparison
 * publisher, not a clinic.
 */
class RetatrutideSemaglutideCompareCopy
{
    public const SLUG = 'retatrutide-vs-semaglutide';

    public const HEADING = 'Head-to-head trial: TRANSCEND-T2D-2 (company-reported)';

    public const LILLY_URL = 'https://investor.lilly.com/news-releases/news-release-details/lillys-retatrutide-delivered-greater-a1c-reduction-and-more';

    /**
     * One note, 60-90 words. Bold and links render in CompareCompoundVs.
     */
    public const NOTE = 'Lilly has reported topline results from TRANSCEND-T2D-2, an open-label Phase 3 head-to-head trial of retatrutide versus once-weekly injectable semaglutide in adults with type 2 diabetes on metformin, announced on 28 Oct 2026 [company-reported]. Full results have not been peer-reviewed or published. Retatrutide is investigational and not approved. Peptidemap is a research-use-only peptide comparison publisher, not a clinic. Sources and limits are in the [retatrutide vs semaglutide article](/blog/retatrutide-vs-semaglutide) and in Lilly\'s [topline press release]('.self::LILLY_URL.').';

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
            'title' => self::HEADING,
            'paragraphs' => [],
            'notes' => [
                self::NOTE,
            ],
            'sources' => [
                [
                    'title' => 'Eli Lilly and Company (company-reported). TRANSCEND-T2D-2 topline press release, 28 October 2026.',
                    'url' => self::LILLY_URL,
                ],
            ],
        ];
    }
}
