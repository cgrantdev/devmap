<?php

use Database\Seeders\EncyclopediaCategoryCreateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fills the hexarelin encyclopedia post from the shell draft.
 *
 * A category that already matches LOWER(slug) hexarelin, or a CategoryAlias
 * for hexarelin or examorelin, is reused with is_active left exactly as
 * stored. A database with no match gets a new inactive category
 * (create_inactive), so /encyclopedia/hexarelin and /compare/hexarelin stay
 * 404 until someone turns the category on. The seeder does not create
 * products. Pemvidutide is already filled, so this second seeder run leaves
 * that entry alone. A second migrate is a no-op because a filled overview
 * is skipped.
 *
 * down() does not delete the row. A reused category cannot be told apart
 * from one this migration created, and rolling back must not remove a
 * category that already existed or flip its visibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hexarelin's half_life string exceeds the original VARCHAR(255)
        // cap. A later migration (2026_10_20_130000) alters this column to
        // TEXT, but it runs AFTER this one — so without the alter hoisted
        // here, the hexarelin insert blows up with "data too long" and
        // every subsequent pending migration stays stuck. Alter first,
        // then seed. Idempotent — repeat ALTERs to TEXT are no-ops.
        try {
            DB::statement('ALTER TABLE education_posts MODIFY half_life TEXT NULL');
        } catch (\Throwable $e) { /* already TEXT or table missing: ignore */ }

        (new EncyclopediaCategoryCreateSeeder)->run();
    }

    public function down(): void
    {
        // The encyclopedia row stays, active or not. See the class comment.
    }
};
