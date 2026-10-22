<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Copy for a /compare hub card, the line rendered beside "lowest listed".
 *
 * Encyclopedia descriptions can quote trial outcomes, doses, and
 * superlatives. Those stay on the encyclopedia page. The hub card
 * substitutes a class line when the rendered blurb matches that pattern.
 */
class CompareHubBlurb
{
    /**
     * Percent outcomes, efficacy or healing results, superlatives, and
     * per-dose figures. Matched against the blurb the hub would render.
     */
    private const OUTCOME_PATTERN = '/\d+(?:[.,]\d+)?\s*%|\bup\s+to\b|\b(?:reduc(?:e|es|ed|ing|tions?)|loss|lose|losing|heal(?:s|ed|ing)?|improv(?:e|es|ed|ing|ements?)|boost(?:s|ed|ing)?|promot(?:e|es|ed|ing)|enhanc(?:e|es|ed|ing|ements?)|increas(?:e|es|ed|ing)|decreas(?:e|es|ed|ing)|lower(?:s|ed|ing)?|burn(?:s|ed|ing)?|accelerat(?:e|es|ed|ing)|regenerat(?:e|es|ed|ing|ion)|restor(?:e|es|ed|ing)|treat(?:s|ed|ing|ments?)?|cur(?:e|es|ed)|effective|efficacy|proven|clinically\s+shown|superior|remarkable|highest|best|cheapest|verified|approved|transformed|has\s+shown|anti[-\s]?aging|weight[-\s]?management|exercise[-\s]mimetic|master\s+upstream|most[-\s][a-z]+)\b|\b\d+(?:\.\d+)?\s*(?:mg|mcg|µg|μg|iu)\b|\b(?:once|twice)[-\s](?:daily|weekly)\b|\bper\s+dose\b/iu';

    /**
     * Class lines for compounds whose stored hub blurb is an outcome claim.
     * Structural facts already on the card stay; nothing here is a result.
     *
     * @var array<string, string>
     */
    private const NEUTRAL = [
        'retatrutide' => 'Retatrutide is a triple agonist (GLP-1, GIP, and glucagon) studied in obesity research. Research use only.',
        'tirzepatide' => 'Tirzepatide is a dual GIP and GLP-1 receptor agonist studied in metabolic research. Research use only.',
        'semaglutide' => 'Semaglutide is a GLP-1 receptor agonist studied in metabolic research. Research use only.',
        'tesamorelin' => 'Tesamorelin is a GHRH analog studied in lipodystrophy research. Research use only.',
        'mots-c' => 'MOTS-c is a mitochondrial-derived peptide studied in metabolic research. Research use only.',
        'bpc-157' => 'BPC-157 is a synthetic pentadecapeptide derived from a gastric protein, studied in musculoskeletal and gastrointestinal research. Research use only.',
        'ipamorelin' => 'Ipamorelin is a ghrelin-receptor (GHS-R1a) agonist and growth hormone secretagogue studied in endocrine research. Research use only.',
        'aod-9604' => 'AOD-9604 is a modified fragment of human growth hormone (amino acids 176-191) studied in metabolic research. Research use only.',
        'tb-500' => 'TB-500 is a synthetic peptide related to thymosin beta-4, studied in musculoskeletal research. Research use only.',
        'sermorelin' => 'Sermorelin is a GHRH(1-29) analog studied in endocrine research. Research use only.',
        'cagrilintide' => 'Cagrilintide is a long-acting amylin analog studied in obesity research. Research use only.',
        'kisspeptin' => 'Kisspeptin is a neuropeptide studied in reproductive endocrinology research. Research use only.',
        'bremelanotide' => 'Bremelanotide is a melanocortin receptor agonist studied in sexual-function research. Research use only.',
        'pt-141' => 'Bremelanotide is a melanocortin receptor agonist studied in sexual-function research. Research use only.',
        'bacteriostatic-water' => 'Bacteriostatic water is sterile water with benzyl alcohol added as a preservative, used as a diluent. It is not a peptide or an active compound.',
        'teriparatide' => 'Teriparatide is recombinant parathyroid hormone (1-34), a 34-amino-acid peptide. Research vials are not the prescription product (Forteo). Research use only.',
        'oxytocin' => 'Oxytocin is a cyclic nonapeptide hormone. Research vials are not the prescription injection (Pitocin). Research use only.',
        'teduglutide' => 'Teduglutide is a 33-residue GLP-2 analog. Research vials are not the prescription product (GATTEX). Research use only.',
        'hmg' => 'HMG (human menopausal gonadotropin, menotropins) is a urinary preparation with FSH and LH activity. Research vials are not the prescription product. Research use only.',
        'methylene-blue' => 'Methylene blue (methylthioninium chloride) is a phenothiazine dye and small molecule, not a peptide. Research use only.',
        'adamax' => 'Adamax is a marketplace name for an N-acetylated, adamantane-modified Semax analog. Research use only.',
        'lipopeptide' => 'Lipopeptides are lipid-modified peptides studied in membrane, antimicrobial, and self-assembly research. Research use only.',
        'ptd-dbm' => 'PTD-DBM is a cell-penetrating fusion peptide (protein transduction domain plus a Dishevelled-binding motif) that blocks the CXXC5–Dishevelled interaction in Wnt signaling research. Research use only.',
        'igf-1-lr3' => 'IGF-1 LR3 is an 83-amino-acid analog of insulin-like growth factor 1 with an Arg3 substitution and a 13-residue N-terminal extension. Research use only.',
        'elamipretide' => 'Elamipretide (SS-31) is a mitochondria-targeted tetrapeptide studied in cardiolipin and inner-membrane research. Research use only.',
        'setmelanotide' => 'Setmelanotide is an eight-residue cyclic melanocortin-4 receptor agonist. Research vials are not the prescription product (IMCIVREE). Research use only.',
        'adipotide-ftpp' => 'Adipotide (FTPP) is a synthetic peptide that targets prohibitin on adipose-tissue blood vessels, studied in preclinical metabolic research. Research use only.',
        'aicar' => 'AICAR (acadesine) is a cell-permeable nucleoside that cells convert to the AMP mimic ZMP, used as an AMPK research tool. Research use only.',
        'mazdutide' => 'Mazdutide (IBI362 / LY3305677) is a glucagon and GLP-1 receptor dual agonist studied in metabolic research. Research use only.',
        'survodutide' => 'Survodutide (BI 456906) is an investigational glucagon and GLP-1 receptor dual agonist studied in metabolic research. Research use only.',
        'p21' => 'P21 is a peptide derived from ciliary neurotrophic factor (CNTF), studied in preclinical neuroscience research. Research use only.',
        'snap-8' => 'SNAP-8 (acetyl octapeptide-3) is a cosmetic-ingredient peptide related to the SNAP-25 sequence. Research use only.',
        'triptorelin' => 'Triptorelin is a synthetic GnRH agonist decapeptide with a D-Trp6 substitution. Research use only.',
    ];

    private const FALLBACK = 'Listed for research use only.';

    public static function render(?string $slug, ?string $raw): ?string
    {
        $plain = trim(strip_tags((string) $raw));
        if ($plain === '') {
            return null;
        }

        $limited = Str::limit($plain, 200);
        if (! self::hasOutcomeClaim($limited)) {
            return $limited;
        }

        return self::replacement($slug);
    }

    /**
     * Compare-page summary or OG tagline. Clean copy stays intact. A known
     * compound with an outcome claim uses its class line. Any other claim
     * keeps the clean opening sentences of the first paragraph and stops
     * at the first claim. A "not approved" disclaimer is not a claim.
     * If every opening sentence is a claim, the short fallback is used.
     */
    public static function guard(?string $slug, ?string $raw): ?string
    {
        $plain = trim(strip_tags((string) $raw));
        if ($plain === '') {
            return null;
        }

        if (! self::hasOutcomeClaim($plain)) {
            return $plain;
        }
        $key = strtolower(trim((string) $slug));
        if (isset(self::NEUTRAL[$key])) {
            return self::NEUTRAL[$key];
        }
        // Keep the clean opening sentences of the first paragraph, so a
        // "not approved" disclaimer survives. Stop at the first claim.
        $para = trim(preg_split('/\R\s*\R/u', $plain)[0]);
        $kept = [];
        foreach (preg_split('/(?<=[.!?])(?<!\bU\.S\.)(?<!\be\.g\.)(?<!\bi\.e\.)\s+(?=[\p{Lu}\d“"(])/u', $para) as $sentence) {
            $check = preg_replace('/\b(?:not|no)\s+(?:an?\s+|the\s+)?(?:U\.S\.\s+)?(?:FDA[-\s]?)?approved\b|\bunapproved\b/iu', '', $sentence);
            if (self::hasOutcomeClaim($check)) {
                break;
            }
            $kept[] = $sentence;
        }

        return $kept === [] ? self::FALLBACK : implode(' ', $kept);
    }

    private static function replacement(?string $slug): string
    {
        $key = strtolower(trim((string) $slug));

        return self::NEUTRAL[$key] ?? self::FALLBACK;
    }

    public static function hasOutcomeClaim(?string $text): bool
    {
        $text = trim(strip_tags((string) $text));
        if ($text === '') {
            return false;
        }

        return preg_match(self::OUTCOME_PATTERN, $text) === 1;
    }
}
