<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colin Sep 15 — PMAP #7a. Two new content sections on the vendor
 * storefront: Manufacturing (where + how it's made) and Independent
 * Testing (who tests it + where results live). Kept as plain text
 * blocks so vendors can describe their setup in their own words,
 * rather than boxed into checkbox USPs.
 *
 * The verified cGMP + 7x-Testing BADGES stay a separate concern —
 * those are gated by the vendor_certification_claims workflow (Julia
 * approves docs). These text fields are marketing copy, not proof.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->text('manufacturing_notes')->nullable()->after('return_policy');
            $table->text('independent_testing_notes')->nullable()->after('manufacturing_notes');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            $table->dropColumn(['manufacturing_notes', 'independent_testing_notes']);
        });
    }
};
