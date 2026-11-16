<?php

use Database\Seeders\BlogOgTriumph4November16Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * retatrutide-side-effects-trial-data: og_description names the TRIUMPH-4
 * knee-OA paper (NEJM). One guarded column (blogs.seo_og_description).
 * Drop this migration, its seeder and test, and the catalog line if Meta
 * Optimizer rejects the wording. down() does not restore the old value.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new BlogOgTriumph4November16Seeder)->run();
    }

    public function down(): void
    {
        // Previous wording is not restored.
    }
};
