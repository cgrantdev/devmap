<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a short tagline for vendor storefronts — a one-liner shown
 * under the brand name in the preview + on /brand/{slug}. Distinct
 * from `description` which is the long-form "about us".
 *
 * 120-char cap enforced client-side + server-side. Existing vendors
 * default to null and the display gracefully falls back to nothing.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_settings', 'tagline')) {
                $table->string('tagline', 160)->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_settings', 'tagline')) {
                $table->dropColumn('tagline');
            }
        });
    }
};
