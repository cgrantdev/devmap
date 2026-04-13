<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use Illuminate\Database\Seeder;

class ExpandArticlesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->getExpansions() as $slug => $data) {
            $post = EducationPost::where('slug', $slug)->first();
            if (!$post) {
                $this->command?->warn("Post not found: {$slug}");
                continue;
            }
            $post->update($data);
            $this->command?->info("Expanded: {$post->title}");
        }
    }

    private function getExpansions(): array
    {
        return [
            'semaglutide' => [
                'background' => 'Semaglutide is a synthetic glucagon-like peptide-1 (GLP-1) receptor agonist developed by Novo Nordisk. It is a 31-amino acid peptide analog of human GLP-1(7-37) with two key modifications: an aminoisobutyric acid substitution at position 8 that confers resistance to dipeptidyl peptidase-4 (DPP-4) degradation, and a C18 fatty diacid side chain attached via a linker at lysine-26 that enables strong albumin binding. These modifications extend its half-life to approximately 7 days, enabling once-weekly administration — a major pharmacological advance over native GLP-1 which has a half-life of only 2-3 minutes. Originally developed for type 2 diabetes management, semaglutide gained widespread attention after clinical trials demonstrated significant body weight reduction effects. It represents one of the most extensively studied peptide-based therapeutics in modern medicine, with a robust clinical trial program spanning thousands of patients across multiple indications. Semaglutide has been approved by the FDA under multiple brand names: Ozempic (injectable, T2D), Wegovy (injectable, weight management), and Rybelsus (oral formulation, T2D) — making it the first GLP-1 RA available in an oral peptide formulation, achieved through co-formulation with the absorption enhancer SNAC (sodium N-[8-(2-hydroxybenzoyl)amino]caprylate).',
                'mechanism_of_action_intro' => 'Semaglutide exerts its effects through selective agonism of the GLP-1 receptor (GLP-1R), a G-protein coupled receptor expressed in pancreatic beta cells, the gastrointestinal tract, the cardiovascular system, and multiple brain regions including the hypothalamus and brainstem nuclei involved in appetite regulation.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The GLP-1 receptor signals through multiple downstream pathways that collectively regulate glucose homeostasis, appetite, gastric emptying, and cardiovascular function.',
                        'points' => [
                            'Stimulates glucose-dependent insulin secretion from pancreatic beta cells via cAMP/PKA and Epac2 signaling pathways — insulin release only occurs when blood glucose is elevated, reducing hypoglycemia risk',
                            'Suppresses glucagon secretion from pancreatic alpha cells in a glucose-dependent manner, reducing hepatic glucose output',
                            'Activates GLP-1 receptors in hypothalamic appetite centers (arcuate nucleus, paraventricular nucleus) and brainstem (area postrema, nucleus tractus solitarius), reducing appetite and food intake through central satiety signaling',
                            'Delays gastric emptying by modulating vagal afferent signaling, contributing to postprandial glucose control and prolonged satiety',
                            'Preclinical evidence suggests direct cardioprotective effects including anti-inflammatory actions on vascular endothelium, reduced oxidative stress, and improved endothelial function independent of glucose lowering',
                            'The C18 fatty diacid modification enables strong non-covalent albumin binding (>99%), creating a circulating reservoir that protects from enzymatic degradation and renal clearance',
                        ],
                    ],
                ]),
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Metabolic Research',
                        'findings' => [
                            ['title' => 'Glucose Homeostasis', 'description' => 'In diabetic rodent models (db/db mice, ZDF rats), semaglutide dose-dependently reduced HbA1c, fasting glucose, and postprandial glucose excursions. Beta cell mass and function were preserved compared to vehicle-treated controls, suggesting protective effects on islet architecture.'],
                            ['title' => 'Body Weight and Composition', 'description' => 'Diet-induced obese (DIO) rodent studies demonstrated significant reductions in body weight (15-20%), driven primarily by decreased food intake and preferential loss of adipose tissue over lean mass. Indirect calorimetry studies showed no significant reduction in energy expenditure, distinguishing the weight loss mechanism from caloric restriction alone.'],
                            ['title' => 'Hepatic Steatosis', 'description' => 'In NASH models, semaglutide reduced hepatic lipid accumulation, liver inflammation markers, and fibrosis scores. These findings supported clinical investigation in metabolic-associated steatohepatitis (MASH).'],
                        ],
                    ],
                    [
                        'title' => 'Cardiovascular Research',
                        'findings' => [
                            ['title' => 'Atherosclerosis Models', 'description' => 'In ApoE-knockout mice, semaglutide reduced atherosclerotic plaque area and inflammatory cell infiltration, independent of metabolic improvements. Direct anti-inflammatory effects on vascular smooth muscle cells and macrophages were observed in vitro.'],
                            ['title' => 'Cardiac Function', 'description' => 'Rodent ischemia-reperfusion studies showed reduced infarct size and improved cardiac output with semaglutide pretreatment, suggesting direct myocardial protective effects mediated through GLP-1R signaling in cardiomyocytes.'],
                        ],
                    ],
                ]),
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'SUSTAIN Trial Program (Type 2 Diabetes)',
                        'entries' => [
                            ['type' => 'content', 'value' => 'The SUSTAIN program comprised 10+ Phase III trials enrolling over 10,000 patients with type 2 diabetes. SUSTAIN-1 through SUSTAIN-10 demonstrated superior HbA1c reductions (1.5-1.8% from baseline) compared to placebo, sitagliptin, exenatide ER, insulin glargine, dulaglutide, and canagliflozin. Weight loss averaged 4-6 kg across trials.'],
                            ['type' => 'content', 'value' => 'SUSTAIN-6 (cardiovascular outcomes trial, n=3,297) demonstrated a 26% reduction in major adverse cardiovascular events (MACE) — the composite of cardiovascular death, non-fatal MI, and non-fatal stroke — establishing cardiovascular benefit beyond glucose lowering.'],
                        ],
                    ],
                    [
                        'title' => 'STEP Trial Program (Weight Management)',
                        'entries' => [
                            ['type' => 'content', 'value' => 'The STEP trials evaluated semaglutide 2.4mg weekly for weight management. STEP-1 (n=1,961) demonstrated mean weight loss of 14.9% vs 2.4% with placebo over 68 weeks in adults with obesity. STEP-2 showed 9.6% weight loss in patients with T2D. STEP-3 combined semaglutide with intensive behavioral therapy, achieving 16% weight loss.'],
                            ['type' => 'content', 'value' => 'SELECT (n=17,604) — the largest cardiovascular outcomes trial for a weight management drug — demonstrated a 20% reduction in MACE in overweight/obese adults without diabetes, establishing cardiovascular benefit of weight loss with semaglutide independent of glycemic effects.'],
                        ],
                    ],
                    [
                        'title' => 'Safety Profile',
                        'entries' => [
                            ['type' => 'content', 'value' => 'The most common adverse events across trials were gastrointestinal: nausea (15-20%), diarrhea (8-10%), vomiting (5-9%), and constipation (5-7%), typically transient and most frequent during dose escalation. Discontinuation rates due to GI events were 4-7%. Rare but serious risks include pancreatitis, gallbladder events, and potential thyroid C-cell effects (boxed warning based on rodent carcinogenicity studies, not confirmed in humans). Retinopathy complications were observed in SUSTAIN-6 in patients with pre-existing diabetic retinopathy, attributed to rapid glucose improvement.'],
                        ],
                    ],
                ]),
                'conclusion' => 'Semaglutide represents a landmark achievement in peptide therapeutics and one of the most significant pharmacological developments of the 2020s. Its journey from incretin biology to a multi-indication therapeutic spanning diabetes, obesity, cardiovascular protection, and potentially MASH and Alzheimer\'s disease illustrates the broad physiological relevance of the GLP-1 signaling pathway. The clinical evidence base is exceptionally robust — with major outcomes trials (SUSTAIN-6, SELECT, PIONEER-6) demonstrating not only metabolic efficacy but meaningful cardiovascular risk reduction. The development of an oral peptide formulation (Rybelsus) overcame a longstanding pharmaceutical challenge and expanded patient access. However, important questions remain regarding long-term safety beyond 2-3 years, optimal treatment duration, weight regain after discontinuation, and equitable access given cost barriers. Research-grade semaglutide continues to serve as a reference compound for studying GLP-1 receptor pharmacology, incretin biology, and the intersection of metabolic and cardiovascular disease. As the therapeutic landscape evolves with dual and triple agonists (tirzepatide, retatrutide), semaglutide provides the benchmark against which new GLP-1-based therapies are measured.',
                'references' => json_encode([
                    ['title' => 'New England Journal of Medicine (2021) — STEP-1', 'authors' => 'Wilding JPH et al.', 'links' => []],
                    ['title' => 'New England Journal of Medicine (2016) — SUSTAIN-6', 'authors' => 'Marso SP et al.', 'links' => []],
                    ['title' => 'New England Journal of Medicine (2023) — SELECT', 'authors' => 'Lincoff AM et al.', 'links' => []],
                    ['title' => 'The Lancet (2019) — PIONEER-6', 'authors' => 'Husain M et al.', 'links' => []],
                    ['title' => 'Diabetes Care (2020)', 'authors' => 'Pratley RE et al.', 'links' => []],
                ]),
            ],

            'tirzepatide' => [
                'background' => 'Tirzepatide is a novel dual glucose-dependent insulinotropic polypeptide (GIP) and glucagon-like peptide-1 (GLP-1) receptor agonist developed by Eli Lilly and Company. It is a 39-amino acid synthetic peptide based on the native GIP sequence, with modifications that confer additional GLP-1 receptor agonist activity and a C20 fatty diacid moiety enabling once-weekly dosing. Tirzepatide represents the first approved "twincretin" — simultaneously engaging both incretin receptor pathways in a single molecule. The GIP receptor, historically underappreciated in drug development after early GIP-based therapies showed limited efficacy in T2D, was reconsidered following research demonstrating that combined GIP/GLP-1 agonism produces synergistic metabolic effects exceeding either pathway alone. Tirzepatide was approved by the FDA in 2022 as Mounjaro for type 2 diabetes and in 2023 as Zepbound for chronic weight management, following clinical trial results demonstrating unprecedented weight loss of up to 22.5% of body weight — surpassing all previously approved pharmacotherapies for obesity.',
                'mechanism_of_action_intro' => 'Tirzepatide engages two distinct incretin receptor pathways simultaneously, producing complementary and potentially synergistic effects on glucose homeostasis, appetite regulation, and energy metabolism.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The dual agonist mechanism provides broader pathway coverage than selective GLP-1 receptor agonists, with GIP receptor activation contributing additional metabolic effects.',
                        'points' => [
                            'GLP-1R agonism: glucose-dependent insulin secretion, glucagon suppression, delayed gastric emptying, and central appetite suppression through hypothalamic/brainstem GLP-1R activation',
                            'GIPR agonism: enhances glucose-dependent insulin secretion via complementary beta cell signaling, may improve beta cell sensitivity to GLP-1, and activates GIP receptors in adipose tissue to promote lipid metabolism and energy expenditure',
                            'Preclinical data suggests GIPR agonism in the CNS may enhance the anorectic effects of GLP-1R activation, potentially explaining the superior weight loss versus selective GLP-1 RAs',
                            'The C20 fatty diacid modification enables albumin binding with a half-life of approximately 5 days, supporting once-weekly administration',
                            'Demonstrates imbalanced agonism — approximately 5:1 GIP:GLP-1 receptor affinity ratio — suggesting GIP pathway engagement is the primary driver with GLP-1 as a complementary mechanism',
                        ],
                    ],
                ]),
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Metabolic and Weight Research',
                        'findings' => [
                            ['title' => 'Dual Incretin Synergy', 'description' => 'In DIO mouse models, tirzepatide produced greater weight loss and glucose lowering than equimolar doses of selective GIP or GLP-1 agonists alone, demonstrating true pharmacological synergy rather than additive effects. Pair-feeding studies confirmed that a portion of weight loss was independent of reduced food intake, suggesting effects on energy expenditure.'],
                            ['title' => 'Adipose Tissue Biology', 'description' => 'GIPR activation in white adipose tissue promoted lipid uptake and storage efficiency, potentially redirecting lipids away from ectopic deposition (liver, muscle). Brown adipose tissue studies showed increased thermogenic gene expression (UCP1) with dual agonism.'],
                            ['title' => 'Hepatic Effects', 'description' => 'In preclinical NASH models, tirzepatide reduced liver fat content, inflammation, and fibrosis markers more effectively than selective GLP-1 RAs, supporting investigation in metabolic liver disease.'],
                        ],
                    ],
                ]),
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'SURPASS Program (Type 2 Diabetes)',
                        'entries' => [
                            ['type' => 'content', 'value' => 'SURPASS-1 through SURPASS-5 enrolled over 6,000 patients with T2D. SURPASS-2 (head-to-head vs semaglutide 1mg) demonstrated superior HbA1c reduction (-2.46% vs -1.86%) and weight loss (-12.4kg vs -6.2kg) with tirzepatide 15mg. Up to 97% of patients achieved HbA1c <7% and 62% achieved normoglycemia (<5.7%).'],
                        ],
                    ],
                    [
                        'title' => 'SURMOUNT Program (Weight Management)',
                        'entries' => [
                            ['type' => 'content', 'value' => 'SURMOUNT-1 (n=2,539) in adults with obesity demonstrated mean weight loss of 22.5% with tirzepatide 15mg vs 2.4% placebo over 72 weeks — the highest pharmacotherapy-induced weight loss ever reported in a Phase III trial. 63% of participants lost ≥20% of body weight. SURMOUNT-2 in T2D patients showed 14.7% weight loss.'],
                            ['type' => 'content', 'value' => 'SURMOUNT-3 and SURMOUNT-4 evaluated tirzepatide after intensive lifestyle intervention and for weight maintenance, respectively, further characterizing the weight loss trajectory and durability.'],
                        ],
                    ],
                    [
                        'title' => 'Safety Profile',
                        'entries' => [
                            ['type' => 'content', 'value' => 'GI adverse events (nausea, diarrhea, vomiting) were the most common, similar in nature to GLP-1 RAs but with comparable or slightly lower rates. Discontinuation for adverse events was 4-7% across dose levels. No signal for pancreatitis or thyroid C-cell tumors in clinical trials to date.'],
                        ],
                    ],
                ]),
                'conclusion' => 'Tirzepatide has fundamentally reshaped the therapeutic landscape for metabolic disease. As the first approved dual GIP/GLP-1 receptor agonist, it validated the long-debated hypothesis that GIP receptor engagement adds meaningful clinical benefit to GLP-1-based therapy. The SURPASS and SURMOUNT programs demonstrated efficacy that exceeded expectations — with weight loss approaching that of bariatric surgery and glucose control reaching near-normoglycemia in the majority of treated patients. The compound has reinvigorated interest in GIP receptor biology, a pathway that was largely abandoned in drug development after early failures. Important ongoing research includes cardiovascular outcomes (SURPASS-CVOT), MASH/NASH (SYNERGY-NASH), heart failure with preserved ejection fraction, and obstructive sleep apnea. Questions remain regarding the optimal GIP:GLP-1 ratio, the contribution of each pathway to overall efficacy, and long-term safety. Research-grade tirzepatide serves as a critical reference compound for studying dual incretin biology and the evolving understanding of how GIP and GLP-1 pathways interact at the molecular, cellular, and systems level.',
                'references' => json_encode([
                    ['title' => 'New England Journal of Medicine (2022) — SURMOUNT-1', 'authors' => 'Jastreboff AM et al.', 'links' => []],
                    ['title' => 'New England Journal of Medicine (2021) — SURPASS-2', 'authors' => 'Frías JP et al.', 'links' => []],
                    ['title' => 'Nature Medicine (2023)', 'authors' => 'Willard FS et al.', 'links' => []],
                    ['title' => 'The Lancet (2023) — SURMOUNT-2', 'authors' => 'Garvey WT et al.', 'links' => []],
                ]),
            ],

            'sermorelin' => [
                'background' => 'Sermorelin (sermorelin acetate) is a synthetic peptide analog consisting of the first 29 amino acids of the naturally occurring 44-amino acid growth hormone-releasing hormone (GHRH). Designated as GRF(1-29)NH₂, sermorelin retains the full biological activity of native GHRH as the first 29 residues contain the entire pharmacophore required for GHRH receptor binding and activation. It was developed in the 1980s and became the first GHRH analog approved by the FDA (as Geref) for diagnostic evaluation of pituitary GH secretory capacity and was later approved as Geref Diagnostic. Sermorelin acts on the GHRH receptor (GHRHR) expressed on somatotroph cells of the anterior pituitary, stimulating growth hormone synthesis and secretion in a physiological pulsatile pattern. Unlike exogenous GH administration, sermorelin preserves the negative feedback regulation of the GH/IGF-1 axis, as GH release remains subject to somatostatin inhibition and endogenous regulatory mechanisms. This makes sermorelin a physiologically "cleaner" approach to GH axis stimulation compared to direct GH replacement.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Growth Hormone Axis Research',
                        'findings' => [
                            ['title' => 'GH Secretion Dynamics', 'description' => 'In rodent and primate models, sermorelin dose-dependently stimulated pulsatile GH release while preserving somatostatin-mediated feedback inhibition. This pulsatile pattern is critical — continuous GH exposure and pulsatile GH exposure activate different gene transcription programs in target tissues (liver, muscle, bone).'],
                            ['title' => 'Age-Related GH Decline', 'description' => 'In aged rodent models, sermorelin partially restored GH pulse amplitude, which declines with age (somatopause). However, the magnitude of GH response decreased with age, suggesting age-related changes in somatotroph responsiveness and/or increased somatostatin tone.'],
                            ['title' => 'Somatotroph Physiology', 'description' => 'Long-term sermorelin administration maintained or increased pituitary somatotroph number and GH mRNA expression, suggesting trophic effects on the pituitary gland — in contrast to exogenous GH which can cause somatotroph atrophy through negative feedback.'],
                        ],
                    ],
                    [
                        'title' => 'Body Composition and Metabolic Effects',
                        'findings' => [
                            ['title' => 'Lean Body Mass', 'description' => 'In GH-deficient animal models, sermorelin-stimulated endogenous GH release was associated with increased lean body mass, improved nitrogen balance, and enhanced protein synthesis rates in skeletal muscle.'],
                            ['title' => 'Sleep Architecture', 'description' => 'GHRH is a recognized physiological sleep-promoting factor. Animal studies confirmed that sermorelin administration during the sleep period enhanced slow-wave (Stage III/IV) sleep duration, consistent with the known role of GHRH neurons in sleep regulation.'],
                        ],
                    ],
                ]),
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Studies',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Sermorelin was FDA-approved as Geref Diagnostic for evaluating pituitary GH reserve. In the diagnostic setting, sermorelin injection reliably stimulates GH release in patients with intact somatotroph function, with failure to respond indicating pituitary-level GH deficiency.'],
                            ['type' => 'content', 'value' => 'Clinical studies in GH-deficient children demonstrated that sermorelin (Geref) could stimulate growth velocity when administered subcutaneously, though it was less potent than direct GH replacement. It was briefly marketed for pediatric GH deficiency before being withdrawn for commercial (not safety) reasons.'],
                            ['type' => 'content', 'value' => 'In healthy older adults, sermorelin increased GH pulse amplitude and IGF-1 levels in studies lasting 3-6 months. A 16-week study showed improvements in body composition parameters including reduced trunk fat and increased lean body mass.'],
                            ['type' => 'content', 'value' => 'Sleep studies in healthy subjects demonstrated that evening GHRH administration increased slow-wave sleep percentage without altering sleep latency or REM sleep, consistent with GHRH\'s role as an endogenous sleep factor.'],
                        ],
                    ],
                ]),
                'conclusion' => 'Sermorelin holds a distinctive position in growth hormone biology as the first clinically validated GHRH analog. Its mechanism — stimulating endogenous GH release while preserving physiological feedback regulation — offers a fundamentally different pharmacological approach compared to exogenous GH administration. The preservation of pulsatile GH secretion is particularly significant, as research has established that the pattern of GH exposure (pulsatile vs continuous) determines downstream gene expression and metabolic outcomes. Although withdrawn from the US market for commercial reasons (not safety concerns), sermorelin remains an active area of research interest. Its effects on sleep architecture, body composition, and the aging GH axis continue to be studied in clinical and preclinical settings. Research-grade sermorelin serves as the reference GHRH agonist against which newer secretagogues (tesamorelin, CJC-1295, MK-677) are benchmarked. The compound illustrates an important principle in endocrine pharmacology: physiological axis stimulation may offer advantages over direct hormone replacement in preserving normal regulatory mechanisms.',
            ],

            'tesamorelin' => [
                'background' => 'Tesamorelin (tesamorelin acetate) is a synthetic analog of growth hormone-releasing hormone (GHRH) consisting of the full 44-amino acid human GHRH sequence with an additional trans-3-hexenoic acid group attached to the tyrosine at position 1. This N-terminal modification significantly improves resistance to enzymatic degradation by dipeptidyl peptidase-4 (DPP-4), which rapidly cleaves native GHRH between positions 2 and 3, thereby extending the compound\'s biological activity. Developed by Theratechnologies Inc. of Montreal, Canada, tesamorelin was approved by the FDA in 2010 as Egrifta for the reduction of excess abdominal fat (lipodystrophy) in HIV-infected patients with lipohypertrophy. This made tesamorelin the only GHRH analog currently FDA-approved for a therapeutic (non-diagnostic) indication. The drug targets the specific pattern of visceral adipose tissue (VAT) accumulation that occurs in HIV-associated lipodystrophy, a metabolic complication of antiretroviral therapy that increases cardiovascular risk.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Visceral Adipose Tissue Research',
                        'findings' => [
                            ['title' => 'Lipolytic Activity', 'description' => 'Tesamorelin-stimulated GH release activated lipolytic pathways preferentially in visceral adipose tissue. Studies showed GH receptor expression is higher in visceral than subcutaneous adipocytes, potentially explaining the preferential reduction of trunk fat observed clinically.'],
                            ['title' => 'Hepatic Fat', 'description' => 'In animal models of hepatic steatosis, GHRH stimulation reduced liver fat content and improved markers of hepatic inflammation. IGF-1 elevation following GHRH agonism may contribute independently to hepatic lipid metabolism improvements.'],
                        ],
                    ],
                    [
                        'title' => 'Cognitive Research',
                        'findings' => [
                            ['title' => 'Neuroprotective Effects', 'description' => 'GHRH receptors are expressed in hippocampal neurons and cortical regions. Preclinical studies demonstrated that GHRH agonism improved cognitive performance in aged animals, potentially through GH/IGF-1-mediated neurotrophic effects, improved cerebral blood flow, and enhanced synaptic plasticity.'],
                        ],
                    ],
                ]),
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'FDA-Approved Indication',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Tesamorelin (Egrifta) is FDA-approved for reduction of excess abdominal fat in HIV-infected patients with lipodystrophy. Phase III trials (n=816) demonstrated a mean reduction of 15-18% in visceral adipose tissue (VAT) measured by CT scan after 26 weeks, compared to increases in placebo groups. Trunk fat reduction was accompanied by improvements in patient-reported body image.'],
                            ['type' => 'content', 'value' => 'The VAT reduction was specific — subcutaneous fat was not significantly affected. Upon discontinuation, VAT returned toward baseline, indicating the need for continuous treatment to maintain effect.'],
                        ],
                    ],
                    [
                        'title' => 'NAFLD/NASH Research',
                        'entries' => [
                            ['type' => 'content', 'value' => 'The TERALYTICS trial evaluated tesamorelin for hepatic steatosis in HIV patients. Results showed a 37% relative reduction in hepatic fat fraction (by MRI-PDFF) vs 10% with placebo. A significant proportion of patients achieved resolution of NAFLD. This has generated interest in tesamorelin for NASH treatment in broader populations.'],
                        ],
                    ],
                    [
                        'title' => 'Cognitive Function Studies',
                        'entries' => [
                            ['type' => 'content', 'value' => 'A 20-week RCT in healthy older adults (n=152) demonstrated that tesamorelin improved executive function and verbal memory compared to placebo. Adults with higher baseline VAT showed the greatest cognitive improvements, suggesting a link between metabolic and cognitive benefits. Larger trials are ongoing to evaluate tesamorelin for mild cognitive impairment and Alzheimer\'s disease prevention.'],
                        ],
                    ],
                ]),
                'conclusion' => 'Tesamorelin is the most clinically validated GHRH analog currently available, with FDA approval for HIV-associated lipodystrophy and an expanding evidence base in hepatic steatosis and cognitive function. Its mechanism of stimulating endogenous pulsatile GH release to preferentially reduce visceral adipose tissue addresses a clinically meaningful endpoint with cardiovascular implications. The TERALYTICS liver fat data and cognitive function trial results suggest potential applications well beyond its current approved indication. As the only GHRH analog with a therapeutic FDA approval, tesamorelin provides a critical reference point for understanding the clinical potential of GHRH axis modulation. Research-grade tesamorelin continues to be used to study the relationship between GH physiology, body fat distribution, hepatic metabolism, and cognitive function — areas of growing importance in aging research and metabolic medicine.',
            ],

            'pt-141' => [
                'background' => 'PT-141, known as bremelanotide, is a cyclic heptapeptide melanocortin receptor agonist with the sequence Ac-Nle-cyclo[Asp-His-D-Phe-Arg-Trp-Lys]-OH. It was developed from the melanocortin peptide Melanotan II through metabolic optimization and was approved by the FDA in 2019 as Vyleesi (bremelanotide injection) for the treatment of hypoactive sexual desire disorder (HSDD) in premenopausal women — making it the first melanocortin-based therapeutic approved for a CNS-mediated indication. PT-141 acts primarily through melanocortin-4 receptor (MC4R) agonism in the central nervous system, where MC4R is expressed in hypothalamic nuclei involved in sexual arousal, autonomic function, and motivated behavior. Unlike phosphodiesterase-5 inhibitors (sildenafil, tadalafil) which act peripherally on vascular smooth muscle, PT-141 acts centrally on neural pathways that initiate sexual arousal, representing a fundamentally different pharmacological mechanism for sexual dysfunction.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Melanocortin Receptor Biology',
                        'findings' => [
                            ['title' => 'MC4R Signaling', 'description' => 'In vitro receptor binding studies demonstrated PT-141 is a non-selective melanocortin agonist with highest functional potency at MC4R and MC1R, moderate activity at MC3R and MC5R. The sexual arousal effects are attributed primarily to MC4R activation in the medial preoptic area (MPOA) and paraventricular nucleus (PVN) of the hypothalamus.'],
                            ['title' => 'Sexual Behavior Models', 'description' => 'In rodent models, intracerebroventricular and systemic PT-141 administration increased solicitation behaviors in female rats and facilitated penile erection in male rats through a mechanism distinct from peripheral vasodilators. The effect was blocked by MC4R antagonists, confirming receptor specificity.'],
                        ],
                    ],
                ]),
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'FDA-Approved Indication',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Bremelanotide (Vyleesi) was approved in June 2019 for HSDD in premenopausal women. The RECONNECT Phase III trials (n=1,247) demonstrated a statistically significant increase in sexual desire (measured by the Female Sexual Distress Scale-Desire/Arousal/Orgasm) and a meaningful increase in satisfying sexual events compared to placebo.'],
                            ['type' => 'content', 'value' => 'Approximately 25% of women in the treatment group achieved a clinically meaningful response vs 17% with placebo. The on-demand dosing regimen (administered as needed, ≥45 minutes before anticipated activity) was designed to match the episodic nature of sexual encounters.'],
                        ],
                    ],
                    [
                        'title' => 'Male Sexual Dysfunction Studies',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Earlier Phase II studies in men with erectile dysfunction demonstrated that PT-141 induced erections in men who did not respond to PDE5 inhibitors, consistent with its central mechanism of action. However, development was not pursued to Phase III for the male indication.'],
                        ],
                    ],
                    [
                        'title' => 'Safety Profile',
                        'entries' => [
                            ['type' => 'content', 'value' => 'The most common adverse event was nausea (40% at the approved dose), which was typically transient. Transient increases in blood pressure and decreases in heart rate were observed. Use is limited to no more than one dose per 24 hours and no more than 8 doses per month due to blood pressure effects. PT-141 is contraindicated in uncontrolled hypertension.'],
                        ],
                    ],
                ]),
                'conclusion' => 'PT-141 (bremelanotide) represents a first-in-class melanocortin-based therapeutic that validated the concept of targeting central neural pathways for sexual dysfunction. Its FDA approval as Vyleesi marked a significant milestone in both melanocortin pharmacology and women\'s sexual health, providing the first non-hormonal, on-demand treatment for HSDD. The compound\'s mechanism — activating MC4R in hypothalamic arousal circuits — is fundamentally distinct from peripheral vasodilators and opened new understanding of the neurobiology of sexual desire. While the clinical effect size was modest compared to placebo, the approval established proof-of-concept for centrally acting melanocortin therapeutics. Research-grade PT-141 continues to be used for studying melanocortin receptor pharmacology, central regulation of sexual behavior, and the broader biology of MC4R signaling in motivated behavior, energy homeostasis, and autonomic function.',
            ],
        ];
    }
}
