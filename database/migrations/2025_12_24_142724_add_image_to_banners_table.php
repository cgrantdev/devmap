<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('banners')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            $table->string('image')->nullable()->after('title'); // For file uploads
            $table->text('image_url')->nullable()->change(); // Make nullable since we can use image file instead
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('banners')) {
            return;
        }

        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('image');
            $table->text('image_url')->nullable(false)->change();
        });
    }
};
