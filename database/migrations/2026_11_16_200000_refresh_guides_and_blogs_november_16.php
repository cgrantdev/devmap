<?php

use Database\Seeders\EducationalFreshnessNovember16Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Refresh two guides and six blogs for the 16 Nov 2026 record.
 *
 * Slugs, in order: compounded-vs-ruo-peptides,
 * peptide-legality-fda-ruo-compounding,
 * bpc-157-vs-tb-500-evidence, retatrutide-side-effects-trial-data,
 * retatrutide-vs-semaglutide, eloralintide-tirzepatide-vs-retatrutide,
 * hhs-503a-peptide-enforcement-discretion-2026, bpc-157-vs-ghk-cu.
 *
 * Read times that move: compounded guide 11 to 13, legality guide
 * 24 to 25, BPC-157 vs TB-500 14 to 19, side-effects 20 to 21,
 * EloraTZP blog 6 to 7. The retatrutide vs semaglutide, HHS 503A
 * and BPC-157 vs GHK-Cu read times stay 19, 4 and 10.
 *
 * Each slug is one row by LOWER(TRIM(slug)). Content whose sha256
 * is the 1a9fb77 publisher render is passed to syncOnly. Content
 * that already equals the current render is left alone. Drift, a
 * missing row or duplicate slugs log a guard-mismatch WARNING and
 * are not written. syncOnly is called once, with only the slugs
 * that passed. A second migrate writes nothing. down() does not
 * restore the previous copy, matching 2026_10_31_200000.
 *
 * Catalog fields other than read time, and the compounded guide
 * FAQ, are unchanged. syncOnly still rewrites the rest of a passing
 * row from that same catalog. Blog images stay when they are not
 * placeholders. No other column is widened.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EducationalFreshnessNovember16Seeder)->run();
    }

    public function down(): void
    {
        // The published rows stay. Previous wording is not restored.
    }
};
