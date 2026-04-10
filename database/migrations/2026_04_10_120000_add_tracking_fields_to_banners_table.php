<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (!Schema::hasColumn('banners', 'impressions')) {
                $table->unsignedBigInteger('impressions')->default(0)->after('active');
            }
            if (!Schema::hasColumn('banners', 'clicks')) {
                $table->unsignedBigInteger('clicks')->default(0)->after('impressions');
            }
            if (!Schema::hasColumn('banners', 'slot')) {
                // Which slot on the site: 'homepage_1', 'homepage_2', 'vendor_sidebar', etc.
                $table->string('slot', 50)->nullable()->after('position');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['impressions', 'clicks', 'slot']);
        });
    }
};
