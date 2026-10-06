<?php

use Database\Seeders\RetatrutideEncyclopediaFaqSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Replaces the Retatrutide encyclopedia FAQ that still said Phase 3 results
 * were "expected in 2025-2026" after TRIUMPH-1 and TRIUMPH-2 published on
 * 29 Sep 2026. The seeder no-ops when the Retatrutide category or post is
 * absent, and a second migrate does not write again.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RetatrutideEncyclopediaFaqSeeder)->run();
    }

    public function down(): void
    {
        // The pre-refresh answer said Phase 3 results were still expected
        // in 2025-2026. That claim is not restored.
    }
};
