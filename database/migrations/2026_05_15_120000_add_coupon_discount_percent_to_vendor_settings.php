<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor discount percentage that PeptideMap surfaces alongside the
 * existing coupon_code. We use this to show a "PeptideMap Price"
 * (retail * (1 - pct/100)) next to the vendor's listed retail price
 * on every product surface.
 *
 * Stored as decimal(5,2) so we can support fractional pcts like 12.5%.
 * Nullable — most vendors won't have one, in which case no comparison
 * price renders.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->decimal('coupon_discount_percent', 5, 2)->nullable()->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn('coupon_discount_percent');
        });
    }
};
