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
        Schema::table('blogs', function (Blueprint $table) {
            // Add new fields
            $table->text('outline')->nullable()->after('author_job');
            $table->longText('introduction')->nullable()->after('description');
            $table->json('key_points')->nullable()->after('introduction');
            $table->longText('detailed_analysis')->nullable()->after('key_points');
            $table->longText('conclusion')->nullable()->after('detailed_analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['outline', 'introduction', 'key_points', 'detailed_analysis', 'conclusion']);
        });
    }
};
