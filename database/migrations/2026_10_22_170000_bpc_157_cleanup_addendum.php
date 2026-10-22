<?php

use Database\Seeders\EncyclopediaBpcCleanupAddendumSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * BPC-157 cleanup addendum (22 Oct 2026) plus TB-500 formula, half-life
 * and conclusion fixes, with Content QA must-fixes M1–M10 applied.
 *
 * Guarded: each post is resolved with App\Support\EncyclopediaPostMatch
 * (exactly 1 category and 1 linked post), one DB::transaction per slug,
 * a column is written only when its normalized stored value equals a
 * listed old value, and skipped when it is already current. Legacy
 * unlinked rows are never written. A mismatch logs a WARNING and does
 * not throw, so later migrations still run.
 *
 * Source payload sha256 73c4a16b71d373b5af73bddd2d48fba36c0fc2c2e17a0df0772f47c3f0019c45.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaBpcCleanupAddendumSeeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};
