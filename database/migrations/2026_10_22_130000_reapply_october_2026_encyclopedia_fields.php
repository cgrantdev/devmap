<?php

use Database\Seeders\EncyclopediaFieldReapplyOctober22Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Re-apply the retatrutide, GHK-Cu, and cagrilintide encyclopedia fields
 * from 2026_10_16_120000 and 2026_10_19_130000.
 *
 * Those migrations looked education_posts up by slug. The page renders
 * the category's educationPost, so the writes never became visible.
 * This seeder resolves the category, then the linked post. A missing or
 * colliding slug logs a warning and writes nothing. down() does not
 * restore the previous copy.
 *
 * Source payload sha256 19ff7f45b21a5819d547cea0855d0327d633b2f288895d493494de6984956ef3.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaFieldReapplyOctober22Seeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};
