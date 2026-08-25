<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ships two PMAP-additions items in one migration:
 *
 * External review URLs (#4) — vendors paste their profile URLs from each
 *   review platform; we aggregate the ratings + logos into a single trust
 *   panel on the storefront.
 *
 * Temporary coupon boost (#7) — Julia enters an elevated coupon percentage
 *   plus expiry; a scheduled job flips coupon_discount_percent back to
 *   coupon_discount_previous_percent when the boost expires. Also
 *   auto-posts to Discord when a boost starts (handled elsewhere).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            // External review platform URLs (trustpilot_url already exists
            // from an earlier migration — don't re-add it).
            if (!Schema::hasColumn('vendor_settings', 'google_reviews_url')) {
                $table->string('google_reviews_url', 512)->nullable()->after('trustpilot_url');
            }
            if (!Schema::hasColumn('vendor_settings', 'reviews_io_url')) {
                $table->string('reviews_io_url', 512)->nullable()->after('google_reviews_url');
            }
            if (!Schema::hasColumn('vendor_settings', 'pepreviewpro_url')) {
                $table->string('pepreviewpro_url', 512)->nullable()->after('reviews_io_url');
            }

            // Cached aggregate rating pulled from the platforms above +
            // native reviews. Recomputed by a scheduled job so we don't
            // hit external sites on every page render.
            if (!Schema::hasColumn('vendor_settings', 'external_rating_avg')) {
                $table->decimal('external_rating_avg', 3, 2)->nullable()->after('pepreviewpro_url');
            }
            if (!Schema::hasColumn('vendor_settings', 'external_rating_count')) {
                $table->unsignedInteger('external_rating_count')->default(0)->after('external_rating_avg');
            }
            if (!Schema::hasColumn('vendor_settings', 'external_ratings_json')) {
                // Per-platform snapshot: { trustpilot: { rating, count, url }, ... }
                $table->json('external_ratings_json')->nullable()->after('external_rating_count');
            }

            // Temporary coupon boost. `previous_percent` is snapshotted when
            // a boost is applied so the auto-revert cron knows where to put
            // it back. Null previous = no active boost.
            if (!Schema::hasColumn('vendor_settings', 'coupon_boost_expires_at')) {
                $table->timestamp('coupon_boost_expires_at')->nullable()->after('coupon_discount_percent');
            }
            if (!Schema::hasColumn('vendor_settings', 'coupon_discount_previous_percent')) {
                $table->decimal('coupon_discount_previous_percent', 5, 2)->nullable()->after('coupon_boost_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            foreach (['google_reviews_url', 'reviews_io_url', 'pepreviewpro_url',
                      'external_rating_avg', 'external_rating_count', 'external_ratings_json',
                      'coupon_boost_expires_at', 'coupon_discount_previous_percent'] as $col) {
                if (Schema::hasColumn('vendor_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
