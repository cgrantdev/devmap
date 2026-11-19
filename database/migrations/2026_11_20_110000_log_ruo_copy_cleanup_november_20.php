<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SEO changelog rows for 2357adc (strict RUO wording pass). Visible at
 * /admin/seo/changelog.
 */
return new class extends Migration
{
    private const SHA = '2357adc';

    private const ROWS = [
        [
            'title' => 'Encyclopedia: "Potential Therapeutic Applications" is now "Further Areas of Research"',
            'summary' => 'Strict RUO wording. The section heading and its contents-list entry on every encyclopedia page now read "Further Areas of Research". The anchor (#potential-applications) is unchanged.',
            'category' => 'content',
        ],
        [
            'title' => 'Encyclopedia: Regulatory Status trimmed to approval and anti-doping status (30 entries)',
            'summary' => 'Court cases, lawsuits, enforcement and seizure items, Health Canada advisories, the Senate letter, expanded-access routes, compounding-policy detail and grey/black-market wording were removed from 30 entries, with the references that only supported them. Key points, overviews, conclusions and other fields that repeated that material were trimmed too. Fields edited after 20 Nov were left as they were.',
            'category' => 'content',
        ],
        [
            'title' => '/blog/retatrutide-testing-coa-limits: H1, SEO title and meta say "Research-Use Labels"',
            'summary' => 'H1, SEO title, OG title, meta and OG descriptions changed "Grey-Market Labels" to "Research-Use Labels". The grey-market FAQ and key point were reworded, and a sentence about Lilly lawsuits against black-market sellers was removed. The URL is unchanged.',
            'category' => 'meta',
        ],
        [
            'title' => 'Blogs and guides: grey-area and grey-market wording replaced',
            'summary' => 'The line "Peptidemap is an RUO / grey-area peptide comparison publisher" and similar lines in about 20 posts now say "research-use-only (RUO)". Grey-market phrases in the COA verification post (/blog/how-to-verify-a-peptide-vendor-certificate-of-analysis) were reworded, and the "Grey market" tag was removed from blog posts.',
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
