<?php

use Database\Seeders\RuoCopyCleanupNovember20bSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Strict RUO wording, pass 2 (20 Nov 2026): dosing-disclaimer sentences,
 * regimen uses of protocol/stack/cycle, and the court and warning-letter
 * passages in two blog posts. See RuoCopyCleanupNovember20bSeeder.
 *
 * Safe to run late or twice: every rule only matches the old wording.
 * down() does not restore the removed copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RuoCopyCleanupNovember20bSeeder)->run();
    }

    public function down(): void
    {
        // The removed copy is not restored.
    }
};
