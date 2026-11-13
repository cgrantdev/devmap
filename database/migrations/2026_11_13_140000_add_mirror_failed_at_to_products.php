<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Last failed image-mirror attempt. products:sync-external-images retried
 * the same geo-blocked rows every hour in id order, so they used up the
 * per-run limit and newer products never got mirrored. Rows that failed
 * within the backoff window are now skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'mirror_failed_at')) {
            return;
        }
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('mirror_failed_at')->nullable()->after('mirrored_image_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('products', 'mirror_failed_at')) {
            return;
        }
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('mirror_failed_at');
        });
    }
};
