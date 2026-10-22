<?php

use App\Content\EducationalContentPublisher;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Re-sync the peptide-legality guide with the October 2026 enforcement
     * note (Senate letter and TikTok search report).
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows.
     * Rolling back does not delete the published guide.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        // The published row stays. Previous wording is not restored.
    }
};
