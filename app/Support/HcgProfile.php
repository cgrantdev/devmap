<?php

namespace App\Support;

/**
 * hCG is a glycoprotein hormone. Labeled uses are reproductive.
 * Weight loss is not a labeled use. No invented CAS.
 */

class HcgProfile
{
    public const SLUG = 'hcg';

    public static function matches(?string $slug, ?string $name = null): bool
    {
        $slug = strtolower(trim((string) $slug));
        $name = strtolower(trim((string) $name));
        $name = str_replace([' ', '_'], '-', $name);

        return in_array($slug, ['hcg', 'h-cg', 'human-chorionic-gonadotropin'], true)
            || in_array($name, ['hcg', 'h-cg', 'human-chorionic-gonadotropin'], true);
    }

    public static function slug(): string
    {
        return self::SLUG;
    }

    public static function categoryName(): string
    {
        return 'hCG';
    }

    public static function seoTitle(): string
    {
        return 'What is hCG? Glycoprotein Hormone';
    }

    public static function seoDescription(): string
    {
        return 'hCG is a prescription glycoprotein hormone labeled for reproductive indications. Weight loss is not a labeled use. Research material is not the Rx product.';
    }

    public static function h1(): string
    {
        return 'What is hCG?';
    }

    public static function subtitle(): string
    {
        return 'Glycoprotein hormone (human chorionic gonadotropin)';
    }

    public static function educationTag(): string
    {
        return 'Glycoprotein Hormone';
    }

    public static function cardDescription(): string
    {
        return self::seoDescription();
    }

    public static function ogEyebrow(): string
    {
        return 'Glycoprotein Hormone';
    }

    public static function researchInstitution(): ?string
    {
        return 'FDA prescribing information';
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
            'title' => 'hCG',
            'peptide_full_name' => self::subtitle(),
            'research_title' => 'hCG: A Glycoprotein Hormone, Not a Weight-Loss Peptide',
            'research_outline' => 'Human chorionic gonadotropin is a heterodimeric glycoprotein hormone. Labeled prescription uses are reproductive. Research-grade material is not the approved medicine.',
            'research_url' => null,
            'education_tag' => self::educationTag(),
            'tags' => ['Glycoprotein Hormone', 'Prescription Hormone', 'Reproductive Endocrinology'],
            'description' => self::cardDescription(),
            'overview' => 'Human chorionic gonadotropin (hCG) is a heterodimeric glycoprotein hormone, not a short peptide. An alpha subunit is shared with LH, FSH, and TSH, and a hormone-specific beta subunit carries the biological identity. Placental trophoblast cells produce it in pregnancy. Prescription hCG products are labeled for specific reproductive indications. Weight loss is not an FDA-labeled use. Research-grade material is not the same thing as those prescription products.',
            'molecular_formula' => 'Heterodimeric glycoprotein',
            'molecular_weight' => 'Approximately 37 kDa',
            'cas_registry_number' => '',
            'half_life' => 'Longer than LH because of additional glycosylation. A single number is not given here as dosing guidance.',
            'bioavailability' => 'Pharmaceutical products are parenteral. This page does not describe administration.',
            'storage' => 'Follow the labeling of the specific pharmaceutical or research product. Do not assume research-grade storage matches a prescription brand.',
            'background' => 'hCG was identified in the early twentieth century as the pregnancy hormone of the placenta. It is a glycoprotein: both subunits carry carbohydrate, and the beta subunit has a glycosylated C-terminal extension that lengthens circulation relative to LH. It signals through the LH/choriogonadotropin receptor. Pharmaceutical forms include urinary chorionic gonadotropin and recombinant choriogonadotropin alfa. Those labeled medicines are prescription products for reproductive care. They are not weight-loss drugs, and a research-catalog listing is not a substitute for them.',
            'mechanism_of_action_intro' => 'hCG binds the LH/choriogonadotropin receptor (LHCGR), a G-protein-coupled receptor on gonadal cells, and stimulates steroidogenesis. That is glycoprotein-hormone pharmacology, not peptide-drug pharmacology.',
            'mechanism_subsections' => [
                [
                    'title' => 'LHCGR signaling',
                    'intro' => 'Receptor activation raises cAMP and supports steroid hormone synthesis in target cells.',
                    'items' => [
                        'Binds LHCGR on gonadal cells.',
                        'Stimulates Leydig-cell testosterone synthesis in male reproductive research and in labeled hypogonadotropic hypogonadism care.',
                        'Supports corpus luteum progesterone production in early pregnancy and is used in labeled ovulation care.',
                    ],
                ],
            ],
            'preclinical_intro' => 'hCG has a long history in reproductive biology. The points below describe physiology and labeled medicine, not a weight-loss regimen.',
            'preclinical_subsections' => [
                [
                    'title' => 'Reproductive physiology',
                    'findings' => [
                        [
                            'title' => 'Pregnancy signal',
                            'description' => 'Trophoblast hCG maintains the corpus luteum and progesterone production in early pregnancy.',
                        ],
                        [
                            'title' => 'Gonadal steroidogenesis',
                            'description' => 'Through LHCGR, hCG stimulates sex-steroid synthesis. That is the basis of its labeled reproductive uses.',
                        ],
                    ],
                ],
            ],
            'preclinical_disclaimer' => 'Weight-loss programs that use hCG are not an FDA-labeled indication. This page does not describe those programs or any dose.',
            'human_use_intro' => 'Prescription hCG is an Rx hormone with reproductive labeling. Research-grade hCG is not that medicine.',
            'human_use_subsections' => [
                [
                    'title' => 'Labeled prescription uses',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'Chorionic gonadotropin products such as Pregnyl have been labeled for prepubertal cryptorchidism not due to anatomical obstruction, for selected hypogonadotropic hypogonadism in males, and for ovulation induction together with other fertility medicines. Recombinant choriogonadotropin alfa (Ovidrel) is labeled to trigger final follicular maturation in fertility treatment.',
                        ],
                        [
                            'type' => 'content',
                            'value' => 'Those are prescription uses of pharmaceutical products. They are not a weight-loss indication. Research-grade hCG sold for laboratory work is not Pregnyl, Novarel, or Ovidrel.',
                        ],
                    ],
                ],
            ],
            'regulatory_subsections' => [
                [
                    'title' => 'Prescription versus research material',
                    'entries' => [
                        [
                            'type' => 'content',
                            'value' => 'Approved hCG medicines are prescription glycoprotein-hormone products for reproductive indications. A research listing is not an approved weight-loss drug and is not interchangeable with the labeled product.',
                        ],
                    ],
                ],
            ],
            'regulatory_important_note' => 'hCG is a prescription glycoprotein hormone when it is the approved medicine, and a laboratory material when it is sold for research. Neither framing is a weight-loss peptide. This page gives no doses and no prices.',
            'potential_applications_intro' => 'The established context is reproductive endocrinology.',
            'potential_applications' => [
                [
                    'title' => 'Reproductive endocrinology',
                    'description' => 'Corpus luteum support, ovulation physiology, and LHCGR signaling.',
                ],
                [
                    'title' => 'Labeled fertility care',
                    'description' => 'Prescription products for the reproductive indications named in their labeling. Not a weight-loss use.',
                ],
            ],
            'potential_applications_important_context' => 'Do not read this entry as support for weight-loss use. Research-grade hCG is not the prescription product.',
            'conclusion' => 'hCG is a glycoprotein hormone. Prescription forms are labeled for reproductive indications, not for weight loss. It should not be described as a peptide, and research-grade material should not be described as the approved medicine.',
            'references' => [
                [
                    'title' => 'Pregnyl (chorionic gonadotropin) prescribing information',
                    'authors' => '',
                    'description' => 'Labeled reproductive indications for urinary chorionic gonadotropin.',
                    'citation' => 'FDA prescribing information. Pregnyl.',
                    'links' => [],
                ],
                [
                    'title' => 'Ovidrel (choriogonadotropin alfa) prescribing information',
                    'authors' => '',
                    'description' => 'Recombinant glycoprotein hormone labeled for final follicular maturation.',
                    'citation' => 'FDA prescribing information. Ovidrel.',
                    'links' => [],
                ],
            ],
            'key_points' => [
                'hCG is a heterodimeric glycoprotein hormone, not a short peptide.',
                'Prescription products are labeled for reproductive indications.',
                'Weight loss is not an FDA-labeled use.',
                'Research-grade material is not the prescription product. No dosing is provided here.',
            ],
            'areas_of_research_intro' => 'hCG belongs with glycoprotein hormone and reproductive endocrinology research.',
            'areas_of_research' => [
                ['name' => 'Reproductive endocrinology', 'description' => 'LHCGR signaling and gonadal steroidogenesis'],
                ['name' => 'Pregnancy biology', 'description' => 'Trophoblast signaling and corpus luteum support'],
            ],
            'key_effects' => [
                'LHCGR agonism',
                'Supports early-pregnancy progesterone production',
                'Stimulates gonadal steroidogenesis',
            ],
            'common_use_cases' => [
                'Reproductive endocrinology research',
                'Glycoprotein-hormone pharmacology',
            ],
            'how_it_works' => 'hCG binds the LH/choriogonadotropin receptor and stimulates cAMP-dependent steroidogenesis. It is a glycosylated heterodimer, not a peptide drug.',
            'faqs' => [
                [
                    'question' => 'Is hCG a peptide?',
                    'answer' => 'No. hCG is a heterodimeric glycoprotein hormone. It is not a short peptide.',
                ],
                [
                    'question' => 'Is hCG a weight-loss drug?',
                    'answer' => 'No. FDA-labeled uses of prescription hCG are reproductive, including selected fertility and hypogonadotropic-hypogonadism indications. Weight loss is not a labeled use.',
                ],
                [
                    'question' => 'Is research-grade hCG the same as Pregnyl or Ovidrel?',
                    'answer' => 'No. Those are prescription products. Research-grade material is not interchangeable with them.',
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
