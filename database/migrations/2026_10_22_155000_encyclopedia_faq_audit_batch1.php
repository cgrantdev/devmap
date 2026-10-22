<?php

use Database\Seeders\EncyclopediaFaqAuditBatch1Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * FAQ audit batch 1 (22 October 2026): TB-500, Ipamorelin, CJC-1295,
 * AOD-9604 and Kisspeptin.
 *
 * The seeder resolves each post through EncyclopediaPostMatch: one
 * product category by LOWER(TRIM(slug)), then that category's
 * educationPost. A second migrate does not write again. down() does
 * not restore the previous FAQ copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaFaqAuditBatch1Seeder)->run();
    }

    public function down(): void
    {
        // Previous encyclopedia FAQ values are not restored.
    }
};
