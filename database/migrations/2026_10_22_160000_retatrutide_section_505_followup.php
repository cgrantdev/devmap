<?php

use Database\Seeders\EncyclopediaRetatrutideS505Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Retatrutide section 505 follow-up (22 October 2026).
 *
 * Resolves the post through EncyclopediaPostMatch: one product category
 * by LOWER(TRIM(slug)), then that category's education post. A second
 * migrate does not write again. down() does not restore the previous copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaRetatrutideS505Seeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};
