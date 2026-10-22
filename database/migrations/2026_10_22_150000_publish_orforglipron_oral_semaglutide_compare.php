<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Publish the orforglipron vs oral semaglutide educational compare page.
     *
     * The public URL is /compare/orforglipron-vs-oral-semaglutide.
     * The stored slug is orforglipron-vs-oral-semaglutide-compare so
     * this row does not replace the ACHIEVE-3 blog.
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts every catalog slug, including the ACHIEVE-3 article
     * orforglipron-vs-oral-semaglutide, so its compare-page backlink
     * is stored on deploy. A second migrate does not duplicate rows.
     * Rolling back removes only the new compare slug.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::where('slug', 'orforglipron-vs-oral-semaglutide-compare')->delete();
    }
};
