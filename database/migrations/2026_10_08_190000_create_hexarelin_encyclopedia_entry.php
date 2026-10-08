<?php

use Database\Seeders\EncyclopediaCategoryCreateSeeder;
use Illuminate\Database\Migrations\Migration;

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
        (new EncyclopediaCategoryCreateSeeder)->run();
    }

    public function down(): void
    {
        // The encyclopedia row stays, active or not. See the class comment.
    }
};
