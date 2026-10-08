<?php

use App\Content\EducationalContentPublisher;
use Database\Seeders\LowercaseEncyclopediaLinksSeeder;
use Database\Seeders\RetatrutideEncyclopediaFaqSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Re-applies the Retatrutide FAQ refresh now that it matches LOWER(slug),
 * republishes educational posts whose encyclopedia links were lowercased,
 * and rewrites those same links on encyclopedia posts that were already
 * filled. Each step upserts in place. A second migrate does not create rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new RetatrutideEncyclopediaFaqSeeder)->run();
        (new LowercaseEncyclopediaLinksSeeder)->run();
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        // The TRIUMPH-1 FAQ and the lowercased links are not restored.
    }
};
