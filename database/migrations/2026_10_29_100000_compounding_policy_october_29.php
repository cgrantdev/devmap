<?php

use Database\Seeders\EncyclopediaCompoundingPolicyOctober29Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * HHS draft interim 503A peptide enforcement-discretion policy, published
 * for comment (FR Doc. 2026-22192), as of 29 October 2026: BPC-157,
 * TB-500, KPV, epitalon, MOTS-c, semax, the BPC-157 / TB-500, GLOW and
 * KLOW blends, and the peptide-legality guide.
 *
 * Guarded: each post is resolved with App\Support\EncyclopediaPostMatch
 * (exactly 1 category and 1 linked post) and the guide by slug (exactly
 * 1 row); one DB::transaction per slug; a column is written only when its
 * normalized stored value equals a listed old value (live production or
 * main), and skipped when already current. Legacy unlinked rows are never
 * written. A mismatch logs a WARNING and does not throw.
 *
 * Source payload sha256 2fb41cac2695e5ed40ad9e54478aff6f85aeb9d0b2cb0bc148bc46dc7a001ea9.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaCompoundingPolicyOctober29Seeder)->run();
    }

    public function down(): void
    {
        // Previous field values are not restored.
    }
};
