<?php

use Database\Seeders\EncyclopediaFieldUpdatesOctober19Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Guarded field updates for the cagrilintide, GHK-Cu, and BPC-157
 * encyclopedia posts (19 October 2026).
 *
 * The seeder matches LOWER(slug) and writes only when exactly one post
 * matches. A second migrate does not write again. BPC-157 guards are the
 * values merged by 2026_10_16_120000, so on a database that still has the
 * pre-#39 copy those column ops skip until that migration has run.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaFieldUpdatesOctober19Seeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};
