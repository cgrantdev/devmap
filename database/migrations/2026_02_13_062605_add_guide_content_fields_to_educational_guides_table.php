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
        Schema::table('educational_guides', function (Blueprint $table) {
            $table->string('guide_type')->nullable()->after('title');
            $table->text('outline')->nullable()->after('guide_type');
            $table->text('introduction')->nullable()->after('outline');
            $table->json('overview_benefits')->nullable()->after('introduction');
            $table->text('usage_guidelines_important_note')->nullable()->after('overview_benefits');
            $table->json('dosage_recommendation')->nullable()->after('usage_guidelines_important_note');
            $table->json('administration_methods')->nullable()->after('dosage_recommendation');
            $table->text('timing_frequency')->nullable()->after('administration_methods');
            $table->text('safety_first')->nullable()->after('timing_frequency');
            $table->json('common_side_effects')->nullable()->after('safety_first');
            $table->json('contraindications')->nullable()->after('common_side_effects');
            $table->json('storage_handling')->nullable()->after('contraindications');
            $table->json('dos')->nullable()->after('storage_handling');
            $table->json('donts')->nullable()->after('dos');
            $table->text('conclusion')->nullable()->after('donts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educational_guides', function (Blueprint $table) {
            $table->dropColumn([
                'guide_type',
                'outline',
                'introduction',
                'overview_benefits',
                'usage_guidelines_important_note',
                'dosage_recommendation',
                'administration_methods',
                'timing_frequency',
                'safety_first',
                'common_side_effects',
                'contraindications',
                'storage_handling',
                'dos',
                'donts',
                'conclusion',
            ]);
        });
    }
};
