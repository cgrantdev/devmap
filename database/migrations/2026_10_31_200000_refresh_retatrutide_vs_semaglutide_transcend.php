<?php

use App\Content\EducationalContentPublisher;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Refresh the retatrutide vs semaglutide article with Lilly's
     * 28 Oct 2026 TRANSCEND-T2D-2 company-reported topline.
     *
     * Only this slug is upserted. Other catalog rows are left as they are.
     * Production pick-up is `php artisan migrate` after deploy.
     * A second migrate updates the same slug and does not duplicate rows.
     * Rolling back does not delete the published article.
     */
    public function up(): void
    {
        EducationalContentPublisher::syncOnly(['retatrutide-vs-semaglutide']);
    }

    public function down(): void
    {
        // The published row stays. Previous wording is not restored.
    }
};
