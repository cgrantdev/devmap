<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use App\Models\EducationalGuide;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Publish the tesamorelin vs sermorelin note and the bacteriostatic-water
     * literacy guide, and refresh the BPC-157 vs TB-500 evidence post and the
     * peptide-legality guide in place.
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows.
     * Rolling back removes only the two new slugs. It does not restore the
     * earlier BPC-157 or legality wording.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::where('slug', 'tesamorelin-vs-sermorelin')->delete();
        EducationalGuide::where('slug', 'bacteriostatic-water-literacy')->delete();
    }
};
