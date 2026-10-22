<?php

namespace Tests\Unit;

use App\Support\CompareHubBlurb;
use PHPUnit\Framework\TestCase;

class CompareHubBlurbTest extends TestCase
{
    public function test_outcome_blurbs_become_class_lines(): void
    {
        foreach ($this->rewrites() as $slug => [$before, $after]) {
            $rendered = CompareHubBlurb::render($slug, $before);

            $this->assertSame($after, $rendered, $slug);
            $this->assertFalse(CompareHubBlurb::hasOutcomeClaim($rendered), $slug);
        }
    }

    public function test_cagrilintide_percent_outcome_uses_the_amylin_line(): void
    {
        $redefine = 'Cagrilintide is a long-acting amylin analogue developed by Novo Nordisk. With semaglutide (as CagriSema), mean weight change was −20.4% vs −3.0% with placebo over 68 weeks in REDEFINE 1 (NEJM 2025). Not approved as of 22 Oct 2026.';

        $this->assertSame(
            'Cagrilintide is a long-acting amylin analog studied in obesity research. Research use only.',
            CompareHubBlurb::render('cagrilintide', $redefine)
        );
    }

    public function test_class_blurbs_pass_through_unchanged(): void
    {
        $samples = [
            'ghk-cu' => 'A copper-binding tripeptide (Glycyl-L-Histidyl-L-Lysine + Cu²⁺) studied for its role in skin remodeling, hair follicle stimulation, and tissue repair pathways.',
            'nad' => 'NAD+ is a critical coenzyme found in every living cell, essential for energy metabolism, DNA repair, and sirtuin activation. Levels decline significantly with age.',
            'tesofensine' => 'Tesofensine is a small-molecule inhibitor of serotonin, norepinephrine, and dopamine reuptake. It is not a peptide.',
            '5-amino-1mq' => '5-Amino-1MQ (5-amino-1-methylquinolinium) is a synthetic small-molecule inhibitor of nicotinamide N-methyltransferase (NNMT). It is a research chemical, not a peptide.',
            'glutathione' => 'Glutathione (GSH) is a ubiquitous endogenous tripeptide (glutamate–cysteine–glycine) central to cellular redox biology, detoxification, and antioxidant defense.',
            'kpv' => 'KPV is the tripeptide lysine–proline–valine, corresponding to α-melanocyte-stimulating hormone residues 11–13. It is studied mainly for anti-inflammatory activity in cell and animal models.',
            'glow' => 'GLOW is a marketplace blend name, not a single molecule. Listings denote a co-lyophilized trio of GHK-Cu, BPC-157, and TB-500.',
        ];

        foreach ($samples as $slug => $text) {
            $this->assertFalse(CompareHubBlurb::hasOutcomeClaim($text), $slug);
            $this->assertSame($text, CompareHubBlurb::render($slug, $text), $slug);
        }
    }

    public function test_empty_blurbs_stay_empty(): void
    {
        $this->assertNull(CompareHubBlurb::render('bpc-157-tb-500', null));
        $this->assertNull(CompareHubBlurb::render('klow-blend-ghk-cu-bpc-157-tb-500-kpv', '   '));
        $this->assertNull(CompareHubBlurb::render('cjc-1295-ipamorelin', '<p></p>'));
    }

    public function test_unknown_compound_does_not_invent_a_class(): void
    {
        $rendered = CompareHubBlurb::render(
            'mystery-peptide',
            'Shown to cut body weight by 18% in a phase 2 trial. Clinically shown healing.'
        );

        $this->assertSame('Listed for research use only.', $rendered);
        $this->assertFalse(CompareHubBlurb::hasOutcomeClaim($rendered));
    }

    public function test_per_dose_figure_is_replaced(): void
    {
        $this->assertSame(
            'Semaglutide is a GLP-1 receptor agonist studied in metabolic research. Research use only.',
            CompareHubBlurb::render('semaglutide', 'Semaglutide is a GLP-1 receptor agonist dosed at 2.4 mg once weekly.')
        );
    }

    public function test_pt_141_slug_uses_the_bremelanotide_class_line(): void
    {
        $this->assertSame(
            'Bremelanotide is a melanocortin receptor agonist studied in sexual-function research. Research use only.',
            CompareHubBlurb::render('pt-141', 'PT-141 increases sexual desire at 1.75 mg.')
        );
    }

    public function test_guard_replaces_a_claim_and_keeps_clean_copy_intact(): void
    {
        $clean = 'Tesofensine is a small-molecule inhibitor of serotonin, norepinephrine, and dopamine reuptake. It is not a peptide. '.str_repeat('Catalog identity only. ', 12);

        $this->assertGreaterThan(200, mb_strlen($clean));
        $this->assertSame(trim($clean), CompareHubBlurb::guard('tesofensine', $clean));
        $this->assertSame(
            'Cagrilintide is a long-acting amylin analog studied in obesity research. Research use only.',
            CompareHubBlurb::guard('cagrilintide', 'The combination showed up to 25% body weight reduction in a later paragraph. '.str_repeat('More context. ', 30))
        );
        $this->assertSame(
            'Ipamorelin is a ghrelin-receptor (GHS-R1a) agonist and growth hormone secretagogue studied in endocrine research. Research use only.',
            CompareHubBlurb::guard('ipamorelin', 'Ipamorelin is the most selective GHS.')
        );
    }

    public function test_outcome_words_match_and_neutral_lines_do_not(): void
    {
        foreach ([
            'Improves insulin sensitivity',
            'Heals tendons',
            'Boosts GH',
            'Reduces appetite',
            'up to twenty pounds',
            'Treats obesity',
            'Cheapest',
            'Verified',
            'FDA-approved',
        ] as $claim) {
            $this->assertTrue(CompareHubBlurb::hasOutcomeClaim($claim), $claim);
        }

        foreach ($this->neutralLines() as $line) {
            $this->assertFalse(CompareHubBlurb::hasOutcomeClaim($line), $line);
        }

        foreach ($this->livePassThroughCards() as $slug => $line) {
            $this->assertFalse(CompareHubBlurb::hasOutcomeClaim($line), $slug);
        }
    }

    /**
     * @return list<string>
     */
    private function neutralLines(): array
    {
        return [
            'Retatrutide is a triple agonist (GLP-1, GIP, and glucagon) studied in obesity research. Research use only.',
            'Tirzepatide is a dual GIP and GLP-1 receptor agonist studied in metabolic research. Research use only.',
            'Semaglutide is a GLP-1 receptor agonist studied in metabolic research. Research use only.',
            'Tesamorelin is a GHRH analog studied in lipodystrophy research. Research use only.',
            'MOTS-c is a mitochondrial-derived peptide studied in metabolic research. Research use only.',
            'BPC-157 is a synthetic pentadecapeptide derived from a gastric protein, studied in musculoskeletal and gastrointestinal research. Research use only.',
            'Ipamorelin is a ghrelin-receptor (GHS-R1a) agonist and growth hormone secretagogue studied in endocrine research. Research use only.',
            'AOD-9604 is a modified fragment of human growth hormone (amino acids 176-191) studied in metabolic research. Research use only.',
            'TB-500 is a synthetic peptide related to thymosin beta-4, studied in musculoskeletal research. Research use only.',
            'Sermorelin is a GHRH(1-29) analog studied in endocrine research. Research use only.',
            'Cagrilintide is a long-acting amylin analog studied in obesity research. Research use only.',
            'Kisspeptin is a neuropeptide studied in reproductive endocrinology research. Research use only.',
            'Bremelanotide is a melanocortin receptor agonist studied in sexual-function research. Research use only.',
            'Bacteriostatic water is sterile water with benzyl alcohol added as a preservative, used as a diluent. It is not a peptide or an active compound.',
            'Teriparatide is recombinant parathyroid hormone (1-34), a 34-amino-acid peptide. Research vials are not the prescription product (Forteo). Research use only.',
            'Oxytocin is a cyclic nonapeptide hormone. Research vials are not the prescription injection (Pitocin). Research use only.',
            'Teduglutide is a 33-residue GLP-2 analog. Research vials are not the prescription product (GATTEX). Research use only.',
            'HMG (human menopausal gonadotropin, menotropins) is a urinary preparation with FSH and LH activity. Research vials are not the prescription product. Research use only.',
            'Methylene blue (methylthioninium chloride) is a phenothiazine dye and small molecule, not a peptide. Research use only.',
            'Adamax is a marketplace name for an N-acetylated, adamantane-modified Semax analog. Research use only.',
            'Lipopeptides are lipid-modified peptides studied in membrane, antimicrobial, and self-assembly research. Research use only.',
            'PTD-DBM is a cell-penetrating fusion peptide (protein transduction domain plus a Dishevelled-binding motif) that blocks the CXXC5–Dishevelled interaction in Wnt signaling research. Research use only.',
            'IGF-1 LR3 is an 83-amino-acid analog of insulin-like growth factor 1 with an Arg3 substitution and a 13-residue N-terminal extension. Research use only.',
            'Elamipretide (SS-31) is a mitochondria-targeted tetrapeptide studied in cardiolipin and inner-membrane research. Research use only.',
            'Setmelanotide is an eight-residue cyclic melanocortin-4 receptor agonist. Research vials are not the prescription product (IMCIVREE). Research use only.',
            'Adipotide (FTPP) is a synthetic peptide that targets prohibitin on adipose-tissue blood vessels, studied in preclinical metabolic research. Research use only.',
            'AICAR (acadesine) is a cell-permeable nucleoside that cells convert to the AMP mimic ZMP, used as an AMPK research tool. Research use only.',
            'Mazdutide (IBI362 / LY3305677) is a glucagon and GLP-1 receptor dual agonist studied in metabolic research. Research use only.',
            'Survodutide (BI 456906) is an investigational glucagon and GLP-1 receptor dual agonist studied in metabolic research. Research use only.',
            'P21 is a peptide derived from ciliary neurotrophic factor (CNTF), studied in preclinical neuroscience research. Research use only.',
            'SNAP-8 (acetyl octapeptide-3) is a cosmetic-ingredient peptide related to the SNAP-25 sequence. Research use only.',
            'Triptorelin is a synthetic GnRH agonist decapeptide with a D-Trp6 substitution. Research use only.',
        ];
    }

    /**
     * Hub cards whose stored copy is already class or identity language.
     *
     * @return array<string, string>
     */
    private function livePassThroughCards(): array
    {
        return [
            'ghk-cu' => 'A copper-binding tripeptide (Glycyl-L-Histidyl-L-Lysine + Cu²⁺) studied for its role in skin remodeling, hair follicle stimulation, and tissue repair pathways.',
            'glow' => 'GLOW is a marketplace blend name, not a single molecule or CAS-registered substance. On Peptidemap and across multiple vendors, GLOW listings typically denote a co-lyophilized trio of GHK-Cu, BPC-157,...',
            'nad' => 'NAD+ is a critical coenzyme found in every living cell, essential for energy metabolism, DNA repair, and sirtuin activation. Levels decline significantly with age.',
            'glutathione' => 'Glutathione (GSH) is a ubiquitous endogenous tripeptide (glutamate–cysteine–glycine) central to cellular redox biology, detoxification, and antioxidant defense. It is sold in research and compounding...',
            'cjc-1295' => 'Catalog labels often call a tetrasubstituted GHRH(1-29) analog “CJC-1295 no DAC” or Modified GRF 1-29. In the peer-reviewed literature, CJC-1295 names the long-acting Drug Affinity Complex (DAC) const...',
            '5-amino-1mq' => '5-Amino-1MQ (5-amino-1-methylquinolinium) is a synthetic small-molecule inhibitor of nicotinamide N-methyltransferase (NNMT). It is a research chemical, not a peptide: it has no amino-acid sequence (l...',
            'igf-1-lr3' => 'IGF-1 LR3 is an 83-amino acid synthetic analog of insulin-like growth factor 1 (IGF-1) with a modified N-terminus. The arginine-to-glutamate substitution at position 3 and 13-amino acid extension sign...',
            'tesofensine' => 'Tesofensine is a small-molecule inhibitor of serotonin, norepinephrine, and dopamine reuptake. It is not a peptide.',
            'epitalon' => 'Epitalon (Epithalon) is the synthetic tetrapeptide Ala-Glu-Asp-Gly (AEDG), developed in pineal bioregulator research associated with the Khavinson group. It is studied primarily for telomerase/telomer...',
            'kpv' => 'KPV is the tripeptide lysine–proline–valine, corresponding to α-melanocyte-stimulating hormone residues 11–13. It is studied mainly for anti-inflammatory activity in cell and animal models, with parti...',
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function rewrites(): array
    {
        return [
            'cagrilintide' => [
                'Cagrilintide is a long-acting amylin analog being developed by Novo Nordisk as a complement to GLP-1 agonists. The combination with semaglutide (CagriSema) showed up to 25% weight reduction.',
                'Cagrilintide is a long-acting amylin analog studied in obesity research. Research use only.',
            ],
            'retatrutide' => [
                "Retatrutide is a triple agonist targeting GLP-1, GIP, and glucagon receptors simultaneously. Phase 2 data showed up to 24% weight reduction — the highest of any peptide compound studied.\n\nAs of early 2026, phase 3 is underway.",
                'Retatrutide is a triple agonist (GLP-1, GIP, and glucagon) studied in obesity research. Research use only.',
            ],
            'tirzepatide' => [
                'Tirzepatide is the first dual GLP-1/GIP receptor agonist, showing superior metabolic outcomes versus GLP-1-only compounds in head-to-head clinical trials.',
                'Tirzepatide is a dual GIP and GLP-1 receptor agonist studied in metabolic research. Research use only.',
            ],
            'semaglutide' => [
                'Semaglutide is a GLP-1 receptor agonist and the most-searched peptide compound globally. FDA-approved for type 2 diabetes (Ozempic) and obesity (Wegovy), it has transformed metabolic research.',
                'Semaglutide is a GLP-1 receptor agonist studied in metabolic research. Research use only.',
            ],
            'tesamorelin' => [
                'Tesamorelin is an FDA-approved GHRH analog used for reducing visceral fat in HIV-associated lipodystrophy. It is the full 44-amino-acid GHRH with a stabilizing modification.',
                'Tesamorelin is a GHRH analog studied in lipodystrophy research. Research use only.',
            ],
            'mots-c' => [
                'MOTS-c is a mitochondrial-derived peptide encoded by mitochondrial DNA that acts as an exercise mimetic, activating AMPK and improving metabolic function.',
                'MOTS-c is a mitochondrial-derived peptide studied in metabolic research. Research use only.',
            ],
            'bpc-157' => [
                'BPC-157 is a synthetic peptide derived from a protective protein found in the stomach. It has shown remarkable healing properties for tendons, ligaments, muscles, and the gastrointestinal tract.',
                'BPC-157 is a synthetic pentadecapeptide derived from a gastric protein, studied in musculoskeletal and gastrointestinal research. Research use only.',
            ],
            'ipamorelin' => [
                'Ipamorelin is the most selective growth hormone secretagogue, stimulating GH release without affecting cortisol, prolactin, or ACTH levels.',
                'Ipamorelin is a ghrelin-receptor (GHS-R1a) agonist and growth hormone secretagogue studied in endocrine research. Research use only.',
            ],
            'aod-9604' => [
                'AOD-9604 is a modified fragment of human growth hormone (amino acids 176-191) that replicates the fat-burning action of GH without anabolic or diabetogenic effects.',
                'AOD-9604 is a modified fragment of human growth hormone (amino acids 176-191) studied in metabolic research. Research use only.',
            ],
            'tb-500' => [
                'TB-500 is a synthetic fragment of Thymosin Beta-4, a naturally occurring protein involved in cell migration and tissue repair. It promotes systemic recovery through actin polymerization modulation.',
                'TB-500 is a synthetic peptide related to thymosin beta-4, studied in musculoskeletal research. Research use only.',
            ],
            'sermorelin' => [
                'Sermorelin is the first 29 amino acids of natural GHRH, previously FDA-approved for pediatric GH deficiency. It stimulates natural GH release while preserving feedback regulation.',
                'Sermorelin is a GHRH(1-29) analog studied in endocrine research. Research use only.',
            ],
            'kisspeptin' => [
                'Kisspeptin is the master upstream regulator of the reproductive hormone cascade, triggering GnRH release and the entire hypothalamic-pituitary-gonadal axis.',
                'Kisspeptin is a neuropeptide studied in reproductive endocrinology research. Research use only.',
            ],
            'bremelanotide' => [
                'PT-141 (Bremelanotide) is an FDA-approved melanocortin receptor agonist that works centrally in the brain to increase sexual desire, unlike PDE5 inhibitors that work peripherally.',
                'Bremelanotide is a melanocortin receptor agonist studied in sexual-function research. Research use only.',
            ],
        ];
    }
}
