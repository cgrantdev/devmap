<?php

namespace App\Support;

/**
 * NAD+ is a dinucleotide coenzyme. Existing articles are phrase-patched
 * rather than replaced. No invented CAS.
 */

class NadPlusProfile
{
    public const SLUG = 'nad';

    public static function matches(?string $slug, ?string $name = null): bool
    {
        $slug = strtolower(trim((string) $slug));
        $name = strtolower(str_replace(' ', '', trim((string) $name)));

        return in_array($slug, ['nad', 'nad+', 'nad-plus'], true)
            || in_array($name, ['nad', 'nad+', 'nad-plus'], true);
    }

    public static function slug(): string
    {
        return self::SLUG;
    }

    public static function categoryName(): string
    {
        return 'NAD+';
    }

    public static function seoTitle(): string
    {
        return 'What is NAD+? Dinucleotide Coenzyme';
    }

    public static function seoDescription(): string
    {
        return 'NAD+ (nicotinamide adenine dinucleotide) is a dinucleotide coenzyme in energy metabolism and DNA repair. It is not a peptide.';
    }

    public static function h1(): string
    {
        return 'What is NAD+?';
    }

    public static function subtitle(): string
    {
        return 'Nicotinamide adenine dinucleotide, a coenzyme';
    }

    public static function educationTag(): string
    {
        return 'Coenzyme';
    }

    public static function cardDescription(): string
    {
        return self::seoDescription();
    }

    public static function ogEyebrow(): string
    {
        return 'Dinucleotide Coenzyme';
    }

    public static function researchInstitution(): ?string
    {
        return null;
    }

    public static function researchUrl(): ?string
    {
        return 'https://pubchem.ncbi.nlm.nih.gov/compound/5893';
    }

    public static function preservesExistingArticle(): bool
    {
        return true;
    }

    public static function overview(): string
    {
        return self::articleAttributes()['overview'];
    }

    /**
     * Exact and pattern fixes for the live NAD+ article, which already
     * calls the molecule a coenzyme but still frames it as part of peptide
     * therapy and uses a Peptide Encyclopedia title.
     *
     * @return array<string, string>
     */
    public static function phraseReplacements(): array
    {
        return [
            'While not technically a peptide, it is widely included in peptide therapy protocols due to its injectable format and complementary role in cellular repair pathways.' => 'It is a dinucleotide coenzyme, not a peptide.',
            'It is included in peptide therapy protocols due to its injectable format and complementary role in cellular repair and longevity pathways.' => 'It is a dinucleotide coenzyme, not a peptide.',
            'in the peptide and longevity research landscape' => 'in cellular-energy and longevity research',
        ];
    }

    public static function stubNarrative(): array
    {
        return OrforglipronProfile::narrativeFromArticle(self::articleAttributes());
    }

    public static function articleAttributes(): array
    {
        return [
            'title' => 'NAD+',
            'peptide_full_name' => self::subtitle(),
            'research_title' => 'NAD+: A Dinucleotide Coenzyme',
            'research_outline' => 'NAD+ is nicotinamide adenine dinucleotide, a dinucleotide coenzyme in redox metabolism. It is not a peptide.',
            'research_url' => self::researchUrl(),
            'education_tag' => self::educationTag(),
            'tags' => ['Coenzyme', 'Dinucleotide', 'Redox Metabolism'],
            'description' => self::cardDescription(),
            'overview' => 'NAD+ (nicotinamide adenine dinucleotide) is a dinucleotide coenzyme found in living cells. It carries electrons in redox metabolism and is a substrate for enzymes that include sirtuins and PARPs. It is not a peptide and it has no amino-acid sequence. This page is informational and does not provide dosing or administration guidance.',
            'molecular_formula' => 'C21H27N7O14P2',
            'molecular_weight' => '663.4 g/mol',
            'cas_registry_number' => '',
            'half_life' => 'Cellular NAD+ turnover is tightly regulated. A dosing interval is not stated here.',
            'bioavailability' => 'Not discussed. This page does not compare routes of administration.',
            'storage' => 'Follow the supplier certificate of analysis for research material.',
            'background' => 'NAD was identified in the early twentieth century as a cofactor of fermentation and later as the general redox coenzyme of cellular metabolism. The oxidized form, NAD+, accepts a hydride to become NADH. Separate from that redox cycle, NAD+ is consumed by sirtuins, PARPs, and other ADP-ribosyl transferases. Those are enzyme-cofactor roles. They do not make NAD+ a peptide.',
            'mechanism_of_action_intro' => 'NAD+ functions as a redox cofactor and as an enzyme substrate. It does not act through a peptide-hormone receptor.',
            'mechanism_subsections' => [
                [
                    'title' => 'Coenzyme roles',
                    'intro' => 'Two well-described biochemical roles account for most NAD+ research.',
                    'items' => [
                        'Redox carrier: NAD+ / NADH in dehydrogenase reactions.',
                        'Enzyme substrate: sirtuins and PARPs cleave NAD+ during protein deacylation and ADP-ribosylation.',
                    ],
                ],
            ],
            'preclinical_intro' => 'A large biochemical literature describes NAD+ metabolism. This entry states the chemical class only and does not give doses.',
            'preclinical_subsections' => [
                [
                    'title' => 'Chemical class',
                    'findings' => [
                        [
                            'title' => 'Dinucleotide',
                            'description' => 'NAD+ is composed of nicotinamide and adenine nucleotides joined by a pyrophosphate bridge. That is not an amino-acid polymer.',
                        ],
                    ],
                ],
            ],
            'preclinical_disclaimer' => 'No dosing, infusion rate, or price is provided.',
            'human_use_intro' => 'NAD+ is a coenzyme. This encyclopedia entry does not describe clinical administration.',
            'human_use_subsections' => [
                [
                    'title' => 'Scope of this page',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'Discussion here is limited to chemical identity and biochemical role. It is not a protocol for supplementation or infusion.',
                        ],
                    ],
                ],
            ],
            'regulatory_subsections' => [
                [
                    'title' => 'Identity',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'NAD+ is a dinucleotide coenzyme. Research-chemical listings should not be labeled as peptides.',
                        ],
                    ],
                ],
            ],
            'regulatory_important_note' => 'Informational overview of a coenzyme. Not dosing or medical advice.',
            'potential_applications_intro' => 'Research contexts are biochemical, not peptide pharmacology.',
            'potential_applications' => [
                [
                    'title' => 'Redox metabolism',
                    'description' => 'Dehydrogenase reactions that use the NAD+ / NADH couple.',
                ],
                [
                    'title' => 'NAD-consuming enzymes',
                    'description' => 'Sirtuin and PARP biology, where NAD+ is a substrate.',
                ],
            ],
            'potential_applications_important_context' => 'These are biochemical research topics. No administration guidance is implied.',
            'conclusion' => 'NAD+ is nicotinamide adenine dinucleotide, a dinucleotide coenzyme. It is not a peptide. Encyclopedia titles and body copy should say coenzyme, not peptide.',
            'references' => [
                [
                    'title' => 'PubChem record for NAD+ (CID 5893)',
                    'authors' => '',
                    'description' => 'Dinucleotide coenzyme identity. CAS is omitted here rather than restated from secondary vendor copy.',
                    'citation' => 'National Center for Biotechnology Information. PubChem Compound CID 5893.',
                    'links' => [
                        ['label' => 'PubChem', 'url' => 'https://pubchem.ncbi.nlm.nih.gov/compound/5893'],
                    ],
                ],
            ],
            'key_points' => [
                'NAD+ is a dinucleotide coenzyme, not a peptide.',
                'It functions as a redox cofactor and as a substrate for sirtuins and PARPs.',
                'This page does not provide dosing or administration guidance.',
            ],
            'areas_of_research_intro' => 'NAD+ research is coenzyme and redox biology.',
            'areas_of_research' => [
                ['name' => 'Redox biochemistry', 'description' => 'NAD+ / NADH in energy metabolism'],
                ['name' => 'NAD-consuming enzymes', 'description' => 'Sirtuins and PARPs'],
            ],
            'key_effects' => [
                'Redox cofactor',
                'Sirtuin and PARP substrate',
                'Not a peptide hormone',
            ],
            'common_use_cases' => [
                'Biochemical research on NAD metabolism',
            ],
            'how_it_works' => 'NAD+ accepts a hydride to form NADH in dehydrogenase reactions and is cleaved by NAD-consuming enzymes. It is a dinucleotide, not a peptide.',
            'faqs' => [
                [
                    'question' => 'Is NAD+ a peptide?',
                    'answer' => 'No. NAD+ is a dinucleotide coenzyme. It has no amino-acid sequence.',
                ],
                [
                    'question' => 'What does NAD stand for?',
                    'answer' => 'Nicotinamide adenine dinucleotide. NAD+ is the oxidized form.',
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
