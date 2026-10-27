<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen education_posts.half_life from VARCHAR(255) to TEXT so the
 * hexarelin seeder (next migration at 190000) can insert its long
 * INN+WADA-status string without truncating. A later migration at
 * 2026_10_20_130000 ALREADY contains this same ALTER — but it never
 * runs because hexarelin at 190000 fails first and halts the chain.
 *
 * Standalone so it is executed on its own, outside of
 * EncyclopediaCategoryCreateSeeder-related code paths. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('education_posts')) return;
        try {
            DB::statement('ALTER TABLE education_posts MODIFY half_life TEXT NULL');
        } catch (\Throwable $e) { /* already TEXT: ignore */ }
    }

    public function down(): void
    {
        // No-op. Reverting to VARCHAR(255) would truncate real data.
    }
};
