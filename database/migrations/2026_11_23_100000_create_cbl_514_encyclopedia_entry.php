<?php

use Database\Seeders\EncyclopediaCategoryCreateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the CBL-514 encyclopedia category and post from the shell draft.
 *
 * No category matches LOWER(slug) cbl-514, and the entry has no aliases,
 * so this creates an inactive category. /encyclopedia/cbl-514 stays 404
 * until someone activates it. An existing cbl-514 row is reused with
 * is_active left as stored. The post is filled only when its overview is
 * empty. Pemvidutide, hexarelin, and cagrisema are already filled, so this
 * seeder run leaves those entries alone. No products, vendors, prices,
 * compare entries, aliases, or featured image are written. A second
 * migrate is a no-op once the overview is filled and read_time is set.
 *
 * read_time is 11. On a fresh migrate the shared seeder can insert the
 * post from an earlier migration, before this column exists, and the
 * 14 Nov FAQ migration then archives those FAQs. This migration adds
 * the column, then the seeder sets read_time to 11 and restores the
 * five visible FAQs once if they are still empty.
 *
 * down() does not delete the row or flip visibility. It drops only the
 * read_time column this migration added.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('education_posts') && ! Schema::hasColumn('education_posts', 'read_time')) {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->string('read_time')->nullable();
            });
        }

        (new EncyclopediaCategoryCreateSeeder)->run();
    }

    public function down(): void
    {
        if (Schema::hasColumn('education_posts', 'read_time')) {
            Schema::table('education_posts', function (Blueprint $table) {
                $table->dropColumn('read_time');
            });
        }
    }
};
