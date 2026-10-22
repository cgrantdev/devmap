<?php

use Database\Seeders\EncyclopediaFieldUpdatesOctober22Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Guarded encyclopedia field updates for 22 October 2026: the BPC-157
 * regulatory re-apply, the BPC-157 FAQ replacement, and the Australia
 * subsection plus references on BPC-157, GHK-Cu, TB-500, retatrutide,
 * and CJC-1295.
 *
 * Each post is resolved the way the encyclopedia page resolves it.
 * A missing category, a missing post, or a duplicate writes nothing
 * and does not throw, so a later migration in the same deploy still runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaFieldUpdatesOctober22Seeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia field values are not restored.
    }
};
