<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single full URL per vendor that every outbound 'Buy' / 'Visit website'
 * click on PeptideMap should redirect to. Populated from the affiliate
 * program's Referral Link (e.g. https://vendor.com/?ref=PMAP or a bespoke
 * tracking URL like https://vendor.com/?_ef_transaction_id=&oid=1&affid=X).
 *
 * VARCHAR(2000) because a couple of the everflow / goaffpro links carry
 * long tracking payloads; the default 255 was too small.
 *
 * Priority when resolving an outbound URL:
 *   1. referral_url (this column)         → single vendor-wide affiliate URL
 *   2. brands.affiliate_url_template     → templated per-product URL
 *   3. product.product_url               → raw scraped product URL
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->string('referral_url', 2000)->nullable()->after('shop_url');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn('referral_url');
        });
    }
};
