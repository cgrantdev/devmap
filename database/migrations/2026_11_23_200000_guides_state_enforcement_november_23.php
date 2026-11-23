<?php

use Database\Seeders\EducationalStateEnforcementNovember23Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * State-enforcement update of two guides for the 23 Nov 2026 record.
 *
 * Slugs, in order: peptide-legality-fda-ruo-compounding,
 * compounded-vs-ruo-peptides.
 *
 * Read times that move: legality guide 25 to 31, compounded guide
 * 13 to 15. The compounded guide's "Is the policy final?" FAQ answer
 * moves from 16 Nov to 23 Nov 2026 in the markdown and in faq(), so
 * syncOnly rewrites seo_schema to match.
 *
 * Each slug is one row by LOWER(TRIM(slug)). Content whose sha256 is
 * the 6fd0905a publisher render (equal to the live content on 23 Nov
 * 2026) is passed to syncOnly. Content that already equals the current
 * render is left alone. Drift, a missing row, duplicate slugs or an
 * admin edit to another catalog-owned column log a guard-mismatch
 * WARNING and are not written. A second migrate writes nothing. down()
 * does not restore the previous copy, matching 2026_11_16_200000.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EducationalStateEnforcementNovember23Seeder)->run();
    }

    public function down(): void
    {
        // The published rows stay. Previous wording is not restored.
    }
};
