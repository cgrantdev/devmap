<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use App\Models\EducationalGuide;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Publish the compounded-vs-RUO guide and the six-peptide explainer
     * for the 28 Oct 2026 HHS draft 503A policy, and refresh catalog
     * rows (including the legality guide's link to the new guide).
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows.
     * Rolling back removes only the two new slugs. The legality-guide
     * sentence is not restored.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::where('slug', 'fda-503a-peptide-enforcement-discretion-2026')->delete();
        EducationalGuide::where('slug', 'compounded-vs-ruo-peptides')->delete();
    }
};
