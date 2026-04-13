<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class EncyclopediaArticlesBatch2Seeder extends Seeder
{
    public function run(): void
    {
        $articles = $this->getArticles();

        foreach ($articles as $slug => $data) {
            $category = ProductCategory::where('slug', $slug)->first();
            if (!$category) {
                $this->command?->warn("Category not found: {$slug}");
                continue;
            }

            // Skip if already has a published article
            $existing = EducationPost::where('product_category_id', $category->id)
                ->where('status', 'published')
                ->first();
            if ($existing) {
                $this->command?->info("Skipping {$data['title']} — already has article");
                continue;
            }

            EducationPost::updateOrCreate(
                ['product_category_id' => $category->id],
                array_merge($data, [
                    'slug' => $slug,
                    'status' => 'published',
                    'published_at' => now(),
                    'storage' => 'Refrigerate at 2-8°C (36-46°F). Freeze lyophilized form for long-term storage.',
                ])
            );

            $this->command?->info("Created article: {$data['title']}");
        }
    }

    private function getArticles(): array
    {
        return [

            //------------------------------------------------------------------
            // 1. PINEALON
            //------------------------------------------------------------------
            'pinealon' => [
                'title' => 'Pinealon',
                'peptide_full_name' => 'Glu-Asp-Arg (EDR) Tripeptide',
                'research_title' => 'Pinealon (EDR): A Comprehensive Research Overview',
                'research_outline' => 'An in-depth analysis of Pinealon, a synthetic tripeptide bioregulator targeting the pineal gland and central nervous system, covering its development within the Khavinson peptide bioregulation framework, mechanisms of action, preclinical findings, and research applications.',
                'education_tag' => 'Neuropeptides',
                'description' => 'Pinealon is a synthetic tripeptide (Glu-Asp-Arg) developed as a pineal gland bioregulator within the Khavinson peptide bioregulation paradigm. It is classified among the short peptide bioregulators (cytogens) designed to normalize function in the central nervous system and pineal gland.',
                'molecular_formula' => 'C₁₄H₂₃N₅O₈',
                'molecular_weight' => '405.36 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral or sublingual administration studied in research settings',
                'background' => 'Pinealon is a synthetic tripeptide with the amino acid sequence Glu-Asp-Arg (EDR), developed by Professor Vladimir Khavinson and colleagues at the St. Petersburg Institute of Bioregulation and Gerontology in Russia. It belongs to a class of short peptide bioregulators known as cytogens, which are synthesized analogs of naturally occurring peptide fractions originally isolated from specific organ tissues. Pinealon was designed as a synthetic analog of peptide fractions derived from the pineal gland (epiphysis), with the goal of normalizing function in pineal tissue and the broader central nervous system. Khavinson\'s peptide bioregulation theory proposes that short peptides (2-4 amino acids) interact with specific DNA sequences and regulatory elements to modulate gene expression in tissue-specific patterns. According to this framework, Pinealon preferentially interacts with gene regulatory regions in neural and pineal tissue, influencing the expression of proteins involved in neuroprotection, melatonin synthesis, and circadian rhythm regulation. The compound has been the subject of numerous publications in Russian and international scientific literature, primarily from research groups in Russia.',
                'mechanism_of_action_intro' => 'Pinealon is proposed to act through epigenetic and gene-regulatory mechanisms consistent with the Khavinson peptide bioregulation model. Research suggests it interacts with DNA and chromatin structures to modulate gene expression in neural tissues.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanisms of Pinealon involve direct interaction with DNA regulatory elements and downstream effects on neuroprotective pathways.',
                        'points' => [
                            'Proposed to interact with specific DNA sequences in the promoter regions of genes involved in neuroprotection and pineal gland function',
                            'In vitro studies suggest modulation of melatonin synthesis pathways and circadian gene expression',
                            'Reported to influence expression of anti-apoptotic proteins including Bcl-2 in neural cell cultures',
                            'May modulate oxidative stress response genes in central nervous system tissue',
                            'Khavinson and colleagues have demonstrated that short peptides can penetrate cell membranes and nuclear envelopes without requiring receptor-mediated uptake',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Pinealon has been investigated in cell culture systems and animal models, primarily by research groups associated with the St. Petersburg Institute of Bioregulation and Gerontology.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro Studies',
                        'findings' => [
                            ['title' => 'Neuroprotective Effects', 'description' => 'In cortical neuron cultures exposed to oxidative stress, Pinealon treatment was associated with increased cell viability and reduced markers of apoptosis. The peptide appeared to upregulate expression of anti-apoptotic factors in neural cell lines.'],
                            ['title' => 'Gene Expression Modulation', 'description' => 'Studies using pineal cell cultures reported that EDR peptide treatment influenced expression of genes associated with melatonin biosynthesis and circadian regulation, consistent with tissue-specific bioregulatory activity.'],
                        ],
                    ],
                    [
                        'title' => 'Animal Model Research',
                        'findings' => [
                            ['title' => 'Aging Models', 'description' => 'In aged rodent models, Pinealon administration was associated with normalization of pineal gland melatonin production and improvements in circadian rhythm markers that had declined with age.'],
                            ['title' => 'Neuroprotection Studies', 'description' => 'Rodent models of induced cerebral ischemia showed that Pinealon pretreatment was associated with reduced infarct volume and improved neurological outcome scores in some studies.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'The majority of preclinical data originates from research groups in Russia. Independent replication by international laboratories remains limited. Results should be interpreted with this context in mind.',
                'human_use_intro' => 'Pinealon has been included in some clinical observations conducted in Russia, though these studies do not meet the standards of Western randomized controlled clinical trials.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Observations',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Published reports from Russian clinical settings describe the use of Pinealon in elderly subjects with age-related cognitive decline. These observational reports suggest improvements in cognitive function scores and sleep quality parameters, though study designs lack the rigor of double-blind, placebo-controlled trials.'],
                            ['type' => 'content', 'value' => 'No Phase I, II, or III clinical trials meeting international regulatory standards (ICH-GCP) have been conducted for Pinealon.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Pinealon is not approved by the FDA, EMA, or any major Western regulatory authority for human therapeutic use. In Russia, some Khavinson peptide preparations have been registered as dietary supplements or parapharmaceuticals, but this does not constitute drug approval.'],
                            ['type' => 'content', 'value' => 'Pinealon is classified as a research compound (RUO) in international markets.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Pinealon is an experimental research compound. It is not approved for human consumption, therapeutic use, or self-administration. Researchers must comply with all applicable regulations.',
                'potential_applications_intro' => 'Based on preclinical evidence, potential research applications span neuroprotection and pineal gland biology.',
                'potential_applications' => json_encode([
                    ['title' => 'Neuroprotection Research', 'description' => 'Investigation of Pinealon\'s proposed neuroprotective mechanisms in models of oxidative stress and ischemic injury.'],
                    ['title' => 'Circadian Biology', 'description' => 'Study of short peptide effects on melatonin synthesis and circadian gene expression in pineal tissue.'],
                    ['title' => 'Peptide-DNA Interaction Research', 'description' => 'Pinealon serves as a model compound for studying the Khavinson hypothesis that short peptides interact directly with DNA regulatory elements.'],
                ]),
                'potential_applications_important_context' => 'All potential applications are based on preclinical research, primarily from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Pinealon (EDR) is a tripeptide bioregulator developed within the Khavinson peptide bioregulation framework, targeting the pineal gland and central nervous system. Preclinical studies, predominantly from Russian research institutions, suggest neuroprotective properties and modulation of pineal function through proposed epigenetic mechanisms. While the peptide bioregulation theory is scientifically interesting, the evidence base requires broader independent replication. Pinealon has not undergone clinical trials meeting international regulatory standards and remains an investigational research compound. Its study contributes to the broader scientific inquiry into whether short peptides can exert tissue-specific gene-regulatory effects.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2011)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Neuroendocrinology Letters (2010)', 'authors' => 'Khavinson VKh, Malinin VV.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2014)', 'authors' => 'Anisimov VN, Khavinson VKh.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Pinealon is a synthetic tripeptide (Glu-Asp-Arg) targeting the pineal gland and CNS',
                    'Developed by Khavinson as part of the short peptide bioregulation framework',
                    'Preclinical data suggest neuroprotective and circadian-regulatory properties',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Pinealon is a synthetic tripeptide bioregulator (Glu-Asp-Arg) designed to normalize pineal gland and central nervous system function.',
                'areas_of_research_intro' => 'Pinealon research spans neuroprotection, circadian biology, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Neuroprotection', 'description' => 'Oxidative stress resistance and anti-apoptotic signaling in neural tissue'],
                    ['name' => 'Pineal Gland Biology', 'description' => 'Melatonin synthesis regulation and circadian rhythm modulation'],
                    ['name' => 'Bioregulation Theory', 'description' => 'Short peptide-DNA interactions and tissue-specific gene regulation'],
                ]),
                'key_effects' => json_encode(['Proposed neuroprotective activity', 'Pineal gland function modulation', 'Circadian gene expression regulation', 'Anti-apoptotic signaling in neural cultures']),
                'common_use_cases' => json_encode(['Neuroprotection research', 'Pineal gland function studies', 'Peptide bioregulation investigations']),
                'how_it_works' => 'Pinealon (Glu-Asp-Arg) is proposed to penetrate cell membranes and interact directly with DNA regulatory regions in neural and pineal tissue. This is theorized to modulate expression of genes involved in melatonin synthesis, neuroprotection, and circadian regulation, consistent with Khavinson\'s peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 2. PE-22-28
            //------------------------------------------------------------------
            'pe-22-28' => [
                'title' => 'PE-22-28',
                'peptide_full_name' => 'Phosphodiesterase Inhibitor Peptide Fragment 22-28',
                'research_title' => 'PE-22-28: A Comprehensive Research Overview',
                'research_outline' => 'An analysis of PE-22-28, a heptapeptide derived from the phosphodiesterase inhibitory region of a larger protein, covering its proposed mechanisms in cognitive research, preclinical findings, and research applications.',
                'education_tag' => 'Nootropics',
                'description' => 'PE-22-28 is a synthetic heptapeptide fragment derived from a phosphodiesterase (PDE) inhibitory sequence. It has been investigated in preclinical models for its effects on cyclic nucleotide signaling and potential cognitive-enhancing properties.',
                'molecular_formula' => 'C₄₁H₆₅N₁₁O₁₁',
                'molecular_weight' => '~872 g/mol',
                'half_life' => 'Short (minutes; typical of small peptides)',
                'bioavailability' => 'Parenteral administration in research settings',
                'background' => 'PE-22-28 is a synthetic heptapeptide that corresponds to a specific fragment (residues 22-28) of a phosphodiesterase inhibitory protein sequence. Phosphodiesterases (PDEs) are a family of enzymes that degrade cyclic nucleotides — cyclic adenosine monophosphate (cAMP) and cyclic guanosine monophosphate (cGMP) — which serve as critical second messengers in intracellular signaling. By inhibiting PDE activity, PE-22-28 is proposed to elevate intracellular cAMP and/or cGMP levels, thereby modulating downstream signaling cascades involved in synaptic plasticity, memory consolidation, and neuronal survival. The compound has generated research interest in the nootropic and cognitive neuroscience fields because PDE inhibition is a well-validated mechanism for enhancing cognitive function. Approved PDE inhibitors such as rolipram (PDE4) and sildenafil (PDE5) have demonstrated that modulation of cyclic nucleotide levels can produce significant physiological effects. PE-22-28 represents an attempt to harness peptide-based PDE inhibition for cognitive research applications.',
                'mechanism_of_action_intro' => 'PE-22-28 is proposed to exert its effects through inhibition of phosphodiesterase enzymes, thereby elevating intracellular levels of cyclic nucleotides involved in synaptic signaling and plasticity.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism centers on cyclic nucleotide signaling pathways that are critical for synaptic plasticity and memory formation.',
                        'points' => [
                            'Proposed to inhibit phosphodiesterase activity, reducing degradation of cAMP and cGMP',
                            'Elevated cAMP levels activate protein kinase A (PKA), which phosphorylates CREB (cAMP response element-binding protein)',
                            'CREB phosphorylation drives transcription of genes involved in long-term potentiation (LTP) and memory consolidation',
                            'cGMP elevation may contribute to vasodilation and improved cerebral perfusion in neural tissue',
                            'PKA-mediated signaling also modulates ion channel conductance and neurotransmitter release at synapses',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'PE-22-28 has been investigated in limited preclinical studies, primarily examining its effects on cyclic nucleotide levels and cognitive-related behavioral endpoints in rodent models.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro Studies',
                        'findings' => [
                            ['title' => 'PDE Inhibition Assays', 'description' => 'Biochemical assays have examined PE-22-28 for its ability to inhibit phosphodiesterase activity in neural tissue homogenates. Results suggest modest inhibitory activity against certain PDE isoforms, though the selectivity profile remains to be fully characterized.'],
                            ['title' => 'cAMP Elevation', 'description' => 'Cell culture studies in neuronal cell lines have reported increases in intracellular cAMP levels following PE-22-28 treatment, consistent with PDE inhibitory activity.'],
                        ],
                    ],
                    [
                        'title' => 'Animal Model Research',
                        'findings' => [
                            ['title' => 'Cognitive Behavioral Testing', 'description' => 'In rodent models, PE-22-28 administration has been associated with improved performance in spatial memory tasks (Morris water maze) and novel object recognition paradigms in some preliminary studies.'],
                            ['title' => 'Synaptic Plasticity', 'description' => 'Electrophysiological studies in hippocampal slice preparations have examined the effects of PE-22-28 on long-term potentiation (LTP) parameters, a cellular correlate of learning and memory.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data for PE-22-28 is limited in scope. The published evidence base is small and findings require independent replication.',
                'human_use_intro' => 'No human clinical trials have been conducted with PE-22-28. All available data derives from in-vitro and animal studies.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'PE-22-28 has not undergone any human clinical evaluation. No safety, pharmacokinetic, or efficacy data exists from human subjects. The compound remains entirely in the preclinical research stage.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'PE-22-28 is not approved by the FDA, EMA, or any regulatory body for human use. It is classified as a research chemical for in-vitro and laboratory use only.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'PE-22-28 is an experimental research compound with no clinical data. It is not approved for human use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on the established role of PDE inhibition in cognitive function, PE-22-28 is relevant to several research domains.',
                'potential_applications' => json_encode([
                    ['title' => 'Cognitive Neuroscience', 'description' => 'Investigation of peptide-based PDE inhibition as a strategy for modulating synaptic plasticity and memory-related signaling pathways.'],
                    ['title' => 'cAMP/CREB Signaling Research', 'description' => 'Study of cyclic nucleotide-mediated gene transcription in neural tissue, particularly CREB-dependent transcription.'],
                    ['title' => 'Neuropeptide Pharmacology', 'description' => 'Comparison of peptide-based versus small-molecule PDE inhibitors in terms of isoform selectivity and tissue distribution.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on limited preclinical data. No therapeutic claims are made.',
                'conclusion' => 'PE-22-28 is a synthetic heptapeptide fragment with proposed phosphodiesterase inhibitory activity, investigated primarily for its potential cognitive research applications. The scientific rationale is grounded in the well-established role of cAMP/CREB signaling in synaptic plasticity and memory consolidation. However, the specific evidence base for PE-22-28 remains limited, with a small number of preclinical studies and no human clinical data. The compound represents an exploratory approach to peptide-based cognitive research and requires substantially more investigation to characterize its pharmacological profile, selectivity, and potential utility as a research tool.',
                'references' => json_encode([
                    ['title' => 'Pharmacology Biochemistry and Behavior (2013)', 'authors' => 'Bhatt DK et al.', 'links' => []],
                    ['title' => 'Neuropharmacology (2005)', 'authors' => 'Barad M et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'PE-22-28 is a synthetic heptapeptide with proposed phosphodiesterase inhibitory activity',
                    'Proposed mechanism involves cAMP/PKA/CREB pathway activation for cognitive enhancement',
                    'Limited preclinical data with no human clinical trials conducted',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'PE-22-28 is a synthetic heptapeptide fragment with proposed phosphodiesterase inhibitory activity, investigated for cognitive research applications.',
                'areas_of_research_intro' => 'PE-22-28 research is focused on cognitive neuroscience and cyclic nucleotide signaling.',
                'areas_of_research' => json_encode([
                    ['name' => 'Cognitive Neuroscience', 'description' => 'Memory consolidation and synaptic plasticity mechanisms'],
                    ['name' => 'Signal Transduction', 'description' => 'cAMP/cGMP signaling and PDE inhibition pharmacology'],
                ]),
                'key_effects' => json_encode(['Proposed PDE inhibition', 'cAMP/CREB pathway modulation', 'Exploratory cognitive research tool', 'Synaptic plasticity investigation']),
                'common_use_cases' => json_encode(['PDE inhibition research', 'Cognitive signaling studies', 'Cyclic nucleotide pathway investigations']),
                'how_it_works' => 'PE-22-28 is proposed to inhibit phosphodiesterase enzymes that degrade cAMP and cGMP. By elevating these cyclic nucleotides, the peptide may activate PKA-mediated CREB phosphorylation, driving transcription of genes involved in long-term potentiation and memory consolidation.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 3. ACE-031
            //------------------------------------------------------------------
            'ace-031' => [
                'title' => 'ACE-031',
                'peptide_full_name' => 'Activin Receptor Type IIB-Fc Fusion Protein',
                'research_title' => 'ACE-031: A Comprehensive Research Overview',
                'research_outline' => 'An in-depth analysis of ACE-031, a soluble activin receptor type IIB decoy that sequesters myostatin and related ligands, covering its mechanism of action, clinical trial data in muscle wasting conditions, and current research status.',
                'education_tag' => 'Muscle Research',
                'description' => 'ACE-031 is a recombinant fusion protein consisting of the extracellular domain of the human activin receptor type IIB (ActRIIB) linked to the Fc portion of human IgG1. It functions as a soluble decoy receptor that binds and neutralizes myostatin, activins, and other TGF-beta superfamily ligands involved in muscle growth regulation.',
                'molecular_formula' => 'Recombinant fusion protein (~90 kDa)',
                'molecular_weight' => '~90,000 g/mol',
                'half_life' => '~2 weeks (estimated from clinical pharmacokinetics)',
                'bioavailability' => 'Subcutaneous injection (biologics administration)',
                'background' => 'ACE-031 was developed by Acceleron Pharma as a therapeutic candidate targeting the myostatin/activin signaling pathway. Myostatin (GDF-8) is a member of the TGF-beta superfamily that functions as a potent negative regulator of skeletal muscle mass. Loss-of-function mutations in the myostatin gene result in dramatic muscle hypertrophy across multiple species, making myostatin inhibition an attractive strategy for treating muscle wasting conditions. ACE-031 is a soluble form of the activin receptor type IIB (ActRIIB), which is a natural receptor for myostatin, activins, GDF-11, and other TGF-beta family members. By circulating as a free decoy receptor, ACE-031 intercepts these ligands before they can bind to cell-surface receptors, thereby blocking their growth-inhibitory signals. This broad ligand-trapping approach differentiates ACE-031 from selective myostatin antibodies. ACE-031 advanced into clinical trials, including studies in Duchenne muscular dystrophy (DMD), before development challenges arose.',
                'mechanism_of_action_intro' => 'ACE-031 functions as a ligand trap for multiple TGF-beta superfamily members that signal through the activin type IIB receptor, thereby removing negative regulators of muscle growth from circulation.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The mechanism involves sequestration of circulating ligands that normally activate ActRIIB on muscle cell surfaces, blocking the downstream Smad2/3 signaling that inhibits muscle protein synthesis.',
                        'points' => [
                            'Binds and neutralizes circulating myostatin (GDF-8), the primary negative regulator of skeletal muscle mass',
                            'Also sequesters activin A, activin B, GDF-11, and other TGF-beta superfamily ligands that signal through ActRIIB',
                            'Prevents activation of Smad2/3 signaling pathways that suppress myogenic gene transcription and protein synthesis',
                            'Removal of inhibitory signaling allows unopposed activity of anabolic pathways including IGF-1/Akt/mTOR',
                            'The Fc fusion component extends circulating half-life through FcRn-mediated recycling',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'ACE-031 and related ActRIIB-Fc constructs have been extensively studied in animal models of muscle wasting and neuromuscular disease.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Animal Model Studies',
                        'findings' => [
                            ['title' => 'Muscle Mass Effects', 'description' => 'In murine models, ActRIIB-Fc treatment produced significant increases in skeletal muscle mass, with some studies reporting 20-40% increases in muscle weight over 4-week treatment periods. Effects were observed in both normal and dystrophic mice (mdx model).'],
                            ['title' => 'DMD Mouse Models', 'description' => 'In the mdx mouse model of Duchenne muscular dystrophy, ActRIIB-Fc treatment increased muscle mass and improved functional measures including grip strength and running endurance.'],
                            ['title' => 'Bone Density Effects', 'description' => 'Preclinical studies also noted increases in bone mineral density and bone formation markers, suggesting effects beyond skeletal muscle.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical findings in dystrophic mouse models do not necessarily predict human clinical outcomes. ACE-031 clinical development was discontinued due to safety signals.',
                'human_use_intro' => 'ACE-031 advanced into human clinical trials, including a Phase II study in Duchenne muscular dystrophy, before development was halted.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Trial Results',
                        'entries' => [
                            ['type' => 'content', 'value' => 'A Phase I study in healthy postmenopausal women demonstrated that a single subcutaneous dose of ACE-031 increased lean body mass and thigh muscle volume in a dose-dependent manner, with effects persisting for approximately one month.'],
                            ['type' => 'content', 'value' => 'A Phase II trial in boys with Duchenne muscular dystrophy was initiated but placed on clinical hold by the FDA due to safety concerns including minor nosebleeds, gum bleeding, and telangiectasias (dilated small blood vessels), which were potentially related to the effects of activin/BMP pathway modulation on vascular biology.'],
                            ['type' => 'content', 'value' => 'Development of ACE-031 was subsequently discontinued. Acceleron Pharma pivoted to more selective ligand traps (such as luspatercept/ACE-536 and sotatercept/ACE-011) with narrower binding profiles.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'ACE-031 clinical development was discontinued following FDA clinical hold. It is not approved for any therapeutic indication. The compound is available only as a research reagent.'],
                            ['type' => 'content', 'value' => 'ACE-031 is prohibited by WADA as a myostatin inhibitor. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'ACE-031 clinical development was halted due to safety concerns. It is not approved for human use and is sold for research purposes only.',
                'potential_applications_intro' => 'Despite the discontinuation of clinical development, ACE-031 remains relevant as a research tool for studying myostatin/activin pathway biology.',
                'potential_applications' => json_encode([
                    ['title' => 'Myostatin Pathway Research', 'description' => 'ACE-031 serves as a reference compound for studying the effects of broad ActRIIB ligand neutralization on muscle biology.'],
                    ['title' => 'Neuromuscular Disease Models', 'description' => 'Continued use in preclinical models of muscular dystrophy and other neuromuscular conditions to study the effects of removing TGF-beta superfamily inhibitory signals.'],
                    ['title' => 'Ligand Trap Biology', 'description' => 'Understanding the differences between broad-spectrum (ACE-031) and selective (luspatercept) ligand trapping approaches.'],
                ]),
                'potential_applications_important_context' => 'ACE-031 is a discontinued clinical candidate with known safety concerns. All applications are strictly research-based.',
                'conclusion' => 'ACE-031 represents an important chapter in the scientific effort to therapeutically target the myostatin/activin pathway. As a soluble ActRIIB decoy receptor, it demonstrated proof-of-concept that blocking negative regulators of muscle growth could increase lean body mass in both preclinical models and human subjects. However, the broad ligand-trapping profile of ACE-031, which neutralized activins, BMPs, and other TGF-beta family members in addition to myostatin, resulted in off-target vascular effects that led to clinical hold and discontinuation. This experience informed the development of more selective next-generation molecules. ACE-031 remains a valuable research tool for studying ActRIIB pathway biology, though its clinical limitations underscore the importance of ligand selectivity in therapeutic development.',
                'references' => json_encode([
                    ['title' => 'Muscle & Nerve (2014)', 'authors' => 'Campbell C et al.', 'links' => []],
                    ['title' => 'Neuromuscular Disorders (2013)', 'authors' => 'Attie KM et al.', 'links' => []],
                    ['title' => 'Proceedings of the National Academy of Sciences (2010)', 'authors' => 'Cadena SM et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'ACE-031 is a soluble ActRIIB-Fc fusion protein that traps myostatin and related TGF-beta ligands',
                    'Demonstrated increases in lean body mass in Phase I human trials',
                    'Phase II DMD trial halted due to vascular safety concerns (telangiectasias, bleeding)',
                    'Clinical development discontinued — classified as research use only (RUO)',
                ]),
                'overview' => 'ACE-031 is a soluble activin receptor type IIB decoy protein that neutralizes myostatin and related growth-inhibitory ligands.',
                'areas_of_research_intro' => 'ACE-031 research spans muscle biology, neuromuscular disease, and TGF-beta pathway pharmacology.',
                'areas_of_research' => json_encode([
                    ['name' => 'Muscle Biology', 'description' => 'Myostatin/activin signaling and skeletal muscle mass regulation'],
                    ['name' => 'Neuromuscular Disease', 'description' => 'Preclinical models of muscular dystrophy and muscle wasting'],
                    ['name' => 'TGF-beta Pharmacology', 'description' => 'Ligand trap selectivity and off-target pathway effects'],
                ]),
                'key_effects' => json_encode(['Myostatin and activin sequestration', 'Increased lean body mass (clinical data)', 'Broad TGF-beta ligand neutralization', 'Discontinued clinical candidate']),
                'common_use_cases' => json_encode(['Myostatin pathway research', 'Muscle wasting model studies', 'ActRIIB signaling investigations']),
                'how_it_works' => 'ACE-031 is a soluble decoy receptor that circulates and binds myostatin, activins, and other TGF-beta superfamily ligands before they can activate cell-surface ActRIIB receptors. This blocks Smad2/3 signaling that normally inhibits muscle growth, allowing anabolic pathways to proceed unopposed.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 4. ARA-290 (CIBINETIDE)
            //------------------------------------------------------------------
            'ara-290' => [
                'title' => 'ARA-290 (Cibinetide)',
                'peptide_full_name' => 'Cibinetide — Erythropoietin-Derived Innate Repair Receptor Agonist',
                'research_title' => 'ARA-290 (Cibinetide): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of ARA-290, an 11-amino acid peptide derived from the erythropoietin molecule that selectively activates the innate repair receptor, covering its tissue-protective mechanisms, clinical trial data, and research applications.',
                'education_tag' => 'Tissue Repair',
                'description' => 'ARA-290 (Cibinetide) is a synthetic 11-amino acid peptide derived from the B-helix of erythropoietin (EPO). It selectively activates the innate repair receptor (IRR), a heteromeric receptor composed of the EPO receptor and the beta common receptor (CD131), without stimulating erythropoiesis.',
                'molecular_formula' => 'C₅₃H₈₅N₁₇O₁₆',
                'molecular_weight' => '1,257.4 g/mol',
                'half_life' => '~2 minutes (rapid clearance)',
                'bioavailability' => 'Subcutaneous or intravenous administration in research settings',
                'background' => 'ARA-290, now known by its international nonproprietary name Cibinetide, is a synthetic 11-amino acid linear peptide derived from the B-helix region of the erythropoietin (EPO) molecule. It was developed by Araim Pharmaceuticals based on the observation that EPO possesses tissue-protective properties independent of its erythropoietic function. The tissue-protective effects of EPO are mediated through the innate repair receptor (IRR), a heteromeric receptor complex composed of the EPO receptor (EPOR) and the beta common receptor (betacR/CD131). This receptor is distinct from the classical homodimeric EPOR that drives red blood cell production. ARA-290 was specifically designed to selectively activate the IRR without binding the classical EPOR homodimer, thereby providing tissue protection without the risks associated with EPO therapy, such as thrombosis and polycythemia. The compound has undergone multiple clinical trials, particularly in sarcoidosis-related small fiber neuropathy and diabetic neuropathy.',
                'mechanism_of_action_intro' => 'ARA-290 selectively activates the innate repair receptor (IRR), triggering anti-inflammatory, anti-apoptotic, and tissue-repair signaling cascades without erythropoietic stimulation.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The IRR is expressed on tissues under metabolic stress or injury, making ARA-290 activity context-dependent and focused on damaged tissues.',
                        'points' => [
                            'Selectively binds the EPOR/betacR heteromeric complex (innate repair receptor) expressed on stressed or injured tissues',
                            'Does not activate the classical EPOR homodimer, avoiding erythropoietic stimulation',
                            'Triggers JAK2/STAT3/5 signaling pathways with anti-apoptotic and anti-inflammatory downstream effects',
                            'Activates NF-kB-mediated cytoprotective gene transcription',
                            'Promotes macrophage phenotype switching from pro-inflammatory (M1) to anti-inflammatory/reparative (M2)',
                            'Inhibits pro-inflammatory cytokine release including TNF-alpha, IL-6, and IL-1beta',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'ARA-290 has been studied extensively in preclinical models of tissue injury, neuropathy, and inflammation.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Animal Model Studies',
                        'findings' => [
                            ['title' => 'Neuropathy Models', 'description' => 'In rodent models of diabetic neuropathy and chemotherapy-induced neuropathy, ARA-290 treatment was associated with preservation of small nerve fiber density and improved sensory function.'],
                            ['title' => 'Ischemia-Reperfusion Injury', 'description' => 'Preclinical models of cardiac, renal, and cerebral ischemia-reperfusion injury showed reduced infarct size and improved functional outcomes with ARA-290 administration.'],
                            ['title' => 'Inflammatory Models', 'description' => 'ARA-290 reduced inflammatory markers and tissue damage in models of chronic inflammation, consistent with its role in promoting M2 macrophage polarization.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical results in animal models of neuropathy and tissue injury may not directly translate to human clinical outcomes.',
                'human_use_intro' => 'ARA-290 (Cibinetide) has been evaluated in multiple human clinical trials, primarily for neuropathic conditions.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Published Clinical Trials',
                        'entries' => [
                            ['type' => 'content', 'value' => 'A Phase II randomized controlled trial in patients with sarcoidosis-associated small fiber neuropathy demonstrated that ARA-290 improved small nerve fiber density on corneal confocal microscopy and patient-reported neuropathy symptom scores.'],
                            ['type' => 'content', 'value' => 'Clinical studies in type 2 diabetes patients with neuropathic symptoms showed improvements in corneal nerve fiber measures and reported symptom relief following ARA-290 treatment.'],
                            ['type' => 'content', 'value' => 'ARA-290 demonstrated a favorable safety profile in clinical trials, with no erythropoietic stimulation (no increase in hemoglobin or red blood cell counts) and no significant adverse events attributed to the study drug.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'ARA-290 (Cibinetide) has received Orphan Drug Designation from the FDA for sarcoidosis. However, it has not received full regulatory approval for any therapeutic indication.'],
                            ['type' => 'content', 'value' => 'Research-grade ARA-290 is classified as an investigational compound (RUO) and is not equivalent to clinical trial material.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'ARA-290 is an investigational compound with Orphan Drug Designation but no approved therapeutic indications. Research-grade material is for laboratory use only.',
                'potential_applications_intro' => 'Based on clinical and preclinical data, ARA-290 is relevant to multiple research domains involving tissue protection and repair.',
                'potential_applications' => json_encode([
                    ['title' => 'Neuropathy Research', 'description' => 'Investigation of IRR-mediated small nerve fiber protection and regeneration in models of diabetic and inflammatory neuropathy.'],
                    ['title' => 'Innate Repair Receptor Biology', 'description' => 'Study of the EPOR/betacR heteromeric receptor as a therapeutic target for tissue protection.'],
                    ['title' => 'Anti-Inflammatory Mechanisms', 'description' => 'Research into macrophage phenotype switching and resolution of chronic inflammation.'],
                ]),
                'potential_applications_important_context' => 'Despite clinical trial data, ARA-290 is not approved for any therapeutic use. All research applications are investigational.',
                'conclusion' => 'ARA-290 (Cibinetide) represents a significant advancement in the field of tissue-protective peptide research. By selectively targeting the innate repair receptor rather than the classical EPO receptor, it provides a strategy for harnessing the tissue-protective properties of erythropoietin signaling without the risks of erythropoietic stimulation. Clinical trial data in sarcoidosis and diabetic neuropathy have provided preliminary evidence of efficacy, and the compound has received Orphan Drug Designation from the FDA. However, ARA-290 has not yet achieved regulatory approval, and its clinical development continues. The compound is valuable as a research tool for studying IRR biology, tissue repair mechanisms, and the resolution of inflammation.',
                'references' => json_encode([
                    ['title' => 'JCI Insight (2018)', 'authors' => 'Dahan A et al.', 'links' => []],
                    ['title' => 'Molecular Medicine (2014)', 'authors' => 'Brines M et al.', 'links' => []],
                    ['title' => 'QJM: An International Journal of Medicine (2016)', 'authors' => 'Heij L et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'ARA-290 is an 11-amino acid EPO-derived peptide that selectively activates the innate repair receptor',
                    'Does not stimulate erythropoiesis — tissue-protective effects only',
                    'Phase II clinical data in sarcoidosis and diabetic neuropathy',
                    'FDA Orphan Drug Designation for sarcoidosis — not yet approved for any indication (RUO)',
                ]),
                'overview' => 'ARA-290 (Cibinetide) is an erythropoietin-derived peptide that selectively activates the innate repair receptor for tissue-protective effects without erythropoietic stimulation.',
                'areas_of_research_intro' => 'ARA-290 research spans neuroprotection, tissue repair, and innate immunity.',
                'areas_of_research' => json_encode([
                    ['name' => 'Neuropathy', 'description' => 'Small fiber neuropathy protection and regeneration'],
                    ['name' => 'Tissue Repair', 'description' => 'Innate repair receptor-mediated cytoprotection'],
                    ['name' => 'Immunomodulation', 'description' => 'Macrophage polarization and anti-inflammatory signaling'],
                ]),
                'key_effects' => json_encode(['Selective IRR activation', 'Anti-inflammatory signaling', 'Small nerve fiber protection', 'No erythropoietic stimulation']),
                'common_use_cases' => json_encode(['Neuropathy research', 'Tissue protection studies', 'Innate repair receptor investigations']),
                'how_it_works' => 'ARA-290 binds the innate repair receptor (EPOR/betacR heteromer) on injured or metabolically stressed tissues, activating JAK2/STAT signaling that drives anti-apoptotic, anti-inflammatory, and tissue-reparative gene expression. It does not bind the classical EPOR homodimer, avoiding erythropoietic stimulation.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 5. B7-33
            //------------------------------------------------------------------
            'b7-33' => [
                'title' => 'B7-33',
                'peptide_full_name' => 'Relaxin Receptor RXFP1 Agonist Peptide',
                'research_title' => 'B7-33: A Comprehensive Research Overview',
                'research_outline' => 'An analysis of B7-33, a single-chain relaxin analog that selectively activates the RXFP1 receptor, covering its anti-fibrotic mechanism, preclinical findings, and research applications in fibrosis models.',
                'education_tag' => 'Anti-Fibrotic Research',
                'description' => 'B7-33 is a single-chain peptide analog of human relaxin-2 (H2 relaxin) that selectively activates the relaxin family peptide receptor 1 (RXFP1). Unlike native relaxin, which is a two-chain peptide, B7-33 is a simplified B-chain derivative designed for improved manufacturability while retaining anti-fibrotic signaling properties.',
                'molecular_formula' => 'C₁₅₃H₂₃₈N₄₄O₄₅S₂',
                'molecular_weight' => '~3,600 g/mol',
                'half_life' => 'Short (minutes; rapidly cleared)',
                'bioavailability' => 'Parenteral administration in research settings',
                'background' => 'B7-33 is a synthetic single-chain peptide derived from the B-chain of human relaxin-2. Relaxin is a peptide hormone belonging to the insulin superfamily that plays important roles in reproductive physiology, cardiovascular regulation, and tissue remodeling. The native relaxin-2 molecule is a two-chain (A and B chain) peptide connected by disulfide bonds, making it expensive and complex to manufacture. B7-33 was developed by researchers at the Florey Institute of Neuroscience and Mental Health (Melbourne, Australia) as a simplified single-chain analog that retains the ability to activate the relaxin family peptide receptor 1 (RXFP1). RXFP1 is a G-protein coupled receptor that mediates the anti-fibrotic, vasodilatory, and anti-inflammatory effects of relaxin. B7-33 represents a biased agonist approach, preferentially activating certain downstream signaling pathways (particularly the anti-fibrotic pERK pathway) while showing reduced activity at others, potentially offering a more targeted pharmacological profile than native relaxin.',
                'mechanism_of_action_intro' => 'B7-33 activates RXFP1 as a biased agonist, preferentially stimulating anti-fibrotic signaling pathways.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The biased agonism of B7-33 at RXFP1 results in selective activation of extracellular signal-regulated kinase (ERK) and downstream anti-fibrotic transcription programs.',
                        'points' => [
                            'Binds and activates RXFP1, the primary relaxin receptor, as a single-chain peptide',
                            'Functions as a biased agonist, preferentially activating the pERK1/2 signaling pathway',
                            'Reduced activation of cAMP-dependent pathways compared to native relaxin-2',
                            'ERK-mediated signaling drives expression of matrix metalloproteinases (MMPs) that degrade excess collagen',
                            'Inhibits TGF-beta1-stimulated collagen synthesis and myofibroblast differentiation',
                            'May reduce Smad2 phosphorylation, a key step in pro-fibrotic TGF-beta signaling',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'B7-33 has been evaluated in multiple preclinical models of organ fibrosis, demonstrating anti-fibrotic activity across different tissue types.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Fibrosis Model Studies',
                        'findings' => [
                            ['title' => 'Cardiac Fibrosis', 'description' => 'In murine models of cardiac fibrosis, B7-33 administration reduced interstitial collagen deposition and improved cardiac function parameters, comparable to effects observed with native relaxin-2.'],
                            ['title' => 'Pulmonary Fibrosis', 'description' => 'Rodent models of bleomycin-induced pulmonary fibrosis showed that B7-33 treatment attenuated collagen accumulation in lung tissue and improved lung compliance measures.'],
                            ['title' => 'Renal Fibrosis', 'description' => 'In models of kidney fibrosis, B7-33 reduced tubulointerstitial fibrosis markers and preserved renal function parameters.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'All fibrosis model data is preclinical. The translation of anti-fibrotic effects from animal models to human disease is complex and not guaranteed.',
                'human_use_intro' => 'B7-33 has not been evaluated in human clinical trials. All available data derives from preclinical research.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'No human clinical trials have been conducted with B7-33. The compound remains in the preclinical research phase. Native relaxin-2 (serelaxin) was evaluated in Phase III clinical trials for acute heart failure (RELAX-AHF studies), providing context for the relaxin pathway, but B7-33 itself has not entered clinical development.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'B7-33 is not approved by any regulatory authority for human use. It is classified as a research compound (RUO) available for in-vitro and animal research only.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'B7-33 is an experimental research compound with no human clinical data. It is not approved for human use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical evidence, B7-33 is relevant to fibrosis research across multiple organ systems.',
                'potential_applications' => json_encode([
                    ['title' => 'Organ Fibrosis Research', 'description' => 'Investigation of RXFP1-mediated anti-fibrotic signaling in models of cardiac, pulmonary, renal, and hepatic fibrosis.'],
                    ['title' => 'Biased Agonism Studies', 'description' => 'Study of pathway-selective GPCR activation and its implications for therapeutic selectivity.'],
                    ['title' => 'Relaxin Biology', 'description' => 'B7-33 as a simplified tool compound for studying RXFP1 pharmacology without the manufacturing complexity of native relaxin.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data. No therapeutic claims are made.',
                'conclusion' => 'B7-33 is an innovative single-chain relaxin analog that addresses a key limitation of native relaxin-2 therapeutics: manufacturing complexity. By retaining anti-fibrotic efficacy through biased RXFP1 agonism while simplifying the molecular structure to a single peptide chain, B7-33 offers advantages as both a research tool and potential therapeutic lead. Preclinical data across cardiac, pulmonary, and renal fibrosis models demonstrate promising anti-fibrotic activity. However, B7-33 has not been evaluated in human clinical trials, and its development as a therapeutic remains at the preclinical stage. The compound contributes to the broader understanding of relaxin pathway biology and biased GPCR agonism as strategies for treating fibrotic diseases.',
                'references' => json_encode([
                    ['title' => 'Science Signaling (2016)', 'authors' => 'Hossain MA et al.', 'links' => []],
                    ['title' => 'Chemical Science (2016)', 'authors' => 'Hossain MA et al.', 'links' => []],
                    ['title' => 'Clinical and Experimental Pharmacology and Physiology (2017)', 'authors' => 'Samuel CS et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'B7-33 is a single-chain relaxin-2 B-chain analog that activates RXFP1',
                    'Functions as a biased agonist preferentially activating anti-fibrotic pERK signaling',
                    'Preclinical anti-fibrotic efficacy demonstrated in cardiac, pulmonary, and renal models',
                    'No human clinical trials — classified as research use only (RUO)',
                ]),
                'overview' => 'B7-33 is a simplified single-chain relaxin analog that selectively activates RXFP1 anti-fibrotic signaling pathways.',
                'areas_of_research_intro' => 'B7-33 research focuses on fibrosis, relaxin pharmacology, and biased GPCR agonism.',
                'areas_of_research' => json_encode([
                    ['name' => 'Fibrosis Research', 'description' => 'Anti-fibrotic mechanisms across cardiac, pulmonary, and renal tissues'],
                    ['name' => 'GPCR Pharmacology', 'description' => 'Biased agonism and pathway-selective receptor activation'],
                    ['name' => 'Peptide Engineering', 'description' => 'Single-chain analogs of multi-chain peptide hormones'],
                ]),
                'key_effects' => json_encode(['RXFP1 biased agonism', 'Anti-fibrotic collagen reduction', 'MMP upregulation', 'TGF-beta pathway modulation']),
                'common_use_cases' => json_encode(['Fibrosis model research', 'Relaxin receptor studies', 'Anti-fibrotic compound screening']),
                'how_it_works' => 'B7-33 binds RXFP1 as a biased agonist, preferentially activating pERK1/2 signaling over cAMP pathways. This drives expression of matrix metalloproteinases (MMPs) that degrade excess collagen while inhibiting TGF-beta1-mediated myofibroblast differentiation and pro-fibrotic gene transcription.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 6. PNC-27
            //------------------------------------------------------------------
            'pnc-27' => [
                'title' => 'PNC-27',
                'peptide_full_name' => 'p53-MDM2/HDM2 Targeting Membranolytic Peptide',
                'research_title' => 'PNC-27: A Comprehensive Research Overview',
                'research_outline' => 'An analysis of PNC-27, a chimeric peptide containing a p53 HDM2-binding domain and a cell membrane-penetrating sequence, covering its proposed mechanism of selective cancer cell membranolysis, preclinical findings, and research applications.',
                'education_tag' => 'Cancer Research',
                'description' => 'PNC-27 is a synthetic chimeric peptide composed of a p53 C-terminal peptide fused to a cell-penetrating peptide sequence. It is designed to selectively target cancer cells that overexpress HDM2 (human double minute 2) on their cell surface, inducing membranolysis in transformed cells while sparing normal cells.',
                'molecular_formula' => 'C₁₅₄H₂₃₇N₄₇O₄₃',
                'molecular_weight' => '~3,500 g/mol',
                'half_life' => 'Short (peptide; estimated minutes)',
                'bioavailability' => 'Parenteral administration in research settings',
                'background' => 'PNC-27 is a chimeric peptide developed by researchers at the UMDNJ-New Jersey Medical School and other institutions for anti-cancer research. The peptide was designed based on the observation that certain cancer cells express HDM2 (the human homolog of mouse MDM2) on their outer cell membrane surface, a localization not typically seen in normal cells. In normal cellular biology, HDM2/MDM2 functions as an intracellular E3 ubiquitin ligase that negatively regulates the tumor suppressor p53 by targeting it for proteasomal degradation. PNC-27 contains a peptide sequence from the p53 protein that binds to the HDM2 binding pocket, fused to a membrane-lytic leader sequence. The hypothesis is that PNC-27 selectively binds to surface-expressed HDM2 on cancer cells, where the membrane-active leader sequence then disrupts membrane integrity, causing cancer-selective cell death through membranolysis (necrosis) rather than apoptosis. This mechanism of action is distinct from most anticancer peptides and has been the subject of ongoing preclinical investigation.',
                'mechanism_of_action_intro' => 'PNC-27 is proposed to induce selective membranolysis of cancer cells through a two-component mechanism involving HDM2 binding and membrane disruption.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism relies on the differential localization of HDM2 on cancer cell surfaces versus its intracellular localization in normal cells.',
                        'points' => [
                            'Contains a p53-derived sequence that binds with high affinity to the HDM2/MDM2 binding pocket',
                            'The leader sequence provides membrane-penetrating/lytic activity when localized at cell membranes',
                            'In cancer cells with surface HDM2, PNC-27 binding concentrates the peptide at the membrane, triggering membranolysis',
                            'Normal cells lacking surface HDM2 are not targeted, providing proposed selectivity',
                            'Cell death occurs through membrane disruption (necrosis) rather than classical apoptotic pathways',
                            'The membranolytic mechanism may circumvent apoptosis resistance common in advanced cancers',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'PNC-27 has been investigated in cell culture systems and limited animal studies using various cancer cell lines and tumor models.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro Studies',
                        'findings' => [
                            ['title' => 'Cancer Cell Selectivity', 'description' => 'In cell culture experiments, PNC-27 demonstrated cytotoxicity against multiple cancer cell lines (pancreatic, breast, leukemia) while showing minimal toxicity to non-transformed control cells, consistent with the selective membranolysis hypothesis.'],
                            ['title' => 'Mechanism Studies', 'description' => 'Electron microscopy of PNC-27-treated cancer cells revealed membrane pore formation and membrane blebbing consistent with membranolytic rather than apoptotic cell death. Confocal imaging confirmed co-localization of PNC-27 with surface HDM2.'],
                        ],
                    ],
                    [
                        'title' => 'Animal Studies',
                        'findings' => [
                            ['title' => 'Tumor Xenograft Models', 'description' => 'Limited animal studies using human tumor xenografts in mice have reported tumor growth inhibition following PNC-27 treatment, though the evidence base remains small and requires broader replication.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'PNC-27 research is at an early preclinical stage with a limited number of published studies. Results in cancer cell lines do not predict clinical efficacy.',
                'human_use_intro' => 'No human clinical trials have been conducted with PNC-27. All data derives from cell culture and limited animal studies.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'PNC-27 has not entered human clinical evaluation. No safety, pharmacokinetic, or efficacy data exists from human subjects. The compound remains at the early preclinical research stage.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'PNC-27 is not approved by the FDA, EMA, or any regulatory authority for human use. It is classified as a research chemical for laboratory use only.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'PNC-27 is an experimental research compound at an early preclinical stage. It is not approved for human use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, PNC-27 is relevant to cancer biology and peptide therapeutic research.',
                'potential_applications' => json_encode([
                    ['title' => 'Cancer Cell Biology', 'description' => 'Investigation of surface HDM2 expression as a cancer-selective targeting strategy.'],
                    ['title' => 'Membranolytic Peptide Research', 'description' => 'Study of peptide-mediated membrane disruption as an alternative to apoptosis-dependent cancer cell killing.'],
                    ['title' => 'p53/MDM2 Pathway Research', 'description' => 'Understanding the translocation and surface expression of MDM2 in transformed cells.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on limited preclinical data. No therapeutic claims are made.',
                'conclusion' => 'PNC-27 represents an innovative approach to cancer-selective peptide research, exploiting the differential surface expression of HDM2 on cancer cells to achieve targeted membranolysis. The proposed mechanism, if validated, could address a major challenge in oncology: killing cancer cells that have developed resistance to apoptotic pathways. In vitro data demonstrating cancer cell selectivity and membranolytic cell death are scientifically interesting, but the evidence base remains limited. PNC-27 has not entered clinical development, and substantial additional preclinical work is needed to characterize its pharmacology, stability, and in vivo efficacy. The compound serves as a valuable research tool for studying surface HDM2 biology and membrane-targeting anticancer strategies.',
                'references' => json_encode([
                    ['title' => 'International Journal of Cancer (2006)', 'authors' => 'Kanovsky M et al.', 'links' => []],
                    ['title' => 'BMC Cancer (2009)', 'authors' => 'Sarafraz-Yazdi E et al.', 'links' => []],
                    ['title' => 'Journal of Oncology (2012)', 'authors' => 'Sarafraz-Yazdi E et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'PNC-27 is a chimeric peptide targeting surface-expressed HDM2 on cancer cells',
                    'Proposed mechanism involves selective membranolysis rather than apoptosis',
                    'In vitro selectivity for cancer cells over normal cells demonstrated',
                    'Early preclinical stage — no human trials — classified as research use only (RUO)',
                ]),
                'overview' => 'PNC-27 is a chimeric peptide designed to selectively target and lyse cancer cells expressing HDM2 on their surface membranes.',
                'areas_of_research_intro' => 'PNC-27 research spans cancer biology, peptide therapeutics, and membrane-targeting strategies.',
                'areas_of_research' => json_encode([
                    ['name' => 'Cancer Biology', 'description' => 'Surface HDM2 expression and cancer-selective targeting'],
                    ['name' => 'Peptide Therapeutics', 'description' => 'Membranolytic peptides and chimeric peptide design'],
                    ['name' => 'p53/MDM2 Research', 'description' => 'MDM2 localization dynamics in transformed cells'],
                ]),
                'key_effects' => json_encode(['Cancer-selective membranolysis', 'HDM2 surface binding', 'Apoptosis-independent cell death', 'Normal cell sparing (in vitro)']),
                'common_use_cases' => json_encode(['Cancer cell targeting research', 'Membranolytic peptide studies', 'HDM2 biology investigations']),
                'how_it_works' => 'PNC-27 contains a p53-derived HDM2-binding domain fused to a membrane-lytic leader sequence. It binds HDM2 expressed on cancer cell surfaces, concentrating the lytic peptide at the membrane. This causes membrane pore formation and membranolysis, killing cancer cells through necrosis rather than apoptosis.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 7. PTD-DBM
            //------------------------------------------------------------------
            'ptd-dbm' => [
                'title' => 'PTD-DBM',
                'peptide_full_name' => 'Protein Transduction Domain–Bone Morphogenetic Peptide',
                'research_title' => 'PTD-DBM: A Comprehensive Research Overview',
                'research_outline' => 'An analysis of PTD-DBM, a cell-penetrating peptide fused to an osteogenic signaling domain, covering its mechanism of intracellular delivery and osteogenic differentiation, preclinical findings, and research applications in bone regeneration.',
                'education_tag' => 'Bone Research',
                'description' => 'PTD-DBM is a synthetic fusion peptide combining a protein transduction domain (PTD) for cellular uptake with a domain derived from bone morphogenetic protein signaling (DBM). It is designed to promote osteogenic differentiation of mesenchymal stem cells through intracellular delivery of osteoinductive signals.',
                'molecular_formula' => 'Fusion peptide (variable depending on construct)',
                'molecular_weight' => 'Variable (~3,000-5,000 g/mol depending on construct)',
                'half_life' => 'Short (peptide; minutes to hours)',
                'bioavailability' => 'Direct application to cell cultures or local injection in research settings',
                'background' => 'PTD-DBM is a rationally designed fusion peptide created for bone regeneration research. It combines two functional domains: a protein transduction domain (PTD) that enables receptor-independent cellular uptake, and a domain derived from bone morphogenetic protein (BMP) signaling sequences (DBM) that activates osteogenic transcription programs once inside the cell. The concept behind PTD-DBM is to overcome limitations of conventional BMP therapy, which relies on extracellular receptor binding and requires supraphysiological concentrations to achieve osteogenic effects. By delivering an osteogenic signaling peptide directly into the cytoplasm and nucleus of target cells, PTD-DBM aims to achieve osteogenic differentiation more efficiently. The PTD component (commonly derived from HIV-TAT, polyarginine, or similar cell-penetrating sequences) allows the peptide to traverse cell membranes without requiring endocytic uptake. Once inside the cell, the DBM domain is proposed to interact with intracellular signaling components that drive osteoblast differentiation, including Smad proteins and osteogenic transcription factors such as Runx2.',
                'mechanism_of_action_intro' => 'PTD-DBM employs a dual mechanism: receptor-independent cellular entry via the protein transduction domain, followed by intracellular activation of osteogenic signaling by the BMP-derived domain.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The mechanism involves two distinct functional phases: cellular penetration and intracellular osteogenic signaling activation.',
                        'points' => [
                            'PTD component enables receptor-independent cellular uptake through direct membrane translocation or macropinocytosis',
                            'Intracellular delivery bypasses the need for cell-surface BMP receptor binding',
                            'DBM domain interacts with Smad signaling proteins and osteogenic transcription factors',
                            'Proposed to activate Runx2/Cbfa1 transcription, the master regulator of osteoblast differentiation',
                            'May enhance expression of downstream osteogenic markers including alkaline phosphatase, osteocalcin, and collagen type I',
                            'Potential to promote mineralization and bone matrix deposition in mesenchymal stem cell populations',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'PTD-DBM has been investigated primarily in cell culture systems and some animal models for bone defect repair.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and In Vivo Studies',
                        'findings' => [
                            ['title' => 'Osteogenic Differentiation', 'description' => 'In mesenchymal stem cell (MSC) cultures, PTD-DBM treatment promoted osteogenic differentiation markers including alkaline phosphatase activity, Runx2 expression, and calcium deposition compared to untreated controls.'],
                            ['title' => 'Cellular Uptake Studies', 'description' => 'Fluorescently labeled PTD-DBM demonstrated rapid cellular uptake in MSC cultures within minutes, confirming the cell-penetrating activity of the PTD component.'],
                            ['title' => 'Bone Defect Models', 'description' => 'In limited rodent calvarial defect models, local application of PTD-DBM was associated with enhanced bone formation at the defect site compared to controls, though studies are preliminary.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'PTD-DBM research is at an early preclinical stage. Bone regeneration results in small animal models may not translate to clinical applications.',
                'human_use_intro' => 'No human clinical trials have been conducted with PTD-DBM. All available data derives from cell culture and small animal studies.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'PTD-DBM has not entered human clinical evaluation. No safety, pharmacokinetic, or efficacy data exists from human subjects.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'PTD-DBM is not approved by any regulatory authority for human use. It is classified as a research reagent for laboratory use only (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'PTD-DBM is an experimental research compound. It is not approved for human use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, PTD-DBM is relevant to bone regeneration and cell-penetrating peptide research.',
                'potential_applications' => json_encode([
                    ['title' => 'Bone Tissue Engineering', 'description' => 'Use as an osteoinductive factor in scaffold-based bone regeneration research as an alternative to recombinant BMPs.'],
                    ['title' => 'Stem Cell Differentiation', 'description' => 'Investigation of intracellular delivery strategies for directing MSC osteogenic commitment.'],
                    ['title' => 'Cell-Penetrating Peptide Research', 'description' => 'Study of PTD-mediated intracellular cargo delivery for biological applications.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on early preclinical data. No therapeutic claims are made.',
                'conclusion' => 'PTD-DBM represents an innovative approach to bone regeneration research by combining cell-penetrating peptide technology with osteogenic signaling. The strategy of delivering osteoinductive signals directly into cells bypasses limitations of conventional BMP therapy, which requires high extracellular concentrations and receptor-dependent uptake. Early preclinical data demonstrating osteogenic differentiation in MSC cultures and bone formation in small animal defect models are encouraging but preliminary. Substantial additional research is needed to optimize the peptide construct, characterize its pharmacokinetics, and validate its efficacy in larger, clinically relevant bone defect models. PTD-DBM contributes to the growing field of intracellular peptide therapeutics and offers a novel tool for bone biology research.',
                'references' => json_encode([
                    ['title' => 'Biomaterials (2016)', 'authors' => 'Suh JS et al.', 'links' => []],
                    ['title' => 'Tissue Engineering Part A (2017)', 'authors' => 'Park SY et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'PTD-DBM is a fusion peptide combining cell-penetrating and osteogenic signaling domains',
                    'Bypasses cell-surface BMP receptors through direct intracellular delivery',
                    'Promotes osteogenic differentiation in MSC cultures and bone formation in rodent models',
                    'Early preclinical stage — no human trials — classified as research use only (RUO)',
                ]),
                'overview' => 'PTD-DBM is a cell-penetrating fusion peptide designed to deliver osteogenic signals directly into cells for bone regeneration research.',
                'areas_of_research_intro' => 'PTD-DBM research spans bone biology, stem cell science, and drug delivery.',
                'areas_of_research' => json_encode([
                    ['name' => 'Bone Regeneration', 'description' => 'Osteogenic differentiation and bone defect repair'],
                    ['name' => 'Stem Cell Biology', 'description' => 'MSC osteogenic commitment and lineage specification'],
                    ['name' => 'Peptide Drug Delivery', 'description' => 'Intracellular delivery via cell-penetrating peptide technology'],
                ]),
                'key_effects' => json_encode(['Intracellular osteogenic signaling', 'Receptor-independent cellular uptake', 'MSC osteogenic differentiation', 'Bone matrix mineralization']),
                'common_use_cases' => json_encode(['Bone regeneration research', 'MSC differentiation studies', 'Cell-penetrating peptide investigations']),
                'how_it_works' => 'The PTD component enables receptor-independent cell membrane traversal, delivering the DBM osteogenic domain directly into the cytoplasm. Inside the cell, the DBM domain activates Smad signaling and Runx2 transcription, driving osteoblast differentiation and bone matrix formation without requiring extracellular BMP receptor binding.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 8. P21
            //------------------------------------------------------------------
            'p21' => [
                'title' => 'P21',
                'peptide_full_name' => 'CNTF-Derived Neurogenic Peptide (P21)',
                'research_title' => 'P21 (CNTF-Derived Peptide): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of P21, a synthetic peptide derived from ciliary neurotrophic factor (CNTF), covering its proposed neurogenic mechanism, preclinical findings in Alzheimer\'s disease models, and research applications.',
                'education_tag' => 'Neurogenesis',
                'description' => 'P21 is an 11-amino acid peptide derived from the active region of ciliary neurotrophic factor (CNTF). It is designed to promote neurogenesis and synaptic plasticity without the full neurotrophic factor\'s limitations, and has been studied primarily in preclinical models of neurodegenerative disease.',
                'molecular_formula' => 'C₅₆H₈₇N₁₅O₁₆',
                'molecular_weight' => '~1,230 g/mol',
                'half_life' => 'Short (peptide; estimated minutes)',
                'bioavailability' => 'Parenteral administration in research settings',
                'background' => 'P21 is a synthetic 11-amino acid peptide that corresponds to a biologically active epitope of ciliary neurotrophic factor (CNTF), a member of the interleukin-6 family of cytokines. CNTF is a potent neurotrophic factor that supports neuronal survival, promotes neurogenesis, and enhances synaptic plasticity. However, the full CNTF protein has significant limitations for therapeutic development, including poor blood-brain barrier penetration, immunogenicity, and pleiotropic effects. P21 was developed by researchers at the New York State Institute for Basic Research in Developmental Disabilities (now the CUNY College of Staten Island) as a small-molecule alternative that retains the neurotrophic signaling capacity of CNTF while offering improved pharmacological properties. The peptide has been studied extensively in transgenic mouse models of Alzheimer\'s disease, where it has shown effects on hippocampal neurogenesis, dendritic complexity, and cognitive function. P21 is proposed to work by competitively inhibiting the interaction between leukemia inhibitory factor (LIF) and its receptor, thereby modulating downstream STAT3 signaling in a way that favors neurogenesis over astrogliogenesis.',
                'mechanism_of_action_intro' => 'P21 is proposed to modulate neurotrophic signaling pathways in a manner that promotes neurogenesis and dendritic remodeling in the hippocampus.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The mechanism involves modulation of the CNTF/LIF receptor signaling axis, with downstream effects on neuronal differentiation and synaptic connectivity.',
                        'points' => [
                            'Derived from a CNTF epitope that modulates LIF receptor signaling',
                            'Proposed to competitively inhibit LIF binding, altering STAT3 signaling dynamics',
                            'Promotes neural progenitor cell proliferation and differentiation in the hippocampal dentate gyrus',
                            'Enhances dendritic branching and spine density, increasing synaptic connectivity',
                            'May reduce tau hyperphosphorylation through modulation of GSK-3beta signaling',
                            'Increases BDNF expression in hippocampal regions, supporting synaptic plasticity',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'P21 has been studied in multiple transgenic mouse models of Alzheimer\'s disease and age-related cognitive decline.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Alzheimer\'s Disease Models',
                        'findings' => [
                            ['title' => 'Neurogenesis Enhancement', 'description' => 'In 3xTg-AD transgenic mice, chronic P21 treatment promoted hippocampal neurogenesis, as measured by BrdU incorporation and doublecortin expression in the dentate gyrus.'],
                            ['title' => 'Cognitive Function', 'description' => 'P21-treated Alzheimer\'s model mice showed improved performance in Morris water maze spatial memory testing and novel object recognition tasks compared to vehicle-treated controls.'],
                            ['title' => 'Dendritic Remodeling', 'description' => 'Golgi staining analysis demonstrated increased dendritic branching complexity and spine density in hippocampal neurons of P21-treated mice.'],
                            ['title' => 'Tau Pathology', 'description' => 'Some studies reported reduced tau hyperphosphorylation in P21-treated transgenic mice, potentially linked to modulation of GSK-3beta activity.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'All findings are from transgenic mouse models. Results in AD mouse models frequently fail to translate to human Alzheimer\'s disease. P21 has not been tested in humans.',
                'human_use_intro' => 'No human clinical trials have been conducted with P21. All available data comes from preclinical research in rodent models.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'P21 has not undergone any human clinical evaluation. The substantial gap between preclinical efficacy in transgenic mouse models and potential human therapeutic applications remains to be addressed through formal clinical development.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'P21 is not approved by the FDA, EMA, or any regulatory authority for human use. It is classified as a research compound for laboratory use only (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'P21 is an experimental research compound with no human clinical data. It is not approved for human use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical evidence, P21 is relevant to neurodegenerative disease research and neurogenesis biology.',
                'potential_applications' => json_encode([
                    ['title' => 'Neurogenesis Research', 'description' => 'Study of adult hippocampal neurogenesis and its modulation by CNTF-derived signaling in aging and disease models.'],
                    ['title' => 'Neurodegenerative Disease Models', 'description' => 'Use as a research tool in transgenic models of Alzheimer\'s disease and related tauopathies.'],
                    ['title' => 'Synaptic Plasticity', 'description' => 'Investigation of dendritic remodeling and spine dynamics in hippocampal circuits.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical rodent data. Alzheimer\'s mouse model results frequently do not translate to human disease.',
                'conclusion' => 'P21 is a CNTF-derived peptide that has shown promising effects on hippocampal neurogenesis, synaptic connectivity, and cognitive function in transgenic mouse models of Alzheimer\'s disease. Its design as a small peptide analog of a full neurotrophic factor addresses key pharmacological limitations of protein therapeutics. Preclinical data across multiple AD mouse models are consistent in demonstrating enhanced neurogenesis and improved cognitive outcomes. However, the well-documented challenge of translating Alzheimer\'s mouse model results to human disease necessitates caution. P21 has not entered clinical development, and its therapeutic potential remains unvalidated in human subjects. The compound is a valuable tool for studying adult neurogenesis, CNTF/LIF pathway biology, and neurodegenerative disease mechanisms in preclinical settings.',
                'references' => json_encode([
                    ['title' => 'Neurobiology of Aging (2014)', 'authors' => 'Bolognin S et al.', 'links' => []],
                    ['title' => 'Journal of Alzheimer\'s Disease (2012)', 'authors' => 'Blanchard J et al.', 'links' => []],
                    ['title' => 'Neuroscience (2010)', 'authors' => 'Li B et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'P21 is an 11-amino acid peptide derived from ciliary neurotrophic factor (CNTF)',
                    'Promotes hippocampal neurogenesis and dendritic remodeling in AD mouse models',
                    'Improved cognitive performance in multiple transgenic Alzheimer\'s models',
                    'No human clinical trials — classified as research use only (RUO)',
                ]),
                'overview' => 'P21 is a CNTF-derived peptide that promotes neurogenesis and synaptic plasticity, studied primarily in preclinical Alzheimer\'s disease models.',
                'areas_of_research_intro' => 'P21 research focuses on neurogenesis, neurodegeneration, and synaptic plasticity.',
                'areas_of_research' => json_encode([
                    ['name' => 'Neurogenesis', 'description' => 'Adult hippocampal neurogenesis and neural progenitor cell biology'],
                    ['name' => 'Alzheimer\'s Research', 'description' => 'Cognitive function and tau pathology in transgenic models'],
                    ['name' => 'Neurotrophic Factor Biology', 'description' => 'CNTF/LIF pathway signaling and receptor modulation'],
                ]),
                'key_effects' => json_encode(['Hippocampal neurogenesis promotion', 'Dendritic remodeling and spine growth', 'Cognitive function improvement (preclinical)', 'BDNF expression enhancement']),
                'common_use_cases' => json_encode(['AD model research', 'Neurogenesis studies', 'Neurotrophic signaling investigations']),
                'how_it_works' => 'P21 modulates the CNTF/LIF receptor signaling axis, competitively inhibiting LIF binding and altering STAT3 signaling dynamics. This shifts neural progenitor cell fate toward neuronal differentiation, enhancing hippocampal neurogenesis, dendritic branching, and BDNF-mediated synaptic plasticity.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 9. ADIPOTIDE (FTPP)
            //------------------------------------------------------------------
            'adipotide-ftpp' => [
                'title' => 'Adipotide (FTPP)',
                'peptide_full_name' => 'Fat-Targeted Proapoptotic Peptide (FTPP/Adipotide)',
                'research_title' => 'Adipotide (FTPP): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Adipotide, a chimeric peptide that targets prohibitin on adipose vasculature endothelium, covering its vascular-targeting mechanism, preclinical findings in obesity models, and research status.',
                'education_tag' => 'Metabolic Research',
                'description' => 'Adipotide (FTPP) is a chimeric peptidomimetic composed of a prohibitin-targeting sequence (CKGGRAKDC) conjugated to a proapoptotic peptide sequence (D(KLAKLAK)2). It is designed to selectively target and ablate blood vessels supplying white adipose tissue.',
                'molecular_formula' => 'C₉₈H₁₆₇N₃₃O₂₅',
                'molecular_weight' => '~2,270 g/mol',
                'half_life' => 'Short (peptide; estimated minutes to hours)',
                'bioavailability' => 'Subcutaneous injection in research settings',
                'background' => 'Adipotide, also known as FTPP (Fat-Targeted Proapoptotic Peptide), was developed by researchers at the University of Texas MD Anderson Cancer Center, led by Dr. Wadih Arap and Dr. Renata Pasqualini. The concept originated from their vascular targeting research, which demonstrated that blood vessels in different tissues express distinct surface markers (the "vascular address system"). Their work identified prohibitin as a marker expressed on the surface of endothelial cells lining blood vessels that supply white adipose tissue. Adipotide was designed as a chimeric molecule with two functional domains: a targeting sequence (CKGGRAKDC) that homes to prohibitin on adipose vasculature, and a proapoptotic effector sequence (D(KLAKLAK)2) that disrupts mitochondrial membranes upon internalization, triggering apoptosis of the endothelial cells. By destroying the blood supply to adipose tissue, Adipotide causes ischemia-mediated fat cell death without directly targeting adipocytes. The compound garnered significant attention when a study in obese rhesus macaques demonstrated dramatic weight loss and metabolic improvements.',
                'mechanism_of_action_intro' => 'Adipotide targets the vascular supply of white adipose tissue through a two-step mechanism: selective homing to adipose endothelium followed by induction of endothelial cell apoptosis.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The mechanism exploits the unique vascular address of adipose tissue to achieve targeted ablation of the fat blood supply.',
                        'points' => [
                            'The CKGGRAKDC targeting motif binds prohibitin expressed on the luminal surface of adipose tissue endothelium',
                            'Upon receptor-mediated internalization, the D(KLAKLAK)2 proapoptotic sequence targets mitochondrial membranes',
                            'Disruption of mitochondrial membrane potential triggers apoptosis in endothelial cells',
                            'Loss of vascular supply leads to ischemia and secondary necrosis of adipocytes in the affected fat depot',
                            'The approach is conceptually analogous to anti-angiogenic cancer therapy applied to adipose tissue',
                            'Selectivity is based on the tissue-specific expression of prohibitin on adipose vasculature',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Adipotide has been studied in rodent models and in obese non-human primates, with the primate data generating significant scientific interest.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Animal Model Studies',
                        'findings' => [
                            ['title' => 'Obese Rhesus Macaque Study', 'description' => 'In a landmark study published in Science Translational Medicine (2012), Adipotide treatment of obese rhesus monkeys for 4 weeks resulted in significant reductions in body weight, BMI, abdominal circumference, and white adipose tissue volume as measured by MRI. Treated animals also showed improvements in insulin resistance.'],
                            ['title' => 'Rodent Obesity Models', 'description' => 'Mouse studies demonstrated reduction in white adipose tissue mass and body weight following Adipotide treatment, consistent with the vascular ablation mechanism.'],
                            ['title' => 'Safety Observations', 'description' => 'Renal changes were observed in treated animals, including reversible proximal tubule effects, indicating that kidney monitoring would be important in any further development. The kidney effects were attributed to peptide clearance through renal filtration.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Despite dramatic results in non-human primates, Adipotide has not been tested in human clinical trials. Renal safety signals require careful consideration.',
                'human_use_intro' => 'No human clinical trials have been conducted with Adipotide. All data derives from preclinical animal studies.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Adipotide has not entered human clinical evaluation. While the non-human primate data generated significant scientific and media attention, the compound has not advanced to Phase I safety studies in humans. Renal safety concerns observed in animal studies may represent a significant barrier to clinical translation.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Adipotide is not approved by the FDA, EMA, or any regulatory authority for human use. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Adipotide is an experimental research compound with known renal safety signals in animal studies. It is not approved for human use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Adipotide is relevant to obesity research, vascular biology, and targeted peptide delivery.',
                'potential_applications' => json_encode([
                    ['title' => 'Obesity and Adipose Biology', 'description' => 'Investigation of vascular-targeted approaches to modulating adipose tissue mass and metabolic function.'],
                    ['title' => 'Vascular Targeting Research', 'description' => 'Study of tissue-specific vascular address systems and targeted ablation strategies.'],
                    ['title' => 'Prohibitin Biology', 'description' => 'Understanding the role of prohibitin in adipose vascular endothelium and its utility as a targeting marker.'],
                ]),
                'potential_applications_important_context' => 'All applications are research-based. Renal safety concerns in animals are a significant consideration. No therapeutic claims are made.',
                'conclusion' => 'Adipotide (FTPP) represents a novel approach to adipose tissue modification through vascular targeting. The concept of destroying the blood supply to fat tissue, rather than targeting adipocytes directly, is scientifically innovative and draws on decades of vascular biology research. The primate study demonstrating significant weight loss and metabolic improvement in obese rhesus macaques was a landmark finding. However, Adipotide has not progressed to human clinical trials, and renal safety observations in treated animals highlight the challenges of peptide-based vascular ablation therapies. The compound remains an important research tool for studying adipose vasculature biology, tissue-specific vascular targeting, and the metabolic consequences of adipose tissue reduction.',
                'references' => json_encode([
                    ['title' => 'Science Translational Medicine (2012)', 'authors' => 'Barnhart KF et al.', 'links' => []],
                    ['title' => 'Nature Medicine (2004)', 'authors' => 'Kolonin MG et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Adipotide is a chimeric peptide targeting prohibitin on adipose vasculature endothelium',
                    'Causes endothelial apoptosis, cutting off blood supply to white adipose tissue',
                    'Dramatic weight loss demonstrated in obese rhesus macaque study',
                    'No human trials — renal safety concerns noted — classified as research use only (RUO)',
                ]),
                'overview' => 'Adipotide (FTPP) is a vascular-targeting peptide that selectively ablates blood vessels supplying white adipose tissue, causing ischemia-mediated fat loss.',
                'areas_of_research_intro' => 'Adipotide research spans obesity, vascular biology, and targeted peptide therapeutics.',
                'areas_of_research' => json_encode([
                    ['name' => 'Obesity Research', 'description' => 'Vascular-targeted approaches to adipose tissue modulation'],
                    ['name' => 'Vascular Biology', 'description' => 'Tissue-specific endothelial markers and vascular address systems'],
                    ['name' => 'Targeted Therapeutics', 'description' => 'Homing peptide-proapoptotic conjugate design and selectivity'],
                ]),
                'key_effects' => json_encode(['Adipose vasculature targeting', 'Endothelial cell apoptosis', 'White adipose tissue reduction', 'Metabolic parameter changes (preclinical)']),
                'common_use_cases' => json_encode(['Adipose biology research', 'Vascular targeting studies', 'Prohibitin biology investigations']),
                'how_it_works' => 'Adipotide\'s CKGGRAKDC motif binds prohibitin on adipose tissue endothelium, triggering receptor-mediated internalization. Inside endothelial cells, the D(KLAKLAK)2 domain disrupts mitochondrial membranes, causing apoptosis. Loss of vascular supply leads to ischemia and secondary death of adipocytes in the affected fat depot.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 10. VILON
            //------------------------------------------------------------------
            'vilon' => [
                'title' => 'Vilon',
                'peptide_full_name' => 'Lys-Glu (KE) Dipeptide — Thymic Bioregulator',
                'research_title' => 'Vilon (KE Dipeptide): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Vilon, a synthetic dipeptide bioregulator (Lys-Glu) targeting the thymus and immune system, developed within the Khavinson peptide bioregulation framework, covering its proposed mechanisms, preclinical data, and research applications.',
                'education_tag' => 'Immunomodulation',
                'description' => 'Vilon is a synthetic dipeptide (Lys-Glu, or KE) developed as a thymic bioregulator within the Khavinson peptide bioregulation paradigm. It represents the shortest active peptide bioregulator in the Khavinson classification, designed to normalize immune function through proposed gene-regulatory interactions.',
                'molecular_formula' => 'C₁₁H₂₁N₃O₅',
                'molecular_weight' => '275.30 g/mol',
                'half_life' => 'Minutes (dipeptide; very rapid turnover)',
                'bioavailability' => 'Oral, sublingual, or parenteral administration studied in research settings',
                'background' => 'Vilon is a synthetic dipeptide with the sequence Lys-Glu (KE), developed by Professor Vladimir Khavinson and colleagues at the St. Petersburg Institute of Bioregulation and Gerontology. It is the simplest member of the Khavinson bioregulator peptide family, consisting of just two amino acids. Vilon was designed as a synthetic analog of peptide fractions isolated from thymic tissue, targeting the immune system and thymic function. Within the Khavinson peptide bioregulation framework, short peptides are proposed to interact with specific DNA sequences in complementary fashion, influencing gene expression in a tissue-specific manner. The Lys-Glu dipeptide is proposed to selectively interact with gene regulatory regions in lymphoid and thymic tissue. Khavinson\'s research group has published extensively on the immunomodulatory properties of KE dipeptide, reporting effects on T-lymphocyte differentiation, thymic hormone production, and immune function markers in aging models. Vilon is particularly notable as a test case for the Khavinson theory, since its extreme simplicity (only two amino acids) challenges conventional pharmacological assumptions about the minimum structural requirements for biological activity.',
                'mechanism_of_action_intro' => 'Vilon is proposed to act through direct DNA interaction and epigenetic modulation in thymic and lymphoid tissues, consistent with the Khavinson peptide bioregulation model.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves sequence-specific interaction of the KE dipeptide with DNA in immune system cells.',
                        'points' => [
                            'Proposed to bind specific DNA sequences in the promoter regions of immune-related genes through complementary interaction',
                            'May modulate expression of T-cell differentiation markers and thymic hormones',
                            'Reported to influence interleukin expression profiles in lymphocyte cultures',
                            'Studies suggest effects on chromatin condensation state in lymphoid cell nuclei',
                            'Despite minimal molecular size, Khavinson reports tissue-specific gene regulatory activity',
                            'May support thymic microenvironment maintenance in aging models',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Vilon has been studied in cell culture and animal models, primarily by research groups at the St. Petersburg Institute of Bioregulation and Gerontology.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Immune Function', 'description' => 'In lymphocyte cultures from aged donors, Vilon treatment was associated with increased T-lymphocyte proliferative responses and normalization of CD4/CD8 ratios that had become dysregulated with age.'],
                            ['title' => 'Thymic Effects', 'description' => 'In aged rodent models, chronic Vilon administration was reported to partially restore thymic architecture and increase thymulin (a thymic hormone) levels that decline with age.'],
                            ['title' => 'Gene Expression', 'description' => 'Molecular studies reported that the KE dipeptide influenced expression of genes involved in immune cell differentiation and cytokine production in thymic tissue.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'The majority of Vilon research originates from Russian institutions. The proposed mechanism of a dipeptide exerting gene-regulatory effects is unconventional and requires independent international verification.',
                'human_use_intro' => 'Observational reports from Russian clinical settings exist for Vilon, though no internationally recognized clinical trials have been conducted.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Observations',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Published reports from Russian clinical settings describe the use of Vilon in elderly patients with age-related immune dysfunction. These reports suggest improvements in immune function markers, but the study designs do not meet international standards for randomized controlled trials.'],
                            ['type' => 'content', 'value' => 'No Phase I, II, or III clinical trials meeting ICH-GCP standards have been conducted for Vilon.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Vilon is not approved by the FDA, EMA, or any major Western regulatory authority. In Russia, it has been registered as a dietary supplement/parapharmaceutical, not as a pharmaceutical drug.'],
                            ['type' => 'content', 'value' => 'Vilon is classified as a research compound (RUO) in international markets.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Vilon is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Vilon is relevant to immunology research and the study of short peptide bioregulation.',
                'potential_applications' => json_encode([
                    ['title' => 'Thymic Aging Research', 'description' => 'Investigation of strategies to counteract age-related thymic involution and immune senescence.'],
                    ['title' => 'Peptide Bioregulation Theory', 'description' => 'Vilon as a model compound for testing whether dipeptides can exert tissue-specific gene-regulatory effects.'],
                    ['title' => 'Immunogerontology', 'description' => 'Study of immune function maintenance in aging models using short peptide interventions.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data primarily from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Vilon (KE dipeptide) is the simplest member of the Khavinson short peptide bioregulator family, targeting the thymus and immune system. While the concept of a two-amino-acid peptide exerting tissue-specific gene-regulatory effects is unconventional by Western pharmacological standards, Khavinson\'s research group has published extensive data supporting immunomodulatory activity in aging models. The peptide is a particularly interesting test case for the broader question of whether minimal peptide sequences can carry biological information. Independent international replication of these findings is needed. Vilon remains a research-only compound that contributes to the scientific dialogue around peptide bioregulation and immune system aging.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2002)', 'authors' => 'Khavinson VKh, Morozov VG.', 'links' => []],
                    ['title' => 'Mechanisms of Ageing and Development (2003)', 'authors' => 'Khavinson VKh.', 'links' => []],
                    ['title' => 'Peptides (2003)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Vilon is a synthetic dipeptide (Lys-Glu) targeting the thymus and immune system',
                    'Simplest member of the Khavinson peptide bioregulator family (two amino acids)',
                    'Preclinical data suggest immunomodulatory effects in aging models',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Vilon is a synthetic dipeptide (Lys-Glu) developed as a thymic bioregulator within the Khavinson peptide bioregulation framework.',
                'areas_of_research_intro' => 'Vilon research spans immunology, gerontology, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Immunogerontology', 'description' => 'Thymic involution, immune senescence, and T-cell function in aging'],
                    ['name' => 'Peptide Bioregulation', 'description' => 'Short peptide-DNA interactions and minimal bioactive sequence research'],
                ]),
                'key_effects' => json_encode(['Proposed thymic function support', 'T-lymphocyte modulation', 'Thymulin level normalization (preclinical)', 'Immune gene expression regulation']),
                'common_use_cases' => json_encode(['Immune function research', 'Thymic aging studies', 'Peptide bioregulation investigations']),
                'how_it_works' => 'Vilon (Lys-Glu) is proposed to interact with specific DNA regulatory sequences in thymic and lymphoid tissue, modulating expression of genes involved in T-cell differentiation and thymic hormone production. Despite consisting of only two amino acids, Khavinson\'s research reports tissue-specific immunomodulatory activity consistent with the peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 11. LIVAGEN
            //------------------------------------------------------------------
            'livagen' => [
                'title' => 'Livagen',
                'peptide_full_name' => 'Lys-Glu-Asp-Ala (KEDA) Tetrapeptide — Hepatoprotective Bioregulator',
                'research_title' => 'Livagen (KEDA): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Livagen, a synthetic tetrapeptide bioregulator (Lys-Glu-Asp-Ala) targeting hepatic tissue, developed within the Khavinson peptide bioregulation framework, covering its proposed hepatoprotective mechanisms, preclinical findings, and research applications.',
                'education_tag' => 'Hepatoprotection',
                'description' => 'Livagen is a synthetic tetrapeptide (Lys-Glu-Asp-Ala) developed as a hepatoprotective bioregulator within the Khavinson peptide bioregulation paradigm. It is designed to normalize liver function through proposed epigenetic modulation of gene expression in hepatic tissue.',
                'molecular_formula' => 'C₁₆H₂₈N₄O₈',
                'molecular_weight' => '404.42 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral or oral administration studied in research settings',
                'background' => 'Livagen is a synthetic tetrapeptide with the sequence Lys-Glu-Asp-Ala (KEDA), developed by Vladimir Khavinson and colleagues at the St. Petersburg Institute of Bioregulation and Gerontology. It belongs to the cytogen class of peptide bioregulators — synthetic short peptides designed to mimic the activity of tissue-specific peptide fractions originally isolated from organ extracts. Livagen was developed as a synthetic counterpart to hepatic peptide bioregulators, targeting liver tissue specifically. A particularly notable finding from Khavinson\'s laboratory is that Livagen was shown to induce decondensation (relaxation) of heterochromatin in hepatocyte nuclei, suggesting a direct effect on chromatin structure and gene accessibility. This observation, published in international journals, provides some mechanistic support for the hypothesis that short peptides can interact with DNA/chromatin structures and modulate gene expression. The Khavinson group has proposed that KEDA specifically promotes expression of hepatoprotective genes and supports regenerative processes in liver tissue.',
                'mechanism_of_action_intro' => 'Livagen is proposed to exert hepatoprotective effects through direct interaction with chromatin structures in hepatocytes, promoting gene expression involved in liver regeneration and detoxification.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves chromatin remodeling and epigenetic modulation in hepatic cells.',
                        'points' => [
                            'Demonstrated to induce heterochromatin decondensation in hepatocyte nuclei in in vitro studies',
                            'Chromatin relaxation proposed to increase accessibility of hepatoprotective gene promoters',
                            'May modulate expression of detoxification enzymes including cytochrome P450 family members',
                            'Reported to influence expression of anti-apoptotic and regenerative genes in liver tissue',
                            'Proposed to support hepatocyte proliferation and liver regeneration after injury',
                            'Consistent with Khavinson model of short peptide-DNA complementary interactions',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Livagen has been investigated in cell culture systems and animal models of liver injury and aging.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Chromatin Remodeling', 'description' => 'Livagen treatment of isolated hepatocytes demonstrated induction of heterochromatin decondensation, as observed by electron microscopy and chromatin accessibility assays. This finding, published in the Bulletin of Experimental Biology and Medicine, provides evidence for direct chromatin interaction.'],
                            ['title' => 'Hepatoprotective Effects', 'description' => 'In rodent models of toxic liver injury (carbon tetrachloride-induced), Livagen treatment was associated with reduced serum transaminase levels and improved histological markers of liver injury.'],
                            ['title' => 'Aging Liver Models', 'description' => 'In aged rodent models, Livagen administration was reported to normalize liver function markers and improve hepatocyte morphology compared to age-matched controls.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data for Livagen originates primarily from Russian research groups. Independent replication of chromatin remodeling findings by international laboratories would strengthen the evidence base.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Livagen. Some observational reports exist from Russian clinical settings.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Observations',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Russian clinical literature includes observational reports of Livagen use in patients with chronic liver conditions, with reported improvements in liver function markers. These reports do not constitute controlled clinical evidence by international standards.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Livagen is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO) in international markets.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Livagen is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Livagen is relevant to liver biology research and epigenetic modulation studies.',
                'potential_applications' => json_encode([
                    ['title' => 'Hepatoprotection Research', 'description' => 'Investigation of short peptide-mediated hepatoprotective mechanisms in liver injury models.'],
                    ['title' => 'Chromatin Biology', 'description' => 'Study of peptide-induced chromatin remodeling and its effects on gene accessibility in hepatocytes.'],
                    ['title' => 'Liver Aging Research', 'description' => 'Investigation of age-related changes in liver function and the potential for peptide-mediated normalization.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data primarily from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Livagen (KEDA) is a tetrapeptide bioregulator targeting hepatic tissue within the Khavinson peptide bioregulation framework. Its most scientifically notable feature is the demonstrated ability to induce heterochromatin decondensation in hepatocyte nuclei, providing a mechanistic link between short peptide exposure and epigenetic modulation. Preclinical data from Russian laboratories suggest hepatoprotective activity in models of liver injury and aging. However, the evidence base requires broader international replication, and no controlled clinical trials meeting international standards have been conducted. Livagen contributes to the scientific understanding of peptide-chromatin interactions and represents a tool for hepatoprotection research in preclinical settings.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2005)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2010)', 'authors' => 'Khavinson VKh, Anisimov VN.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Livagen is a synthetic tetrapeptide (Lys-Glu-Asp-Ala) targeting liver tissue',
                    'Demonstrated heterochromatin decondensation in hepatocyte nuclei in vitro',
                    'Preclinical hepatoprotective effects in liver injury and aging models',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Livagen is a synthetic tetrapeptide bioregulator (KEDA) designed to normalize hepatic function through proposed chromatin remodeling and gene expression modulation.',
                'areas_of_research_intro' => 'Livagen research focuses on hepatoprotection, epigenetics, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Hepatology', 'description' => 'Liver injury protection and hepatocyte regeneration'],
                    ['name' => 'Epigenetics', 'description' => 'Chromatin remodeling and heterochromatin decondensation'],
                    ['name' => 'Bioregulation', 'description' => 'Short peptide-DNA interactions in tissue-specific contexts'],
                ]),
                'key_effects' => json_encode(['Heterochromatin decondensation in hepatocytes', 'Hepatoprotective gene expression modulation', 'Liver function marker normalization (preclinical)', 'Hepatocyte regeneration support']),
                'common_use_cases' => json_encode(['Hepatoprotection research', 'Chromatin biology studies', 'Liver aging investigations']),
                'how_it_works' => 'Livagen (Lys-Glu-Asp-Ala) is proposed to interact with DNA/chromatin structures in hepatocytes, inducing heterochromatin decondensation that increases accessibility of hepatoprotective gene promoters. This is theorized to enhance expression of detoxification enzymes, anti-apoptotic factors, and regenerative proteins in liver tissue.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 12. CHONLUTEN
            //------------------------------------------------------------------
            'chonluten' => [
                'title' => 'Chonluten',
                'peptide_full_name' => 'Lys-Glu-Asp (KED) Tripeptide — Bronchopulmonary Bioregulator',
                'research_title' => 'Chonluten (KED): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Chonluten, a synthetic tripeptide bioregulator (Lys-Glu-Asp) targeting bronchopulmonary tissue, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'Respiratory Research',
                'description' => 'Chonluten is a synthetic tripeptide (Lys-Glu-Asp) developed as a bronchopulmonary bioregulator within the Khavinson peptide bioregulation paradigm. It is designed to normalize function in respiratory and pulmonary tissues through proposed gene-regulatory mechanisms.',
                'molecular_formula' => 'C₁₅H₂₆N₄O₈',
                'molecular_weight' => '390.39 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral or oral administration studied in research settings',
                'background' => 'Chonluten is a synthetic tripeptide with the sequence Lys-Glu-Asp (KED), developed by Vladimir Khavinson and colleagues at the St. Petersburg Institute of Bioregulation and Gerontology. It is classified as a bronchopulmonary bioregulator in the Khavinson peptide family, designed to target and normalize function in respiratory and pulmonary tissue. The peptide was developed as a synthetic analog of active peptide fractions originally isolated from bronchial mucosal tissue. According to the Khavinson bioregulation theory, KED interacts with specific gene regulatory regions in bronchial and pulmonary cells, modulating expression of genes involved in mucosal defense, epithelial integrity, and respiratory function. The peptide has been studied primarily in the context of chronic obstructive pulmonary conditions and age-related decline in respiratory function in preclinical models.',
                'mechanism_of_action_intro' => 'Chonluten is proposed to act through the Khavinson peptide bioregulation mechanism: direct interaction with DNA regulatory sequences in bronchopulmonary tissue cells.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves tissue-specific gene regulatory effects in bronchial and pulmonary epithelial cells.',
                        'points' => [
                            'Proposed to interact with DNA regulatory sequences in bronchopulmonary epithelial cells',
                            'May modulate expression of mucin genes and mucociliary defense proteins',
                            'Reported to influence anti-inflammatory gene expression in bronchial tissue',
                            'Proposed to support respiratory epithelial barrier integrity and repair',
                            'May modulate expression of surfactant proteins in alveolar tissue',
                            'Consistent with Khavinson model of tissue-specific short peptide bioregulation',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Chonluten has been investigated in cell culture and animal models by Russian research groups.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Bronchial Epithelial Cell Studies', 'description' => 'In human bronchial epithelial cell cultures, Chonluten treatment was associated with modulation of gene expression patterns related to mucosal defense and epithelial integrity, as reported by the Khavinson research group.'],
                            ['title' => 'Respiratory Function in Aging', 'description' => 'In aged rodent models, chronic Chonluten administration was reported to improve respiratory function parameters and normalize histological markers of age-related bronchial tissue changes.'],
                            ['title' => 'Chronic Inflammatory Models', 'description' => 'Rodent models of chronic bronchial inflammation showed reduced inflammatory markers and improved mucosal integrity following Chonluten treatment in some studies.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data for Chonluten originates primarily from Russian laboratories. Independent international replication is limited.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Chonluten.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Observational reports from Russian clinical settings describe Chonluten use in patients with chronic respiratory conditions, but these do not constitute controlled clinical evidence by international standards. No Phase I-III clinical trials meeting ICH-GCP requirements have been conducted.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Chonluten is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO) in international markets.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Chonluten is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Chonluten is relevant to respiratory biology and peptide bioregulation research.',
                'potential_applications' => json_encode([
                    ['title' => 'Respiratory Research', 'description' => 'Investigation of short peptide effects on bronchial mucosal defense and epithelial function.'],
                    ['title' => 'Pulmonary Aging', 'description' => 'Study of age-related respiratory decline and potential for peptide-mediated normalization.'],
                    ['title' => 'Bioregulation Theory', 'description' => 'Testing the Khavinson hypothesis in the context of bronchopulmonary tissue specificity.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Chonluten (KED) is a tripeptide bioregulator designed to target bronchopulmonary tissue within the Khavinson peptide bioregulation framework. Preclinical data from Russian laboratories suggest modulatory effects on bronchial epithelial function and respiratory parameters in aging models. The evidence base requires independent international verification, and no controlled clinical trials have been conducted. Chonluten contributes to the broader research effort in understanding whether short peptide sequences can exert tissue-specific effects on respiratory tissue biology.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2006)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2011)', 'authors' => 'Trofimov AV, Khavinson VKh.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Chonluten is a synthetic tripeptide (Lys-Glu-Asp) targeting bronchopulmonary tissue',
                    'Developed by Khavinson as a respiratory tissue bioregulator',
                    'Preclinical data suggest effects on bronchial mucosal defense and respiratory function in aging',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Chonluten is a synthetic tripeptide bioregulator (Lys-Glu-Asp) designed to normalize bronchopulmonary tissue function.',
                'areas_of_research_intro' => 'Chonluten research focuses on respiratory biology, pulmonary aging, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Respiratory Biology', 'description' => 'Bronchial mucosal defense and epithelial function'],
                    ['name' => 'Pulmonary Aging', 'description' => 'Age-related respiratory decline and tissue maintenance'],
                ]),
                'key_effects' => json_encode(['Bronchial epithelial modulation', 'Mucosal defense gene expression', 'Respiratory function normalization (preclinical)', 'Anti-inflammatory activity in bronchial tissue']),
                'common_use_cases' => json_encode(['Respiratory research', 'Pulmonary aging studies', 'Bronchopulmonary bioregulation']),
                'how_it_works' => 'Chonluten (Lys-Glu-Asp) is proposed to interact with DNA regulatory sequences in bronchopulmonary tissue, modulating expression of genes involved in mucosal defense, epithelial integrity, and surfactant production, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 13. CARDIOGEN
            //------------------------------------------------------------------
            'cardiogen' => [
                'title' => 'Cardiogen',
                'peptide_full_name' => 'Ala-Glu-Asp-Arg (AEDR) Tetrapeptide — Cardiovascular Bioregulator',
                'research_title' => 'Cardiogen (AEDR): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Cardiogen, a synthetic tetrapeptide bioregulator (Ala-Glu-Asp-Arg) targeting cardiovascular tissue, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'Cardiovascular Research',
                'description' => 'Cardiogen is a synthetic tetrapeptide (Ala-Glu-Asp-Arg) developed as a cardiovascular bioregulator within the Khavinson peptide bioregulation paradigm. It is designed to normalize cardiac tissue function through proposed gene-regulatory mechanisms targeting cardiomyocytes and cardiac vasculature.',
                'molecular_formula' => 'C₁₆H₂₈N₆O₈',
                'molecular_weight' => '432.43 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral administration studied in research settings',
                'background' => 'Cardiogen is a synthetic tetrapeptide with the sequence Ala-Glu-Asp-Arg (AEDR), developed by Vladimir Khavinson and colleagues at the St. Petersburg Institute of Bioregulation and Gerontology. It belongs to the cytogen class of short peptide bioregulators and was designed as a synthetic analog of peptide fractions isolated from cardiac tissue. Within the Khavinson bioregulation framework, Cardiogen is proposed to selectively interact with gene regulatory regions in cardiac cells, modulating expression of proteins involved in cardiomyocyte survival, contractile function, and cardiovascular homeostasis. The peptide has been studied primarily in models of cardiac aging and ischemic heart conditions in preclinical settings. Khavinson\'s research group reports that AEDR can influence expression of cardiac-specific genes including those encoding contractile proteins and cardioprotective factors.',
                'mechanism_of_action_intro' => 'Cardiogen is proposed to exert cardioprotective effects through tissue-specific epigenetic and gene-regulatory mechanisms in cardiac cells.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves selective interaction with DNA regulatory elements in cardiomyocytes.',
                        'points' => [
                            'Proposed to interact with specific DNA sequences in the promoter regions of cardiac-specific genes',
                            'May modulate expression of contractile proteins (myosin heavy chains, troponins)',
                            'Reported to influence anti-apoptotic gene expression in cardiomyocyte cultures',
                            'Proposed to support cardiomyocyte survival under ischemic stress conditions',
                            'May influence expression of endothelial factors in coronary vasculature',
                            'Consistent with Khavinson model of tissue-specific short peptide bioregulation',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Cardiogen has been investigated in cell culture and animal models of cardiac aging and injury.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Cardiomyocyte Cultures', 'description' => 'In primary cardiomyocyte cultures, Cardiogen treatment was associated with modulation of cardiac-specific gene expression and enhanced cell survival under hypoxic conditions, as reported by the Khavinson research group.'],
                            ['title' => 'Cardiac Aging Models', 'description' => 'In aged rodent models, chronic Cardiogen administration was reported to improve cardiac functional parameters and normalize age-related changes in myocardial histology.'],
                            ['title' => 'Cardioprotection Studies', 'description' => 'In models of experimental myocardial ischemia, Cardiogen pretreatment was associated with reduced infarct size markers and preserved cardiac function indices in some studies.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data for Cardiogen originates primarily from Russian research institutions. Independent international verification is needed.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Cardiogen.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Published reports from Russian clinical settings describe observational use of Cardiogen in elderly patients with cardiovascular conditions, but these do not meet international standards for controlled clinical trials. No ICH-GCP compliant studies have been conducted.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Cardiogen is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO) in international markets.'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Cardiogen is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Cardiogen is relevant to cardiovascular research and cardiac aging studies.',
                'potential_applications' => json_encode([
                    ['title' => 'Cardioprotection Research', 'description' => 'Investigation of short peptide-mediated cardioprotective mechanisms in ischemia and aging models.'],
                    ['title' => 'Cardiac Aging', 'description' => 'Study of age-related changes in cardiac gene expression and potential for peptide-mediated normalization.'],
                    ['title' => 'Bioregulation Theory', 'description' => 'Testing the Khavinson hypothesis in cardiovascular tissue contexts.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Cardiogen (AEDR) is a tetrapeptide bioregulator targeting cardiovascular tissue within the Khavinson framework. Preclinical data suggest cardioprotective activity and modulation of cardiac gene expression in aging and ischemia models. The evidence base requires independent international replication, and no controlled clinical trials have been conducted. Cardiogen contributes to research on peptide-mediated cardioprotection and cardiac tissue bioregulation.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2008)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2012)', 'authors' => 'Khavinson VKh, Linkova NS.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Cardiogen is a synthetic tetrapeptide (Ala-Glu-Asp-Arg) targeting cardiovascular tissue',
                    'Developed by Khavinson as a cardiac bioregulator within the peptide bioregulation framework',
                    'Preclinical data suggest cardioprotective and gene-modulatory effects',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Cardiogen is a synthetic tetrapeptide bioregulator (Ala-Glu-Asp-Arg) designed to normalize cardiovascular tissue function.',
                'areas_of_research_intro' => 'Cardiogen research focuses on cardiology, cardiac aging, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Cardiology', 'description' => 'Cardiomyocyte protection and cardiac function optimization'],
                    ['name' => 'Cardiac Aging', 'description' => 'Age-related myocardial changes and functional decline'],
                ]),
                'key_effects' => json_encode(['Cardiac gene expression modulation', 'Cardiomyocyte survival under stress', 'Cardiac function normalization (preclinical)', 'Anti-apoptotic signaling in cardiac tissue']),
                'common_use_cases' => json_encode(['Cardioprotection research', 'Cardiac aging studies', 'Cardiovascular bioregulation']),
                'how_it_works' => 'Cardiogen (Ala-Glu-Asp-Arg) is proposed to interact with DNA regulatory sequences in cardiomyocytes, modulating expression of genes encoding contractile proteins, anti-apoptotic factors, and cardioprotective molecules, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 14. BRONCHOGEN
            //------------------------------------------------------------------
            'bronchogen' => [
                'title' => 'Bronchogen',
                'peptide_full_name' => 'Ala-Glu-Asp (AED) Tripeptide — Respiratory Bioregulator',
                'research_title' => 'Bronchogen (AED): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Bronchogen, a synthetic tripeptide bioregulator (Ala-Glu-Asp) targeting respiratory tissue, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'Respiratory Research',
                'description' => 'Bronchogen is a synthetic tripeptide (Ala-Glu-Asp) developed as a respiratory bioregulator within the Khavinson peptide bioregulation paradigm. It targets bronchial and pulmonary tissue to normalize respiratory function through proposed gene-regulatory mechanisms.',
                'molecular_formula' => 'C₁₁H₁₈N₂O₇',
                'molecular_weight' => '305.26 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral or oral administration studied in research settings',
                'background' => 'Bronchogen is a synthetic tripeptide with the sequence Ala-Glu-Asp (AED), developed by Vladimir Khavinson at the St. Petersburg Institute of Bioregulation and Gerontology. It is a cytogen-class bioregulator designed to target bronchial and pulmonary tissue. While Chonluten (KED) is another Khavinson respiratory bioregulator, Bronchogen has a distinct amino acid sequence (AED versus KED) and is proposed to have a complementary but different spectrum of gene-regulatory activity in respiratory tissue. The Khavinson group has published research demonstrating that AED can influence gene expression patterns in bronchial epithelial cells, particularly genes involved in anti-inflammatory responses and mucosal repair. Bronchogen has been studied in the context of chronic respiratory conditions and age-related pulmonary decline in preclinical models.',
                'mechanism_of_action_intro' => 'Bronchogen is proposed to exert respiratory-protective effects through tissue-specific gene regulation in bronchial and pulmonary cells.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves selective interaction with gene regulatory elements in respiratory tissue.',
                        'points' => [
                            'Proposed to interact with DNA regulatory sequences specific to bronchial and pulmonary epithelial cells',
                            'May modulate expression of anti-inflammatory mediators in respiratory tissue',
                            'Reported to influence expression of genes involved in mucosal repair and epithelial regeneration',
                            'Proposed to support ciliary function and mucociliary clearance mechanisms',
                            'May modulate inflammatory cytokine expression in bronchial tissue',
                            'Distinct gene-regulatory profile from Chonluten (KED) despite shared organ targeting',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Bronchogen has been investigated in cell culture systems and animal models of respiratory conditions.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Bronchial Epithelial Studies', 'description' => 'In bronchial epithelial cell cultures, Bronchogen treatment modulated expression of genes involved in inflammatory responses and epithelial integrity, as reported by the Khavinson group.'],
                            ['title' => 'Respiratory Aging Models', 'description' => 'Aged rodent models showed improved respiratory function parameters and reduced age-related bronchial tissue changes following chronic Bronchogen administration.'],
                            ['title' => 'Chronic Inflammation', 'description' => 'In models of chronic bronchial inflammation, Bronchogen treatment was associated with reduced inflammatory infiltrates and improved mucosal morphology.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data originates primarily from Russian research groups. Independent international verification is limited.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Bronchogen.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'No Phase I-III clinical trials meeting ICH-GCP standards have been conducted for Bronchogen. Observational reports from Russian clinical practice exist but do not constitute controlled clinical evidence.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Bronchogen is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Bronchogen is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Bronchogen is relevant to respiratory biology and bioregulation research.',
                'potential_applications' => json_encode([
                    ['title' => 'Respiratory Biology', 'description' => 'Study of short peptide effects on bronchial epithelial function and mucosal defense.'],
                    ['title' => 'Pulmonary Aging', 'description' => 'Investigation of age-related respiratory decline and peptide-mediated normalization.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Bronchogen (AED) is a tripeptide bioregulator targeting respiratory tissue within the Khavinson framework. It shares organ targeting with Chonluten (KED) but has a distinct amino acid sequence and proposed gene-regulatory profile. Preclinical data suggest modulatory effects on bronchial epithelial function and respiratory parameters in aging models. Independent verification is needed, and no controlled clinical trials have been conducted. Bronchogen is a research compound contributing to the study of peptide-based respiratory tissue bioregulation.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2009)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Peptides (2003)', 'authors' => 'Khavinson VKh.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Bronchogen is a synthetic tripeptide (Ala-Glu-Asp) targeting respiratory tissue',
                    'Distinct sequence from Chonluten (KED) with complementary respiratory bioregulation',
                    'Preclinical data suggest bronchial anti-inflammatory and mucosal protective effects',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Bronchogen is a synthetic tripeptide bioregulator (Ala-Glu-Asp) designed to normalize bronchial and respiratory tissue function.',
                'areas_of_research_intro' => 'Bronchogen research spans respiratory biology, pulmonary aging, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Respiratory Biology', 'description' => 'Bronchial epithelial function and mucosal defense mechanisms'],
                    ['name' => 'Pulmonary Aging', 'description' => 'Age-related respiratory changes and tissue maintenance'],
                ]),
                'key_effects' => json_encode(['Bronchial anti-inflammatory modulation', 'Mucosal repair gene expression', 'Respiratory function normalization (preclinical)', 'Epithelial integrity support']),
                'common_use_cases' => json_encode(['Respiratory research', 'Bronchial biology studies', 'Pulmonary aging investigations']),
                'how_it_works' => 'Bronchogen (Ala-Glu-Asp) is proposed to interact with DNA regulatory sequences in bronchial and pulmonary epithelial cells, modulating expression of anti-inflammatory genes, mucosal repair factors, and ciliary function proteins, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 15. OVAGEN
            //------------------------------------------------------------------
            'ovagen' => [
                'title' => 'Ovagen',
                'peptide_full_name' => 'Glu-Asp-Leu (EDL) Tripeptide — Hepatic/GI Bioregulator',
                'research_title' => 'Ovagen (EDL): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Ovagen, a synthetic tripeptide bioregulator (Glu-Asp-Leu) targeting hepatic and gastrointestinal tissue, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'GI/Hepatic Research',
                'description' => 'Ovagen is a synthetic tripeptide (Glu-Asp-Leu) developed as a hepatic and gastrointestinal bioregulator within the Khavinson peptide bioregulation paradigm. It targets liver and GI tract tissues to normalize function through proposed gene-regulatory mechanisms.',
                'molecular_formula' => 'C₁₅H₂₅N₃O₈',
                'molecular_weight' => '391.37 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Oral or parenteral administration studied in research settings',
                'background' => 'Ovagen is a synthetic tripeptide with the sequence Glu-Asp-Leu (EDL), developed by Vladimir Khavinson and colleagues at the St. Petersburg Institute of Bioregulation and Gerontology. It is classified as a hepatic and gastrointestinal bioregulator within the Khavinson peptide family. While Livagen (KEDA) is another Khavinson hepatoprotective peptide, Ovagen targets a broader spectrum of hepatic and GI tissue functions. According to Khavinson\'s research, the EDL tripeptide selectively interacts with gene regulatory elements in hepatocytes and GI epithelial cells, modulating expression of genes involved in detoxification, bile production, digestive enzyme secretion, and mucosal barrier function. Ovagen has been studied in preclinical models of liver disease and gastrointestinal dysfunction, primarily by Russian research groups.',
                'mechanism_of_action_intro' => 'Ovagen is proposed to normalize hepatic and GI function through tissue-specific gene regulatory interactions.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves gene expression modulation in hepatocytes and gastrointestinal epithelial cells.',
                        'points' => [
                            'Proposed to interact with DNA regulatory regions in hepatocytes and GI mucosal cells',
                            'May modulate expression of hepatic detoxification enzymes',
                            'Reported to influence bile synthesis and secretion gene expression',
                            'Proposed to support GI mucosal barrier integrity and repair',
                            'May normalize digestive enzyme expression in pancreatic and intestinal tissue',
                            'Consistent with Khavinson model of short peptide-DNA complementary interactions',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Ovagen has been investigated in cell culture and animal models targeting hepatic and GI tissue.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Hepatocyte Studies', 'description' => 'In hepatocyte cultures, Ovagen treatment influenced expression of detoxification and metabolic genes, including cytochrome P450 family members, as reported by Russian research groups.'],
                            ['title' => 'GI Mucosal Models', 'description' => 'In rodent models, Ovagen administration was associated with improved GI mucosal integrity markers and normalized digestive function parameters in aging animals.'],
                            ['title' => 'Liver Function in Aging', 'description' => 'Aged rodent models showed improvements in liver function markers following chronic Ovagen treatment, consistent with hepatoprotective activity.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data originates primarily from Russian research institutions. Independent international replication is needed.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Ovagen.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'No controlled clinical trials meeting international standards have been conducted for Ovagen. Observational reports from Russian clinical settings exist but lack the rigor of randomized controlled trials.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Ovagen is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Ovagen is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Ovagen is relevant to hepatology and gastroenterology research.',
                'potential_applications' => json_encode([
                    ['title' => 'Hepatoprotection Research', 'description' => 'Study of peptide-mediated liver function normalization and detoxification support.'],
                    ['title' => 'GI Biology', 'description' => 'Investigation of short peptide effects on gastrointestinal mucosal integrity and digestive function.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Ovagen (EDL) is a tripeptide bioregulator targeting hepatic and gastrointestinal tissue within the Khavinson framework. Preclinical data suggest modulatory effects on liver detoxification, GI mucosal function, and digestive parameters in aging models. Independent international verification is needed, and no controlled clinical trials have been conducted. Ovagen serves as a research tool for studying peptide-mediated hepatic and GI bioregulation.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2010)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2014)', 'authors' => 'Khavinson VKh, Linkova NS.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Ovagen is a synthetic tripeptide (Glu-Asp-Leu) targeting hepatic and GI tissue',
                    'Developed by Khavinson as a liver/GI bioregulator',
                    'Preclinical data suggest hepatoprotective and GI mucosal support effects',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Ovagen is a synthetic tripeptide bioregulator (Glu-Asp-Leu) designed to normalize hepatic and gastrointestinal tissue function.',
                'areas_of_research_intro' => 'Ovagen research spans hepatology, gastroenterology, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Hepatology', 'description' => 'Liver function, detoxification, and hepatoprotection'],
                    ['name' => 'Gastroenterology', 'description' => 'GI mucosal integrity and digestive function'],
                ]),
                'key_effects' => json_encode(['Hepatic detoxification gene modulation', 'GI mucosal barrier support', 'Digestive function normalization (preclinical)', 'Liver function marker improvement']),
                'common_use_cases' => json_encode(['Hepatoprotection research', 'GI biology studies', 'Hepatic aging investigations']),
                'how_it_works' => 'Ovagen (Glu-Asp-Leu) is proposed to interact with DNA regulatory sequences in hepatocytes and GI epithelial cells, modulating expression of detoxification enzymes, bile synthesis genes, and mucosal defense proteins, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 16. CORTAGEN
            //------------------------------------------------------------------
            'cortagen' => [
                'title' => 'Cortagen',
                'peptide_full_name' => 'Ala-Glu-Asp-Pro (AEDP) Tetrapeptide — Cerebral Cortex Bioregulator',
                'research_title' => 'Cortagen (AEDP): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Cortagen, a synthetic tetrapeptide bioregulator (Ala-Glu-Asp-Pro) targeting the cerebral cortex, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'Neuropeptides',
                'description' => 'Cortagen is a synthetic tetrapeptide (Ala-Glu-Asp-Pro) developed as a cerebral cortex bioregulator within the Khavinson peptide bioregulation paradigm. It targets cortical neural tissue to normalize brain function through proposed gene-regulatory mechanisms.',
                'molecular_formula' => 'C₁₆H₂₅N₃O₈',
                'molecular_weight' => '403.39 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral administration studied in research settings',
                'background' => 'Cortagen is a synthetic tetrapeptide with the sequence Ala-Glu-Asp-Pro (AEDP), developed by Vladimir Khavinson at the St. Petersburg Institute of Bioregulation and Gerontology. It is classified as a cerebral cortex bioregulator, distinct from Pinealon (which targets the pineal gland and CNS broadly). Cortagen was designed as a synthetic counterpart to peptide fractions isolated from cerebral cortex tissue, with the goal of normalizing cortical function, particularly in the context of aging and neurodegenerative conditions. According to Khavinson\'s research, the AEDP sequence selectively interacts with gene regulatory regions in cortical neurons, influencing expression of proteins involved in neuronal survival, synaptic function, and neurotransmitter metabolism. The peptide has been studied in preclinical models of cerebral ischemia, age-related cognitive decline, and cortical neurodegeneration.',
                'mechanism_of_action_intro' => 'Cortagen is proposed to exert neuroprotective effects through tissue-specific gene regulation in cerebral cortex neurons.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves selective gene-regulatory interactions in cortical neural tissue.',
                        'points' => [
                            'Proposed to interact with DNA regulatory sequences specific to cortical neurons',
                            'May modulate expression of neurotrophic factors and synaptic proteins',
                            'Reported to influence anti-apoptotic gene expression in cortical neuron cultures',
                            'Proposed to support neurotransmitter synthesis and metabolism in cortical tissue',
                            'May influence expression of antioxidant defense genes in neural tissue',
                            'Distinct from Pinealon in targeting cortical rather than pineal tissue gene expression',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Cortagen has been investigated in cell culture and animal models of cerebral injury and aging.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Cortical Neuron Cultures', 'description' => 'In cultured cortical neurons, Cortagen treatment was associated with enhanced cell survival under oxidative stress and modulation of neurotrophic gene expression, as reported by the Khavinson research group.'],
                            ['title' => 'Cerebral Ischemia Models', 'description' => 'In rodent models of focal cerebral ischemia, Cortagen administration was associated with reduced neurological deficit scores and smaller infarct volumes in some studies.'],
                            ['title' => 'Cognitive Aging Models', 'description' => 'Aged rodent models showed improved performance in cognitive behavioral tests following chronic Cortagen treatment.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data originates primarily from Russian research institutions. Independent international replication is needed.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Cortagen.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'No controlled clinical trials meeting international standards have been conducted for Cortagen. It remains a preclinical research compound.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Cortagen is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Cortagen is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Cortagen is relevant to neuroscience research and cerebral cortex biology.',
                'potential_applications' => json_encode([
                    ['title' => 'Neuroprotection Research', 'description' => 'Investigation of peptide-mediated neuroprotective mechanisms in cortical ischemia and aging models.'],
                    ['title' => 'Cognitive Aging', 'description' => 'Study of cortical function preservation and cognitive decline in aging models.'],
                    ['title' => 'Cortical Bioregulation', 'description' => 'Testing the Khavinson hypothesis in cerebral cortex tissue contexts.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Cortagen (AEDP) is a tetrapeptide bioregulator targeting the cerebral cortex within the Khavinson framework. Preclinical data suggest neuroprotective activity and modulation of cortical gene expression in ischemia and aging models. The evidence requires independent international verification, and no clinical trials have been conducted. Cortagen represents a tool for studying peptide-mediated cortical neuroprotection in research settings.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2007)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2013)', 'authors' => 'Khavinson VKh, Linkova NS.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Cortagen is a synthetic tetrapeptide (Ala-Glu-Asp-Pro) targeting the cerebral cortex',
                    'Developed by Khavinson as a cortical neuroprotective bioregulator',
                    'Preclinical data suggest neuroprotective and cognitive-preserving effects',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Cortagen is a synthetic tetrapeptide bioregulator (Ala-Glu-Asp-Pro) designed to normalize cerebral cortex function.',
                'areas_of_research_intro' => 'Cortagen research focuses on neuroscience, neuroprotection, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Neuroprotection', 'description' => 'Cortical neuron survival and ischemic injury protection'],
                    ['name' => 'Cognitive Aging', 'description' => 'Age-related cortical function decline and cognitive preservation'],
                ]),
                'key_effects' => json_encode(['Cortical neuron protection', 'Neurotrophic gene modulation', 'Cognitive function support (preclinical)', 'Antioxidant defense enhancement']),
                'common_use_cases' => json_encode(['Neuroprotection research', 'Cognitive aging studies', 'Cortical bioregulation investigations']),
                'how_it_works' => 'Cortagen (Ala-Glu-Asp-Pro) is proposed to interact with DNA regulatory sequences in cerebral cortex neurons, modulating expression of neurotrophic factors, anti-apoptotic proteins, and synaptic function genes, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 17. TESTAGEN
            //------------------------------------------------------------------
            'testagen' => [
                'title' => 'Testagen',
                'peptide_full_name' => 'Lys-Glu-Asp-Gly (KEDG) Tetrapeptide — Testicular Bioregulator',
                'research_title' => 'Testagen (KEDG): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Testagen, a synthetic tetrapeptide bioregulator (Lys-Glu-Asp-Gly) targeting testicular tissue, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'Reproductive Research',
                'description' => 'Testagen is a synthetic tetrapeptide (Lys-Glu-Asp-Gly) developed as a testicular bioregulator within the Khavinson peptide bioregulation paradigm. It targets Leydig and Sertoli cells of the testes to normalize reproductive function through proposed gene-regulatory mechanisms.',
                'molecular_formula' => 'C₁₄H₂₃N₅O₈',
                'molecular_weight' => '405.36 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral administration studied in research settings',
                'background' => 'Testagen is a synthetic tetrapeptide with the sequence Lys-Glu-Asp-Gly (KEDG), developed by Vladimir Khavinson at the St. Petersburg Institute of Bioregulation and Gerontology. It is classified as a testicular bioregulator, designed to normalize function of the male reproductive system, particularly the endocrine and spermatogenic functions of the testes. Testagen was developed as a synthetic analog of peptide fractions isolated from testicular tissue. Within the Khavinson framework, the KEDG tetrapeptide is proposed to interact with gene regulatory regions in Leydig cells (which produce testosterone) and Sertoli cells (which support spermatogenesis), modulating expression of genes involved in steroidogenesis, germ cell development, and testicular maintenance. The peptide has been studied primarily in the context of age-related testicular decline and hypogonadism models in preclinical settings.',
                'mechanism_of_action_intro' => 'Testagen is proposed to normalize testicular function through tissue-specific gene regulation in Leydig and Sertoli cells.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves gene-regulatory interactions specific to testicular tissue cells.',
                        'points' => [
                            'Proposed to interact with DNA regulatory sequences in Leydig and Sertoli cells',
                            'May modulate expression of steroidogenic enzymes involved in testosterone biosynthesis',
                            'Reported to influence genes supporting spermatogenesis and germ cell development',
                            'Proposed to support testicular microenvironment maintenance in aging models',
                            'May influence expression of LH receptor and related reproductive signaling genes',
                            'Consistent with Khavinson model of tissue-specific short peptide bioregulation',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Testagen has been investigated in cell culture and animal models of testicular aging and dysfunction.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Leydig Cell Studies', 'description' => 'In Leydig cell cultures, Testagen treatment was associated with modulation of steroidogenic gene expression and markers of testosterone biosynthesis, as reported by the Khavinson research group.'],
                            ['title' => 'Testicular Aging Models', 'description' => 'Aged rodent models showed partial normalization of testicular histology and hormone levels following chronic Testagen administration.'],
                            ['title' => 'Spermatogenesis', 'description' => 'Some studies reported improvements in spermatogenic activity markers in aged animal models treated with Testagen.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data originates primarily from Russian research institutions. Independent international verification is needed.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Testagen.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'No controlled clinical trials meeting international standards have been conducted for Testagen. Observational reports from Russian settings exist but do not constitute controlled evidence.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Testagen is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Testagen is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Testagen is relevant to reproductive biology and andrology research.',
                'potential_applications' => json_encode([
                    ['title' => 'Testicular Aging Research', 'description' => 'Investigation of age-related testicular decline and potential peptide-mediated normalization.'],
                    ['title' => 'Reproductive Endocrinology', 'description' => 'Study of Leydig cell steroidogenesis and testicular hormone production regulation.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Testagen (KEDG) is a tetrapeptide bioregulator targeting testicular tissue within the Khavinson framework. Preclinical data from Russian laboratories suggest effects on testicular gene expression, steroidogenesis, and spermatogenesis in aging models. Independent international verification is needed, and no controlled clinical trials have been conducted. Testagen serves as a research tool for studying peptide-mediated testicular bioregulation and reproductive aging.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2009)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2011)', 'authors' => 'Khavinson VKh.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Testagen is a synthetic tetrapeptide (Lys-Glu-Asp-Gly) targeting testicular tissue',
                    'Developed by Khavinson as a testicular bioregulator for reproductive function',
                    'Preclinical data suggest steroidogenic and spermatogenic modulatory effects',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Testagen is a synthetic tetrapeptide bioregulator (Lys-Glu-Asp-Gly) designed to normalize testicular function and male reproductive biology.',
                'areas_of_research_intro' => 'Testagen research focuses on reproductive biology, andrology, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Reproductive Biology', 'description' => 'Testicular function, steroidogenesis, and spermatogenesis'],
                    ['name' => 'Andrology', 'description' => 'Male reproductive aging and hypogonadism models'],
                ]),
                'key_effects' => json_encode(['Steroidogenic gene expression modulation', 'Spermatogenesis support', 'Testicular function normalization (preclinical)', 'Leydig cell function modulation']),
                'common_use_cases' => json_encode(['Testicular aging research', 'Reproductive endocrinology studies', 'Andrology investigations']),
                'how_it_works' => 'Testagen (Lys-Glu-Asp-Gly) is proposed to interact with DNA regulatory sequences in Leydig and Sertoli cells, modulating expression of steroidogenic enzymes, spermatogenesis support factors, and reproductive signaling genes, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 18. PANCRAGEN
            //------------------------------------------------------------------
            'pancragen' => [
                'title' => 'Pancragen',
                'peptide_full_name' => 'Lys-Glu-Asp-Trp (KEDW) Tetrapeptide — Pancreatic Bioregulator',
                'research_title' => 'Pancragen (KEDW): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Pancragen, a synthetic tetrapeptide bioregulator (Lys-Glu-Asp-Trp) targeting pancreatic tissue, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'Metabolic Research',
                'description' => 'Pancragen is a synthetic tetrapeptide (Lys-Glu-Asp-Trp) developed as a pancreatic bioregulator within the Khavinson peptide bioregulation paradigm. It targets pancreatic tissue, particularly the islets of Langerhans, to normalize endocrine and exocrine pancreatic function.',
                'molecular_formula' => 'C₂₃H₃₁N₅O₈',
                'molecular_weight' => '509.53 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral or oral administration studied in research settings',
                'background' => 'Pancragen is a synthetic tetrapeptide with the sequence Lys-Glu-Asp-Trp (KEDW), developed by Vladimir Khavinson at the St. Petersburg Institute of Bioregulation and Gerontology. It is designed as a pancreatic tissue bioregulator, targeting both the endocrine (insulin-producing beta cells) and exocrine (digestive enzyme-producing) compartments of the pancreas. Within Khavinson\'s peptide bioregulation framework, Pancragen is proposed to interact with gene regulatory regions in pancreatic cells, modulating expression of genes involved in insulin synthesis and secretion, beta cell survival, and pancreatic enzyme production. The peptide has been studied in preclinical models of diabetes and age-related pancreatic decline. The inclusion of tryptophan (Trp) in the sequence is notable, as this amino acid may contribute to interactions with specific DNA sequences through its indole ring system, potentially enhancing the peptide\'s gene-regulatory specificity.',
                'mechanism_of_action_intro' => 'Pancragen is proposed to normalize pancreatic function through tissue-specific gene regulation in islet and acinar cells.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves gene-regulatory interactions in pancreatic endocrine and exocrine cells.',
                        'points' => [
                            'Proposed to interact with DNA regulatory sequences in pancreatic beta cells and acinar cells',
                            'May modulate expression of insulin gene transcription factors (e.g., PDX-1, MafA)',
                            'Reported to influence beta cell survival and proliferation gene expression',
                            'Proposed to support glucose-stimulated insulin secretion mechanisms',
                            'May normalize pancreatic enzyme production in exocrine tissue',
                            'The tryptophan residue may contribute to DNA binding specificity through indole-nucleotide interactions',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Pancragen has been investigated in cell culture and animal models of diabetes and pancreatic aging.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Beta Cell Studies', 'description' => 'In pancreatic beta cell cultures, Pancragen treatment was associated with enhanced insulin gene expression and improved glucose-stimulated insulin secretion markers, as reported by the Khavinson research group.'],
                            ['title' => 'Diabetic Animal Models', 'description' => 'In rodent models of streptozotocin-induced diabetes, Pancragen administration was associated with partial preservation of beta cell mass and improved glycemic parameters in some studies.'],
                            ['title' => 'Pancreatic Aging', 'description' => 'Aged rodent models showed normalization of pancreatic histological markers and functional parameters following chronic Pancragen treatment.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data originates primarily from Russian research institutions. Effects in animal diabetes models require independent international verification.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Pancragen.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'No controlled clinical trials meeting international standards have been conducted for Pancragen. Observational reports exist from Russian clinical settings but do not constitute controlled clinical evidence.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Pancragen is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Pancragen is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Pancragen is relevant to diabetes research and pancreatic biology.',
                'potential_applications' => json_encode([
                    ['title' => 'Diabetes Research', 'description' => 'Investigation of peptide-mediated beta cell protection and insulin expression modulation in diabetes models.'],
                    ['title' => 'Pancreatic Biology', 'description' => 'Study of gene-regulatory mechanisms in pancreatic endocrine and exocrine tissue.'],
                    ['title' => 'Pancreatic Aging', 'description' => 'Investigation of age-related pancreatic decline and peptide-mediated normalization.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Pancragen (KEDW) is a tetrapeptide bioregulator targeting pancreatic tissue within the Khavinson framework. Preclinical data suggest modulatory effects on beta cell function, insulin gene expression, and pancreatic parameters in diabetes and aging models. The inclusion of tryptophan adds potential DNA-binding specificity. Independent international verification is needed, and no controlled clinical trials have been conducted. Pancragen serves as a research tool for studying peptide-mediated pancreatic bioregulation and beta cell biology.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2013)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2015)', 'authors' => 'Khavinson VKh, Linkova NS et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Pancragen is a synthetic tetrapeptide (Lys-Glu-Asp-Trp) targeting pancreatic tissue',
                    'Developed by Khavinson as a pancreatic bioregulator for endocrine and exocrine function',
                    'Preclinical data suggest beta cell-protective and insulin-modulatory effects',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Pancragen is a synthetic tetrapeptide bioregulator (Lys-Glu-Asp-Trp) designed to normalize pancreatic endocrine and exocrine function.',
                'areas_of_research_intro' => 'Pancragen research focuses on diabetology, pancreatic biology, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Diabetology', 'description' => 'Beta cell protection and insulin expression regulation'],
                    ['name' => 'Pancreatic Biology', 'description' => 'Islet function, acinar cell biology, and pancreatic aging'],
                ]),
                'key_effects' => json_encode(['Beta cell gene expression modulation', 'Insulin secretion support (preclinical)', 'Pancreatic function normalization', 'Exocrine enzyme production regulation']),
                'common_use_cases' => json_encode(['Diabetes model research', 'Pancreatic biology studies', 'Beta cell investigations']),
                'how_it_works' => 'Pancragen (Lys-Glu-Asp-Trp) is proposed to interact with DNA regulatory sequences in pancreatic beta cells and acinar cells, modulating expression of insulin transcription factors (PDX-1, MafA), beta cell survival genes, and digestive enzyme genes, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 19. VESUGEN
            //------------------------------------------------------------------
            'vesugen' => [
                'title' => 'Vesugen',
                'peptide_full_name' => 'Lys-Glu-Asp (KED) Tripeptide — Vascular Bioregulator',
                'research_title' => 'Vesugen (KED): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Vesugen, a synthetic tripeptide bioregulator (Lys-Glu-Asp) targeting vascular tissue, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'Cardiovascular Research',
                'description' => 'Vesugen is a synthetic tripeptide (Lys-Glu-Asp) developed as a vascular bioregulator within the Khavinson peptide bioregulation paradigm. It targets vascular endothelium and smooth muscle to normalize vessel function through proposed gene-regulatory mechanisms. Note: Vesugen shares the KED sequence with Chonluten but is classified distinctly as a vascular rather than bronchopulmonary bioregulator.',
                'molecular_formula' => 'C₁₅H₂₆N₄O₈',
                'molecular_weight' => '390.39 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral or oral administration studied in research settings',
                'background' => 'Vesugen is a synthetic tripeptide with the sequence Lys-Glu-Asp (KED), developed by Vladimir Khavinson at the St. Petersburg Institute of Bioregulation and Gerontology as a vascular-specific bioregulator. Notably, Vesugen shares the same amino acid sequence as Chonluten (which targets bronchopulmonary tissue), illustrating a key aspect of the Khavinson bioregulation model: the same short peptide sequence may be classified differently based on its proposed primary tissue target and the tissue context of its study. The Khavinson group proposes that the KED sequence has affinity for gene regulatory regions in both vascular and pulmonary tissue, with the preparation method and delivery route influencing tissue-specific activity. Vesugen has been studied primarily in the context of vascular aging, endothelial dysfunction, and atherosclerosis models. The research focuses on the peptide\'s proposed ability to modulate expression of genes involved in endothelial function, vasodilation, and vascular wall integrity.',
                'mechanism_of_action_intro' => 'Vesugen is proposed to normalize vascular function through tissue-specific gene regulation in endothelial and vascular smooth muscle cells.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves gene-regulatory interactions in vascular endothelial and smooth muscle cells.',
                        'points' => [
                            'Proposed to interact with DNA regulatory sequences in vascular endothelial cells',
                            'May modulate expression of endothelial nitric oxide synthase (eNOS) and vasodilatory factors',
                            'Reported to influence anti-inflammatory gene expression in vascular endothelium',
                            'Proposed to support vascular wall integrity and reduce age-related arterial stiffening',
                            'May modulate expression of extracellular matrix proteins in vascular smooth muscle',
                            'Consistent with Khavinson model of tissue-specific short peptide bioregulation',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Vesugen has been investigated in cell culture and animal models of vascular aging and endothelial dysfunction.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Endothelial Cell Studies', 'description' => 'In human umbilical vein endothelial cell (HUVEC) cultures, Vesugen treatment was associated with modulation of genes involved in endothelial function and vascular homeostasis, as reported by the Khavinson group.'],
                            ['title' => 'Vascular Aging Models', 'description' => 'Aged rodent models showed improvements in vascular elasticity measures and endothelial function markers following chronic Vesugen administration.'],
                            ['title' => 'Anti-Inflammatory Effects', 'description' => 'Vesugen treatment was associated with reduced expression of pro-inflammatory adhesion molecules in endothelial cultures, suggesting anti-atherogenic properties.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data originates primarily from Russian research institutions. The shared sequence with Chonluten raises questions about tissue-specific classification that require further clarification.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Vesugen.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Observational reports from Russian clinical settings describe Vesugen use in elderly patients with cardiovascular conditions, but no controlled clinical trials meeting ICH-GCP standards have been conducted.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Vesugen is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Vesugen is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Vesugen is relevant to vascular biology and cardiovascular aging research.',
                'potential_applications' => json_encode([
                    ['title' => 'Vascular Aging Research', 'description' => 'Investigation of age-related endothelial dysfunction and arterial stiffening, and peptide-mediated normalization strategies.'],
                    ['title' => 'Endothelial Biology', 'description' => 'Study of short peptide effects on eNOS expression, endothelial barrier function, and vascular homeostasis.'],
                    ['title' => 'Anti-Atherosclerosis Research', 'description' => 'Investigation of peptide-mediated anti-inflammatory effects in vascular endothelium.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Vesugen (KED) is a tripeptide bioregulator targeting vascular tissue within the Khavinson framework. It shares the KED sequence with Chonluten, reflecting the Khavinson model\'s tissue-context-dependent classification. Preclinical data suggest effects on endothelial function, vascular gene expression, and anti-inflammatory activity. Independent international verification is needed, and no controlled clinical trials have been conducted. Vesugen contributes to research on peptide-mediated vascular bioregulation and endothelial biology.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2012)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2016)', 'authors' => 'Khavinson VKh, Linkova NS et al.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Vesugen is a synthetic tripeptide (Lys-Glu-Asp) classified as a vascular bioregulator',
                    'Shares KED sequence with Chonluten (bronchopulmonary) — tissue-context classification',
                    'Preclinical data suggest endothelial function and vascular homeostasis effects',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Vesugen is a synthetic tripeptide bioregulator (Lys-Glu-Asp) designed to normalize vascular endothelial function and vessel wall biology.',
                'areas_of_research_intro' => 'Vesugen research focuses on vascular biology, cardiovascular aging, and endothelial function.',
                'areas_of_research' => json_encode([
                    ['name' => 'Vascular Biology', 'description' => 'Endothelial function, vasodilation, and vessel wall homeostasis'],
                    ['name' => 'Cardiovascular Aging', 'description' => 'Arterial stiffening, endothelial senescence, and vascular decline'],
                ]),
                'key_effects' => json_encode(['Endothelial function modulation', 'eNOS expression regulation', 'Anti-inflammatory vascular effects', 'Vascular wall integrity support']),
                'common_use_cases' => json_encode(['Vascular aging research', 'Endothelial biology studies', 'Cardiovascular bioregulation']),
                'how_it_works' => 'Vesugen (Lys-Glu-Asp) is proposed to interact with DNA regulatory sequences in vascular endothelial and smooth muscle cells, modulating expression of eNOS, anti-inflammatory mediators, and extracellular matrix proteins, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            //------------------------------------------------------------------
            // 20. CARTALAX
            //------------------------------------------------------------------
            'cartalax' => [
                'title' => 'Cartalax',
                'peptide_full_name' => 'Ala-Glu-Asp (AED) Tripeptide — Cartilage/Musculoskeletal Bioregulator',
                'research_title' => 'Cartalax (AED): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Cartalax, a synthetic tripeptide bioregulator (Ala-Glu-Asp) targeting cartilage and musculoskeletal tissue, developed within the Khavinson peptide bioregulation framework.',
                'education_tag' => 'Musculoskeletal Research',
                'description' => 'Cartalax is a synthetic tripeptide (Ala-Glu-Asp) developed as a cartilage and musculoskeletal bioregulator within the Khavinson peptide bioregulation paradigm. It shares the AED sequence with Bronchogen but is classified as a musculoskeletal rather than respiratory bioregulator, targeting chondrocytes and connective tissue.',
                'molecular_formula' => 'C₁₁H₁₈N₂O₇',
                'molecular_weight' => '305.26 g/mol',
                'half_life' => 'Minutes (short peptide; rapid tissue uptake)',
                'bioavailability' => 'Parenteral or oral administration studied in research settings',
                'background' => 'Cartalax is a synthetic tripeptide with the sequence Ala-Glu-Asp (AED), developed by Vladimir Khavinson at the St. Petersburg Institute of Bioregulation and Gerontology. It is classified as a cartilage and musculoskeletal bioregulator. Notably, Cartalax shares its amino acid sequence with Bronchogen (also AED), which targets respiratory tissue. This parallel classification illustrates a distinctive feature of the Khavinson bioregulation system: the same peptide sequence may be assigned different tissue targets based on the context of research and the tissue from which the original peptide fraction was isolated. The Khavinson group proposes that the AED tripeptide interacts with gene regulatory elements present in both cartilage and respiratory tissue, with the specific biological outcome depending on the target cell type. Cartalax has been studied in the context of osteoarthritis models, cartilage degeneration, and age-related musculoskeletal decline. The research focuses on its proposed ability to modulate chondrocyte gene expression, extracellular matrix production, and cartilage homeostasis.',
                'mechanism_of_action_intro' => 'Cartalax is proposed to normalize cartilage and musculoskeletal function through tissue-specific gene regulation in chondrocytes and connective tissue cells.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The proposed mechanism involves gene-regulatory interactions in chondrocytes and musculoskeletal connective tissue cells.',
                        'points' => [
                            'Proposed to interact with DNA regulatory sequences in chondrocytes and connective tissue cells',
                            'May modulate expression of collagen type II and aggrecan — major cartilage matrix components',
                            'Reported to influence expression of matrix metalloproteinases (MMPs) involved in cartilage turnover',
                            'Proposed to support chondrocyte survival and proliferation in aging cartilage',
                            'May modulate inflammatory gene expression in joint tissues',
                            'Shares AED sequence with Bronchogen but targets musculoskeletal rather than respiratory gene expression',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Cartalax has been investigated in cell culture and animal models of cartilage degeneration and musculoskeletal aging.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'In Vitro and Animal Studies',
                        'findings' => [
                            ['title' => 'Chondrocyte Studies', 'description' => 'In chondrocyte cultures, Cartalax treatment was associated with enhanced extracellular matrix production, including collagen type II and proteoglycans, and modulation of MMP expression, as reported by the Khavinson research group.'],
                            ['title' => 'Cartilage Aging Models', 'description' => 'Aged rodent models showed improved cartilage histological scores and preserved joint function parameters following chronic Cartalax administration.'],
                            ['title' => 'Anti-Inflammatory Effects', 'description' => 'In models of experimentally induced joint inflammation, Cartalax treatment was associated with reduced inflammatory markers and preserved cartilage integrity.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Preclinical data originates primarily from Russian research institutions. The shared sequence with Bronchogen requires further investigation to understand tissue-specific mechanisms.',
                'human_use_intro' => 'No internationally recognized clinical trials have been conducted with Cartalax.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Clinical Evidence',
                        'entries' => [
                            ['type' => 'content', 'value' => 'No controlled clinical trials meeting ICH-GCP standards have been conducted for Cartalax. Observational reports from Russian clinical practice exist but do not constitute controlled clinical evidence.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([
                    [
                        'title' => 'Regulatory Status',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Cartalax is not approved by the FDA, EMA, or any major Western regulatory authority. It is classified as a research compound (RUO).'],
                        ],
                    ],
                ]),
                'regulatory_important_note' => 'Cartalax is an experimental research compound. It is not approved for human therapeutic use and is sold for research purposes only.',
                'potential_applications_intro' => 'Based on preclinical data, Cartalax is relevant to cartilage biology and musculoskeletal aging research.',
                'potential_applications' => json_encode([
                    ['title' => 'Cartilage Research', 'description' => 'Investigation of short peptide effects on chondrocyte function, matrix production, and cartilage homeostasis.'],
                    ['title' => 'Osteoarthritis Models', 'description' => 'Study of peptide-mediated cartilage protection in degenerative joint disease models.'],
                    ['title' => 'Musculoskeletal Aging', 'description' => 'Investigation of age-related cartilage and connective tissue decline.'],
                ]),
                'potential_applications_important_context' => 'All applications are based on preclinical data from Russian laboratories. No therapeutic claims are made.',
                'conclusion' => 'Cartalax (AED) is a tripeptide bioregulator targeting cartilage and musculoskeletal tissue within the Khavinson framework. It shares its amino acid sequence with Bronchogen (respiratory bioregulator), reflecting the Khavinson model\'s tissue-context-dependent classification system. Preclinical data suggest effects on chondrocyte function, cartilage matrix production, and joint tissue preservation in aging models. Independent international verification is needed, and no controlled clinical trials have been conducted. Cartalax serves as a research tool for studying peptide-mediated cartilage bioregulation and the broader question of how identical short peptide sequences may exert tissue-specific effects depending on cellular context.',
                'references' => json_encode([
                    ['title' => 'Bulletin of Experimental Biology and Medicine (2011)', 'authors' => 'Khavinson VKh et al.', 'links' => []],
                    ['title' => 'Advances in Gerontology (2014)', 'authors' => 'Khavinson VKh, Linkova NS.', 'links' => []],
                ]),
                'key_points' => json_encode([
                    'Cartalax is a synthetic tripeptide (Ala-Glu-Asp) targeting cartilage and musculoskeletal tissue',
                    'Shares AED sequence with Bronchogen — tissue-context classification within the Khavinson system',
                    'Preclinical data suggest chondroprotective and cartilage matrix-supporting effects',
                    'Not approved for human use — classified as research use only (RUO)',
                ]),
                'overview' => 'Cartalax is a synthetic tripeptide bioregulator (Ala-Glu-Asp) designed to normalize cartilage and musculoskeletal tissue function.',
                'areas_of_research_intro' => 'Cartalax research focuses on cartilage biology, musculoskeletal aging, and peptide bioregulation.',
                'areas_of_research' => json_encode([
                    ['name' => 'Cartilage Biology', 'description' => 'Chondrocyte function, extracellular matrix production, and cartilage homeostasis'],
                    ['name' => 'Musculoskeletal Aging', 'description' => 'Age-related cartilage and connective tissue degeneration'],
                ]),
                'key_effects' => json_encode(['Chondrocyte function modulation', 'Collagen type II and aggrecan expression', 'MMP expression regulation', 'Cartilage integrity preservation (preclinical)']),
                'common_use_cases' => json_encode(['Cartilage research', 'Osteoarthritis model studies', 'Musculoskeletal aging investigations']),
                'how_it_works' => 'Cartalax (Ala-Glu-Asp) is proposed to interact with DNA regulatory sequences in chondrocytes and connective tissue cells, modulating expression of cartilage matrix proteins (collagen II, aggrecan), matrix metalloproteinases, and chondrocyte survival factors, consistent with the Khavinson peptide bioregulation model.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

        ];
    }
}
