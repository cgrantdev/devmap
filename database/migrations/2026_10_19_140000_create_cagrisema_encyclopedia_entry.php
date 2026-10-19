<?php

use Database\Seeders\EncyclopediaCategoryCreateSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Fills the CagriSema encyclopedia post from the shell draft.
 *
 * No category matches LOWER(slug) cagrisema today, and the entry has no
 * aliases, so this creates an inactive category. /encyclopedia/cagrisema
 * stays 404 until someone activates it. An existing cagrisema row is
 * reused with is_active left as stored. Pemvidutide and hexarelin are
 * already filled, so this seeder run leaves those entries alone. The
 * seeder does not create products, vendors, prices, compare entries, or
 * aliases. A second migrate is a no-op because a filled overview is skipped.
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
