<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rudy at Coastal Peptides Sep 22 — the URL field on the vendor
 * import page clears after submission, so there's no way to confirm
 * whether the feed is being re-pulled daily or the import was
 * one-time. Persist the URL on vendor_settings so a scheduled
 * command can re-fetch nightly and the vendor sees their current
 * sync URL in the import UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->string('feed_url', 1024)->nullable()->after('shop_url');
            $table->timestamp('feed_last_synced_at')->nullable()->after('feed_url');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn(['feed_url', 'feed_last_synced_at']);
        });
    }
};
