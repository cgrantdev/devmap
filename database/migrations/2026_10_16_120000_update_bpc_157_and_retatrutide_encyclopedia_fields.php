<?php

use Database\Seeders\Bpc157RetatrutideOctober2026Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Field-level update for the BPC-157 and retatrutide encyclopedia posts.
 *
 * The seeder matches LOWER(slug) and writes only when exactly one post
 * matches. A second migrate does not write again. Other posts and other
 * columns are left as stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new Bpc157RetatrutideOctober2026Seeder)->run();
    }

    public function down(): void
    {
        // The previous United States compounding paragraph and the previous
        // regulatory notes are not restored.
    }
};
