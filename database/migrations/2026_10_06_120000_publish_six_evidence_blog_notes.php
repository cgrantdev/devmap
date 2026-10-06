<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Publish the six Content-QA-passed RUO evidence notes.
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::whereIn('slug', [
            'glow-vs-klow',
            'orforglipron-vs-tirzepatide',
            'retatrutide-vs-tirzepatide',
            'retatrutide-cagrilintide-blend',
            'cagrilintide-vs-eloralintide',
            'eloralintide-tirzepatide-vs-retatrutide',
        ])->delete();
    }
};
