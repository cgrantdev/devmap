<?php

use App\Content\EducationalContentPublisher;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Re-sync retatrutide notes so TRIUMPH efficacy rows, and the
     * TRIUMPH-1 adverse-event figures that appear in the NEJM paper,
     * are labelled as reported in the paper and company-reported by Lilly.
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
