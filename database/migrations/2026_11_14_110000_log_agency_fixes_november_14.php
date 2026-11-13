<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO changelog rows for the Advantage Collective fixes shipped in 48cfa00
 * (report tested Nov 12, 2026). Visible at /admin/seo/changelog.
 */
return new class extends Migration
{
    private const SHA = '48cfa00';

    private const ROWS = [
        [
            'title' => 'Compound SEO editor: Peptides tab now saves',
            'summary' => 'The editor at /admin/seo/categories mapped the Peptides block to a key the form never sends, so every save blanked the Peptides title, meta, H1, intro and FAQs while Compare saved. Fixed. Each tab now names the page and URL it edits, and validation errors show on the field.',
            'category' => 'technical',
        ],
        [
            'title' => '/compare/{slug}: agency intro and FAQs replace the defaults',
            'summary' => 'When a Compare intro is set, the default description (encyclopedia summary, or the retatrutide price intro) is hidden. When Compare FAQs are set, the generated FAQs are dropped from the page and the FAQPage JSON-LD, so both carry the same count (BPC-157: 9, was 13 in schema). /product/{slug} already showed only agency FAQs and no default description.',
            'category' => 'content',
        ],
        [
            'title' => 'Intro links styled as links',
            'summary' => 'Links in agency intros on /compare/{slug} and /product/{slug} now use the site link style (blue, underlined). Lists in intros keep their bullets.',
            'category' => 'interlinks',
        ],
        [
            'title' => 'Calculator chip renamed to Peptide calculator',
            'summary' => 'The cross-link pill on /product/{slug} reads "Peptide calculator" (link label "{name} peptide calculator"), matching the footer.',
            'category' => 'interlinks',
        ],
        [
            'title' => 'Encyclopedia: Research Use Only box on FDA-approved compounds',
            'summary' => 'Entries for compounds sold as FDA-approved branded medicines now say so instead of "not approved for human use by any regulatory agency": semaglutide, tirzepatide, tesamorelin, bremelanotide (PT-141), setmelanotide, teriparatide, teduglutide, oxytocin, hCG, HMG, triptorelin, HGH 191AA, Melanotan-I, methylene blue, L-carnitine. Elamipretide keeps its Forzinity notice. The default box text no longer misspells Peptidemap.',
            'category' => 'content',
        ],
        [
            'title' => 'Encyclopedia: Areas of Research note is per entry; real last-updated date',
            'summary' => 'The fixed note saying all findings are from preclinical animal and cell studies is removed. Each entry has an optional "Research note" field instead, shown only when filled in. The footer shows the date the entry was last saved instead of a fixed "February 2026".',
            'category' => 'content',
        ],
        [
            'title' => 'Encyclopedia: hidden FAQ JSON-LD removed; visible FAQ field added',
            'summary' => '68 entries sent FAQPage JSON-LD with no visible FAQ, including three outdated tirzepatide answers. Those FAQs moved to an archive (shown read-only in the editor, not published). FAQPage JSON-LD is now built only from FAQs shown on the page. The encyclopedia editor has a FAQ field (question and answer, up to 12) that renders on the page.',
            'category' => 'schema',
        ],
    ];

    public function up(): void
    {
        foreach (self::ROWS as $row) {
            $exists = DB::table('seo_changes')
                ->where('title', $row['title'])
                ->where('happened_at', '2026-11-14')
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('seo_changes')->insert($row + [
                'happened_at' => '2026-11-14',
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
            ->where('happened_at', '2026-11-14')
            ->whereIn('title', array_column(self::ROWS, 'title'))
            ->delete();
    }
};
