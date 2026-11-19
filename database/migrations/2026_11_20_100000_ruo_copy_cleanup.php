<?php

use Database\Seeders\RuoCopyCleanupNovember20Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Strict RUO language pass (20 Nov 2026): encyclopedia regulatory
 * sections reduced to approval and anti-doping status; court cases,
 * enforcement, seizures and grey/black-market wording removed from the
 * encyclopedia, blogs and guides. See RuoCopyCleanupNovember20Seeder.
 *
 * Safe to run late or twice: encyclopedia fields are written only while
 * they still match the 20 Nov text, and the blog/guide rules only match
 * the old wording. down() does not restore the removed copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RuoCopyCleanupNovember20Seeder)->run();
    }

    public function down(): void
    {
        // The removed copy is not restored.
    }
};
