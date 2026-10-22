<?php

use App\Content\EducationalContentPublisher;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Re-sync the retatrutide vs tirzepatide note so TRIUMPH efficacy
     * estimands are labelled as reported in the journal papers and
     * company-reported by Lilly.
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
