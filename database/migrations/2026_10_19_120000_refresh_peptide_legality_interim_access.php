<?php

use App\Content\EducationalContentPublisher;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Refresh the peptide-legality guide for the October 2026 interim access
     * policy, and the beginner guide FAQ that points at it.
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows
     * and does not delete existing content.
     * Rolling back leaves the published rows in place.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        // Existing guides stay published. The previous wording is not restored.
    }
};
