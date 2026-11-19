<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO changelog rows for c007ce5 (strict RUO wording, pass 2). Visible
 * at /admin/seo/changelog.
 */
return new class extends Migration
{
    private const SHA = 'c007ce5';

    private const ROWS = [
        [
            'title' => '/calculator: on-page wording changed from dosing to research quantities',
            'summary' => 'The "Dosage" tab is now "Research Quantity" ("Quantity" on phones). "Dose per injection" is now "Amount per aliquot", and "Doses per vial"/"Cost per dose" are now per aliquot. Human-use lines were removed ("how often you take it", co-administering). FAQ Q1 changed from "How do I calculate peptide dosage in insulin syringe units?" to "How do I convert a peptide research quantity into insulin syringe units?". The FAQPage JSON-LD now matches the visible FAQ word for word (11 of 24 strings had drifted). The HowTo step and WebApplication description say "per aliquot" and "research quantity". Titles, metas and URL parameters are unchanged.',
            'category' => 'content',
        ],
        [
            'title' => 'Encyclopedia, compare and catalog copy: dosing disclaimers and protocol/stack/cycle wording removed',
            'summary' => 'Sentences such as "No dose is given.", "They are not a protocol.", "No therapeutic protocol is described." and "Not medical advice, and not a dosing guide." were deleted or shortened across encyclopedia entries (bullets that were only that sentence removed). Regimen uses of protocol, stack and cycle were reworded (e.g. "Combination Protocols" is now "Combination Research"). Dose words that report what a trial gave were kept. Fifteen catalog meta descriptions lost a trailing "No dosing is given here." sentence, and the encyclopedia hub default meta now reads "mechanisms, safety, and areas of research".',
            'category' => 'meta',
        ],
        [
            'title' => 'Blogs and guides: dosing and stacking disclaimers reworded; two legal passages removed',
            'summary' => 'Lines like "It is not medical advice, gives no dosing, and does not..." and "stacked online as if..." were reworded in about 25 posts. FAQ "Which one should someone take?" (tesamorelin vs sermorelin) is now "Which one is better?". retatrutide-vs-semaglutide lost the FDA classification court passage, the "challenging in court" FAQ sentence and two court references (citations renumbered). bpc-157-vs-ghk-cu lost the FDA warning-letter paragraph and its reference.',
            'category' => 'content',
        ],
    ];

    public function up(): void
    {
        foreach (self::ROWS as $row) {
            $exists = DB::table('seo_changes')
                ->where('title', $row['title'])
                ->where('happened_at', '2026-11-20')
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('seo_changes')->insert($row + [
                'happened_at' => '2026-11-20',
                'actor' => 'colin',
                'commit_sha' => self::SHA,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('seo_changes')
            ->where('happened_at', '2026-11-20')
            ->whereIn('title', array_column(self::ROWS, 'title'))
            ->delete();
    }
};
