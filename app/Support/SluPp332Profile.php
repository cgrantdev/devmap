<?php

namespace App\Support;

/**
 * Canonical encyclopedia framing for SLU-PP-332.
 *
 * SLU-PP-332 is a synthetic small-molecule pan-agonist of the
 * estrogen-related receptors (ERRα, ERRβ, ERRγ). It is not a peptide.
 * Vendor copy sometimes calls it a REV-ERB agonist; the primary
 * literature (Billon et al., ACS Chem Biol 2023; JPET 2024) does not.
 */
class SluPp332Profile
{
    public const SLUG = 'slu-pp-332';

    public static function matches(?string $slug, ?string $name = null): bool
    {
        $slug = strtolower(trim((string) $slug));
        $name = strtolower(trim((string) $name));

        return $slug === self::SLUG || $name === 'slu-pp-332';
    }

    public static function seoTitle(): string
    {
        return 'What is SLU-PP-332? Small-Molecule ERR Agonist';
    }

    public static function seoDescription(): string
    {
        return 'SLU-PP-332 is a synthetic small-molecule pan-agonist of the estrogen-related receptors, studied in preclinical models as an exercise mimetic. Research use only.';
    }

    public static function h1(): string
    {
        return 'What is SLU-PP-332?';
    }

    public static function subtitle(): string
    {
        return 'Small-molecule estrogen-related receptor (ERR) pan-agonist';
    }

    public static function educationTag(): string
    {
        return 'Small Molecule';
    }

    public static function categoryName(): string
    {
        return 'SLU-PP-332';
    }

    public static function slug(): string
    {
        return self::SLUG;
    }

    public static function researchInstitution(): ?string
    {
        return 'Burris laboratory (Saint Louis University / Washington University)';
    }

    public static function researchUrl(): ?string
    {
        return 'https://doi.org/10.1021/acschembio.2c00720';
    }

    public static function preservesExistingArticle(): bool
    {
        return false;
    }

    public static function cardDescription(): string
    {
        return self::seoDescription();
    }

    public static function ogEyebrow(): string
    {
        return 'Small-Molecule Research Compound';
    }

    public static function overview(): string
    {
        return self::articleAttributes()['overview'];
    }

    /**
     * Inertia field names used when a category exists but no education
     * post has been saved yet. Keeps the empty stub from rendering
     * peptide framing or a blank overview.
     */
    public static function stubNarrative(): array
    {
        $article = self::articleAttributes();

        return [
            'subtitle' => self::subtitle(),
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

    public static function articleAttributes(): array
    {
        return [
            'title' => 'SLU-PP-332',
            'peptide_full_name' => self::subtitle(),
            'research_title' => 'SLU-PP-332: Small-Molecule ERR Agonist Research Overview',
            'research_outline' => 'A research overview of SLU-PP-332, a synthetic small-molecule pan-agonist of the estrogen-related receptors, covering chemical identity, preclinical exercise-mimetic findings, and research-use-only status.',
            'research_url' => 'https://doi.org/10.1021/acschembio.2c00720',
            'education_tag' => 'Small Molecule',
            'tags' => ['Small Molecule', 'ERR Agonist', 'Research Compound'],
            'description' => self::cardDescription(),
            'overview' => 'SLU-PP-332 is a synthetic small-molecule pan-agonist of the estrogen-related receptors ERRα, ERRβ, and ERRγ. It is a research chemical, not a peptide: PubChem lists it as a benzohydrazide (C18H14N2O2; CAS 303760-60-3) with no amino-acid sequence. Published work characterizes it as an exercise-mimetic probe that activates an acute aerobic-exercise transcriptional program in preclinical models. Some secondary sources call it a REV-ERB agonist; the primary literature identifies it as an ERR agonist, which is a different nuclear-receptor family. It is not approved for human use and is discussed here for research context only.',
            'molecular_formula' => 'C18H14N2O2',
            'molecular_weight' => '290.3 g/mol',
            'cas_registry_number' => '303760-60-3',
            'half_life' => 'Not established in humans',
            'bioavailability' => 'Not established in humans. Published exposure data are from preclinical models.',
            'storage' => 'Protect from light and moisture. Long-term research storage is typically frozen. Follow the supplier certificate of analysis.',
            'background' => 'SLU-PP-332 was developed as a chemical tool for the estrogen-related receptors, orphan nuclear receptors that regulate mitochondrial and oxidative metabolism. The compound code reflects work associated with Saint Louis University. Billon and colleagues reported it in 2023 as a synthetic ERR pan-agonist with the greatest potency at ERRα, suitable for in vivo experiments in animal models. A 2024 follow-up examined the same molecule in mouse models of obesity and metabolic syndrome. The systematic name is (E)-4-hydroxy-N\'-(naphthalen-2-ylmethylene)benzohydrazide. That structure is a small molecule. It is not a peptide, and it has no residue sequence. REV-ERB agonists such as SR9009 come from a different chemical series and a different receptor family. Profiles that label SLU-PP-332 a REV-ERB agonist are not supported by the primary papers.',
            'mechanism_of_action_intro' => 'SLU-PP-332 binds and activates the three estrogen-related receptors, with the highest reported potency at ERRα. ERR activation is linked to mitochondrial biogenesis, fatty-acid oxidation, and the transcriptional program of aerobic exercise in skeletal muscle.',
            'mechanism_subsections' => [
                [
                    'title' => 'ERR pan-agonism',
                    'intro' => 'Cell-based reporter work describes SLU-PP-332 as an agonist at ERRα, ERRβ, and ERRγ rather than a peptide-hormone ligand or a REV-ERB ligand.',
                    'items' => [
                        'Activates ERRα, ERRβ, and ERRγ, with the greatest potency reported at ERRα.',
                        'Induces an ERRα-dependent acute aerobic-exercise gene program in preclinical muscle studies.',
                        'Increases mitochondrial function and cellular respiration in a skeletal-muscle cell line.',
                    ],
                ],
            ],
            'preclinical_intro' => 'Published evidence is preclinical. The lead papers report exercise-capacity and metabolic findings in cell culture and in mice. Those results are research observations, not human efficacy data.',
            'preclinical_subsections' => [
                [
                    'title' => 'Exercise-mimetic findings',
                    'findings' => [
                        [
                            'title' => 'Skeletal muscle and endurance',
                            'description' => 'In mice, SLU-PP-332 increased type IIa oxidative skeletal-muscle fibers and enhanced exercise endurance. The endurance effect depended on ERRα.',
                        ],
                        [
                            'title' => 'Energy expenditure',
                            'description' => 'In diet-induced obese and ob/ob mouse models, administration increased energy expenditure and fatty-acid oxidation and was accompanied by less fat-mass accumulation.',
                        ],
                        [
                            'title' => 'Metabolic syndrome models',
                            'description' => 'The same ERR agonist reduced obesity and improved insulin sensitivity in those mouse models. Translation to humans has not been shown.',
                        ],
                    ],
                ],
            ],
            'preclinical_disclaimer' => 'All findings above are from cell culture and animal models. They do not establish human safety or effectiveness. No dosing guidance is provided.',
            'human_use_intro' => 'No published human clinical trials of SLU-PP-332 are cited in the primary papers summarized here. Human pharmacokinetics, safety, and efficacy have not been established.',
            'human_use_subsections' => [
                [
                    'title' => 'Clinical evidence',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'SLU-PP-332 remains a preclinical research compound. It should not be described as a therapeutic, a supplement with established human effects, or a peptide drug.',
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
                            'value' => 'SLU-PP-332 is not approved by the FDA, EMA, or another medicines regulator for human use. Material offered commercially is positioned as a research chemical.',
                        ],
                        [
                            'type' => 'content',
                            'value' => 'Analytical anti-doping literature has flagged pan-ERR agonists, including SLU-PP-332, as non-approved substances with performance-related research interest. This page does not interpret sport-rule status.',
                        ],
                    ],
                ],
            ],
            'regulatory_important_note' => 'SLU-PP-332 is an experimental small-molecule research compound. It is not approved for human consumption, therapeutic use, or self-administration. This profile is informational and does not provide dosing, protocols, or sourcing advice.',
            'potential_applications_intro' => 'Research interest follows the receptor biology, not an approved indication. The domains below are preclinical research topics.',
            'potential_applications' => [
                [
                    'title' => 'Exercise-mimetic probe research',
                    'description' => 'A tool compound for studying ERR-dependent transcriptional programs that overlap with aerobic exercise in animal and cell models.',
                ],
                [
                    'title' => 'Mitochondrial metabolism',
                    'description' => 'Relevant to laboratory work on oxidative metabolism, fatty-acid oxidation, and mitochondrial function downstream of ERR activation.',
                ],
                [
                    'title' => 'Metabolic-disease models',
                    'description' => 'Used in mouse models of obesity and metabolic syndrome to study energy expenditure and insulin sensitivity. Human benefit has not been demonstrated.',
                ],
            ],
            'potential_applications_important_context' => 'These are research contexts drawn from preclinical papers. No therapeutic claim is made. SLU-PP-332 is for research use only.',
            'conclusion' => 'SLU-PP-332 is a synthetic small-molecule ERR pan-agonist, not a peptide and not a REV-ERB agonist. The primary literature describes it as an exercise-mimetic chemical probe with preclinical effects on oxidative muscle phenotype, endurance, energy expenditure, and metabolic readouts in mice. No human approval and no established human safety profile exist. Encyclopedia copy should name the compound as a small-molecule research chemical and keep a research-use-only frame.',
            'references' => [
                [
                    'title' => 'Synthetic ERRα/β/γ Agonist Induces an ERRα-Dependent Acute Aerobic Exercise Response and Enhances Exercise Capacity',
                    'authors' => 'Billon C, Sitaula S, Banerjee S, et al.',
                    'description' => 'Original report of SLU-PP-332 as a synthetic ERR pan-agonist and preclinical exercise mimetic.',
                    'citation' => 'ACS Chemical Biology. 2023;18(4):756-771. doi:10.1021/acschembio.2c00720',
                    'links' => [
                        ['label' => 'DOI', 'url' => 'https://doi.org/10.1021/acschembio.2c00720'],
                    ],
                ],
                [
                    'title' => 'A Synthetic ERR Agonist Alleviates Metabolic Syndrome',
                    'authors' => 'Billon C, Schoepke E, Avdagic A, et al.',
                    'description' => 'Follow-up in mouse models of obesity and metabolic syndrome.',
                    'citation' => 'Journal of Pharmacology and Experimental Therapeutics. 2024;388(2):232-240. doi:10.1124/jpet.123.001733',
                    'links' => [
                        ['label' => 'DOI', 'url' => 'https://doi.org/10.1124/jpet.123.001733'],
                    ],
                ],
                [
                    'title' => 'PubChem record for SLU-PP-332 (CID 5338394)',
                    'authors' => '',
                    'description' => 'Chemical identity: C18H14N2O2, molecular weight 290.3, CAS 303760-60-3.',
                    'citation' => 'National Center for Biotechnology Information. PubChem Compound CID 5338394.',
                    'links' => [
                        ['label' => 'PubChem', 'url' => 'https://pubchem.ncbi.nlm.nih.gov/compound/5338394'],
                    ],
                ],
            ],
            'key_points' => [
                'SLU-PP-332 is a synthetic small-molecule pan-agonist of ERRα, ERRβ, and ERRγ, with the highest reported potency at ERRα.',
                'It is a benzohydrazide (C18H14N2O2; CAS 303760-60-3), not a peptide.',
                'Preclinical papers describe exercise-mimetic and metabolic effects in cell and mouse models.',
                'Not approved for human use. Research use only. No dosing guidance is provided here.',
            ],
            'areas_of_research_intro' => 'Laboratory work on SLU-PP-332 sits in nuclear-receptor pharmacology and exercise metabolism, not in peptide biology.',
            'areas_of_research' => [
                ['name' => 'ERR pharmacology', 'description' => 'Pan-agonism at estrogen-related receptors, distinct from REV-ERB ligands'],
                ['name' => 'Exercise metabolism', 'description' => 'Oxidative muscle phenotype and endurance readouts in preclinical models'],
                ['name' => 'Metabolic research', 'description' => 'Energy expenditure and insulin-sensitivity readouts in mouse models of obesity'],
            ],
            'key_effects' => [
                'ERR pan-agonism in cell-based assays',
                'Exercise-mimetic gene program in preclinical muscle studies',
                'Higher energy expenditure in mouse metabolic models',
            ],
            'common_use_cases' => [
                'Nuclear-receptor pharmacology research',
                'In-vitro ERR assays',
                'Preclinical exercise-metabolism studies',
            ],
            'how_it_works' => 'SLU-PP-332 is a small-molecule agonist of ERRα, ERRβ, and ERRγ. ERR activation is tied to mitochondrial and oxidative metabolic transcription. It does not act as a peptide hormone.',
            'faqs' => [
                [
                    'question' => 'What is SLU-PP-332?',
                    'answer' => 'SLU-PP-332 is a synthetic small-molecule pan-agonist of the estrogen-related receptors ERRα, ERRβ, and ERRγ. It is studied in preclinical models as an exercise mimetic. Research use only.',
                ],
                [
                    'question' => 'Is SLU-PP-332 a peptide?',
                    'answer' => 'No. SLU-PP-332 is a small molecule, (E)-4-hydroxy-N\'-(naphthalen-2-ylmethylene)benzohydrazide (CAS 303760-60-3). It has no amino-acid sequence.',
                ],
                [
                    'question' => 'Is SLU-PP-332 a REV-ERB agonist?',
                    'answer' => 'The primary literature characterizes SLU-PP-332 as an estrogen-related receptor agonist. REV-ERB is a different nuclear-receptor family. Secondary and vendor writeups sometimes conflate the two; this profile follows the published ERR characterization.',
                ],
                [
                    'question' => 'Is SLU-PP-332 approved for human use?',
                    'answer' => 'No. It is an experimental research compound and is not approved for human therapeutic use.',
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
}
