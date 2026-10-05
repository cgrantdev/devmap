<?php

namespace App\Support;

/**
 * TRIUMPH compare copy for /compare/retatrutide.
 *
 * Kept off education_posts so the retatrutide encyclopedia page is not
 * overwritten. The lead is the NEJM treatment-regimen result. 28.3% is the
 * efficacy estimand in that same paper.
 */
class RetatrutideCompareNarrative
{
    /**
     * @return array<string, mixed>|null
     */
    public static function forSlug(?string $slug): ?array
    {
        $canonical = CompareSlug::canonical($slug);
        if ($canonical !== 'retatrutide' && strtolower(trim((string) $slug)) !== 'retatrutide') {
            return null;
        }

        return self::payload();
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(): array
    {
        $lead = 'TRIUMPH-1 (NEJM, 29 Sep 2026) treatment-regimen result at 12 mg was about −25.0%. The same paper’s efficacy estimand at 12 mg was −28.3%.';

        return [
            'lead' => $lead,
            'paragraphs' => [
                'Retatrutide (LY3437943) is an investigational GIP, GLP-1, and glucagon receptor agonist. It is not FDA-approved and is not a marketed medicine. PeptideMap cites published trial readouts and compares research-use (RUO) vendor listings. This page is not medical advice, not a dose, and not a clinic or pharmacy recommendation.',
                'TRIUMPH-1 (NCT05929066; Jastreboff et al. for the TRIUMPH-1 Investigators, N Engl J Med, 29 Sep 2026; DOI 10.1056/NEJMoa2604169; PMID 42814954) enrolled adults with obesity or overweight without diabetes. Under the treatment-regimen estimand, mean weight change was −17.6% at 4 mg, −23.7% at 9 mg, and −25.0% at 12 mg, versus −3.9% with placebo. The same paper’s efficacy estimand was −19.0% at 4 mg, −25.9% at 9 mg, and −28.3% at 12 mg. Those are two analysis methods in one study, not rival headlines and not a second trial.',
            ],
            'key_points' => [
                'Lead figure: TRIUMPH-1 treatment-regimen result of about −25.0% at 12 mg (NEJM, 29 Sep 2026), versus −3.9% placebo.',
                '−28.3% is the efficacy estimand at 12 mg in that same NEJM paper, not a rival result.',
                'TRIUMPH-2 is a separate type 2 diabetes trial. Lilly’s 29 Sep 2026 release and the Lancet paper report different estimands; both are labeled below.',
                'Not a head-to-head comparison with semaglutide or tirzepatide. A stated Q1 2027 U.S. BLA is a filing plan, not approval.',
                'RUO vendor listings are not clinical-trial supply.',
            ],
            'sections' => [
                [
                    'title' => 'TRIUMPH-2 efficacy estimand (Lilly release, 29 Sep 2026)',
                    'paragraphs' => [
                        'TRIUMPH-2 (NCT05929079) was a separate Phase 3 trial in 1,152 adults with type 2 diabetes and obesity or overweight, followed for 80 weeks. Eli Lilly’s press release of 29 Sep 2026 reports the efficacy estimand: mean body-weight change −12.7% (4 mg), −19.1% (9 mg), and −20.8% (12 mg) versus −4.0% with placebo. A1C fell about 1.4–1.6 percentage points versus −0.2 with placebo. Gastrointestinal events were the most common adverse events Lilly reported (diarrhea, nausea, vomiting, constipation), and dysesthesia was also reported. Those figures describe the trial. They are not instructions for using a research-use product.',
                    ],
                ],
                [
                    'title' => 'TRIUMPH-2 treatment-regimen estimand (Lancet)',
                    'paragraphs' => [
                        'The Lancet paper (DOI 10.1016/S0140-6736(26)01861-1) reports the treatment-regimen estimand for the same TRIUMPH-2 trial: mean percent weight change −11.9% (4 mg), −16.8% (9 mg), and −18.8% (12 mg) versus −5.1% with placebo. Estimated treatment differences versus placebo were −6.9, −11.8, and −13.8 percentage points. Quote the estimand with the number. TRIUMPH-2 compared retatrutide with placebo, not with semaglutide or tirzepatide.',
                    ],
                ],
                [
                    'title' => 'Background that is not the −25.0% source',
                    'paragraphs' => [
                        'Phase 2 obesity results (Jastreboff et al., NEJM 2023) are earlier background for a non-diabetes cohort. They are not the source of the TRIUMPH-1 treatment-regimen result of about −25.0%. Lilly has said it plans a U.S. biologics license application in Q1 2027. That is a company filing plan, not marketing authorization.',
                    ],
                ],
            ],
            'conclusion' => 'Retatrutide remains investigational and unapproved. The comparison lead is the TRIUMPH-1 treatment-regimen result of about −25.0% at 12 mg from the 29 Sep 2026 NEJM paper. −28.3% is that paper’s efficacy estimand. TRIUMPH-2’s Lilly efficacy-estimand figures and the Lancet treatment-regimen figures are a different trial in adults with type 2 diabetes.',
            'regulatory_note' => 'Retatrutide is not approved by the FDA or the EMA for any indication. PeptideMap is not a pharmacy or a clinic. Vendor listings on this page are research-use comparison data only. They are not prescriptions, compounding advice, or clinical-trial supply.',
            'references' => [
                [
                    'title' => 'Jastreboff et al., TRIUMPH-1, N Engl J Med, 29 Sep 2026. DOI 10.1056/NEJMoa2604169. PMID 42814954. NCT05929066.',
                    'url' => 'https://doi.org/10.1056/NEJMoa2604169',
                ],
                [
                    'title' => 'Eli Lilly and Company, TRIUMPH-2 press release, 29 Sep 2026.',
                    'url' => 'https://www.prnewswire.com/news-releases/lillys-triple-agonist-retatrutide-delivered-substantial-weight-loss-and-a1c-reduction-underscoring-its-potential-promise-for-people-with-obesity-and-type-2-diabetes-302891798.html',
                ],
                [
                    'title' => 'The Lancet, TRIUMPH-2, DOI 10.1016/S0140-6736(26)01861-1. NCT05929079.',
                    'url' => 'https://www.thelancet.com/journals/lancet/article/PIIS0140-6736(26)01861-1/abstract',
                ],
            ],
        ];
    }
}
