<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class EncyclopediaArticlesSeeder extends Seeder
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
            'igf-1-lr3' => [
                'title' => 'IGF-1 LR3',
                'peptide_full_name' => 'Insulin-like Growth Factor 1 Long R3',
                'research_title' => 'IGF-1 LR3: A Comprehensive Research Overview',
                'research_outline' => 'An in-depth analysis of IGF-1 LR3, a modified insulin-like growth factor with extended biological activity, covering mechanisms of action, preclinical findings, and research applications.',
                'education_tag' => 'Growth Factors',
                'description' => 'IGF-1 LR3 is an 83-amino acid synthetic analog of insulin-like growth factor 1 (IGF-1) with a modified N-terminus. The arginine-to-glutamate substitution at position 3 and 13-amino acid extension significantly reduces IGF binding protein affinity, resulting in enhanced and prolonged biological activity compared to native IGF-1.',
                'molecular_formula' => 'C₄₀₀H₆₂₅N₁₁₁O₁₁₅S₉',
                'molecular_weight' => '9,117.5 g/mol',
                'half_life' => '20-30 hours',
                'bioavailability' => 'High (subcutaneous research administration)',
                'background' => 'IGF-1 LR3 (Long R3 Insulin-like Growth Factor-1) is an 83-amino acid synthetic analog of human IGF-1. It was developed by modifying the native IGF-1 sequence — substituting a glutamic acid for the arginine at position 3 and adding a 13-amino acid N-terminal extension peptide. These modifications dramatically reduce binding to IGF binding proteins (IGFBPs), which normally sequester circulating IGF-1 and limit its bioavailability. As a result, IGF-1 LR3 maintains significantly higher biological potency and an extended half-life compared to native IGF-1. Originally developed as a research tool for studying IGF-1 signaling pathways, IGF-1 LR3 has become widely used in cell culture applications where it promotes cellular proliferation and inhibits apoptosis more effectively than standard IGF-1.',
                'mechanism_of_action_intro' => 'IGF-1 LR3 exerts its effects primarily through the IGF-1 receptor (IGF-1R), a transmembrane tyrosine kinase receptor expressed on virtually all cell types. Upon binding, it activates two major downstream signaling cascades that regulate cell growth, survival, and metabolism.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The primary signaling pathways activated by IGF-1 LR3 include the PI3K/Akt/mTOR pathway and the Ras/MAPK/ERK cascade, both of which are central regulators of cellular proliferation and survival.',
                        'points' => [
                            'Activates the PI3K/Akt pathway, promoting protein synthesis via mTOR signaling and inhibiting apoptosis through phosphorylation of pro-apoptotic factors',
                            'Stimulates the Ras/MAPK/ERK signaling cascade, driving cellular proliferation and differentiation',
                            'Reduced IGFBP binding allows higher free concentrations at the receptor, resulting in more sustained pathway activation compared to native IGF-1',
                            'Promotes glucose uptake and glycogen synthesis through insulin receptor substrate (IRS) signaling',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'IGF-1 LR3 has been extensively studied in both in-vitro cell culture systems and animal models. Its enhanced potency over native IGF-1 has made it a valuable research tool across multiple biological disciplines.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Cell Culture and In-Vitro Research',
                        'findings' => [
                            ['title' => 'Cell Proliferation', 'description' => 'IGF-1 LR3 is widely used as a cell culture supplement due to its ability to promote proliferation across multiple cell types including myoblasts, fibroblasts, and epithelial cells. Studies demonstrate 2-3x greater mitogenic activity compared to native IGF-1 at equivalent concentrations.'],
                            ['title' => 'Anti-Apoptotic Effects', 'description' => 'Research has shown that IGF-1 LR3 provides significant protection against programmed cell death in serum-free conditions, making it valuable for maintaining cell viability in research settings.'],
                        ],
                    ],
                    [
                        'title' => 'Animal Model Research',
                        'findings' => [
                            ['title' => 'Muscle Tissue Studies', 'description' => 'In rodent models, systemic administration of IGF-1 LR3 has been associated with increased lean body mass and enhanced satellite cell activation in skeletal muscle tissue.'],
                            ['title' => 'Metabolic Research', 'description' => 'Animal studies suggest IGF-1 LR3 may influence glucose metabolism and body composition parameters, though findings require further investigation in controlled settings.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'All preclinical findings are from cell culture and animal studies. IGF-1 LR3 has not been evaluated in human clinical trials. Results observed in laboratory settings may not translate to human outcomes.',
                'human_use_intro' => 'No published human clinical trials exist for IGF-1 LR3 specifically. While native IGF-1 (mecasermin) has been studied in clinical settings for certain growth disorders, the LR3 analog remains a research-only compound without clinical evaluation.',
                'human_use_subsections' => json_encode([['title' => 'Clinical Evidence', 'entries' => [['type' => 'content', 'value' => 'IGF-1 LR3 has not undergone formal human clinical trials. Its use remains restricted to laboratory research applications. Native IGF-1 (as mecasermin/Increlex) is FDA-approved for specific pediatric growth disorders, but this approval does not extend to IGF-1 LR3.']]]]),
                'regulatory_subsections' => json_encode([['title' => 'Regulatory Status', 'entries' => [['type' => 'content', 'value' => 'IGF-1 LR3 is not approved by the FDA, EMA, or any regulatory body for human therapeutic use. It is classified as a research chemical sold exclusively for in-vitro laboratory and research purposes.'], ['type' => 'content', 'value' => 'IGF-1 LR3 is prohibited by the World Anti-Doping Agency (WADA) under the category of peptide hormones and growth factors.']]]]),
                'regulatory_important_note' => 'IGF-1 LR3 is an experimental research compound. It is not approved for human consumption, therapeutic use, or self-administration. Researchers must comply with all applicable regulations.',
                'potential_applications_intro' => 'Based on preclinical evidence, potential research applications include areas where IGF-1 signaling plays a recognized role.',
                'potential_applications' => json_encode([
                    ['title' => 'Cell Culture Optimization', 'description' => 'IGF-1 LR3 is already widely used as a serum-free media supplement to support cell proliferation and viability in research settings.'],
                    ['title' => 'Tissue Engineering Research', 'description' => 'Studies are exploring IGF-1 LR3 as a component in tissue scaffold systems to promote cellular growth and tissue regeneration in vitro.'],
                    ['title' => 'Metabolic Pathway Investigation', 'description' => 'Researchers use IGF-1 LR3 to study insulin/IGF signaling pathway dynamics and their role in metabolic regulation.'],
                ]),
                'potential_applications_important_context' => 'All potential applications are based on preclinical and in-vitro research. No therapeutic claims are made.',
                'conclusion' => 'IGF-1 LR3 represents a significant advancement in growth factor research tools. Its engineered resistance to binding protein sequestration provides researchers with a more potent and longer-acting analog for studying IGF-1 signaling pathways. While extensively validated in cell culture and animal research, IGF-1 LR3 has not been evaluated in human clinical trials and remains strictly a research compound. The distinction between this synthetic analog and the clinically studied native IGF-1 is important — therapeutic applications, if any, would require comprehensive clinical development programs. Researchers continue to utilize IGF-1 LR3 as a valuable tool for understanding growth factor biology, cellular proliferation mechanisms, and metabolic signaling pathways.',
                'references' => json_encode([
                    ['title' => 'Journal of Biological Chemistry (1991)', 'authors' => 'Francis GL et al.', 'links' => []],
                    ['title' => 'Endocrine Reviews (2009)', 'authors' => 'Bentov I, Werner H.', 'links' => []],
                ]),
                'key_points' => json_encode(['IGF-1 LR3 is a synthetic 83-amino acid analog of native IGF-1 with enhanced potency', 'Reduced binding to IGFBPs extends biological half-life to 20-30 hours', 'Widely used in cell culture for its superior mitogenic and anti-apoptotic properties', 'Not approved for human use — classified as research use only (RUO)']),
                'overview' => 'IGF-1 LR3 is a modified form of insulin-like growth factor 1 with enhanced biological activity due to reduced binding protein affinity.',
                'areas_of_research_intro' => 'IGF-1 LR3 is studied across several research domains where IGF-1 signaling is relevant.',
                'areas_of_research' => json_encode([
                    ['name' => 'Cell Biology', 'description' => 'Proliferation, survival signaling, and serum-free culture optimization'],
                    ['name' => 'Tissue Engineering', 'description' => 'Scaffold-based regenerative research applications'],
                    ['name' => 'Metabolic Research', 'description' => 'Insulin/IGF pathway dynamics and glucose metabolism'],
                ]),
                'key_effects' => json_encode(['Potent cell proliferation factor', 'Extended biological half-life vs native IGF-1', 'Anti-apoptotic activity in cell culture', 'Research tool for IGF-1 pathway studies']),
                'common_use_cases' => json_encode(['Cell culture supplement', 'In-vitro proliferation studies', 'Growth factor signaling research']),
                'how_it_works' => 'IGF-1 LR3 binds the IGF-1 receptor (IGF-1R) and activates PI3K/Akt/mTOR and Ras/MAPK/ERK signaling cascades. Its modified N-terminus reduces IGFBP binding, resulting in higher free concentrations and prolonged receptor activation.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            'mk-677' => [
                'title' => 'MK-677',
                'peptide_full_name' => 'Ibutamoren Mesylate',
                'research_title' => 'MK-677 (Ibutamoren): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of MK-677, an orally active growth hormone secretagogue, examining its mechanism as a ghrelin receptor agonist, clinical research findings, and current regulatory status.',
                'education_tag' => 'Growth Hormone',
                'description' => 'MK-677 (Ibutamoren) is a non-peptide, orally active growth hormone secretagogue that mimics the action of ghrelin at the growth hormone secretagogue receptor (GHS-R1a). It stimulates pulsatile growth hormone release from the pituitary gland without affecting cortisol levels.',
                'molecular_formula' => 'C₂₇H₃₆N₄O₅S',
                'molecular_weight' => '528.66 g/mol',
                'half_life' => '24 hours',
                'bioavailability' => 'Oral (non-peptide compound)',
                'background' => 'MK-677, also known as Ibutamoren, is a potent, orally active growth hormone secretagogue developed by Merck & Co. in the 1990s. Unlike peptide-based GH secretagogues, MK-677 is a small molecule that can be administered orally. It functions as a selective agonist of the growth hormone secretagogue receptor (GHS-R1a), the same receptor activated by the endogenous hormone ghrelin. MK-677 stimulates the pituitary gland to release growth hormone (GH) in a pulsatile pattern that mimics natural physiology, subsequently increasing circulating levels of insulin-like growth factor 1 (IGF-1). Notably, MK-677 does not suppress the hypothalamic-pituitary-adrenal axis and does not increase cortisol levels at physiological GH-stimulating doses. It has been investigated in multiple Phase II clinical trials for applications including growth hormone deficiency, sarcopenia, and age-related muscle wasting.',
                'mechanism_of_action_intro' => 'MK-677 acts as a functional mimetic of ghrelin, the endogenous ligand for the GHS-R1a receptor. Its mechanism involves direct stimulation of somatotroph cells in the anterior pituitary.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'As a ghrelin receptor agonist, MK-677 activates signaling pathways that culminate in growth hormone release from pituitary somatotrophs.',
                        'points' => [
                            'Binds and activates the GHS-R1a receptor on pituitary somatotroph cells, triggering GH release',
                            'Stimulates pulsatile GH secretion that mimics natural circadian patterns',
                            'Increases circulating IGF-1 levels through sustained GH elevation',
                            'Does not suppress endogenous GH production or desensitize the GH axis with continued use',
                            'Does not significantly affect cortisol, prolactin, or thyroid hormone levels at standard research doses',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'MK-677 has been studied in both animal models and human clinical trials, with a more extensive evidence base than many research peptides.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Animal Studies',
                        'findings' => [
                            ['title' => 'Growth Hormone Axis', 'description' => 'In aged rodent and canine models, MK-677 administration restored GH pulsatility and elevated IGF-1 to levels comparable with younger animals.'],
                            ['title' => 'Body Composition', 'description' => 'Animal studies demonstrated increases in lean body mass and alterations in body composition parameters following chronic MK-677 administration.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'While MK-677 has more clinical data than many research compounds, it remains unapproved for therapeutic use. Animal and human study results require further validation.',
                'human_use_intro' => 'MK-677 is notable among research compounds for having undergone multiple Phase II clinical trials in human subjects, providing more clinical data than most peptide secretagogues.',
                'human_use_subsections' => json_encode([
                    [
                        'title' => 'Published Clinical Trials',
                        'entries' => [
                            ['type' => 'content', 'value' => 'A randomized, double-blind trial in healthy older adults (65+) demonstrated that MK-677 (25mg/day for 12 months) increased GH and IGF-1 levels to those of young adults, with increases in fat-free mass (published in Annals of Internal Medicine, 2008).'],
                            ['type' => 'content', 'value' => 'Studies in GH-deficient adults showed MK-677 increased 24-hour mean GH concentration and IGF-1 levels in a dose-dependent manner.'],
                            ['type' => 'content', 'value' => 'A 2-year study in elderly subjects showed sustained IGF-1 elevation but did not meet primary endpoints for body composition changes, highlighting the complexity of translating GH elevation to functional outcomes.'],
                        ],
                    ],
                    [
                        'title' => 'Observed Side Effects in Trials',
                        'entries' => [
                            ['type' => 'content', 'value' => 'Increased appetite (consistent with ghrelin receptor agonism), transient water retention, mild increases in fasting glucose, and increased IGF-1 levels were the most commonly reported effects in clinical settings.'],
                        ],
                    ],
                ]),
                'regulatory_subsections' => json_encode([['title' => 'Regulatory Status', 'entries' => [['type' => 'content', 'value' => 'Despite clinical trial data, MK-677 has not received FDA or EMA approval for any therapeutic indication. It remains an investigational compound.'], ['type' => 'content', 'value' => 'MK-677 is prohibited by WADA as a growth hormone secretagogue. It is classified as a research compound (RUO).']]]]),
                'regulatory_important_note' => 'MK-677 is an investigational compound that has not been approved for human therapeutic use. It is sold for research purposes only.',
                'potential_applications_intro' => 'Clinical trial data has identified several areas where MK-677 may have research relevance.',
                'potential_applications' => json_encode([
                    ['title' => 'Age-Related GH Decline Research', 'description' => 'Clinical data suggests MK-677 can restore age-declined GH/IGF-1 levels, making it relevant for somatopause research.'],
                    ['title' => 'Body Composition Studies', 'description' => 'Trials have shown effects on fat-free mass, making it relevant for sarcopenia and cachexia research models.'],
                    ['title' => 'Sleep Architecture Research', 'description' => 'Studies have shown MK-677 increases Stage IV sleep duration and REM sleep, suggesting relevance to sleep physiology research.'],
                ]),
                'potential_applications_important_context' => 'Despite clinical trial evidence, MK-677 is not approved for any therapeutic use. All applications remain investigational.',
                'conclusion' => 'MK-677 (Ibutamoren) occupies a unique position among growth hormone-related research compounds due to its oral bioavailability and relatively extensive clinical trial history. Published Phase II studies have demonstrated its ability to reliably elevate GH and IGF-1 levels in both young and elderly subjects, with a generally manageable side effect profile. However, the translation of these hormonal changes to meaningful clinical endpoints has proven more complex, and no Phase III trials have been completed. MK-677 remains an unapproved investigational compound. Its mechanism as a ghrelin receptor agonist and its effects on the GH/IGF-1 axis continue to make it a subject of research interest in endocrinology, gerontology, and metabolic science.',
                'references' => json_encode([
                    ['title' => 'Annals of Internal Medicine (2008)', 'authors' => 'Nass R et al.', 'links' => []],
                    ['title' => 'Journal of Clinical Endocrinology & Metabolism (1997)', 'authors' => 'Chapman IM et al.', 'links' => []],
                    ['title' => 'Clinical Endocrinology (2001)', 'authors' => 'Murphy MG et al.', 'links' => []],
                ]),
                'key_points' => json_encode(['MK-677 is an orally active, non-peptide growth hormone secretagogue', 'Acts as a ghrelin receptor (GHS-R1a) agonist to stimulate pulsatile GH release', 'Multiple Phase II clinical trials published with human data', 'Not FDA/EMA approved — remains investigational (RUO)']),
                'overview' => 'MK-677 (Ibutamoren) is an orally active growth hormone secretagogue that stimulates GH release through ghrelin receptor agonism.',
                'areas_of_research_intro' => 'MK-677 has been studied across endocrinology, gerontology, and metabolic research domains.',
                'areas_of_research' => json_encode([
                    ['name' => 'Endocrinology', 'description' => 'GH/IGF-1 axis modulation and somatopause research'],
                    ['name' => 'Gerontology', 'description' => 'Age-related muscle wasting and body composition'],
                    ['name' => 'Sleep Research', 'description' => 'Effects on sleep architecture and REM sleep patterns'],
                ]),
                'key_effects' => json_encode(['Elevates GH and IGF-1 levels', 'Orally bioavailable (non-peptide)', 'Does not suppress cortisol', 'Stimulates pulsatile GH secretion']),
                'common_use_cases' => json_encode(['GH secretagogue research', 'IGF-1 pathway studies', 'Ghrelin receptor investigations']),
                'how_it_works' => 'MK-677 binds the ghrelin receptor (GHS-R1a) on pituitary somatotroph cells, triggering intracellular calcium signaling and pulsatile growth hormone release. This increases circulating IGF-1 without suppressing endogenous GH production.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],

            'gonadorelin' => [
                'title' => 'Gonadorelin',
                'peptide_full_name' => 'Gonadotropin-Releasing Hormone (GnRH)',
                'research_title' => 'Gonadorelin (GnRH): A Comprehensive Research Overview',
                'research_outline' => 'An analysis of Gonadorelin, a synthetic form of gonadotropin-releasing hormone, covering its role in reproductive endocrinology, clinical applications, and regulatory status.',
                'education_tag' => 'Reproductive Health',
                'description' => 'Gonadorelin is a synthetic decapeptide identical to endogenous gonadotropin-releasing hormone (GnRH). It stimulates the anterior pituitary to release luteinizing hormone (LH) and follicle-stimulating hormone (FSH), playing a central role in the hypothalamic-pituitary-gonadal axis.',
                'molecular_formula' => 'C₅₅H₇₅N₁₇O₁₃',
                'molecular_weight' => '1,182.29 g/mol',
                'half_life' => '2-4 minutes',
                'bioavailability' => 'Requires parenteral administration (very short half-life)',
                'background' => 'Gonadorelin is a synthetic peptide that is structurally identical to the naturally occurring gonadotropin-releasing hormone (GnRH), also known as luteinizing hormone-releasing hormone (LHRH). This decapeptide (pyroGlu-His-Trp-Ser-Tyr-Gly-Leu-Arg-Pro-Gly-NH₂) was first characterized by Nobel laureate Andrew Schally in 1971, a discovery that fundamentally advanced the field of reproductive endocrinology. GnRH is secreted in a pulsatile fashion by the hypothalamus and acts on GnRH receptors in the anterior pituitary to stimulate the synthesis and release of the gonadotropins LH and FSH. These hormones in turn regulate gonadal function including steroidogenesis and gametogenesis. Synthetic gonadorelin has been used clinically as a diagnostic agent for evaluating pituitary function.',
                'mechanism_of_action_intro' => 'Gonadorelin exerts its effects through binding to GnRH receptors (GnRHR) on gonadotrope cells in the anterior pituitary gland. The pattern of administration — pulsatile versus continuous — determines dramatically different physiological outcomes.',
                'mechanism_subsections' => json_encode([
                    [
                        'intro' => 'The GnRH receptor is a G-protein coupled receptor that activates phospholipase C signaling, leading to calcium mobilization and gonadotropin release.',
                        'points' => [
                            'Pulsatile administration mimics natural GnRH secretion and stimulates LH/FSH release',
                            'Continuous administration paradoxically downregulates GnRH receptors, suppressing gonadotropin release',
                            'Activates Gq/11 signaling pathway → phospholipase C → IP3/DAG → calcium release',
                            'Very short half-life (2-4 minutes) due to rapid enzymatic degradation',
                        ],
                    ],
                ]),
                'preclinical_intro' => 'Gonadorelin has been extensively studied in both animal models and human clinical settings, with decades of research supporting its role in reproductive physiology.',
                'preclinical_subsections' => json_encode([
                    [
                        'title' => 'Reproductive Endocrinology Research',
                        'findings' => [
                            ['title' => 'HPG Axis Stimulation', 'description' => 'Gonadorelin reliably stimulates LH and FSH release in a dose-dependent manner across all mammalian species studied, confirming the conserved nature of GnRH signaling.'],
                            ['title' => 'Pulsatile vs Continuous Administration', 'description' => 'Research demonstrated that pulsatile GnRH stimulates gonadotropin release while continuous exposure causes receptor downregulation and functional suppression — a finding that led to the development of GnRH agonist therapeutics.'],
                        ],
                    ],
                ]),
                'preclinical_disclaimer' => 'Gonadorelin is one of the more well-characterized research peptides with significant clinical data. However, its use outside of approved clinical indications remains investigational.',
                'human_use_intro' => 'Gonadorelin has an established clinical history as a diagnostic agent and has been studied in therapeutic applications for reproductive disorders.',
                'human_use_subsections' => json_encode([['title' => 'Clinical Applications', 'entries' => [['type' => 'content', 'value' => 'Gonadorelin (as Factrel) was FDA-approved as a diagnostic agent for evaluating anterior pituitary gonadotrope function via the GnRH stimulation test.'], ['type' => 'content', 'value' => 'Pulsatile GnRH therapy has been used in clinical research for hypothalamic amenorrhea and hypogonadotropic hypogonadism, demonstrating the ability to restore physiological LH pulsatility.']]]]),
                'regulatory_subsections' => json_encode([['title' => 'Regulatory Status', 'entries' => [['type' => 'content', 'value' => 'Gonadorelin has been approved in pharmaceutical form (Factrel) as a diagnostic agent. However, research-grade gonadorelin products are sold strictly for laboratory use only (RUO) and are not equivalent to pharmaceutical preparations.']]]]),
                'regulatory_important_note' => 'Research-grade gonadorelin is not equivalent to pharmaceutical gonadorelin. It is sold for laboratory research purposes only and is not intended for human therapeutic use.',
                'potential_applications_intro' => 'Based on extensive clinical and preclinical data, gonadorelin research spans multiple areas of reproductive endocrinology.',
                'potential_applications' => json_encode([
                    ['title' => 'Pituitary Function Assessment', 'description' => 'GnRH stimulation testing for evaluating gonadotrope reserve and diagnosing hypogonadism subtypes.'],
                    ['title' => 'Reproductive Physiology Research', 'description' => 'Studying pulsatile hormone release patterns and HPG axis regulation in research settings.'],
                ]),
                'potential_applications_important_context' => 'Research-grade gonadorelin is for laboratory investigation only. Clinical applications require pharmaceutical-grade preparations under medical supervision.',
                'conclusion' => 'Gonadorelin occupies a unique position as one of the most well-characterized peptides in endocrinology. The discovery of GnRH and its role as the master regulator of the reproductive axis earned Andrew Schally the Nobel Prize and transformed the field of reproductive medicine. Synthetic gonadorelin remains an essential tool for studying HPG axis physiology, and its clinical utility as a diagnostic agent is well-established. The paradoxical effects of pulsatile versus continuous GnRH exposure have informed the development of an entire class of GnRH agonist and antagonist therapeutics. Research-grade gonadorelin continues to serve as a reference compound for investigations into reproductive endocrinology and neuroendocrine signaling.',
                'references' => json_encode([
                    ['title' => 'Science (1971)', 'authors' => 'Schally AV et al.', 'links' => []],
                    ['title' => 'Endocrine Reviews (1997)', 'authors' => 'Conn PM, Crowley WF.', 'links' => []],
                ]),
                'key_points' => json_encode(['Gonadorelin is a synthetic decapeptide identical to endogenous GnRH', 'Stimulates pituitary release of LH and FSH in pulsatile administration', 'Has established clinical history as a diagnostic agent (Factrel)', 'Research-grade products are for laboratory use only (RUO)']),
                'overview' => 'Gonadorelin is a synthetic form of gonadotropin-releasing hormone (GnRH) that stimulates pituitary gonadotropin release.',
                'areas_of_research_intro' => 'Gonadorelin research spans reproductive endocrinology, neuroendocrinology, and diagnostic medicine.',
                'areas_of_research' => json_encode([
                    ['name' => 'Reproductive Endocrinology', 'description' => 'HPG axis regulation and gonadotropin signaling'],
                    ['name' => 'Diagnostic Research', 'description' => 'Pituitary function assessment protocols'],
                ]),
                'key_effects' => json_encode(['Stimulates LH and FSH release', 'Pulsatile pattern mimics physiology', 'Very short half-life (2-4 min)', 'Established clinical diagnostic tool']),
                'common_use_cases' => json_encode(['HPG axis research', 'Gonadotropin signaling studies', 'Reproductive physiology']),
                'how_it_works' => 'Gonadorelin binds GnRH receptors on anterior pituitary gonadotrope cells, activating Gq/11-coupled phospholipase C signaling. This triggers calcium mobilization and exocytosis of LH and FSH granules. Pulsatile exposure stimulates release; continuous exposure downregulates receptors.',
                'rating' => '0.00',
                'rating_count' => 0,
            ],
        ];
    }
}
