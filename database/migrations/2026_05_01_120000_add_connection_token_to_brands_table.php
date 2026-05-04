<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands', 'connection_token')) {
                // Random 40-char token used by the WordPress plugin to auto-push
                // WooCommerce REST API credentials to PeptideMap during install.
                $table->string('connection_token', 64)->nullable()->unique()->after('affiliate_tag');
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'connection_token')) {
                $table->dropUnique(['connection_token']);
                $table->dropColumn('connection_token');
            }
        });
    }
};
