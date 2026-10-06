<?php

use Database\Seeders\ElamipretideForzinitySeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Deepens the elamipretide encyclopedia with Forzinity accelerated-approval
 * wording after the empty-shell fill. The seeder no-ops when the category
 * or post is absent, and a second migrate does not write again.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new ElamipretideForzinitySeeder)->run();
    }

    public function down(): void
    {
        // The previous regulatory note included an unverified Category 2 /
        // PCAC / 503A Bulks List claim. That sentence is not restored.
    }
};
