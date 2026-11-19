<?php

use Database\Seeders\RuoCopyCleanupNovember20bSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Strict RUO wording, last phrases found by the 20 Nov live re-scan:
 * the /products OG description ("purity, price, and dose across…"),
 * CJC-1295 "invent clinical protocols" and triptorelin "assisted
 * reproductive technology protocols". Re-runs the pass-2 seeder; rows
 * already clean are left unchanged.
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
