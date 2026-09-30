<?php

namespace App\Support;

/**
 * Orforglipron is an oral nonpeptide GLP-1 receptor agonist.
 * Foundayo tablets are not research-chemical material. No invented CAS.
 */

class OrforglipronProfile
{
    public const SLUG = 'orforglipron';

    public static function matches(?string $slug, ?string $name = null): bool
    {
        $slug = strtolower(trim((string) $slug));
        $name = strtolower(trim((string) $name));

        return $slug === self::SLUG || $name === 'orforglipron';
    }

    public static function slug(): string
    {
        return self::SLUG;
    }

    public static function categoryName(): string
    {
        return 'Orforglipron';
    }

    public static function seoTitle(): string
    {
        return 'What is Orforglipron? Nonpeptide Oral GLP-1 Agonist';
    }

    public static function seoDescription(): string
    {
        return 'Orforglipron is an oral, nonpeptide GLP-1 receptor agonist. FDA-approved Foundayo tablets are a different product from research-chemical powders.';
    }

    public static function h1(): string
    {
        return 'What is orforglipron?';
    }

    public static function subtitle(): string
    {
        return 'Oral nonpeptide GLP-1 receptor agonist';
    }

    public static function educationTag(): string
    {
        return 'Small Molecule';
    }

    public static function cardDescription(): string
    {
        return self::seoDescription();
    }

    public static function ogEyebrow(): string
    {
        return 'Small-Molecule Research Compound';
    }

    public static function researchInstitution(): ?string
    {
        return 'Chugai Pharmaceutical / Eli Lilly and Company';
    }

    public static function researchUrl(): ?string
    {
        return 'https://pubchem.ncbi.nlm.nih.gov/compound/137319706';
    }

    public static function preservesExistingArticle(): bool
    {
        return false;
    }

    public static function overview(): string
    {
        return self::articleAttributes()['overview'];
    }

    public static function stubNarrative(): array
    {
        return self::narrativeFromArticle(self::articleAttributes());
    }

    public static function articleAttributes(): array
    {
        return [
            'title' => 'Orforglipron',
            'peptide_full_name' => self::subtitle(),
            'research_title' => 'Orforglipron: Nonpeptide Oral GLP-1 Receptor Agonist',
            'research_outline' => 'Research overview of orforglipron, a small-molecule nonpeptide GLP-1 receptor agonist, and how the approved Foundayo tablet differs from research-chemical material.',
            'research_url' => self::researchUrl(),
            'education_tag' => self::educationTag(),
            'tags' => ['Small Molecule', 'Nonpeptide', 'GLP-1 Receptor Agonist'],
            'description' => self::cardDescription(),
            'overview' => 'Orforglipron is a synthetic small-molecule GLP-1 receptor agonist designed for oral use. It is a nonpeptide: it is not built from an amino-acid chain. PubChem lists the free base as C48H48F2N10O5 (CID 137319706). The FDA-approved medicine is Foundayo, an orforglipron tablet from Eli Lilly, approved on April 1, 2026 for chronic weight management together with diet and physical activity. Research-chemical powders, capsules, or vials sold under the orforglipron name are not Foundayo and are not the approved dosage form. This page does not provide dosing or prices.',
            'molecular_formula' => 'C48H48F2N10O5',
            'molecular_weight' => '883.0 g/mol',
            'cas_registry_number' => '',
            'half_life' => 'See the Foundayo prescribing information for the approved tablet. Not stated here for research-chemical material.',
            'bioavailability' => 'The approved product is an oral tablet. Research-chemical material is a separate substance presentation and is not the approved dosage form.',
            'storage' => 'Follow the applicable product labeling or supplier certificate of analysis. Do not assume research-chemical storage conditions match Foundayo tablets.',
            'background' => 'Orforglipron was discovered by Chugai Pharmaceutical and licensed to Eli Lilly. It is a small-molecule agonist of the GLP-1 receptor, in a different chemical class from peptide GLP-1 medicines such as semaglutide or tirzepatide. On April 1, 2026 the FDA approved Foundayo (orforglipron) tablets for adults with obesity, or overweight with a weight-related condition, as an adjunct to a reduced-calorie diet and increased physical activity. Foundayo is a finished prescription tablet. Material offered for laboratory research under the same chemical name is not that tablet, is not interchangeable with it, and should not be described as Foundayo.',
            'mechanism_of_action_intro' => 'Orforglipron binds and activates the GLP-1 receptor as a small molecule. That mechanism is the same receptor class targeted by peptide GLP-1 agonists, but the ligand itself is nonpeptide.',
            'mechanism_subsections' => [
                [
                    'title' => 'Nonpeptide GLP-1 receptor agonism',
                    'intro' => 'Published descriptions and the Foundayo prescribing information identify orforglipron as a small-molecule GLP-1 receptor agonist.',
                    'items' => [
                        'Activates the GLP-1 receptor without an amino-acid backbone.',
                        'Developed for once-daily oral administration in the approved tablet.',
                        'Chemically distinct from peptide GLP-1 receptor agonists.',
                    ],
                ],
            ],
            'preclinical_intro' => 'Clinical development of the oral molecule supported the Foundayo approval. This summary does not restate study doses or imply that research-chemical powders match the approved tablet.',
            'preclinical_subsections' => [
                [
                    'title' => 'Approved product versus research chemical',
                    'findings' => [
                        [
                            'title' => 'Foundayo tablets',
                            'description' => 'Foundayo is the FDA-approved orforglipron tablet for chronic weight management in specified adults, together with diet and physical activity. Use of that medicine is governed by its prescribing information.',
                        ],
                        [
                            'title' => 'Research-chemical material',
                            'description' => 'Powders and other research presentations that use the orforglipron name are not Foundayo. Identity, purity, and formulation are not established by the Foundayo approval.',
                        ],
                    ],
                ],
            ],
            'preclinical_disclaimer' => 'The Foundayo approval does not convert research-chemical orforglipron into the approved tablet. No dosing guidance is provided on this page.',
            'human_use_intro' => 'The only FDA-approved orforglipron product named here is Foundayo tablets. This encyclopedia entry is not a prescription and does not describe how to take either the tablet or research material.',
            'human_use_subsections' => [
                [
                    'title' => 'Labeled product',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'Foundayo (orforglipron) tablets were FDA-approved on April 1, 2026 as an oral nonpeptide GLP-1 receptor agonist for chronic weight management in specified adults, with diet and physical activity. Concomitant use with another GLP-1 receptor agonist is not recommended in the labeling.',
                        ],
                    ],
                ],
            ],
            'regulatory_subsections' => [
                [
                    'title' => 'Regulatory status',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'Foundayo is an approved prescription tablet. Research-chemical orforglipron is not that product and is not a substitute for it.',
                        ],
                    ],
                ],
            ],
            'regulatory_important_note' => 'Do not treat research-chemical orforglipron as Foundayo. This page is informational, does not give doses or prices, and is not medical advice.',
            'potential_applications_intro' => 'Research interest follows GLP-1 receptor pharmacology. The approved use of Foundayo is the labeled weight-management indication, which applies to the tablet, not to research powders.',
            'potential_applications' => [
                [
                    'title' => 'GLP-1 receptor pharmacology',
                    'description' => 'A nonpeptide ligand for studying oral GLP-1 receptor agonism, distinct from peptide analogs.',
                ],
                [
                    'title' => 'Labeled Foundayo use',
                    'description' => 'Chronic weight management in the adults described in the Foundayo prescribing information. That indication belongs to the approved tablet.',
                ],
            ],
            'potential_applications_important_context' => 'Research-chemical material is not Foundayo and has no approval of its own. No therapeutic claim is made for research presentations.',
            'conclusion' => 'Orforglipron is a small-molecule, nonpeptide GLP-1 receptor agonist. Foundayo tablets are the FDA-approved product. Research powders and similar listings are a different presentation and must not be described as Foundayo or as a peptide.',
            'references' => [
                [
                    'title' => 'FDA approves Foundayo (orforglipron) tablets',
                    'authors' => 'Eli Lilly and Company',
                    'description' => 'Approval announcement for the oral nonpeptide GLP-1 receptor agonist tablet.',
                    'citation' => 'Eli Lilly and Company press release. April 1, 2026.',
                    'links' => [
                        ['label' => 'Lilly', 'url' => 'https://investor.lilly.com/news-releases/news-release-details/fda-approves-lillys-foundayotm-orforglipron-only-glp-1-pill'],
                    ],
                ],
                [
                    'title' => 'PubChem record for orforglipron (CID 137319706)',
                    'authors' => '',
                    'description' => 'Free-base formula C48H48F2N10O5, molecular weight 883.0. No CAS is stated on this page.',
                    'citation' => 'National Center for Biotechnology Information. PubChem Compound CID 137319706.',
                    'links' => [
                        ['label' => 'PubChem', 'url' => 'https://pubchem.ncbi.nlm.nih.gov/compound/137319706'],
                    ],
                ],
            ],
            'key_points' => [
                'Orforglipron is a small-molecule, nonpeptide GLP-1 receptor agonist.',
                'Foundayo is the FDA-approved orforglipron tablet (April 1, 2026).',
                'Research-chemical powders and vials are not Foundayo and are not the approved dosage form.',
                'This page does not provide dosing or prices.',
            ],
            'areas_of_research_intro' => 'The relevant class is oral nonpeptide GLP-1 receptor agonism, not peptide chemistry.',
            'areas_of_research' => [
                ['name' => 'GLP-1 receptor pharmacology', 'description' => 'Small-molecule agonism at the GLP-1 receptor'],
                ['name' => 'Oral GLP-1 medicines', 'description' => 'How a nonpeptide ligand differs from peptide GLP-1 analogs'],
            ],
            'key_effects' => [
                'GLP-1 receptor agonism',
                'Oral small-molecule ligand',
                'Distinct from peptide GLP-1 analogs',
            ],
            'common_use_cases' => [
                'GLP-1 receptor pharmacology research',
                'Comparison of nonpeptide and peptide GLP-1 ligands',
            ],
            'how_it_works' => 'Orforglipron is a nonpeptide small molecule that activates the GLP-1 receptor. It is not an amino-acid hormone.',
            'faqs' => [
                [
                    'question' => 'Is orforglipron a peptide?',
                    'answer' => 'No. Orforglipron is a synthetic small-molecule, nonpeptide GLP-1 receptor agonist.',
                ],
                [
                    'question' => 'Is research-chemical orforglipron the same as Foundayo?',
                    'answer' => 'No. Foundayo is the FDA-approved orforglipron tablet. Research powders, capsules, or vials that use the orforglipron name are not Foundayo and are not the approved dosage form.',
                ],
                [
                    'question' => 'What is Foundayo approved for?',
                    'answer' => 'Foundayo tablets are approved for chronic weight management in specified adults, together with diet and physical activity. That approval does not extend to research-chemical material. This page does not provide dosing.',
                ],
            ],
            'seo_page_title' => self::seoTitle(),
            'seo_description' => self::seoDescription(),
            'seo_og_title' => self::seoTitle(),
            'seo_og_description' => self::seoDescription(),
            'show_in_encyclopedia' => true,
            'rating' => '0.00',
            'rating_count' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $article
     * @return array<string, mixed>
     */
    public static function narrativeFromArticle(array $article): array
    {
        return [
            'subtitle' => $article['peptide_full_name'],
            'tags' => $article['tags'],
            'overview' => $article['overview'],
            'keyPoints' => $article['key_points'],
            'areasOfResearch' => $article['areas_of_research'],
            'areasOfResearchIntro' => $article['areas_of_research_intro'],
            'background' => $article['background'],
            'mechanismOfActionIntro' => $article['mechanism_of_action_intro'],
            'mechanismSubsections' => $article['mechanism_subsections'],
            'preclinicalIntro' => $article['preclinical_intro'],
            'preclinicalSubsections' => $article['preclinical_subsections'],
            'preclinicalDisclaimer' => $article['preclinical_disclaimer'],
            'humanUseIntro' => $article['human_use_intro'],
            'humanUseSubsections' => $article['human_use_subsections'],
            'regulatorySubsections' => $article['regulatory_subsections'],
            'regulatoryImportantNote' => $article['regulatory_important_note'],
            'potentialApplicationsIntro' => $article['potential_applications_intro'],
            'potentialApplications' => $article['potential_applications'],
            'potentialApplicationsImportantContext' => $article['potential_applications_important_context'],
            'conclusion' => $article['conclusion'],
            'references' => $article['references'],
            'molecularInfo' => [
                'formula' => $article['molecular_formula'],
                'molecularWeight' => $article['molecular_weight'],
                'casNumber' => $article['cas_registry_number'],
            ],
        ];
    }
}
