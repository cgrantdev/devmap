<?php

use Database\Seeders\EncyclopediaRetatrutideOsaLitigationSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Retatrutide OSA trials + 2026 seller lawsuits (29 October 2026).
 *
 * Resolves the post through EncyclopediaPostMatch: one product category
 * by LOWER(TRIM(slug)), then that category's education post. Writes only
 * when each column still equals its guard value; a second migrate does not
 * write again. down() does not restore the previous copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaRetatrutideOsaLitigationSeeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};
