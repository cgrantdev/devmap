<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Julia Sep 18 — Hydro Research runs an affiliate-only Monday code
 * that differs from their standard PMAP coupon. When she launches
 * their limited-time promo, she also needs the code itself to swap
 * for the boost window and revert automatically when it ends.
 *
 * Two new columns on vendor_settings:
 *   - coupon_boost_code            → the code to display while active
 *   - coupon_code_previous         → snapshot of the standard code so
 *                                    revert commands can put it back
 *
 * Existing boost flow stays intact when boost_code is left null —
 * only the % changes then, same as before this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->string('coupon_boost_code', 64)->nullable()->after('coupon_boost_percent');
            $table->string('coupon_code_previous', 64)->nullable()->after('coupon_boost_code');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn(['coupon_boost_code', 'coupon_code_previous']);
        });
    }
};
