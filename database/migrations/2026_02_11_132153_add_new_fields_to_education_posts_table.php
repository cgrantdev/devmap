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
        Schema::table('education_posts', function (Blueprint $table) {
            $table->string('education_tag')->nullable()->after('title');
            $table->string('peptide_full_name')->nullable()->after('education_tag');
            $table->json('common_use_cases')->nullable()->after('key_effects');
            $table->text('how_it_works')->nullable()->after('common_use_cases');
            $table->string('typical_dosage')->nullable()->after('how_it_works');
            $table->string('frequency')->nullable()->after('typical_dosage');
            $table->string('administration')->nullable()->after('frequency');
            $table->string('cycle_duration')->nullable()->after('administration');
            $table->json('possible_side_effects')->nullable()->after('cycle_duration');
            $table->json('contraindications')->nullable()->after('possible_side_effects');
            $table->json('stacking_recommendations')->nullable()->after('contraindications');
            $table->json('faqs')->nullable()->after('stacking_recommendations');
            $table->string('half_life')->nullable()->after('description');
            $table->string('bioavailability')->nullable()->after('half_life');
            $table->string('storage')->nullable()->after('bioavailability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_posts', function (Blueprint $table) {
            $table->dropColumn([
                'education_tag',
                'peptide_full_name',
                'common_use_cases',
                'how_it_works',
                'typical_dosage',
                'frequency',
                'administration',
                'cycle_duration',
                'possible_side_effects',
                'contraindications',
                'stacking_recommendations',
                'faqs',
                'half_life',
                'bioavailability',
                'storage',
            ]);
        });
    }
};
