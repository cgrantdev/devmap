<?php

use App\Models\ScrapingConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Runs V8 Peptides' JSON-LD import again a minute after deploy instead of
 * tomorrow. Its first run listed each size of a page separately but left
 * the page-level rows "Discover Products" had made (GHK-Cu, Retatrutide,
 * CJC-1295 + Ipamorelin with no size) visible beside them, and rows such
 * as "BPC-157" without a size. The importer now hides a superseded
 * page-level row and fills an empty size from the vendor's data.
 *
 * Nothing to undo: down() leaves the schedule alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brands') || ! Schema::hasTable('scraping_configs')) {
            return;
        }

        $brandId = DB::table('brands')->where('slug', 'v8-peptides')->value('id');
        if (! $brandId) {
            return;
        }

        DB::table('scraping_configs')
            ->where('vendor_id', $brandId)
            ->where('type', ScrapingConfig::TYPE_JSON_LD)
            ->where('enabled', true)
            ->update(['next_run_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // The schedule moves on by itself.
    }
};
