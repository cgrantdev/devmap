<?php

use Database\Seeders\EncyclopediaFramingSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Production encyclopedia pages read education_posts. This migration
 * publishes corrected class language for the framed compounds. The seeder
 * no-ops for categories that are not in the database, and it does not
 * publish KPV.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaFramingSeeder())->run();
    }

    public function down(): void
    {
        (new EncyclopediaFramingSeeder())->rollback();
    }
};
