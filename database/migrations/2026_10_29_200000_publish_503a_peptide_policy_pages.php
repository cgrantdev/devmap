<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use App\Models\EducationalGuide;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Publish the compounded-vs-RUO guide and the six-peptide explainer
     * for the 28 Oct 2026 HHS draft 503A policy.
     *
     * Only those two slugs are upserted. Other catalog rows, including
     * the legality guide and any admin edits since the last full sync,
     * are left as they are. The BPC-157 vs TB-500 note is read from
     * BpcTb500CompareCopy at request time and is not a content row.
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * A second migrate updates the same two slugs and does not duplicate
     * rows. Rolling back removes only those two slugs.
     */
    public function up(): void
    {
        EducationalContentPublisher::syncOnly([
            'compounded-vs-ruo-peptides',
            'hhs-503a-peptide-enforcement-discretion-2026',
        ]);
    }

    public function down(): void
    {
        Blog::where('slug', 'hhs-503a-peptide-enforcement-discretion-2026')->delete();
        EducationalGuide::where('slug', 'compounded-vs-ruo-peptides')->delete();
    }
};
