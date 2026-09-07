<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Affiliate management + per-click commission estimation.
 *
 * vendor_settings:
 *   - affiliate_platform          which affiliate program the vendor runs
 *                                  (goaffpro, refersion, impact, sharesale,
 *                                  manual, none)
 *   - affiliate_credentials       encrypted JSON with per-platform auth
 *                                  (goaffpro: access_token; refersion:
 *                                  api_key + affiliate_id; etc.)
 *   - affiliate_stats_json        cached snapshot from the affiliate API
 *                                  (clicks/sales/revenue/commission) —
 *                                  refreshed daily by scheduled job.
 *   - affiliate_stats_updated_at  when the snapshot was last refreshed
 *   - commission_rate_pct         our commission percentage. Used to
 *                                  ESTIMATE per-click revenue before the
 *                                  real API sync happens.
 *
 * product_clicks:
 *   - estimated_commission_usd    price × commission_rate_pct at click
 *                                  time. Rolled up per-vendor for the
 *                                  /admin/affiliates dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_settings', 'affiliate_platform')) {
                $table->string('affiliate_platform', 32)->nullable()->after('api_key');
            }
            if (!Schema::hasColumn('vendor_settings', 'affiliate_credentials')) {
                // Encrypted via Laravel Crypt — never store raw tokens.
                $table->text('affiliate_credentials')->nullable()->after('affiliate_platform');
            }
            if (!Schema::hasColumn('vendor_settings', 'affiliate_stats_json')) {
                $table->json('affiliate_stats_json')->nullable()->after('affiliate_credentials');
            }
            if (!Schema::hasColumn('vendor_settings', 'affiliate_stats_updated_at')) {
                $table->timestamp('affiliate_stats_updated_at')->nullable()->after('affiliate_stats_json');
            }
            if (!Schema::hasColumn('vendor_settings', 'commission_rate_pct')) {
                $table->decimal('commission_rate_pct', 5, 2)->nullable()->after('affiliate_stats_updated_at');
            }
        });

        Schema::table('product_clicks', function (Blueprint $table) {
            if (!Schema::hasColumn('product_clicks', 'estimated_commission_usd')) {
                $table->decimal('estimated_commission_usd', 10, 2)->nullable()->after('utm_campaign');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            foreach (['affiliate_platform', 'affiliate_credentials', 'affiliate_stats_json',
                      'affiliate_stats_updated_at', 'commission_rate_pct'] as $c) {
                if (Schema::hasColumn('vendor_settings', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('product_clicks', function (Blueprint $table) {
            if (Schema::hasColumn('product_clicks', 'estimated_commission_usd')) {
                $table->dropColumn('estimated_commission_usd');
            }
        });
    }
};
