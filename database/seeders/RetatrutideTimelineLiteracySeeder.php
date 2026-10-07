<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use Illuminate\Database\Seeder;

/**
 * Timeline and filing literacy for the live retatrutide encyclopedia row.
 *
 * Adds investigational status, the Q1 2027 BLA submission plan, the
 * Seventh Circuit argument, and expanded-access boundaries. Does not rewrite
 * existing TRIUMPH-2 body figures or the TRIUMPH-1 −25.0% vs −28.3% labels.
 *
 * Matches LOWER(slug) = retatrutide and exactly one category. Does not
 * create a category and does not write other encyclopedia pages. Safe to
 * run twice.
 */
class RetatrutideTimelineLiteracySeeder extends Seeder
{
    public const SLUG = 'retatrutide';

    public const OVERVIEW_SHORT_ADDITION = 'As of early October 2026, retatrutide remains investigational: not FDA-approved and not commercially marketed by the sponsor. Lilly has stated a plan to submit a U.S. BLA in Q1 2027 while completing CMC (filing plan ≠ acceptance ≠ approval). A Seventh Circuit oral argument on biologic classification (Eli Lilly v. Kennedy, No. 26-1301, 24 Sep 2026) was held; no ruling was located as of early Oct 2026.';

    public const OVERVIEW_ADDITION = 'Timeline literacy (early Oct 2026): Retatrutide (LY3437943) is still an investigational triple agonist. It is not FDA-approved and is not a sponsor-marketed medicine. Lilly’s 23 July 2026 topline on TRIUMPH-2 and TRIUMPH-3 stated that the company is completing the Chemistry, Manufacturing, and Controls (CMC) package required for a Biologics License Application and plans to submit in Q1 2027 for U.S. review — a company filing plan, not FDA acceptance and not approval. Grey-market / RUO catalog vials are not clinical-trial supply and are not expanded-access supply.';

    public const REGULATORY_NOTE = 'Retatrutide is investigational and not approved by the FDA, EMA, or other regulators for any indication. Lilly’s stated Q1 2027 U.S. BLA intent is a filing plan, not marketing authorization. PeptideMap is not a pharmacy or clinic. Vendor listings are RUO / research-use comparison data only — not prescriptions, not compounding advice, not clinical-trial supply, and not expanded-access supply.';

    public const CONCLUSION_ADDITION = 'Program literacy for readers: TRIUMPH-1 / TRIUMPH-2 / TRIUMPH-3 are different trials with differently labeled estimands; Q1 2027 is a planned BLA filing window, not an approval prophecy; the Seventh Circuit argument of 24 Sep 2026 had no located ruling by early Oct 2026; expanded access is a narrow manufacturer/FDA-process pathway and is not a grey-market catalog.';

    public const MAP_TITLE = 'TRIUMPH program map (names + disclosure type)';

    /** @var list<string> */
    public const KEY_POINT_ADDITIONS = [
        'Status (early Oct 2026): investigational; not FDA-approved; not commercially marketed by Lilly',
        'TRIUMPH map: TRIUMPH-1 (obesity without diabetes; NEJM), TRIUMPH-2 (T2D + obesity/overweight; Lancet), TRIUMPH-3 (severe obesity + CVD; July 2026 topline) — quote labeled estimands only',
        'Q1 2027 = Lilly’s stated BLA submission window while finishing CMC — not an approval date',
        'Seventh Circuit Eli Lilly v. Kennedy, No. 26-1301: oral argument 24 Sep 2026; no published ruling as of early Oct 2026',
        'Expanded access is not the same as trials and is not grey-market vials (manufacturer/FDA-process literacy only)',
    ];

    /** @var list<string> */
    private const FIELDS = [
        'description',
        'overview',
        'key_points',
        'human_use_subsections',
        'regulatory_subsections',
        'regulatory_important_note',
        'faqs',
        'conclusion',
        'references',
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
        $match = EncyclopediaCategoryMatch::find(self::SLUG, $this->command, 'Retatrutide timeline literacy');
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
            $this->command?->warn('Retatrutide timeline literacy: category exists but has no education post.');

            return;
        }

        $before = $this->snapshot($post);
        $this->apply($post);
        if ($this->snapshot($post) === $before) {
            $this->command?->info('Retatrutide timeline literacy: already current.');

            return;
        }

        $post->save();
        $this->report['updated'] = true;
        $this->command?->info('Retatrutide timeline literacy: updated '.$match->category->slug.'.');
    }

    private function apply(EducationPost $post): void
    {
        $post->description = $this->appendOnce($post->description, self::OVERVIEW_SHORT_ADDITION, 'Seventh Circuit oral argument');
        $post->overview = $this->appendOnce($post->overview, self::OVERVIEW_ADDITION, 'Timeline literacy (early Oct 2026)');
        $post->key_points = $this->appendKeyPoints(is_array($post->key_points) ? $post->key_points : []);
        $post->human_use_subsections = $this->insertMap(
            is_array($post->human_use_subsections) ? $post->human_use_subsections : []
        );
        $post->regulatory_subsections = $this->appendRegulatory(
            is_array($post->regulatory_subsections) ? $post->regulatory_subsections : []
        );
        $post->regulatory_important_note = $this->regulatoryNote($post->regulatory_important_note);
        $post->faqs = $this->upsertFaqs(is_array($post->faqs) ? $post->faqs : []);
        $post->conclusion = $this->appendOnce($post->conclusion, self::CONCLUSION_ADDITION, 'planned BLA filing window');
        $post->references = $this->appendReferences(is_array($post->references) ? $post->references : []);
    }

    private function appendOnce(?string $existing, string $addition, string $marker): string
    {
        $existing ??= '';
        if (str_contains($existing, $marker)) {
            return $existing;
        }
        if (trim($existing) === '') {
            return $addition;
        }

        return rtrim($existing)."\n\n".$addition;
    }

    /**
     * @param  list<mixed>  $points
     * @return list<mixed>
     */
    private function appendKeyPoints(array $points): array
    {
        foreach (self::KEY_POINT_ADDITIONS as $addition) {
            $present = false;
            foreach ($points as $point) {
                if (is_string($point) && $point === $addition) {
                    $present = true;
                    break;
                }
            }
            if (! $present) {
                $points[] = $addition;
            }
        }

        return $points;
    }

    /**
     * @param  list<mixed>  $sections
     * @return list<mixed>
     */
    private function insertMap(array $sections): array
    {
        if ($this->hasExactTitle($sections, self::MAP_TITLE)) {
            return $sections;
        }

        $insertAt = count($sections);
        $last = null;
        foreach ($sections as $index => $section) {
            if (! is_array($section)) {
                continue;
            }
            $title = (string) ($section['title'] ?? '');
            if (str_contains($title, 'TRIUMPH-1') || str_contains($title, 'TRIUMPH-2') || str_contains($title, 'TRIUMPH-3')) {
                $last = (int) $index;
            }
        }
        if ($last !== null) {
            $insertAt = $last + 1;
        }

        array_splice($sections, $insertAt, 0, [$this->mapSubsection()]);

        return $sections;
    }

    /**
     * @return array{title: string, entries: list<array{type: string, value: string}>}
     */
    private function mapSubsection(): array
    {
        return [
            'title' => self::MAP_TITLE,
            'entries' => [
                [
                    'type' => 'content',
                    'value' => 'The TRIUMPH Phase 3 program evaluates retatrutide across related obesity and complication settings. Names below are literacy labels only. Prefer the estimand label printed in the source you cite.',
                ],
                [
                    'type' => 'item',
                    'value' => 'TRIUMPH-1 (NCT05929066) — adults with obesity without diabetes; NEJM 29 Sep 2026 (DOI 10.1056/NEJMoa2604169; PMID 42814954). Abstract weight result uses a treatment-regimen (ITT) estimand: mean percent body-weight change −17.6% (4 mg), −23.7% (9 mg), −25.0% (12 mg) versus −3.9% placebo. A separate efficacy-estimand row of −28.3% at 12 mg (vs −2.2% placebo) appears on Lilly Medical’s TRIUMPH-1 outcomes table citing that paper — keep −25.0% and −28.3% labeled separately; do not present −28.3% as the abstract’s treatment-regimen primary weight line.',
                ],
                [
                    'type' => 'item',
                    'value' => 'TRIUMPH-2 (NCT05929079) — adults with type 2 diabetes and obesity or overweight; EASD / Lancet 29 Sep 2026 (DOI 10.1016/S0140-6736(26)01861-1). Do not rewrite live figures. Locked efficacy estimand includes up to −20.8% mean weight change at 12 mg vs −4.0% placebo; locked treatment-regimen includes −18.8% at 12 mg vs −5.1% placebo.',
                ],
                [
                    'type' => 'item',
                    'value' => 'TRIUMPH-3 (NCT05882045) — adults with severe obesity (BMI ≥35) and established cardiovascular disease, with or without T2D; Lilly topline 23 Jul 2026 (not treated here as a peer-reviewed cardiovascular-outcomes paper). Efficacy-estimand mean weight change −21.6% (9 mg) and −22.6% (12 mg) versus −3.2% placebo. Pre-specified in-study MACE-5 HR 0.82 (95% CI 0.55–1.22) and MACE-3 HR 1.12 (95% CI 0.64–1.96); intervals include 1 — the release does not establish cardiovascular-event reduction.',
                ],
                [
                    'type' => 'item',
                    'value' => 'Label topline versus peer-reviewed whenever you quote. July 2026 PR Newswire is topline for TRIUMPH-2/3 efficacy-estimand headlines; September 2026 Lancet is peer-reviewed for TRIUMPH-2 treatment-regimen Findings; September 2026 NEJM is peer-reviewed for TRIUMPH-1 treatment-regimen weight means.',
                ],
            ],
        ];
    }

    /**
     * @param  list<mixed>  $sections
     * @return list<mixed>
     */
    private function appendRegulatory(array $sections): array
    {
        foreach ($this->regulatorySubsections() as $section) {
            if ($this->hasExactTitle($sections, $section['title'])) {
                continue;
            }
            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * @return list<array{title: string, entries: list<array{type: string, value: string}>}>
     */
    private function regulatorySubsections(): array
    {
        return [
            [
                'title' => 'Development status (investigational)',
                'entries' => [
                    [
                        'type' => 'content',
                        'value' => 'Retatrutide is an investigational GIP/GLP-1/glucagon receptor agonist. It is not approved by the FDA (or EMA / other regulators cited here) for any indication and is not commercially marketed by Eli Lilly as a prescription medicine. PeptideMap vendor listings are RUO / research-use comparison data only — not trial supply, not expanded-access supply, and not prescriptions.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'Company materials continue to describe retatrutide as an investigational molecule that cannot be legally sold or marketed for human use pending approval pathways.',
                    ],
                ],
            ],
            [
                'title' => 'Filing literacy — planned BLA in Q1 2027 (≠ approval)',
                'entries' => [
                    [
                        'type' => 'content',
                        'value' => 'On 23 July 2026, Eli Lilly announced positive topline results from TRIUMPH-2 and TRIUMPH-3 and stated it is completing the comprehensive CMC data package required for a Biologics License Application (BLA) and plans to subsequently submit retatrutide in Q1 2027 for U.S. approval. That sentence is a sponsor filing plan.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'Filing is not FDA acceptance (filing decision) and acceptance is not approval. Until FDA receives and files an application and assigns a review clock, there is no agency action date to treat as an approval date.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'Do not convert “Q1 2027” into “approved in 2027.” Secondary timeline calendars that project review windows are estimates, not facts, and are not pasted here as predictions.',
                    ],
                ],
            ],
            [
                'title' => 'Biologic vs drug classification dispute — Seventh Circuit argument (no ruling yet)',
                'entries' => [
                    [
                        'type' => 'content',
                        'value' => 'Lilly’s stated U.S. path is a BLA (biologic). Whether retatrutide is classified as a biological product under the relevant protein / “analogous product” rules has been litigated. The U.S. Court of Appeals for the Seventh Circuit listed oral argument in Eli Lilly and Company v. Robert Kennedy, Jr., No. 26-1301, on 24 September 2026 (civil), with public argument audio on the court’s oral-argument calendar.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'As of early October 2026, no published appellate ruling in No. 26-1301 was located on a Seventh Circuit opinions lookup attempted for this pass. Same-day argument coverage dated 24–25 Sep 2026 confirms the hearing. Argument is not a decision.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'Educational takeaway only: the classification dispute can affect which application type (BLA vs NDA) a sponsor pursues. It is not an approval decision and must not be narrated as one.',
                    ],
                ],
            ],
            [
                'title' => 'Access before approval — trials vs expanded access vs grey-market',
                'entries' => [
                    [
                        'type' => 'content',
                        'value' => 'Before any approval, legitimate pathways discussed in primary manufacturer/FDA-process materials are clinical trials and, in limited circumstances, expanded access (sometimes called compassionate use) coordinated with a treating physician and applicable FDA IND / IRB requirements.',
                    ],
                    [
                        'type' => 'content',
                        'value' => 'Lilly Medical states that retatrutide is not currently FDA-approved; that Lilly is supporting single-patient expanded-access requests for individuals who meet eligibility criteria when they cannot join a trial and have exhausted available options; that availability depends on geography, supply, and case-by-case assessment; and that requests are physician-initiated. ClinicalTrials.gov lists pre-approval expanded access as NCT07629401.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'High-level eligibility themes on Lilly Medical include refractory obesity (BMI ≥35 despite highest tolerated approved chronic weight-management therapy), at least two serious or life-threatening obesity-related complications under standard care, and inability to join an ongoing retatrutide (or comparable) trial. This page does not turn those criteria into an application guide or medical advice.',
                    ],
                    [
                        'type' => 'item',
                        'value' => 'Grey-market / RUO vials sold online are not clinical-trial supply and are not expanded access. Catalog adjacency is not a manufacturer program.',
                    ],
                ],
            ],
        ];
    }

    private function regulatoryNote(?string $existing): string
    {
        $existing ??= '';
        if ($existing === self::REGULATORY_NOTE || str_contains($existing, 'not expanded-access supply')) {
            return $existing;
        }
        if ($this->containsLockedTriumphFigure($existing)) {
            return $this->appendOnce($existing, self::REGULATORY_NOTE, 'not expanded-access supply');
        }
        if (trim($existing) === '') {
            return self::REGULATORY_NOTE;
        }

        return self::REGULATORY_NOTE;
    }

    private function containsLockedTriumphFigure(string $value): bool
    {
        return str_contains($value, '20.8')
            || str_contains($value, '18.8')
            || str_contains($value, '25.0')
            || str_contains($value, '28.3');
    }

    /**
     * @param  list<mixed>  $faqs
     * @return list<array<string, mixed>>
     */
    private function upsertFaqs(array $faqs): array
    {
        $pairs = [
            'Is retatrutide approved?' => 'No. As of early October 2026 it remains investigational and is not FDA-approved, and it is not commercially marketed by the sponsor as an approved medicine.',
            'Does “BLA in Q1 2027” mean retatrutide will be approved in 2027?' => 'No. That date is Lilly’s stated plan to submit a Biologics License Application while completing CMC. Submission is not FDA acceptance, and acceptance is not approval. No approval date should be inferred from the filing window alone.',
            'What did the Seventh Circuit decide in Eli Lilly v. Kennedy?' => 'As of early October 2026: the Seventh Circuit heard oral argument on 24 September 2026 in No. 26-1301. No published ruling from that appeal was located in this check. Argument is not a decision.',
            'Are research-vendor “retatrutide” vials the same as trial or expanded-access supply?' => 'No. Grey-market / RUO listings are not Lilly clinical-trial material and are not expanded-access product released through the manufacturer/physician FDA-process pathway.',
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
                'needles' => ['302832674'],
                'reference' => [
                    'title' => 'Lilly\'s triple agonist, retatrutide, successful in two additional Phase 3 obesity trials',
                    'authors' => 'Eli Lilly and Company',
                    'citation' => 'PR Newswire, 23 Jul 2026.',
                    'description' => 'TRIUMPH-2/3 topline; CMC package and planned BLA submit in Q1 2027. Investigational; a filing plan is not approval.',
                    'links' => [
                        [
                            'url' => 'https://www.prnewswire.com/news-releases/lillys-triple-agonist-retatrutide-successful-in-two-additional-phase-3-obesity-trials-delivering-significant-improvements-in-weight-and-a1c-302832674.html',
                            'label' => 'PR Newswire',
                        ],
                    ],
                ],
            ],
            [
                'needles' => ['10.1016/S0140-6736(26)01861-1', 'S0140-6736(26)01861-1'],
                'reference' => [
                    'title' => 'Retatrutide in adults with obesity and type 2 diabetes (TRIUMPH-2)',
                    'authors' => 'The Lancet',
                    'citation' => 'DOI 10.1016/S0140-6736(26)01861-1; 29 Sep 2026.',
                    'description' => 'Peer-reviewed TRIUMPH-2. Live treatment-regimen figures on this page are not rewritten here.',
                    'links' => [
                        [
                            'url' => 'https://www.thelancet.com/journals/lancet/article/PIIS0140-6736(26)01861-1/abstract',
                            'label' => 'The Lancet',
                        ],
                    ],
                ],
            ],
            [
                'needles' => ['10.1056/NEJMoa2604169', '42814954'],
                'reference' => [
                    'title' => 'Retatrutide, a Triple Hormone Receptor Agonist, for Treatment of Obesity (TRIUMPH-1)',
                    'authors' => 'Jastreboff AM, et al.; TRIUMPH-1 Investigators',
                    'citation' => 'N Engl J Med. 2026 Sep 29. DOI 10.1056/NEJMoa2604169. PMID 42814954.',
                    'description' => 'Treatment-regimen weight means −17.6% / −23.7% / −25.0% vs −3.9%. −28.3% is a separate efficacy estimand.',
                    'links' => [
                        ['url' => 'https://pubmed.ncbi.nlm.nih.gov/42814954/', 'label' => 'PubMed'],
                        ['url' => 'https://doi.org/10.1056/NEJMoa2604169', 'label' => 'DOI'],
                    ],
                ],
            ],
            [
                'needles' => ['26-1301', 'media.ca7.uscourts.gov'],
                'reference' => [
                    'title' => 'Seventh Circuit oral-argument calendar — Eli Lilly and Company v. Robert Kennedy, Jr., No. 26-1301',
                    'authors' => 'U.S. Court of Appeals for the Seventh Circuit',
                    'citation' => 'Argument date 24 Sep 2026.',
                    'description' => 'Confirms oral argument was held. Not a ruling.',
                    'links' => [
                        [
                            'url' => 'https://media.ca7.uscourts.gov/oralArguments/oar.jsp?amonth=09%2F2026&aMonth=List+case%28s%29',
                            'label' => 'Argument calendar',
                        ],
                        [
                            'url' => 'https://media.ca7.uscourts.gov/sound/external/ef.26-1301.26-1301_09_24_2026.mp3',
                            'label' => 'Argument audio',
                        ],
                    ],
                ],
            ],
            [
                'needles' => ['416668', 'NCT07629401'],
                'reference' => [
                    'title' => 'Is there an expanded access program for retatrutide?',
                    'authors' => 'Lilly Medical',
                    'citation' => 'medical.lilly.com answer; cites NCT07629401.',
                    'description' => 'Investigational and not FDA-approved. Single-patient expanded access is physician-initiated and is not a grey-market catalog.',
                    'links' => [
                        [
                            'url' => 'https://medical.lilly.com/us/products/answers/is-there-an-expanded-access-program-for-retatrutide-416668',
                            'label' => 'Lilly Medical',
                        ],
                        [
                            'url' => 'https://clinicaltrials.gov/study/NCT07629401',
                            'label' => 'ClinicalTrials.gov',
                        ],
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

    /**
     * @param  list<mixed>  $sections
     */
    private function hasExactTitle(array $sections, string $title): bool
    {
        foreach ($sections as $section) {
            if (is_array($section) && (string) ($section['title'] ?? '') === $title) {
                return true;
            }
        }

        return false;
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
