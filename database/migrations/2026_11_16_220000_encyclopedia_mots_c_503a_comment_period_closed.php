<?php

use Database\Seeders\EncyclopediaMotsC503aStatusNovember16Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * MOTS-c encyclopedia: the 503A notice paragraph moves from "scheduled
 * for publication on 30 October 2026" to published (FR Doc. 2026-22192,
 * 30 October 2026), comment period closed 13 November 2026 11:59 pm ET,
 * nothing final as of 16 November 2026. One column
 * (regulatory_subsections), guarded; runs after 2026_11_16_210000 and
 * accepts the column either before or after that migration. A second
 * migrate writes nothing. down() does not restore the previous wording.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaMotsC503aStatusNovember16Seeder)->run();
    }

    public function down(): void
    {
        // Previous wording is not restored.
    }
};
