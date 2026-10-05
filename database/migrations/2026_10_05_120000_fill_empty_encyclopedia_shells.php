<?php

use Database\Seeders\EncyclopediaEmptyShellsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Publishes the 22 empty encyclopedia shells and stores the recommended H1.
 * The seeder matches existing category slugs and no-ops when a category
 * is absent or its overview is already filled. Retatrutide is not written.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('education_posts', 'seo_h1')) {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->string('seo_h1')->nullable()->after('seo_page_title');
            });
        }

        (new EncyclopediaEmptyShellsSeeder())->run();
    }

    public function down(): void
    {
        if (Schema::hasColumn('education_posts', 'seo_h1')) {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->dropColumn('seo_h1');
            });
        }
    }
};
