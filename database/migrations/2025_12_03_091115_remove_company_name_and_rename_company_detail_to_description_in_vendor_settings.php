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
        Schema::table('vendor_settings', function (Blueprint $table) {
            // Add description column
            $table->text('description')->nullable()->after('logo');
        });

        // Copy data from company_detail to description
        \DB::statement('UPDATE vendor_settings SET description = company_detail WHERE company_detail IS NOT NULL');

        Schema::table('vendor_settings', function (Blueprint $table) {
            // Remove old columns
            $table->dropColumn(['company_name', 'company_detail']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_settings', function (Blueprint $table) {
            // Add back old columns
            $table->string('company_name')->nullable()->after('logo');
            $table->text('company_detail')->nullable()->after('company_name');
        });

        // Copy data back from description to company_detail
        \DB::statement('UPDATE vendor_settings SET company_detail = description WHERE description IS NOT NULL');

        Schema::table('vendor_settings', function (Blueprint $table) {
            // Remove description column
            $table->dropColumn('description');
        });
    }
};
