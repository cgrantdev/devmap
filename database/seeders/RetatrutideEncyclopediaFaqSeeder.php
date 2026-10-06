<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Refreshes the Retatrutide encyclopedia FAQ and approval-status copy after
 * TRIUMPH-1 (NEJM) and TRIUMPH-2 (Lancet) published on 29 Sep 2026.
 *
 * Matches the case-sensitive category slug "Retatrutide" only. Does not
 * create a category, does not write /compare/retatrutide, and does not
 * rewrite an existing TRIUMPH-2 subsection. Safe to run twice.
 */
class RetatrutideEncyclopediaFaqSeeder extends Seeder
{
    public const SLUG = 'Retatrutide';

    public const FDA_ANSWER = 'No. Retatrutide is still not FDA-approved. Phase 3 TRIUMPH results are published, including TRIUMPH-1 in The New England Journal of Medicine (29 Sep 2026) and TRIUMPH-2 in The Lancet (29 Sep 2026). Eli Lilly has stated a planned U.S. biologics license application in Q1 2027. That filing plan is not FDA approval. PeptideMap is a research-use (RUO) comparison publisher, not a clinic or pharmacy. This is trial reporting, not medical advice and not a dose.';

    public const TRIUMPH1_QUESTION = 'How do published TRIUMPH-1 results differ from TRIUMPH-2?';

    public const TRIUMPH1_BODY = 'TRIUMPH-1 (NCT05929066) is not TRIUMPH-2. Jastreboff and colleagues, for the TRIUMPH-1 Investigators, reported it in The New England Journal of Medicine (29 Sep 2026; DOI 10.1056/NEJMoa2604169; PMID 42814954): adults with obesity or overweight without diabetes, randomized to once-weekly retatrutide 4 mg, 9 mg, or 12 mg or placebo for 80 weeks (2,339 participants). The abstract states that, for weight-related endpoints, intercurrent events were handled by a treatment-regimen estimand. Under that analysis, mean percent change in body weight was −17.6% (4 mg), −23.7% (9 mg), and −25.0% (12 mg) versus −3.9% with placebo. Those abstract figures are the treatment-regimen result. They are not a treatment recommendation and not a head-to-head comparison with semaglutide, tirzepatide, or eloralintide. The same paper’s abstract does not print −28.3%. Lilly Medical’s table of TRIUMPH-1 outcomes, which cites that NEJM article, lists a separate efficacy estimand: percent change in body weight at 80 weeks was −19.0% (4 mg), −25.9% (9 mg), and −28.3% (12 mg) versus −2.2% with placebo. −28.3% is not the abstract primary weight result. TRIUMPH-2 remains the separate type 2 diabetes plus obesity or overweight trial; its published figures are not restated here and are not a ranking against TRIUMPH-1. PeptideMap is a research-use (RUO) comparison publisher, not a clinic or pharmacy. This is not medical advice and not a dose.';

    public const TRIUMPH1_TITLE = 'TRIUMPH-1 Phase 3 (NEJM, 29 Sep 2026) — different trial from TRIUMPH-2';

    public const TRIUMPH1_KEY_POINT = 'TRIUMPH-1 (obesity without diabetes): treatment-regimen −25.0% at 12 mg vs −3.9% placebo (NEJM abstract, 29 Sep 2026; PMID 42814954). −28.3% is the efficacy estimand on Lilly Medical citing that paper, not the abstract primary.';

    public const TRIUMPH3_QUESTION = 'What did TRIUMPH-3 report for cardiovascular events?';

    public const TRIUMPH3_BODY = 'No. TRIUMPH-3 (NCT05882045) is not sourced from a peer-reviewed NEJM or Lancet cardiovascular-outcomes paper. Eli Lilly’s 23 July 2026 topline is the source. The trial enrolled adults with severe obesity (BMI ≥35 kg/m²) and established cardiovascular disease, with or without type 2 diabetes (1,949 participants; 9 mg, 12 mg, or placebo; 80 weeks). Under the efficacy estimand in that release, mean percent weight change at 80 weeks was −21.6% (9 mg) and −22.6% (12 mg) versus −3.2% with placebo. That is not the TRIUMPH-1 or TRIUMPH-2 result, and a treatment-regimen weight figure for TRIUMPH-3 was not in the release. The same release reported pre-specified in-study analyses of time to first event for pooled 9 mg and 12 mg versus placebo. MACE-5 was 44 events versus 52 (hazard ratio 0.82, 95% CI 0.55 to 1.22). MACE-3 was 27 events versus 23 (hazard ratio 1.12, 95% CI 0.64 to 1.96). Both intervals include 1. The release does not establish a reduction in cardiovascular events. It says these events occurred less frequently than anticipated in both the retatrutide and placebo arms. Urinary tract infection in that topline was 6.1% (9 mg) and 7.0% (12 mg) versus 5.3% with placebo in TRIUMPH-3. This is not medical advice and not a dose.';

    public const TRIUMPH3_TITLE = 'TRIUMPH-3 topline (Lilly, 23 Jul 2026) — not a peer-reviewed cardiovascular-outcomes paper';

    public const APPROVAL_STATUS = 'Retatrutide is not FDA-approved. Phase 3 TRIUMPH results are published, including TRIUMPH-1 (The New England Journal of Medicine, 29 Sep 2026) and TRIUMPH-2 (The Lancet, 29 Sep 2026). Eli Lilly has stated a planned U.S. BLA in Q1 2027. A filing plan is not approval. Research-grade retatrutide on PeptideMap is listed for research-use (RUO) comparison only. It is not clinical trial supply, not a prescription, and not medical advice.';

    public const PHASE2_UNDERWAY = 'Phase 3 trials are underway with enrollment expanded significantly.';

    public const PHASE2_PUBLISHED = 'Phase 3 TRIUMPH results have since been published; see the TRIUMPH-1 and TRIUMPH-2 subsections. Publication is not FDA approval, and those figures are not a dose.';

    /** @var list<string> */
    private const STRING_FIELDS = [
        'description',
        'overview',
        'background',
        'conclusion',
        'peptide_full_name',
        'human_use_intro',
        'regulatory_important_note',
        'potential_applications_intro',
        'potential_applications_important_context',
        'seo_description',
        'seo_page_title',
        'seo_og_description',
        'areas_of_research_intro',
        'mechanism_of_action_intro',
        'preclinical_intro',
        'preclinical_disclaimer',
        'research_outline',
    ];

    /** @var list<string> */
    private const ARRAY_FIELDS = [
        'faqs',
        'key_points',
        'key_effects',
        'human_use_subsections',
        'regulatory_subsections',
        'areas_of_research',
        'potential_applications',
        'mechanism_subsections',
        'preclinical_subsections',
        'references',
        'common_use_cases',
        'possible_side_effects',
        'contraindications',
    ];

    /** @var array<string, mixed> */
    public array $report = [
        'updated' => false,
        'missing_category' => false,
        'missing_post' => false,
        'skipped_case_mismatch' => false,
    ];

    public function run(): void
    {
        $category = $this->exactCategory();
        if (! $category) {
            return;
        }

        $post = EducationPost::query()->where('product_category_id', $category->id)->first();
        if (! $post) {
            $this->report['missing_post'] = true;
            $this->command?->warn('Retatrutide encyclopedia FAQ refresh: category exists but has no education post.');

            return;
        }

        $before = $this->snapshot($post);
        $this->apply($post);
        if ($this->snapshot($post) === $before) {
            $this->command?->info('Retatrutide encyclopedia FAQ refresh: already current.');

            return;
        }

        $post->save();
        $this->report['updated'] = true;
        $this->command?->info('Retatrutide encyclopedia FAQ refresh: updated '.$category->slug.'.');
    }

    private function apply(EducationPost $post): void
    {
        foreach (self::STRING_FIELDS as $field) {
            $value = $post->{$field};
            if (is_string($value)) {
                $post->{$field} = $this->scrubString($value);
            }
        }

        foreach (self::ARRAY_FIELDS as $field) {
            $value = $post->{$field};
            if (is_array($value)) {
                $post->{$field} = $this->scrubValue($value);
            }
        }

        $post->faqs = $this->upsertFaqs(is_array($post->faqs) ? $post->faqs : []);

        if (is_array($post->regulatory_subsections)) {
            $post->regulatory_subsections = $this->refreshRegulatory($post->regulatory_subsections);
        }

        $post->human_use_subsections = $this->insertTrialSubsections(
            is_array($post->human_use_subsections) ? $post->human_use_subsections : []
        );
        $post->key_points = $this->appendKeyPoint(is_array($post->key_points) ? $post->key_points : []);
        $post->references = $this->appendReferences(is_array($post->references) ? $post->references : []);
    }

    /**
     * @return array{status: string, category: ?ProductCategory}
     */
    private function exactCategory(): ?ProductCategory
    {
        $rows = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', ['retatrutide'])
            ->get();

        $exact = $rows->first(fn (ProductCategory $category) => $category->slug === self::SLUG);
        if ($exact) {
            return $exact;
        }

        if ($rows->isNotEmpty()) {
            $this->report['skipped_case_mismatch'] = true;
            $this->command?->warn('Retatrutide encyclopedia FAQ refresh: skipped case mismatch (stored slug '.$rows->first()->slug.').');

            return null;
        }

        $this->report['missing_category'] = true;

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $faqs
     * @return list<array<string, mixed>>
     */
    private function upsertFaqs(array $faqs): array
    {
        $foundFda = false;
        foreach ($faqs as &$faq) {
            if (! is_array($faq)) {
                continue;
            }
            $question = (string) ($faq['question'] ?? $faq['q'] ?? '');
            if (! preg_match('/fda[-\s]?approved/i', $question)) {
                continue;
            }
            $foundFda = true;
            $current = (string) ($faq['answer'] ?? $faq['a'] ?? '');
            if ($this->fdaAnswerIsCurrent($current)) {
                continue;
            }
            if (array_key_exists('a', $faq) && ! array_key_exists('answer', $faq)) {
                $faq['a'] = self::FDA_ANSWER;
            } else {
                $faq['answer'] = self::FDA_ANSWER;
            }
        }
        unset($faq);

        if (! $foundFda) {
            $faqs[] = [
                'question' => 'Is retatrutide FDA-approved?',
                'answer' => self::FDA_ANSWER,
            ];
        }

        $faqs = $this->upsertLabeledFaq(
            $faqs,
            'TRIUMPH-1',
            self::TRIUMPH1_QUESTION,
            self::TRIUMPH1_BODY,
            fn (string $answer) => $this->triumph1AnswerIsCurrent($answer)
        );

        return $this->upsertLabeledFaq(
            $faqs,
            'TRIUMPH-3',
            self::TRIUMPH3_QUESTION,
            self::TRIUMPH3_BODY,
            fn (string $answer) => $this->triumph3AnswerIsCurrent($answer)
        );
    }

    /**
     * @param  list<array<string, mixed>>  $faqs
     * @return list<array<string, mixed>>
     */
    private function upsertLabeledFaq(array $faqs, string $needle, string $question, string $answer, callable $isCurrent): array
    {
        foreach ($faqs as &$faq) {
            if (! is_array($faq)) {
                continue;
            }
            $existing = (string) ($faq['question'] ?? $faq['q'] ?? '');
            if (! str_contains($existing, $needle)) {
                continue;
            }
            $current = (string) ($faq['answer'] ?? $faq['a'] ?? '');
            if ($isCurrent($current)) {
                return $faqs;
            }
            if (array_key_exists('a', $faq) && ! array_key_exists('answer', $faq)) {
                $faq['a'] = $answer;
            } else {
                $faq['answer'] = $answer;
            }

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
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function refreshRegulatory(array $sections): array
    {
        foreach ($sections as &$section) {
            if (! is_array($section) || $this->isTriumph2Section($section)) {
                continue;
            }
            if (! is_array($section['entries'] ?? null)) {
                continue;
            }
            foreach ($section['entries'] as &$entry) {
                if (! is_array($entry)) {
                    continue;
                }
                $value = (string) ($entry['value'] ?? '');
                if ($value !== '' && $this->approvalStatusNeedsRefresh($value)) {
                    $entry['value'] = self::APPROVAL_STATUS;
                }
            }
            unset($entry);
        }
        unset($section);

        return $sections;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return list<array<string, mixed>>
     */
    private function insertTrialSubsections(array $sections): array
    {
        $missing = [];
        if (! $this->hasTitle($sections, 'TRIUMPH-1')) {
            $missing[] = $this->subsection(self::TRIUMPH1_TITLE, self::TRIUMPH1_BODY);
        }
        if (! $this->hasTitle($sections, 'TRIUMPH-3')) {
            $missing[] = $this->subsection(self::TRIUMPH3_TITLE, self::TRIUMPH3_BODY);
        }
        if ($missing === []) {
            return $sections;
        }

        $insertAt = $this->indexAfterTitle($sections, 'TRIUMPH-2');
        if ($insertAt === null) {
            $insertAt = count($sections);
        }
        array_splice($sections, $insertAt, 0, $missing);

        return $sections;
    }

    /**
     * @param  list<mixed>  $points
     * @return list<mixed>
     */
    private function appendKeyPoint(array $points): array
    {
        foreach ($points as $point) {
            if (is_string($point) && str_contains($point, 'TRIUMPH-1') && str_contains($point, '25.0') && str_contains($point, '28.3')) {
                return $points;
            }
        }
        $points[] = self::TRIUMPH1_KEY_POINT;

        return $points;
    }

    /**
     * @param  list<mixed>  $references
     * @return list<mixed>
     */
    private function appendReferences(array $references): array
    {
        $known = $this->referenceUrls($references);
        foreach ($this->references() as $reference) {
            $urls = $this->referenceUrls([$reference]);
            if ($urls !== [] && array_intersect($urls, $known) !== []) {
                continue;
            }
            $references[] = $reference;
            $known = array_merge($known, $urls);
        }

        return $references;
    }

    /**
     * @param  list<mixed>  $references
     * @return list<string>
     */
    private function referenceUrls(array $references): array
    {
        $urls = [];
        $walk = function (mixed $node) use (&$urls, &$walk): void {
            if (! is_array($node)) {
                return;
            }
            if (isset($node['url']) && is_string($node['url']) && $node['url'] !== '') {
                $urls[] = $node['url'];
            }
            foreach ($node as $item) {
                $walk($item);
            }
        };
        $walk($references);

        return $urls;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function references(): array
    {
        return [
            [
                'title' => 'Retatrutide, a Triple Hormone Receptor Agonist, for Treatment of Obesity (TRIUMPH-1)',
                'authors' => 'Jastreboff et al. for the TRIUMPH-1 Investigators',
                'citation' => 'N Engl J Med. 2026 Sep 29. DOI 10.1056/NEJMoa2604169. PMID 42814954. NCT05929066.',
                'description' => 'Abstract weight result is the treatment-regimen estimand: −17.6%, −23.7%, and −25.0% versus −3.9% placebo. −28.3% is not the abstract figure.',
                'links' => [
                    ['url' => 'https://pubmed.ncbi.nlm.nih.gov/42814954/', 'label' => 'PubMed'],
                    ['url' => 'https://doi.org/10.1056/NEJMoa2604169', 'label' => 'DOI'],
                ],
            ],
            [
                'title' => 'Lilly Medical table of TRIUMPH-1 outcomes',
                'authors' => 'Eli Lilly and Company',
                'citation' => 'Lilly Medical document 386151, citing NEJM DOI 10.1056/NEJMoa2604169.',
                'description' => 'Efficacy estimand percent change in body weight: −19.0%, −25.9%, and −28.3% versus −2.2% placebo. The treatment-regimen row matches the NEJM abstract.',
                'links' => [
                    [
                        'url' => 'https://medical.lilly.com/us/products/answers/what-are-the-results-of-retatrutide-from-triumph-1-in-participants-with-obesity-or-overweight-without-type-2-diabetes-386151',
                        'label' => 'Lilly Medical',
                    ],
                ],
            ],
            [
                'title' => 'Eli Lilly topline on two additional Phase 3 retatrutide trials (TRIUMPH-3)',
                'authors' => 'Eli Lilly and Company',
                'citation' => 'PR Newswire, 23 Jul 2026. NCT05882045.',
                'description' => 'Topline only. Efficacy-estimand weight change −21.6% (9 mg) and −22.6% (12 mg) versus −3.2% placebo. Pre-specified MACE-5 and MACE-3 hazard ratios have 95% confidence intervals that include 1. Not a peer-reviewed cardiovascular-outcomes paper.',
                'links' => [
                    [
                        'url' => 'https://www.prnewswire.com/news-releases/lillys-triple-agonist-retatrutide-successful-in-two-additional-phase-3-obesity-trials-delivering-significant-improvements-in-weight-and-a1c-302832674.html',
                        'label' => 'PR Newswire',
                    ],
                ],
            ],
        ];
    }

    private function fdaAnswerIsCurrent(string $answer): bool
    {
        if ($this->containsStaleResultsClaim($answer)) {
            return false;
        }
        $lower = mb_strtolower($answer);

        $notApproved = str_contains($lower, 'not fda-approved')
            || str_contains($lower, 'not yet fda-approved')
            || str_contains($lower, 'still not fda-approved');

        return $notApproved
            && str_contains($answer, 'Q1 2027')
            && str_contains($lower, 'published')
            && str_contains($lower, 'triumph')
            && (str_contains($lower, 'filing plan') || str_contains($lower, 'not fda approval') || str_contains($lower, 'not approval'));
    }

    private function triumph1AnswerIsCurrent(string $answer): bool
    {
        if ($this->containsStaleResultsClaim($answer)) {
            return false;
        }
        $lower = mb_strtolower($answer);

        return str_contains($answer, '25.0')
            && str_contains($answer, '28.3')
            && str_contains($answer, '3.9')
            && str_contains($lower, 'treatment-regimen')
            && str_contains($lower, 'efficacy estimand')
            && str_contains($answer, 'TRIUMPH-2')
            && (str_contains($lower, 'not that abstract') || str_contains($lower, 'not the abstract'));
    }

    private function triumph3AnswerIsCurrent(string $answer): bool
    {
        if ($this->containsStaleResultsClaim($answer)) {
            return false;
        }
        $lower = mb_strtolower($answer);

        return str_contains($answer, '0.82')
            && str_contains($answer, '1.12')
            && str_contains($lower, 'include 1')
            && str_contains($lower, 'does not establish a reduction in cardiovascular events')
            && str_contains($lower, '23 july 2026')
            && str_contains($answer, '−21.6%')
            && str_contains($answer, '−22.6%');
    }

    private function approvalStatusNeedsRefresh(string $value): bool
    {
        if ($value === self::APPROVAL_STATUS) {
            return false;
        }
        if ($this->containsStaleResultsClaim($value)) {
            return true;
        }

        $lower = mb_strtolower($value);
        $alreadyCurrent = str_contains($lower, 'published')
            && str_contains($value, 'Q1 2027')
            && (str_contains($lower, 'not fda-approved') || str_contains($lower, 'not yet fda-approved') || str_contains($lower, 'still not fda-approved'));
        if ($alreadyCurrent) {
            return false;
        }

        $mentionsApproval = str_contains($lower, 'fda');
        $saysPendingPhase3 = str_contains($lower, 'phase 3')
            && ! str_contains($lower, 'published')
            && (str_contains($lower, 'clinical trial') || str_contains($lower, 'underway'));

        return $mentionsApproval && $saysPendingPhase3;
    }

    private function containsStaleResultsClaim(string $value): bool
    {
        return (bool) preg_match('/expected in 2025\s*[–-]\s*2026/iu', $value);
    }

    private function scrubString(string $text): string
    {
        if (str_contains($text, self::PHASE2_UNDERWAY)) {
            $text = str_replace(self::PHASE2_UNDERWAY, self::PHASE2_PUBLISHED, $text);
        }

        $replacement = 'Phase 3 TRIUMPH results are published (29 Sep 2026); a planned U.S. BLA in Q1 2027 is a filing plan, not FDA approval';

        $updated = preg_replace('/results expected in 2025\s*[–-]\s*2026/iu', $replacement, $text);
        $text = is_string($updated) ? $updated : $text;
        $updated = preg_replace('/expected in 2025\s*[–-]\s*2026/iu', $replacement, $text);

        return is_string($updated) ? $updated : $text;
    }

    private function scrubValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->scrubString($value);
        }
        if (! is_array($value)) {
            return $value;
        }
        if ($this->isTriumph2Section($value)) {
            return $value;
        }
        foreach ($value as $key => $item) {
            $value[$key] = $this->scrubValue($item);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function isTriumph2Section(array $section): bool
    {
        $title = (string) ($section['title'] ?? '');

        return str_contains($title, 'TRIUMPH-2');
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     */
    private function hasTitle(array $sections, string $needle): bool
    {
        foreach ($sections as $section) {
            if (is_array($section) && str_contains((string) ($section['title'] ?? ''), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     */
    private function indexAfterTitle(array $sections, string $needle): ?int
    {
        $found = null;
        foreach ($sections as $index => $section) {
            if (is_array($section) && str_contains((string) ($section['title'] ?? ''), $needle)) {
                $found = (int) $index;
            }
        }

        return $found === null ? null : $found + 1;
    }

    /**
     * @return array{title: string, entries: list<array{type: string, value: string}>}
     */
    private function subsection(string $title, string $body): array
    {
        return [
            'title' => $title,
            'entries' => [
                ['type' => 'content', 'value' => $body],
            ],
        ];
    }

    private function snapshot(EducationPost $post): string
    {
        $fields = array_merge(self::STRING_FIELDS, self::ARRAY_FIELDS);

        return json_encode($post->only($fields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
