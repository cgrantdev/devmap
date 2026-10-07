<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use Illuminate\Database\Seeder;

/**
 * Identity polish for the live CJC-1295 encyclopedia row.
 *
 * Literature CJC-1295 is the DAC / albumin-binding construct (Jetté
 * PMID 15817669). Teichman PMID 16352683 multi-day half-life is DAC-only
 * and is not transferred to catalog “no DAC” / Modified GRF 1-29.
 *
 * Matches LOWER(slug) = cjc-1295 and exactly one category. Does not create
 * a category, does not create /encyclopedia/CJC-1295-with-DAC, and does not
 * fill /encyclopedia/CJC-1295-Ipamorelin. Formula, molecular weight, and CAS
 * already stored on the row are left unchanged. Safe to run twice.
 */
class Cjc1295DacIdentitySeeder extends Seeder
{
    public const SLUG = 'cjc-1295';

    public const SUBTITLE = 'Modified GRF 1-29 — often cataloged as “CJC-1295 no DAC” (not the literature DAC construct)';

    public const OVERVIEW_SHORT = 'Catalog labels often call a tetrasubstituted GHRH(1-29) analog “CJC-1295 no DAC” or Modified GRF 1-29. In the peer-reviewed literature, CJC-1295 names the long-acting Drug Affinity Complex (DAC) construct designed for covalent albumin binding (Jetté et al., 2005; Teichman et al., 2006). Those are not the same molecular identity. This page’s catalog lane is the no-DAC / Mod GRF naming; multi-day human half-life figures from Teichman belong to the DAC construct only and must not be transferred here. Educational RUO framing only — not medical advice.';

    public const OVERVIEW_BODY = 'CJC-1295 is a name with two common marketplace meanings. In ConjuChem / peer-reviewed characterization, CJC-1295 is a tetrasubstituted human GHRH(1-29) amide carrying a C-terminal lysine with an Nε-3-maleimidopropionamide (DAC) group intended to bind serum albumin after administration (Jetté et al., Endocrinology 2005, PMID 15817669). That albumin-binding design is part of the studied construct, not an optional label.

Research catalogs frequently sell a related but distinct backbone under nicknames such as “CJC-1295 no DAC” or Modified GRF 1-29 — a stabilized GHRH(1-29)-type analog without the DAC albumin-binding group. Equating those nicknames with literature “CJC-1295” is the central identity trap on this topic.

Both lanes act through the GHRH receptor on pituitary somatotrophs (distinct from ghrelin-receptor / GHS-R1a secretagogues such as ipamorelin). This encyclopedia row is the without-DAC / Mod GRF catalog lane. Human pharmacokinetic numbers from Teichman et al. (2006, PMID 16352683) — including the estimated 5.8–8.1 day half-life and multi-day GH/IGF-I elevation — were measured for the DAC construct and do not establish half-life or endocrine duration for no-DAC / Mod GRF material.

Blend vials marketed as CJC-1295 / Ipamorelin typically pair a GHRH-pathway analog (often labeled no-DAC / Mod GRF) with ipamorelin as two receptor lanes. The blend encyclopedia at <a href="/encyclopedia/CJC-1295-Ipamorelin">/encyclopedia/CJC-1295-Ipamorelin</a> is still an empty live shell (vendor listings only). Until that page ships, use the commerce/compare surfaces: <a href="/compare/CJC-1295-Ipamorelin">CJC-1295 / Ipamorelin vendor compare</a> and <a href="/compare/cjc-1295-vs-ipamorelin">CJC-1295 vs Ipamorelin</a>. This page does not re-author the blend.

Educational only. Not approved therapy guidance. Not dosing or reconstitution.';

    public const MOLECULAR_NOTE = 'Literature CJC-1295 (DAC) adds an albumin-binding maleimide-lysine element to a tetrasubstituted GHRH(1-29) amide; removing DAC changes molecular identity and expected mass. Use lot COA / mass spectrometry to confirm whether a vial matches a DAC or no-DAC analyte. Do not assume the live CAS field proves literature equivalence.';

    public const BACKGROUND_LEAD = 'Natural GHRH fragments are rapidly degraded in plasma; DPP-IV cleavage is a classical limitation of short GHRH sequences. Catalog Modified GRF 1-29 / “CJC-1295 no DAC” materials are marketed as tetrasubstituted GHRH(1-29)-type analogs engineered for greater proteolytic stability without the ConjuChem DAC albumin-binding group. Separately, literature CJC-1295 adds that DAC group so the pharmacophore can covalently attach to albumin (Jetté et al., 2005). Stability substitutions and albumin binding are different design strategies; citing one construct’s human PK for the other is an identity error.';

    public const HUMAN_USE_INTRO = 'Published healthy-adult pharmacokinetic and endocrine studies that established multi-day CJC-1295 exposure (Teichman et al., 2006) tested the long-acting DAC construct, not catalog “no DAC” / Mod GRF vials. Those papers are primary for DAC identity literacy. They are not automatic evidence for every product titled “CJC-1295” in a research catalog.';

    public const TEICHMAN_TITLE = 'DAC construct — Teichman et al. 2006 (do not transfer to no-DAC)';

    public const NODAC_TITLE = 'No-DAC / Mod GRF — evidence boundary';

    public const CONCLUSION = '“CJC-1295” on a research label is not self-explanatory. Peer-reviewed CJC-1295 denotes the DAC albumin-binding GHRH analog; catalog “no DAC” / Modified GRF 1-29 is a different identity lane that must carry its own evidence. PeptideMap’s job is to keep those lanes separate, cite construct-specific papers, and point blend shoppers to the blend encyclopedia when it ships — not to rank constructs or invent clinical protocols.';

    /** @var list<string> */
    public const KEY_POINTS = [
        'Literature “CJC-1295” = DAC / albumin-binding GHRH(1-29) construct (Jetté 2005; Teichman 2006)',
        'Catalog “CJC-1295 no DAC” ≈ Modified GRF 1-29 — different naming; not interchangeable evidence',
        'Teichman estimated half-life 5.8–8.1 days applies to the DAC construct only',
        'No-DAC / Mod GRF human PK is not established by those DAC papers — do not import “~30 min” from secondary blogs as Teichman data',
        'GHRH-receptor lane (not GHS-R1a); blends with ipamorelin are two-receptor pairings, not proven combination RCTs',
        'Research use only; not FDA-approved therapy',
    ];

    /** @var list<string> */
    private const FIELDS = [
        'peptide_full_name',
        'description',
        'overview',
        'background',
        'human_use_intro',
        'human_use_subsections',
        'key_points',
        'faqs',
        'references',
        'conclusion',
        'amino_acid_stability',
        'molecular_formula',
        'molecular_weight',
        'cas_registry_number',
    ];

    /** @var array<string, bool> */
    public array $report = [
        'updated' => false,
        'missing_category' => false,
        'missing_post' => false,
        'skipped_slug_collision' => false,
    ];

    public function run(): void
    {
        $match = EncyclopediaCategoryMatch::find(self::SLUG, $this->command, 'CJC-1295 DAC identity');
        $this->report['missing_category'] = $match->missingCategory;
        $this->report['skipped_slug_collision'] = $match->skippedSlugCollision;
        if (! $match->category) {
            return;
        }

        $post = EducationPost::query()
            ->where('product_category_id', $match->category->id)
            ->orderBy('id')
            ->first();
        if (! $post) {
            $this->report['missing_post'] = true;
            $this->command?->warn('CJC-1295 DAC identity: category exists but has no education post.');

            return;
        }

        $formula = $post->molecular_formula;
        $weight = $post->molecular_weight;
        $cas = $post->cas_registry_number;

        $before = $this->snapshot($post);
        $this->apply($post);
        $post->molecular_formula = $formula;
        $post->molecular_weight = $weight;
        $post->cas_registry_number = $cas;

        if ($this->snapshot($post) === $before) {
            $this->command?->info('CJC-1295 DAC identity: already current.');

            return;
        }

        $post->save();
        $this->report['updated'] = true;
        $this->command?->info('CJC-1295 DAC identity: updated '.$match->category->slug.'.');
    }

    private function apply(EducationPost $post): void
    {
        $post->peptide_full_name = self::SUBTITLE;
        $post->description = self::OVERVIEW_SHORT;
        $post->overview = self::OVERVIEW_SHORT."\n\n".self::OVERVIEW_BODY;
        $post->background = $this->replaceBackgroundLead($post->background);
        $post->human_use_intro = self::HUMAN_USE_INTRO;
        $post->human_use_subsections = $this->humanUseSubsections(
            is_array($post->human_use_subsections) ? $post->human_use_subsections : []
        );
        $post->key_points = self::KEY_POINTS;
        $post->faqs = $this->upsertFaqs(is_array($post->faqs) ? $post->faqs : []);
        $post->references = $this->appendReferences(is_array($post->references) ? $post->references : []);
        $post->conclusion = $this->conclusion($post->conclusion);
        $post->amino_acid_stability = self::MOLECULAR_NOTE;
    }

    private function replaceBackgroundLead(?string $background): string
    {
        $background ??= '';
        if (str_contains($background, 'Stability substitutions and albumin binding are different design strategies')) {
            return $background;
        }
        if (trim($background) === '') {
            return self::BACKGROUND_LEAD;
        }

        $parts = preg_split("/\n\s*\n/", $background, 2) ?: [];
        $rest = trim((string) ($parts[1] ?? ''));
        if ($rest === '') {
            return self::BACKGROUND_LEAD;
        }

        return self::BACKGROUND_LEAD."\n\n".$rest;
    }

    /**
     * @param  list<mixed>  $sections
     * @return list<array<string, mixed>>
     */
    private function humanUseSubsections(array $sections): array
    {
        $kept = [];
        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }
            $title = (string) ($section['title'] ?? '');
            if ($title === self::TEICHMAN_TITLE || $title === self::NODAC_TITLE) {
                continue;
            }
            $blob = json_encode($section, JSON_UNESCAPED_UNICODE) ?: '';
            if (preg_match('/Teichman|half-life|half life/iu', $blob)) {
                continue;
            }
            $kept[] = $section;
        }

        return array_merge($this->canonicalHumanUse(), $kept);
    }

    /**
     * @return list<array{title: string, entries: list<array{type: string, value: string}>}>
     */
    private function canonicalHumanUse(): array
    {
        return [
            [
                'title' => self::TEICHMAN_TITLE,
                'entries' => [
                    [
                        'type' => 'content',
                        'value' => 'Teichman et al. (J Clin Endocrinol Metab 2006; PMID 16352683) studied CJC-1295, described as a long-acting GHRH analog (DAC-GRF / albumin-binding design), in healthy adults in two randomized, placebo-controlled ascending-dose trials (28 and 49 days). After a single injection, mean plasma GH rose about 2- to 10-fold for 6 days or more and mean IGF-I about 1.5- to 3-fold for 9–11 days. The estimated half-life of CJC-1295 in that paper was 5.8–8.1 days. After multiple doses, mean IGF-I remained above baseline for up to 28 days. Those quantitative findings belong to the DAC construct, population, and assays in that study.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'Do not quote the 5.8–8.1 day half-life, multi-day GH/IGF-I windows, or “once-weekly” DAC framing as properties of Modified GRF 1-29 / “CJC-1295 no DAC.”',
                    ],
                ],
            ],
            [
                'title' => self::NODAC_TITLE,
                'entries' => [
                    [
                        'type' => 'content',
                        'value' => 'Removing the DAC group changes molecular identity. Selected CJC-1295 human PK and pulsatility papers characterize the albumin-binding construct (see also Ionescu & Frohman 2006 for DAC pulsatility context in secondary maps). Equivalent human half-life for Mod GRF / no-DAC was not established by Teichman et al. Secondary catalogs sometimes repeat an approximate short half-life for no-DAC; that figure is not a Teichman result and is not treated as verified primary data on this page.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'Analytical identity (expected mass with vs without the DAC linker element) is the practical check that a label matches the molecule — wrong mass implies wrong construct, not a “version” of the same evidence base.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  list<mixed>  $faqs
     * @return list<array<string, mixed>>
     */
    private function upsertFaqs(array $faqs): array
    {
        $pairs = [
            'Is “CJC-1295 no DAC” the same molecule as literature CJC-1295?' => 'No. In the peer-reviewed characterization, CJC-1295 is the DAC / albumin-binding GHRH(1-29) analog (Jetté et al., 2005). “CJC-1295 no DAC” is a common catalog nickname for Modified GRF 1-29-type material without that DAC group. Shared GHRH-receptor pharmacology does not make the identities or PK datasets interchangeable.',
            'Can the Teichman half-life be quoted for Mod GRF / no-DAC?' => 'No. Teichman et al. (2006) estimated 5.8–8.1 days for the DAC construct. That number must not be transferred to no-DAC / Mod GRF products.',
            'What about CJC-1295 / Ipamorelin blend vials?' => 'Those are marketed two-component research blends (GHRH-pathway analog + ipamorelin). Live /encyclopedia/CJC-1295-Ipamorelin is still empty of educational body text (vendors only). For now see /compare/CJC-1295-Ipamorelin and /compare/cjc-1295-vs-ipamorelin. This page does not give stacking, synergy, or dosing guidance.',
            'What should a blend CoA name?' => 'Each component’s construct (DAC vs no-DAC / Mod GRF; ipamorelin form) should be identifiable. A CoA that only says “CJC-1295” without construct clarity cannot resolve the naming trap above.',
        ];

        foreach ($pairs as $question => $answer) {
            $faqs = $this->upsertFaq($faqs, $question, $answer);
        }

        return $faqs;
    }

    /**
     * @param  list<mixed>  $faqs
     * @return list<array<string, mixed>>
     */
    private function upsertFaq(array $faqs, string $question, string $answer): array
    {
        $needle = $this->normalize($question);
        foreach ($faqs as &$faq) {
            if (! is_array($faq)) {
                continue;
            }
            $existing = $this->normalize((string) ($faq['question'] ?? $faq['q'] ?? ''));
            if ($existing !== $needle) {
                continue;
            }
            $key = array_key_exists('a', $faq) && ! array_key_exists('answer', $faq) ? 'a' : 'answer';
            if ((string) ($faq[$key] ?? '') === $answer) {
                return $faqs;
            }
            $faq[$key] = $answer;

            return $faqs;
        }
        unset($faq);

        $faqs[] = [
            'question' => $question,
            'answer' => $answer,
        ];

        return $faqs;
    }

    /**
     * @param  list<mixed>  $references
     * @return list<mixed>
     */
    private function appendReferences(array $references): array
    {
        $additions = [
            [
                'needles' => ['16352683', '10.1210/jc.2005-1536'],
                'reference' => [
                    'title' => 'Prolonged stimulation of growth hormone (GH) and insulin-like growth factor I secretion by CJC-1295, a long-acting analog of GH-releasing hormone, in healthy adults',
                    'authors' => 'Teichman SL, Neale A, Lawrence B, Gagnon C, Castaigne JP, Frohman LA',
                    'citation' => 'J Clin Endocrinol Metab. 2006; DOI 10.1210/jc.2005-1536. PMID 16352683.',
                    'description' => 'Healthy-adult PK/PD for DAC CJC-1295; estimated half-life 5.8–8.1 days; multi-day GH/IGF-I elevation. DAC construct only.',
                    'links' => [
                        ['url' => 'https://pubmed.ncbi.nlm.nih.gov/16352683/', 'label' => 'PubMed'],
                        ['url' => 'https://doi.org/10.1210/jc.2005-1536', 'label' => 'DOI'],
                    ],
                ],
            ],
            [
                'needles' => ['15817669', '10.1210/en.2004-1286'],
                'reference' => [
                    'title' => 'Human growth hormone-releasing factor (hGRF)1-29-albumin bioconjugates with an extended duration of action. Identification of CJC-1295 as a long-lasting GRF analog',
                    'authors' => 'Jetté L, Léger R, Thibaudeau K, et al.',
                    'citation' => 'Endocrinology. 2005; DOI 10.1210/en.2004-1286. PMID 15817669.',
                    'description' => 'Defines CJC-1295 as tetrasubstituted hGRF(1-29) with Nε-3-maleimidopropionamide lysine (DAC) for albumin binding.',
                    'links' => [
                        ['url' => 'https://pubmed.ncbi.nlm.nih.gov/15817669/', 'label' => 'PubMed'],
                        ['url' => 'https://doi.org/10.1210/en.2004-1286', 'label' => 'DOI'],
                    ],
                ],
            ],
            [
                'needles' => ['apexlab.org/cjc-1295-research-guide'],
                'reference' => [
                    'title' => 'CJC-1295 With DAC vs No DAC: An Identity-First Research Guide',
                    'authors' => 'Apex Laboratory Editorial Team',
                    'citation' => 'apexlab.org research guide (structure/identity map).',
                    'description' => 'Structure-only identity map for DAC versus Modified GRF naming. Not a primary pharmacokinetic source.',
                    'links' => [
                        ['url' => 'https://apexlab.org/cjc-1295-research-guide/', 'label' => 'Source'],
                    ],
                ],
            ],
        ];

        $blob = json_encode($references, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        foreach ($additions as $addition) {
            $present = false;
            foreach ($addition['needles'] as $needle) {
                if (str_contains($blob, $needle)) {
                    $present = true;
                    break;
                }
            }
            if ($present) {
                continue;
            }
            $references[] = $addition['reference'];
            $blob = json_encode($references, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return $references;
    }

    private function conclusion(?string $existing): string
    {
        $existing ??= '';
        if (str_contains($existing, 'not to rank constructs or invent clinical protocols')) {
            return $existing;
        }
        if (trim($existing) === '' || preg_match('/most robust|without-DAC is the standard|standard amplification/i', $existing)) {
            return self::CONCLUSION;
        }

        return rtrim($existing)."\n\n".self::CONCLUSION;
    }

    private function normalize(string $question): string
    {
        $question = str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"],
            ['"', '"', "'", "'"],
            $question
        );
        $question = preg_replace('/\s+/u', ' ', trim($question)) ?? trim($question);

        return mb_strtolower($question);
    }

    private function snapshot(EducationPost $post): string
    {
        return json_encode($post->only(self::FIELDS), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
