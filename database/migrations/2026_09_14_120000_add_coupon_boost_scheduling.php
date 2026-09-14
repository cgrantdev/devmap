<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colin Sep 14 — Julia's PMAP feedback: the Limited-Time Promo panel
 * needs a start-date so she can pre-schedule promos ahead of a launch
 * without babysitting the moment they go live.
 *
 * Two new columns on vendor_settings:
 *   - coupon_boost_percent    — the target boost %, held separately from
 *     coupon_discount_percent so we can display "scheduled — activates at X"
 *     without touching the live discount value yet.
 *   - coupon_boost_starts_at  — when the boost should flip from scheduled
 *     to active. Omitted / past = start immediately (matches existing UX).
 *
 * Existing flow (immediate boost) still works because starts_at is nullable
 * and the ActivateScheduledCouponBoosts command treats past/null as "run now."
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->decimal('coupon_boost_percent', 5, 2)->nullable()->after('coupon_boost_expires_at');
            $table->timestamp('coupon_boost_starts_at')->nullable()->after('coupon_boost_percent');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn(['coupon_boost_percent', 'coupon_boost_starts_at']);
        });
    }
};
