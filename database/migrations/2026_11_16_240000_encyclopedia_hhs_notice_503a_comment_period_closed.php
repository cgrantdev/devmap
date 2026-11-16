<?php

use Database\Seeders\EncyclopediaHhsNotice503aStatusNovember16Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * BPC-157, TB-500, epitalon and KPV encyclopedia entries: the HHS 503A
 * notice paragraph moves from "scheduled for publication on 30 October
 * 2026" to published (FR Doc. 2026-22192, 30 October 2026), comment
 * period closed 13 November 2026 11:59 pm ET, nothing final as of
 * 16 November 2026, the wording of 2026_11_16_220000 (MOTS-c). One
 * column (regulatory_subsections) per entry, guarded; bpc-157 is accepted
 * before or after 2026_11_16_210000. A second migrate writes nothing.
 * down() does not restore the previous wording.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaHhsNotice503aStatusNovember16Seeder)->run();
    }

    public function down(): void
    {
        // Previous wording is not restored.
    }
};
