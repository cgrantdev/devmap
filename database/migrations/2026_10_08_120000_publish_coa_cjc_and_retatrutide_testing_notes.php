<?php

use App\Content\EducationalContentPublisher;
use App\Models\Blog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Deepen the live COA literacy post and publish the CJC-1295 DAC vs no DAC
     * note and the retatrutide testing / COA limits note. The COA post and the
     * retatrutide testing post link each other, so they ship in one sync.
     *
     * Production pick-up is `php artisan migrate` after deploy.
     * Sync upserts by slug, so a second migrate does not duplicate rows.
     * Rolling back removes only the two new slugs. It does not restore the
     * earlier COA wording.
     */
    public function up(): void
    {
        EducationalContentPublisher::sync();
    }

    public function down(): void
    {
        Blog::whereIn('slug', [
            'cjc-1295-dac-vs-no-dac',
            'retatrutide-testing-coa-limits',
        ])->delete();
    }
};
