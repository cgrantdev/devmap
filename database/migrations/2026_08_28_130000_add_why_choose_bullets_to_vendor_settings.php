<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-vendor "Why Choose {brand}?" bullet list. Until now this section
 * on /brand/{slug} was hardcoded to a generic four-line list on every
 * vendor page — no differentiation. Storing the list as JSON per
 * vendor so owners (and admins) can edit their own inline. Null =
 * fall back to the generic defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_settings', 'why_choose_bullets')) {
                $table->json('why_choose_bullets')->nullable()->after('usps');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_settings', 'why_choose_bullets')) {
                $table->dropColumn('why_choose_bullets');
            }
        });
    }
};
