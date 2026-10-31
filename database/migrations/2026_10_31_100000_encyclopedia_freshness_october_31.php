<?php

use Database\Seeders\EncyclopediaFreshnessOctober31Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Freshness update, 31 October 2026: the two 503A legality guides
 * (FR Doc. 2026-22192 at 91 FR 68526, comment deadline 13 November 2026,
 * PSM citizen petition, AJMC state and February PCAC reporting, stale
 * "scheduled" / "forthcoming" lines), orforglipron ACHIEVE-4 and
 * eloralintide Phase 3 starts (company-reported).
 *
 * Writes only where the stored value equals origin/main's (= live) value;
 * every column logs "updated", "already current" or a "guard mismatch —
 * nothing written" WARNING through App\Support\GuardedFieldWriter. A
 * second migrate writes nothing. down() does not restore the previous copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaFreshnessOctober31Seeder)->run();
    }

    public function down(): void
    {
        // Previous field values are not restored.
    }
};
