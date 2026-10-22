<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Publish the eloralintide vs petrelintide comparison.
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows.
     * Rolling back removes only the new eloralintide-vs-petrelintide slug.
     * The EloraTZP status note on /compare/eloralintide-vs-tirzepatide
     * lives in code, not in this row.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::where('slug', 'eloralintide-vs-petrelintide')->delete();
    }
};
