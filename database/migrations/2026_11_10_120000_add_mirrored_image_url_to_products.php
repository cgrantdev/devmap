<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors vendor product imagery onto our own storage. Vendors (Bioedge,
 * Certified-Pep as of 2026-11) turn on WordPress hotlink protection and
 * their product images 403 when loaded from peptidemap.com — cards
 * render with the broken-image fallback. products:sync-external-images
 * fetches the vendor's image_url server-side (where no browser Referer
 * is sent), stores it as WebP in /storage/vendor-mirror, and writes the
 * resulting public URL here.
 *
 * image_url is left untouched as the source-of-truth from scrapes.
 * Frontend prefers mirrored_image_url when present; falls back to
 * image_url otherwise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('mirrored_image_url', 2048)->nullable()->after('image_url');
            $table->timestamp('mirrored_image_at')->nullable()->after('mirrored_image_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['mirrored_image_url', 'mirrored_image_at']);
        });
    }
};
