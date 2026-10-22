<?php

use App\Content\EducationalContentPublisher;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Re-sync the three dose-arm blogs so published rows use
     * lowest, middle, and highest arm labels instead of milligram
     * arm names, and so their FAQs render with the catalog.
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows.
     * Rolling back does not delete the published article.
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
