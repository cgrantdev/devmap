<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured USPs (JSON array of selected preset keys) + structured
 * business hours JSON. Legacy `business_hours` string column stays for
 * backward-compat display fallback; new `business_hours_json` holds the
 * per-day open/close data + timezone. Legacy `uniqueSellingPoints` free-
 * form field on the form still writes to `description`-adjacent text —
 * the structured `usps` array is what the storefront actually displays.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_settings', 'usps')) {
                $table->json('usps')->nullable()->after('tagline');
            }
            if (!Schema::hasColumn('vendor_settings', 'business_hours_json')) {
                $table->json('business_hours_json')->nullable()->after('business_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_settings', 'usps')) $table->dropColumn('usps');
            if (Schema::hasColumn('vendor_settings', 'business_hours_json')) $table->dropColumn('business_hours_json');
        });
    }
};
