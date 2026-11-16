<?php

use Database\Seeders\BlogOgTriumph4November16Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * retatrutide-side-effects-trial-data: og_description names the NEJM
 * knee-OA paper (TRIUMPH-4 + TRIUMPH-1-OA). One guarded column
 * (blogs.seo_og_description). Meta Optimizer signed off on this wording.
 * down() does not restore the old value.
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
