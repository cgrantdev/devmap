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

        return array_merge([
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
        ], self::priceUpgrade());
    }

    /**
     * Price-intent copy for /compare/retatrutide. Content QA passed this
     * wording on 2026-10-08. Tokens in {braces} are filled in the controller.
     * Product links are ids, resolved to live URLs at render time.
     *
     * The testing-post link is stored twice: the shorter bullet and the
     * related-reading item without it, and the full versions behind
     * requires_published_slug. The controller keeps the full version only
     * when that Blog row is published.
     *
     * @return array<string, mixed>
     */
    private static function priceUpgrade(): array
    {
        $coaHref = '/blog/how-to-verify-a-peptide-vendor-certificate-of-analysis';
        $testingSlug = 'retatrutide-testing-coa-limits';
        $testingHref = '/blog/'.$testingSlug;
        $labsHref = '/testing-labs';

        $testingBullet = 'Some vendors publish a certificate of analysis (CoA) from an independent lab. Others publish an in-house sheet or nothing. A CoA only tells you something if it matches the lot, names the lab, and can be checked. ';

        return [
            'h1' => 'Retatrutide Price Comparison',
            'price_intro' => 'This page compares retatrutide research listings for sale in the Peptidemap catalog: {listing_count} listings from {vendor_count} vendors, as their prices read when we last checked them. Sort the table by listed price or by price per milligram. The cheapest retatrutide vial is not always the cheapest per mg, so the summary below shows both. Peptidemap does not sell retatrutide and does not vouch for any vendor. Retatrutide is an investigational compound, not an FDA-approved medicine, and listings here are for research use only.',
            'per_mg_title' => 'Retatrutide price per mg',
            'per_mg_intro' => 'The same listed price can buy very different amounts. This table divides each listing\'s listed price by its vial size in milligrams. It is sorted from lowest to highest price per mg, as a plain sort, not a ranking of vendors. Only USD single-vial listings with a confirmed vial size are included. Kits, multi-vial packs, blends, pens, and non-USD listings stay in the full table above. Price per mg says nothing about identity, purity, or testing. For that, read each vendor\'s certificate of analysis (see "Why retatrutide prices differ").',
            'code_names' => [
                'title' => 'Catalog names vendors use for retatrutide',
                'intro' => [
                    ['text' => 'Many research vendors don\'t list retatrutide under its own name. They use short catalog codes instead, and Peptidemap matches those listings to retatrutide so they show up in one table. The codes below appear in the current catalog. A code name tells you which listing you\'re looking at. It does not confirm what is in the vial. Only an independent, lot-matched test can do that (see '],
                    ['text' => 'How to read a peptide CoA', 'href' => $coaHref],
                    ['text' => ').'],
                ],
                'rows' => [
                    [
                        'label' => 'SA-3R (often searched as "sa 3r" or "sa3r")',
                        'listed_by' => 'Southern Aminos LLC',
                        'product_id' => 1087,
                        'link_label' => 'SA-3R — 30mg Single Vial',
                    ],
                    [
                        'label' => 'GLP-3 RT',
                        'listed_by' => 'Instant Peptides (the same label is also used by ALPHAPEP LLC, IDUN Peptides, and Nova Peptide Supply)',
                        'product_id' => 2861,
                        'link_label' => 'GLP-3 RT — 10mg · Vial',
                    ],
                    [
                        'label' => 'GLP-3R',
                        'listed_by' => 'Nura Peptides (also Aspire Peptides)',
                        'product_id' => 4049,
                        'link_label' => 'GLP-3R — 10MG',
                    ],
                    [
                        'label' => 'GLP3(R)',
                        'listed_by' => 'Oasis Lab',
                        'product_id' => 1585,
                        'link_label' => 'GLP3(R) — 10mg',
                    ],
                    [
                        'label' => 'Reta GLP-3',
                        'listed_by' => 'Vertex Labs',
                        'product_id' => 5552,
                        'link_label' => 'Reta GLP-3 — 10mg',
                    ],
                ],
                'blend' => [
                    'product_id' => 1065,
                    'lead' => 'Blend listing (two components), not a single-molecule retatrutide listing:',
                    'link_label' => 'SA-3R / SA-2T BLEND 34mg',
                    'after' => ' (Southern Aminos LLC). The name pairs two catalog codes, SA-3R and SA-2T, and Peptidemap files it under its \'Retatrutide / Tirzepatide Blend\' category, not in the retatrutide table above. It is excluded from every price-per-mg figure on this page, because a blend\'s milligrams are split between two compounds.',
                ],
                'outro' => 'Other catalog labels in the retatrutide table include R-GLP3, S13-R, EZP-3P, PP-3R, PG-3RT, OC-3RT, FG3-R, and Elite-3RT. Peptidemap also uses "GLP3-R" as its own alias label for retatrutide. None of these codes is a trial name, and none of these listings is Eli Lilly trial material. Retatrutide is not FDA-approved.',
            ],
            'why_prices' => [
                'title' => 'Why retatrutide prices differ',
                'intro' => 'Listed prices for retatrutide vary widely between vendors, and between listings from the same vendor. A lower number is not automatically a better comparison. These are the usual reasons:',
                'bullets' => [
                    [
                        'title' => 'Vial size.',
                        'parts' => [
                            ['text' => 'Listings range from 5 mg to 100 mg vials. A larger vial usually costs more in total but less per milligram, so compare price per mg as well as the sticker price.'],
                        ],
                    ],
                    [
                        'title' => 'Price per mg vs pack pricing.',
                        'parts' => [
                            ['text' => 'Multi-vial kits, boxes, and bulk listings spread one price across several vials. They sit in the full table, but they\'re kept out of per-mg math so they don\'t distort single-vial comparisons.'],
                        ],
                    ],
                    [
                        'title' => 'Third-party testing and CoA availability.',
                        'requires_published_slug' => $testingSlug,
                        'parts' => [
                            ['text' => $testingBullet.'Our guides cover '],
                            ['text' => 'how to verify a peptide CoA', 'href' => $coaHref],
                            ['text' => ' and '],
                            ['text' => 'which vendors use which testing labs', 'href' => $labsHref],
                            ['text' => '.'],
                        ],
                        'parts_when_testing_post_live' => [
                            ['text' => $testingBullet.'Our guides cover '],
                            ['text' => 'how to verify a peptide CoA', 'href' => $coaHref],
                            ['text' => ', '],
                            ['text' => 'what retatrutide testing can and can\'t show', 'href' => $testingHref],
                            ['text' => ', and '],
                            ['text' => 'which vendors use which testing labs', 'href' => $labsHref],
                            ['text' => '.'],
                        ],
                    ],
                    [
                        'title' => 'Stated purity.',
                        'parts' => [
                            ['text' => 'Vendors state purity figures, often 98% or 99%+. A stated figure is a claim. It is not a result unless a CoA for that lot backs it up.'],
                        ],
                    ],
                    [
                        'title' => 'Lot recency.',
                        'parts' => [
                            ['text' => 'A CoA from an old lot says little about the lot shipping today. Check the test date and lot number against the listing.'],
                        ],
                    ],
                    [
                        'title' => 'Shipping and region.',
                        'parts' => [
                            ['text' => 'Vendors are based in different countries and price in different currencies. Shipping costs and minimum orders are not included in listed prices. Non-USD listings show in their own currency and are left out of the USD per-mg comparison.'],
                        ],
                    ],
                    [
                        'title' => 'Codes and sale prices.',
                        'parts' => [
                            ['text' => 'The "price with code" column applies the vendor\'s Peptidemap code where one exists. Sale prices and codes change, so the listed price is the neutral basis for per-mg figures.'],
                        ],
                    ],
                    [
                        'title' => 'Catalog data quality.',
                        'parts' => [
                            ['text' => 'Some vendor feeds list a parent product without a size, or a size that doesn\'t match the product name. Those rows show in the table but are left out of per-mg figures until the size is confirmed.'],
                        ],
                    ],
                ],
                'closing' => 'Peptidemap compares listings. It does not test products, does not verify vendors, and does not endorse anyone in this table.',
            ],
            'faqs' => [
                [
                    'q' => 'Why is one retatrutide listing cheaper than another?',
                    'a' => 'Listings differ in vial size, in whether they are single vials or multi-vial kits, in currency, and in whether a vendor code or sale price applies. Vendors also differ in whether they publish a lot-matched third-party certificate of analysis and in the purity they state. A lower listed price on its own does not tell you which listing is comparable. Peptidemap shows listed price, price with code, and price per mg so the numbers can be compared, and it does not endorse any vendor.',
                ],
                [
                    'q' => 'Is the cheapest retatrutide per mg the same as the cheapest vial?',
                    'a' => 'Not usually. Smaller vials tend to have the lowest total price, while larger vials tend to have the lowest price per milligram. The summary at the top of this page shows both figures, calculated from the current catalog. Per-mg figures cover only USD single-vial listings with a confirmed vial size, and they exclude kits, packs, blends, and pens.',
                ],
                [
                    'q' => 'What is SA-3R peptide?',
                    'a' => 'SA-3R (also searched as "sa 3r" or "sa3r") is a catalog code Southern Aminos LLC uses on listings that Peptidemap\'s catalog matches to retatrutide. Peptidemap matches those listings to retatrutide so they appear in this comparison next to other vendors\' listings. A catalog code identifies a listing but does not confirm what a vial contains. Peptidemap lists Southern Aminos LLC as catalog data only and does not endorse it or any other vendor. Check the vendor\'s lot-matched certificate of analysis yourself.',
                ],
                [
                    'q' => 'Does a certificate of analysis prove a retatrutide vial is safe?',
                    'a' => 'No. A certificate of analysis reports what one lab measured in one sample, usually identity and purity, on one date. It can support a listing only if it names the lab, matches the lot you are looking at, and can be checked with the lab. It does not prove that every vial in a lot matches the sample, it does not cover tests the lab did not run, and it does not make a research compound safe for any use. Peptidemap\'s guide on reading a CoA explains what to check.',
                ],
                [
                    'q' => 'Is retatrutide FDA-approved?',
                    'a' => 'No. Retatrutide (LY3437943) is an investigational compound from Eli Lilly and is not approved by the FDA or the EMA for any use. TRIUMPH-1 and TRIUMPH-2 results were published on 29 September 2026, and Lilly has said it plans a U.S. filing in Q1 2027. A filing plan is not approval. Research-use vials sold online are not Lilly products and are not clinical-trial material.',
                ],
                [
                    'q' => 'Does Peptidemap sell retatrutide?',
                    'a' => 'No. Peptidemap is a comparison publisher. It does not sell, stock, or ship retatrutide or any other compound, and it is not a pharmacy or clinic. This page lists where retatrutide research listings are for sale from {vendor_count} vendors in our catalog, with links to the vendors\' own pages. Listings are for research use only, and Peptidemap does not say any vendor is safe or recommended.',
                ],
                [
                    'q' => 'How current are the retatrutide prices on this page?',
                    'a' => 'Prices come from each vendor\'s catalog. The time they were last checked is shown on this page ({prices_updated_human} when this page was generated). Vendors can change prices, codes, and stock at any time, so the price on the vendor\'s site is the one that applies.',
                ],
            ],
            'related_reading' => [
                ['label' => 'Retatrutide encyclopedia entry', 'href' => '/encyclopedia/retatrutide'],
                ['label' => 'How to read a peptide CoA', 'href' => $coaHref],
                [
                    'label' => 'Retatrutide testing: what a CoA can and can\'t show',
                    'href' => $testingHref,
                    'requires_published_slug' => $testingSlug,
                ],
                ['label' => 'Retatrutide vs tirzepatide listings', 'href' => '/compare/retatrutide-vs-tirzepatide'],
                ['label' => 'Third-party testing labs', 'href' => $labsHref],
            ],
        ];
    }
}
