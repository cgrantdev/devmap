<?php

use Database\Seeders\EncyclopediaOrfoRetaOctober22Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Orforglipron CHMP opinion and retatrutide Senate letter / section 505
 * (22 October 2026).
 *
 * The seeder resolves each post the way EncyclopediaController::showArticle
 * does, through EncyclopediaPostMatch: one product category by
 * LOWER(TRIM(slug)), then that category's educationPost. A second migrate
 * does not write again. down() does not restore the previous copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaOrfoRetaOctober22Seeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};
