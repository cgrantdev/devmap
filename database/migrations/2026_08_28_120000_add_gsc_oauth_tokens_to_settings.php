<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Store the GSC OAuth refresh token in the existing `settings` singleton
 * table. Uses key/value rows the app already has plumbing for (see
 * App\Models\Setting); no schema shape change required beyond confirming
 * the table exists.
 *
 * The refresh token itself is stored via Setting::setEncrypted so it lands
 * as ciphertext at rest.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) return;
        // Nothing to change — just a marker so future contributors can trace
        // when the GSC OAuth wiring was added.
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) return;
        DB::table('settings')->whereIn('key', [
            'gsc_oauth_refresh_token',
            'gsc_oauth_access_token_cache',
            'gsc_oauth_connected_email',
        ])->delete();
    }
};
