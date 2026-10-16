<?php

namespace Database\Seeders;

use App\Models\EducationPost;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Field-level update for the BPC-157 and retatrutide encyclopedia posts
 * (16 October 2026).
 *
 * Each post is matched with LOWER(slug), the same exact-one rule the
 * encyclopedia seeders use. Zero rows and more than one row are skipped.
 * JSON columns are assigned as PHP arrays and saved on the EducationPost
 * model, which is how those seeders store subsections and key points.
 *
 * BPC-157 writes regulatory_subsections, regulatory_important_note, and
 * key_points. Retatrutide writes human_use_subsections (appended),
 * regulatory_important_note, and key_points. A second run writes nothing.
 */
class Bpc157RetatrutideOctober2026Seeder extends Seeder
{
    public const BPC_SLUG = 'bpc-157';

    public const RETA_SLUG = 'retatrutide';

    public const BPC_US_TITLE = 'United States';

    public const BPC_COMPOUNDING_TITLE_PREFIX = 'U.S. regulatory status — compounding review';

    public const BPC_NOTE_GUARD = 'RIN 0910-ZD94';

    public const BPC_KEY_POINT_PREFIX = 'Regulatory status (as of 16 Oct 2026)';

    public const RETA_SUBSECTION_TITLE_PREFIX = 'Reported safety signals outside clinical trials';

    public const RETA_KEY_POINT_PREFIX = 'Safety reports outside trials (Oct 2026)';

    public const BPC_INSERT_INDEX = 2;

    private const BPC_JSON = <<<'JSON'
{"slug_match":"LOWER(slug) = 'bpc-157'; skip if match count != 1; no-op if 0","columns":{"regulatory_subsections":{"camelCase_prop":"regulatorySubsections","op":"guarded replace of entries[0].value in subsection titled 'United States' (only if exact match to old value) + insert new subsection at index 2 unless a subsection title starts with 'U.S. regulatory status — compounding review'","old_united_states_value":"The U.S. Food and Drug Administration (FDA) has taken action against companies marketing BPC-157 as a dietary supplement, stating it does not meet the definition of a legal supplement ingredient. There is also no legal basis for compounding pharmacies to include BPC-157 in medications, though regulatory agencies have found some clinics doing so illegally.\n\nAny product being sold is unregulated. Many websites sell BPC-157 as a \"research chemical\" with disclaimers that it's not for human use. This grey market means quality control is questionable – products might not contain the stated dose or could have impurities.","new_value":[{"title":"Current Approval Status","entries":[{"type":"content","value":"As of now, no country has approved BPC-157 as a prescription drug. It's not available through legitimate pharmacies for medical use. The peptide is officially designated for research use only."}]},{"title":"United States","entries":[{"type":"content","value":"The U.S. Food and Drug Administration (FDA) has taken action against companies marketing BPC-157 as a dietary supplement, stating it does not meet the definition of a legal supplement ingredient. As of 16 October 2026, BPC-157 is not on the 503A Bulks List (21 CFR 216.23), and no FDA policy permitting its use in compounding has been published. FDA has also found some clinics compounding it. See \"U.S. regulatory status — compounding review\" below for the July 2026 advisory committee meeting and the October 2026 interim policy submitted for OIRA review.\n\nProducts sold as \"research chemicals\" are not FDA-regulated drugs. Many websites sell BPC-157 with disclaimers that it is not for human use. This grey market means quality control is questionable: products might not contain the stated amount or could have impurities."}]},{"title":"U.S. regulatory status — compounding review (as of 16 October 2026)","entries":[{"type":"content","value":"BPC-157 is not an FDA-approved drug, and no FDA policy permitting its use in compounding has been published as of 16 October 2026. In 2026 two separate U.S. steps concerned BPC-157 and compounding: an advisory committee vote in July and an interim policy sent for White House review in October. Neither one approves BPC-157 or makes it lawful to compound."},{"type":"content","value":"Pharmacy Compounding Advisory Committee (23 July 2026). FDA's Pharmacy Compounding Advisory Committee (PCAC) met on 23–24 July 2026. On 23 July it considered BPC-157 (free base) and BPC-157 acetate for the list of bulk drug substances that may be used in compounding under section 503A of the FD&C Act (the \"503A Bulks List\"). FDA evaluated both forms for ulcerative colitis. FDA's published voting questions were: \"Should BPC-157 (free base) be placed on the list?\" and \"Should BPC-157 acetate be placed on the list?\" In its briefing document, FDA proposed that neither BPC-157 (free base) nor BPC-157 acetate be included on the 503A Bulks List. FDA states that advisory committee recommendations are non-binding. Reuters reported on 15 October 2026 that the advisers had recommended FDA loosen restrictions on six of the seven peptides reviewed in July, including BPC-157. As of 16 October 2026, FDA has not posted official minutes or vote tallies on the meeting page, so this entry gives no vote counts. The 503A Bulks List (21 CFR 216.23) has not been amended to include BPC-157."},{"type":"content","value":"Interim access policy under OIRA review (received 14 October 2026). OMB's Office of Information and Regulatory Affairs (OIRA) lists a pending Executive Order 12866 review with these details. Title: \"Interim Access Policy for Compounding Using Certain Peptides\". Agency/Subagency: HHS / FDA. RIN: 0910-ZD94. Stage: Notice. Received Date: 10/14/2026. Legal Deadline: None. Economically Significant: No (OIRA listing as indexed; live page unavailable 16 Oct 2026). The listing does not name any peptides and does not include the policy text. As of 16 October 2026, no matching Federal Register document and no FDA press announcement were found. Reuters, citing an Associated Press report based on two unnamed people, reported that the policy would allow some popular peptides, including BPC-157, to be compounded. That is a press report and has not been confirmed in any published text."},{"type":"item","value":"Not an approval: neither the PCAC vote nor the OIRA submission is FDA approval of BPC-157 for any use."},{"type":"item","value":"No published text: the policy text has not been released, and no Federal Register notice has been located."},{"type":"item","value":"No effective or start date: none has been announced."},{"type":"item","value":"Scope unknown: the OIRA listing does not say which peptides would be covered."},{"type":"item","value":"Review is not a final policy: an OIRA review is a step before publication. The policy may be changed, withdrawn, or published in a different form."},{"type":"item","value":"Other status lines are unchanged: on FDA's \"Certain Bulk Drug Substances for Use in Compounding that May Present Significant Safety Risks\" page (content current as of 22 April 2026), BPC-157 is listed as nominated but withdrawn. BPC-157 remains on WADA's S0 (non-approved substances) list."}]},{"title":"World Anti-Doping Agency (WADA)","entries":[{"type":"content","value":"WADA has banned BPC-157 for athletes under the class of \"S0: Non-approved substances.\" This means athletes can face sanctions if caught using it in sport. The ban signals that BPC-157 is considered experimental and not recognized as a legitimate medication."}]},{"title":"Animal Safety Data","entries":[{"type":"content","value":"One attractive feature of BPC-157 in research is its apparently favorable safety profile in animals. Toxicology studies have found no lethal dose even at extremely high amounts:"},{"type":"item","value":"In rats, a single injection of 20 mg per kilogram (a massive dose for a peptide) caused no deaths or obvious toxic effects"},{"type":"item","value":"In dogs, daily injections for a month at doses up to 1 mg/kg showed no significant adverse effects compared to control animals"},{"type":"item","value":"Studies reported no changes in body weight, behavior, or organ health attributable to BPC-157"}]},{"title":"Theoretical Concerns","entries":[{"type":"content","value":"The most prominent theoretical concern is BPC-157's ability to promote growth factors and blood vessel formation. While beneficial for healing injuries, this raises a question: could BPC-157 unintentionally help existing cancer cells grow or spread? Cancerous tumors often exploit the same pathways (like FAK/paxillin and VEGF) to invade tissues and nourish themselves with blood supply.\n\nThis idea is currently theoretical – no study has shown BPC-157 causes cancer in animals or humans. In fact, one lab test showed BPC-157 inhibited growth of a particular cancer cell line in culture. Nonetheless, experts urge caution until more is known about long-term effects."}]}]},"regulatory_important_note":{"camelCase_prop":"regulatoryImportantNote","op":"write only if current value exactly equals old_value; skip if current contains 'RIN 0910-ZD94'","old_value":"\"Absence of evidence is not evidence of absence\" when it comes to safety. Because BPC-157 has not been extensively studied in people, there could be side effects that haven't been observed in small samples. Minor side effects like headaches, dizziness, or injection site reactions might not show up in tiny studies but could in larger populations.","new_value":"\"Absence of evidence is not evidence of absence\" when it comes to safety. Because BPC-157 has not been extensively studied in people, there could be side effects that haven't been observed in small samples. Minor side effects like headaches, dizziness, or injection site reactions might not show up in tiny studies but could in larger populations.\n\nRegulatory status (as of 16 October 2026): BPC-157 is not approved by FDA. A July 2026 advisory committee vote is non-binding. The \"Interim Access Policy for Compounding Using Certain Peptides\" (RIN 0910-ZD94) has been under OIRA review since 14 October 2026. Its text has not been published, it has no effective date, and the peptides it covers are not known. PeptideMap does not predict outcomes."},"key_points":{"camelCase_prop":"keyPoints","op":"append new_item only if no existing item starts with 'Regulatory status (as of 16 Oct 2026)'","new_item":"Regulatory status (as of 16 Oct 2026): FDA's Pharmacy Compounding Advisory Committee voted on BPC-157 on 23 July 2026; the vote is non-binding. HHS/FDA sent an \"Interim Access Policy for Compounding Using Certain Peptides\" to OIRA on 14 Oct 2026. No text has been published, no effective date has been set, and coverage is unknown. This is not an approval."}}}
JSON;

    private const RETA_JSON = <<<'JSON'
{"slug_match":"LOWER(slug) = 'retatrutide'; skip if match count != 1; no-op if 0","columns":{"human_use_subsections":{"camelCase_prop":"humanUseSubsections","op":"append at END of array unless any subsection title starts with 'Reported safety signals outside clinical trials'","new_subsection":{"title":"Reported safety signals outside clinical trials (as of 16 October 2026)","entries":[{"type":"content","value":"This section summarizes reports about products sold under the name retatrutide outside Lilly's clinical trials. It is separate from the TRIUMPH clinical-trial results and does not change them. None of the reports below establishes that retatrutide caused any event. In most cases, the identity, content, and purity of the product involved are unknown. Products sold online under this name are not Lilly trial supply."},{"type":"content","value":"FDA Adverse Event Reporting System (FAERS) death reports, as reported by CBS News. On 13 October 2026, CBS News reported that FDA had received two reports of deaths after people took products claiming to be retatrutide. CBS said it reviewed redacted FAERS reports obtained under the Freedom of Information Act. According to CBS, the first report (July 2026) described a 41-year-old woman who had cardiac arrest; the report listed retatrutide as a \"suspect product\" obtained \"from an unreliable source.\" The second report (September 2026) described a 49-year-old patient who had severe dehydration, malnutrition, and cardiac arrest after buying the product from an online peptide vendor. CBS reported that it could not independently verify the reports, that FDA declined to say whether it had verified them, and that neither death was connected to a clinical trial. CBS also reported Lilly's statement that the reports \"make clear the two individuals were taking black-market retatrutide.\" PeptideMap has not seen these reports. The public openFDA FAERS data (last updated 30 July 2026) listed 44 reports in which retatrutide appears as a drug name and no report coded as a death. That dataset predates the September report and may not yet include the July report."},{"type":"content","value":"Poison center exposures, as reported by CBS News. CBS reported that America's Poison Centers logged 862 retatrutide exposures in the three months ending in September 2026, 460 in the three months before that, and 249 in the first three months of 2026. America's Poison Centers began tracking retatrutide in late 2025. CBS noted that rising counts are common for a newly monitored drug. An exposure call is not a confirmed adverse effect. PeptideMap has not checked these figures against a public America's Poison Centers or NPDS release."},{"type":"content","value":"Limits of FAERS. FAERS is a spontaneous reporting system. Reports are not verified by FDA, can be incomplete or duplicated, and do not show that a product caused an event. For grey-market or \"research use\" products, the substance actually taken is usually unknown. Report counts cannot be used to estimate how often an event happens."},{"type":"content","value":"Social-media pharmacovigilance study (JAMA Network Open, 14 October 2026). Sehgal NKR, Tronieri JS, Rader B, Ungar L, and Guntuku SC published \"Self-Reported Adverse Effects Among Social Media Users Taking Nonapproved Retatrutide\" in JAMA Network Open (doi:10.1001/jamanetworkopen.2026.38929). The study was a cross-sectional analysis of 148,640 retatrutide-related Reddit posts and comments. These came from 38,936 users across 27 communities, posted between May 2021 and December 2025. Large language models classified self-reported use and mapped the symptoms users attributed to retatrutide to MedDRA Preferred Terms, and the authors manually validated 100 posts. The models identified 13,589 users reporting current use. Of these, 7,823 had at least one mapped term after prespecified weight-loss and appetite-suppression terms were excluded. Among those 7,823 users, the most frequent terms were increased appetite (17.1%), fatigue (15.2%), increased energy (12.4%), nausea (11.6%), food craving (7.3%), insomnia (6.1%), and heart rate increased (4.8%). The authors note that gastrointestinal symptoms were common but not dominant, which differs from phase 2 trial findings."},{"type":"item","value":"Limits the authors themselves state: the findings are hypothesis-generating. Product identity, purity, dose, route, duration, concomitant medications, and medical history cannot be verified. Reddit users may not represent all users, and adverse events may be preferentially reported. The analysis cannot assess incidence, causality, dose-response, or comparative safety. The reported events may also reflect reconstitution practices, and natural language processing and MedDRA mapping may miss or misclassify symptoms. The reports may reflect product variability, peptide stacking, user characteristics, or misattribution. The study's comparison with semaglutide and tirzepatide was descriptive only, with unmatched samples and no statistical tests."},{"type":"item","value":"These reports describe unregulated products and cannot be compared directly with the controlled TRIUMPH trial safety data."}]}},"regulatory_important_note":{"camelCase_prop":"regulatoryImportantNote","op":"write only if current value exactly equals old_value (otherwise skip)","old_value":"Retatrutide is investigational and not approved by the FDA, EMA, or other regulators for any indication. PeptideMap is not a pharmacy or clinic. Vendor listings are RUO / research-use comparison data only — not prescriptions, not compounding advice, and not clinical supply.","new_value":"Retatrutide is investigational and not approved by the FDA, EMA, or other regulators for any indication. PeptideMap is not a pharmacy or clinic. Vendor listings are RUO / research-use comparison data only — not prescriptions, not compounding advice, and not clinical supply. Products sold online under this name are not Lilly trial supply. Reports of deaths and adverse effects linked to products sold as retatrutide (October 2026) are unverified and do not establish causation."},"key_points":{"camelCase_prop":"keyPoints","op":"append new_item unless an item starts with 'Safety reports outside trials (Oct 2026)'","new_item":"Safety reports outside trials (Oct 2026): CBS News reported two unverified FAERS death reports and rising poison-center exposure counts, and a JAMA Network Open Reddit study described self-reported symptoms. None of these establishes causation. Products sold online under this name are not Lilly trial supply."}},"do_not_touch":"any other column; TRIUMPH figures; existing Retatrutide seeders"}
JSON;

    /** @var array<string, mixed>|null */
    private static ?array $bpcPayload = null;

    /** @var array<string, mixed>|null */
    private static ?array $retaPayload = null;

    /** @var array<string, string> */
    public array $report = [
        self::BPC_SLUG => 'missing',
        self::RETA_SLUG => 'missing',
    ];

    public function run(): void
    {
        $this->report = [
            self::BPC_SLUG => 'missing',
            self::RETA_SLUG => 'missing',
        ];
        self::assertPayload();
        $this->update(self::BPC_SLUG, $this->applyBpc(...));
        $this->update(self::RETA_SLUG, $this->applyRetatrutide(...));
    }

    public static function oldBpcUnitedStates(): string
    {
        return (string) self::bpcColumn('regulatory_subsections')['old_united_states_value'];
    }

    public static function newBpcUnitedStates(): string
    {
        return (string) self::sectionByTitle(self::bpcNewSections(), self::BPC_US_TITLE)['entries'][0]['value'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function bpcCompoundingSubsection(): array
    {
        foreach (self::bpcNewSections() as $section) {
            $title = $section['title'] ?? null;
            if (is_string($title) && str_starts_with($title, self::BPC_COMPOUNDING_TITLE_PREFIX)) {
                return $section;
            }
        }

        throw new RuntimeException('BPC-157 compounding subsection is missing from the field payload.');
    }

    public static function oldBpcNote(): string
    {
        return (string) self::bpcColumn('regulatory_important_note')['old_value'];
    }

    public static function newBpcNote(): string
    {
        return (string) self::bpcColumn('regulatory_important_note')['new_value'];
    }

    public static function bpcKeyPoint(): string
    {
        return (string) self::bpcColumn('key_points')['new_item'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function retaSubsection(): array
    {
        $section = self::retaColumn('human_use_subsections')['new_subsection'] ?? null;
        if (! is_array($section)) {
            throw new RuntimeException('Retatrutide subsection is missing from the field payload.');
        }

        return $section;
    }

    public static function oldRetaNote(): string
    {
        return (string) self::retaColumn('regulatory_important_note')['old_value'];
    }

    public static function newRetaNote(): string
    {
        return (string) self::retaColumn('regulatory_important_note')['new_value'];
    }

    public static function retaKeyPoint(): string
    {
        return (string) self::retaColumn('key_points')['new_item'];
    }

    private function update(string $slug, callable $apply): void
    {
        $posts = EducationPost::query()
            ->whereRaw('LOWER(slug) = ?', [$slug])
            ->orderBy('id')
            ->get();

        if ($posts->count() !== 1) {
            $this->report[$slug] = $posts->isEmpty() ? 'missing' : 'collision';
            if ($posts->count() > 1) {
                $stored = $posts->map(fn (EducationPost $post) => $post->slug)->implode(', ');
                $this->command?->warn('October 2026 encyclopedia update: skipped '.$slug.' slug collision ('.$stored.').');
            }

            return;
        }

        /** @var EducationPost $post */
        $post = $posts->first();
        $allowed = $this->columnsFor($slug);
        $before = $this->snapshot($post, $allowed);
        $apply($post);
        if ($this->snapshot($post, $allowed) === $before) {
            $this->report[$slug] = 'unchanged';
            $this->command?->info('October 2026 encyclopedia update: '.$post->slug.' already current.');

            return;
        }

        // saveQuietly skips the model's JSON unwrapping hook, which would
        // otherwise mark every JSON column dirty and rewrite it.
        $post->saveQuietly();
        $this->report[$slug] = 'updated';
        $this->command?->info('October 2026 encyclopedia update: updated '.$post->slug.'.');
    }

    private function applyBpc(EducationPost $post): void
    {
        $sections = $this->bpcSections($post->regulatory_subsections);
        if ($sections !== $post->regulatory_subsections) {
            $post->regulatory_subsections = $sections;
        }

        $note = $this->replacedNote($post->regulatory_important_note, self::oldBpcNote(), self::newBpcNote(), self::BPC_NOTE_GUARD);
        if ($note !== $post->regulatory_important_note) {
            $post->regulatory_important_note = $note;
        }

        $points = $this->withKeyPoint($post->key_points, self::BPC_KEY_POINT_PREFIX, self::bpcKeyPoint());
        if ($points !== $post->key_points) {
            $post->key_points = $points;
        }
    }

    private function applyRetatrutide(EducationPost $post): void
    {
        $sections = $this->retaSections($post->human_use_subsections);
        if ($sections !== $post->human_use_subsections) {
            $post->human_use_subsections = $sections;
        }

        $note = $this->replacedNote($post->regulatory_important_note, self::oldRetaNote(), self::newRetaNote(), null);
        if ($note !== $post->regulatory_important_note) {
            $post->regulatory_important_note = $note;
        }

        $points = $this->withKeyPoint($post->key_points, self::RETA_KEY_POINT_PREFIX, self::retaKeyPoint());
        if ($points !== $post->key_points) {
            $post->key_points = $points;
        }
    }

    /**
     * @return list<string>
     */
    private function columnsFor(string $slug): array
    {
        if ($slug === self::BPC_SLUG) {
            return ['regulatory_subsections', 'regulatory_important_note', 'key_points'];
        }

        return ['human_use_subsections', 'regulatory_important_note', 'key_points'];
    }

    private function bpcSections(mixed $current): mixed
    {
        if ($current === null) {
            $sections = [];
        } elseif (! is_array($current) || ! array_is_list($current)) {
            return $current;
        } else {
            $sections = $current;
        }

        $old = self::oldBpcUnitedStates();
        $new = self::newBpcUnitedStates();
        foreach ($sections as $index => $section) {
            if (! is_array($section) || ($section['title'] ?? null) !== self::BPC_US_TITLE) {
                continue;
            }
            $entries = $section['entries'] ?? null;
            if (! is_array($entries) || ! array_key_exists(0, $entries) || ! is_array($entries[0])) {
                continue;
            }
            if (($entries[0]['value'] ?? null) !== $old) {
                continue;
            }
            $entries[0]['value'] = $new;
            $sections[$index]['entries'] = $entries;
        }

        foreach ($sections as $section) {
            if ($this->titleStartsWith($section, self::BPC_COMPOUNDING_TITLE_PREFIX)) {
                return $sections;
            }
        }

        array_splice($sections, self::BPC_INSERT_INDEX, 0, [self::bpcCompoundingSubsection()]);

        return $sections;
    }

    private function retaSections(mixed $current): mixed
    {
        if ($current === null) {
            $sections = [];
        } elseif (! is_array($current) || ! array_is_list($current)) {
            return $current;
        } else {
            $sections = $current;
        }

        foreach ($sections as $section) {
            if ($this->titleStartsWith($section, self::RETA_SUBSECTION_TITLE_PREFIX)) {
                return $current;
            }
        }

        $sections[] = self::retaSubsection();

        return $sections;
    }

    private function replacedNote(mixed $current, string $old, string $new, ?string $skipIfContains): mixed
    {
        if (! is_string($current)) {
            return $current;
        }
        if ($skipIfContains !== null && str_contains($current, $skipIfContains)) {
            return $current;
        }
        if ($current !== $old) {
            return $current;
        }

        return $new;
    }

    private function withKeyPoint(mixed $current, string $prefix, string $item): mixed
    {
        if ($current === null) {
            $points = [];
        } elseif (! is_array($current) || ! array_is_list($current)) {
            return $current;
        } else {
            $points = $current;
        }

        foreach ($points as $point) {
            if (is_string($point) && str_starts_with($point, $prefix)) {
                return $current;
            }
        }

        $points[] = $item;

        return $points;
    }

    private function titleStartsWith(mixed $section, string $prefix): bool
    {
        if (! is_array($section)) {
            return false;
        }
        $title = $section['title'] ?? null;

        return is_string($title) && str_starts_with($title, $prefix);
    }

    /**
     * @param  list<string>  $fields
     */
    private function snapshot(EducationPost $post, array $fields): string
    {
        return json_encode($post->only($fields), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    private static function assertPayload(): void
    {
        $unitedStates = self::newBpcUnitedStates();
        $compounding = json_encode(self::bpcCompoundingSubsection(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        $reta = json_encode(self::retaSubsection(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        foreach ([
            'OIRA listing as indexed; live page unavailable 16 Oct 2026' => $compounding,
            'RIN: 0910-ZD94' => $compounding,
            'Reuters reported on 15 October 2026' => $compounding,
            'no legal basis for compounding pharmacies' => self::oldBpcUnitedStates(),
            'U.S. regulatory status — compounding review' => $unitedStates,
            self::BPC_NOTE_GUARD => self::newBpcNote(),
            '44 reports in which retatrutide appears as a drug name' => $reta,
            'doi:10.1001/jamanetworkopen.2026.38929' => $reta,
            '862 retatrutide exposures' => $reta,
        ] as $needle => $haystack) {
            if (! str_contains($haystack, $needle)) {
                throw new RuntimeException('October 2026 encyclopedia field payload failed its content check.');
            }
        }
        if (! str_starts_with(self::bpcKeyPoint(), self::BPC_KEY_POINT_PREFIX)
            || ! str_starts_with(self::retaKeyPoint(), self::RETA_KEY_POINT_PREFIX)
            || ! str_starts_with((string) self::retaSubsection()['title'], self::RETA_SUBSECTION_TITLE_PREFIX)) {
            throw new RuntimeException('October 2026 encyclopedia field payload failed its content check.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function bpcColumn(string $key): array
    {
        $column = self::bpcPayload()['columns'][$key] ?? null;
        if (! is_array($column)) {
            throw new RuntimeException('BPC-157 field payload is missing '.$key.'.');
        }

        return $column;
    }

    /**
     * @return array<string, mixed>
     */
    private static function retaColumn(string $key): array
    {
        $column = self::retaPayload()['columns'][$key] ?? null;
        if (! is_array($column)) {
            throw new RuntimeException('Retatrutide field payload is missing '.$key.'.');
        }

        return $column;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function bpcNewSections(): array
    {
        $sections = self::bpcColumn('regulatory_subsections')['new_value'] ?? null;
        if (! is_array($sections)) {
            throw new RuntimeException('BPC-157 regulatory subsections are missing from the field payload.');
        }

        return $sections;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array<string, mixed>
     */
    private static function sectionByTitle(array $sections, string $title): array
    {
        foreach ($sections as $section) {
            if (is_array($section) && ($section['title'] ?? null) === $title) {
                return $section;
            }
        }

        throw new RuntimeException('Encyclopedia field payload is missing the '.$title.' subsection.');
    }

    /**
     * @return array<string, mixed>
     */
    private static function bpcPayload(): array
    {
        return self::$bpcPayload ??= self::decode(self::BPC_JSON);
    }

    /**
     * @return array<string, mixed>
     */
    private static function retaPayload(): array
    {
        return self::$retaPayload ??= self::decode(self::RETA_JSON);
    }

    /**
     * @return array<string, mixed>
     */
    private static function decode(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Encyclopedia field payload is not an object.');
        }

        return $decoded;
    }
}
