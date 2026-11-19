<?php

use Database\Seeders\RuoCopyCleanupNovember20bSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Strict RUO wording, pass 2 follow-up (20 Nov 2026). The live check after
 * 2026_11_20_120000 found the same dosing disclaimers in fields that pass
 * did not scan: encyclopedia meta descriptions (education_posts
 * seo_description / seo_og_description), product category descriptions
 * (shown on product pages and as the encyclopedia subtitle fallback) and
 * seo_pages metas (e.g. /products "…and dose"). The seeder now covers
 * those and three more phrases; running it again leaves already-clean
 * rows unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RuoCopyCleanupNovember20bSeeder)->run();
    }

    public function down(): void
    {
        // The removed copy is not restored.
    }
};
