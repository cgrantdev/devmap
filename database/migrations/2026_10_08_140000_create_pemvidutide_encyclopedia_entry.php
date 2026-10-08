<?php

use Database\Seeders\EncyclopediaCategoryCreateSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Creates the pemvidutide encyclopedia category and published post when
 * neither the slug nor an ALT-801 / pemvidutide alias already points at
 * one. The seeder does not create products. A second migrate is a no-op
 * because the seeder skips a category whose overview is already filled.
 *
 * down() does not delete the row. A reused category cannot be told apart
 * from one this migration created, and rolling back must not remove a
 * category that already existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaCategoryCreateSeeder)->run();
    }

    public function down(): void
    {
        // The published encyclopedia row stays. See the class comment.
    }
};
