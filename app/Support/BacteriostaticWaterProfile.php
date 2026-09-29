<?php

namespace App\Support;

/**
 * Bacteriostatic water is a sterile diluent, not a peptide.
 * No reconstitution volumes or mixing charts.
 */

class BacteriostaticWaterProfile
{
    public const SLUG = 'bacteriostatic-water';

    public static function matches(?string $slug, ?string $name = null): bool
    {
        $slug = strtolower(trim((string) $slug));
        $name = strtolower(trim((string) $name));

        return $slug === self::SLUG
            || $name === 'bacteriostatic water'
            || $name === 'bacteriostatic-water';
    }

    public static function slug(): string
    {
        return self::SLUG;
    }

    public static function categoryName(): string
    {
        return 'Bacteriostatic Water';
    }

    public static function seoTitle(): string
    {
        return 'What is Bacteriostatic Water? Sterile Diluent';
    }

    public static function seoDescription(): string
    {
        return 'Bacteriostatic water is sterile water preserved with 0.9% benzyl alcohol. It is a diluent, not an active compound. No mixing guidance is given here.';
    }

    public static function h1(): string
    {
        return 'What is bacteriostatic water?';
    }

    public static function subtitle(): string
    {
        return 'Sterile diluent with 0.9% benzyl alcohol';
    }

    public static function educationTag(): string
    {
        return 'Diluent';
    }

    public static function cardDescription(): string
    {
        return self::seoDescription();
    }

    public static function ogEyebrow(): string
    {
        return 'Diluent, Not an Active Compound';
    }

    public static function researchInstitution(): ?string
    {
        return 'United States Pharmacopeia';
    }

    public static function researchUrl(): ?string
    {
        return null;
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
        return OrforglipronProfile::narrativeFromArticle(self::articleAttributes());
    }

    public static function articleAttributes(): array
    {
        return [
            'title' => 'Bacteriostatic Water',
            'peptide_full_name' => self::subtitle(),
            'research_title' => 'Bacteriostatic Water: A Diluent, Not an Active Compound',
            'research_outline' => 'What bacteriostatic water is: sterile water for injection preserved with benzyl alcohol, used as a diluent. Not a peptide and not a drug.',
            'research_url' => null,
            'education_tag' => self::educationTag(),
            'tags' => ['Diluent', 'Solvent', 'Research Supply'],
            'description' => self::cardDescription(),
            'overview' => 'Bacteriostatic water is sterile water containing 0.9% benzyl alcohol, which limits bacterial growth after a vial is entered. It is a diluent. It is not a peptide, not a hormone, and not a drug with its own therapeutic claim. Laboratories use it as a vehicle for dissolving lyophilized research materials. This page does not include reconstitution volumes, ratios, or mixing charts.',
            'molecular_formula' => 'H2O with 0.9% benzyl alcohol',
            'molecular_weight' => '',
            'cas_registry_number' => '',
            'half_life' => 'Not applicable. Bacteriostatic water is a diluent, not an active compound.',
            'bioavailability' => 'Not applicable.',
            'storage' => 'Store as directed on the vial. Do not use a vial that is cloudy or that has been stored contrary to its label.',
            'background' => 'Compendial bacteriostatic water for injection is sterile water with benzyl alcohol added as a preservative, commonly at 0.9%. The benzyl alcohol is what makes repeated withdrawals from one vial different from preservative-free sterile water, which is intended for single entry. Neither product is a peptide. Bacteriostatic water does not have an amino-acid sequence, a receptor target, or a therapeutic indication of its own.',
            'mechanism_of_action_intro' => 'There is no pharmacological mechanism to assign. Benzyl alcohol is present as a preservative in the diluent, not as a research ligand.',
            'mechanism_subsections' => [
                [
                    'title' => 'What the liquid is',
                    'intro' => 'The article is a solvent description, not a compound monograph.',
                    'items' => [
                        'Sterile water is the vehicle.',
                        'Benzyl alcohol 0.9% is the bacteriostatic preservative in the compendial product.',
                        'The liquid does not become a peptide because it is used around research peptides.',
                    ],
                ],
            ],
            'preclinical_intro' => 'Bacteriostatic water is a laboratory and pharmacy supply. It is not studied as a bioactive peptide.',
            'preclinical_subsections' => [
                [
                    'title' => 'Role in the laboratory',
                    'findings' => [
                        [
                            'title' => 'Diluent only',
                            'description' => 'It is used to dissolve or dilute dry research materials. That use does not change its identity.',
                        ],
                        [
                            'title' => 'Preservative versus preservative-free water',
                            'description' => 'Benzyl alcohol distinguishes bacteriostatic water from preservative-free sterile water. Choice of diluent is a labeling and handling question, not a peptide-class question.',
                        ],
                    ],
                ],
            ],
            'preclinical_disclaimer' => 'No reconstitution chart, volume, or mixing ratio is provided. Follow the label of the specific vial in use.',
            'human_use_intro' => 'Bacteriostatic water is not an approved treatment. This page does not describe how to prepare injections.',
            'human_use_subsections' => [
                [
                    'title' => 'Not a therapeutic',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'Bacteriostatic water has no standalone therapeutic indication on this page. It is described only as a preserved sterile diluent.',
                        ],
                    ],
                ],
            ],
            'regulatory_subsections' => [
                [
                    'title' => 'What it is not',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'It is not a peptide, protein, or small-molecule drug. Products sold as bacteriostatic water should be read as diluents.',
                        ],
                    ],
                ],
            ],
            'regulatory_important_note' => 'This entry is informational. It intentionally omits reconstitution volumes and mixing charts. It is not preparation or dosing guidance.',
            'potential_applications_intro' => 'The only role described here is as a diluent and preservative vehicle.',
            'potential_applications' => [
                [
                    'title' => 'Laboratory diluent',
                    'description' => 'A preserved sterile water used when a protocol calls for a bacteriostatic vehicle. Volumes are not specified here.',
                ],
            ],
            'potential_applications_important_context' => 'Do not infer a mixing recipe from this page. Bacteriostatic water is not itself a research peptide.',
            'conclusion' => 'Bacteriostatic water is sterile water preserved with benzyl alcohol. It is a diluent only. Encyclopedia copy should not call it a peptide or attach a reconstitution chart.',
            'references' => [
                [
                    'title' => 'Bacteriostatic Water for Injection',
                    'authors' => '',
                    'description' => 'Compendial sterile water containing benzyl alcohol as a bacteriostatic preservative.',
                    'citation' => 'United States Pharmacopeia. Bacteriostatic Water for Injection monograph.',
                    'links' => [],
                ],
            ],
            'key_points' => [
                'Bacteriostatic water is a sterile diluent, not a peptide.',
                'The compendial preservative is benzyl alcohol, commonly 0.9%.',
                'It has no amino-acid sequence and no drug indication of its own.',
                'This page does not include reconstitution volumes or mixing charts.',
            ],
            'areas_of_research_intro' => 'This entry sits with laboratory supplies, not with bioactive compounds.',
            'areas_of_research' => [
                ['name' => 'Laboratory supplies', 'description' => 'Preserved sterile diluents used as vehicles'],
            ],
            'key_effects' => [
                'Diluent',
                'Benzyl alcohol preservative',
                'Not an active compound',
            ],
            'common_use_cases' => [
                'Laboratory diluent',
            ],
            'how_it_works' => 'Benzyl alcohol in sterile water slows bacterial growth in a multi-entry vial. The liquid is a vehicle, not a signaling molecule.',
            'faqs' => [
                [
                    'question' => 'Is bacteriostatic water a peptide?',
                    'answer' => 'No. It is sterile water with benzyl alcohol added as a preservative. It is a diluent.',
                ],
                [
                    'question' => 'Does this page explain how to reconstitute a vial?',
                    'answer' => 'No. Reconstitution volumes and mixing charts are intentionally omitted.',
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
