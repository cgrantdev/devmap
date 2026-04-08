<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // URL template with placeholders like {product_url}, {slug}, {id}
            // e.g. "https://vendor.com/product/{slug}?ref=peptidemap"
            // Falls back to product.product_url if null.
            $table->text('affiliate_url_template')->nullable()->after('packaging');
            $table->string('affiliate_tag', 100)->nullable()->after('affiliate_url_template');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['affiliate_url_template', 'affiliate_tag']);
        });
    }
};
