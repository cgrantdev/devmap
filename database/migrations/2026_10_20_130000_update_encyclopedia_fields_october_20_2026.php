<?php

use Database\Seeders\EncyclopediaFieldUpdatesOctober20Seeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Guarded field updates for the NAD+ and Sermorelin encyclopedia posts
 * (20 October 2026).
 *
 * The seeder resolves each post the way EncyclopediaController::showArticle
 * does: one product category by LOWER(TRIM(slug)), then that category's
 * educationPost. A second migrate does not write again.
 *
 * education_posts.half_life was added as VARCHAR(255). The Sermorelin
 * replacement is 284 characters, which MySQL strict mode rejects. Widen
 * the column to TEXT before the seeder runs. SQLite does not enforce
 * VARCHAR length and has no MODIFY COLUMN, so the tests need no rewrite.
 * down() does not shrink the column, because shrinking would truncate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE education_posts MODIFY half_life TEXT NULL');
        }

        (new EncyclopediaFieldUpdatesOctober20Seeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};
