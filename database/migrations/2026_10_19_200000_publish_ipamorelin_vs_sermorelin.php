<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Publish the Content-QA-passed ipamorelin vs sermorelin comparison.
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows.
     * Rolling back removes only this new slug.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::where('slug', 'ipamorelin-vs-sermorelin')->delete();
    }
};
