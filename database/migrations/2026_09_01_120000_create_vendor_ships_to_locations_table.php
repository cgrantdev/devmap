<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Many-to-many: which countries a vendor ships to. Previously the vendor
 * had a single vendor_settings.location_id which meant BOTH "where they
 * are" and "who they ship to" — but a US-based vendor might ship
 * worldwide, and a UK-based vendor might only ship to UK + EU. This
 * separates the concerns.
 *
 * Existing single location_id is preserved as the vendor's HQ /
 * physical-location signal; ships_to is the new filter surface on
 * /brands. See VendorSetting::shipsToLocations().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_ships_to_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_setting_id')->constrained('vendor_settings')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['vendor_setting_id', 'location_id']);
            $table->index('location_id');
        });

        // Seed the pivot from the existing single location_id so vendors
        // don't lose any /brands filter hits on day 1.
        // (Safe if a vendor already has no location_id — inserts skip nulls.)
        \DB::statement("
            INSERT INTO vendor_ships_to_locations (vendor_setting_id, location_id, created_at, updated_at)
            SELECT id, location_id, NOW(), NOW()
            FROM vendor_settings
            WHERE location_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_ships_to_locations');
    }
};
