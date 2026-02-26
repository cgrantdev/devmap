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
            // Tags
            $table->json('tags')->nullable()->after('education_tag');
            
            // Molecular Information
            $table->string('molecular_formula')->nullable()->after('tags');
            $table->string('molecular_weight')->nullable()->after('molecular_formula');
            $table->string('cas_registry_number')->nullable()->after('molecular_weight');
            
            // Amino Acid Sequence
            $table->text('amino_acid_sequence')->nullable()->after('cas_registry_number');
            $table->string('amino_acid_net_charge')->nullable()->after('amino_acid_sequence');
            $table->string('amino_acid_hydrophobic')->nullable()->after('amino_acid_net_charge');
            $table->string('amino_acid_stability')->nullable()->after('amino_acid_hydrophobic');
            $table->string('amino_acid_solubility')->nullable()->after('amino_acid_stability');
            
            // Key Points
            $table->json('key_points')->nullable()->after('amino_acid_solubility');
            
            // Overview
            $table->text('overview')->nullable()->after('key_points');
            
            // Areas of Research
            $table->text('areas_of_research_intro')->nullable()->after('overview');
            $table->json('areas_of_research')->nullable()->after('areas_of_research_intro');
            
            // Background
            $table->text('background')->nullable()->after('areas_of_research');
            
            // Mechanism of Action
            $table->text('mechanism_of_action_intro')->nullable()->after('background');
            $table->json('mechanism_subsections')->nullable()->after('mechanism_of_action_intro');
            
            // Preclinical Research
            $table->text('preclinical_intro')->nullable()->after('mechanism_subsections');
            $table->json('preclinical_subsections')->nullable()->after('preclinical_intro');
            $table->text('preclinical_disclaimer')->nullable()->after('preclinical_subsections');
            
            // Human Use & Evidence
            $table->text('human_use_intro')->nullable()->after('preclinical_disclaimer');
            $table->json('human_use_subsections')->nullable()->after('human_use_intro');
            
            // Regulatory Status
            $table->json('regulatory_subsections')->nullable()->after('human_use_subsections');
            $table->text('regulatory_important_note')->nullable()->after('regulatory_subsections');
            
            // Potential Applications
            $table->text('potential_applications_intro')->nullable()->after('regulatory_important_note');
            $table->json('potential_applications')->nullable()->after('potential_applications_intro');
            
            // Conclusion
            $table->text('conclusion')->nullable()->after('potential_applications');
            
            // References
            $table->json('references')->nullable()->after('conclusion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('education_posts', function (Blueprint $table) {
            $table->dropColumn([
                'tags',
                'molecular_formula',
                'molecular_weight',
                'cas_registry_number',
                'amino_acid_sequence',
                'amino_acid_net_charge',
                'amino_acid_hydrophobic',
                'amino_acid_stability',
                'amino_acid_solubility',
                'key_points',
                'overview',
                'areas_of_research_intro',
                'areas_of_research',
                'background',
                'mechanism_of_action_intro',
                'mechanism_subsections',
                'preclinical_intro',
                'preclinical_subsections',
                'preclinical_disclaimer',
                'human_use_intro',
                'human_use_subsections',
                'regulatory_subsections',
                'regulatory_important_note',
                'potential_applications_intro',
                'potential_applications',
                'conclusion',
                'references',
            ]);
        });
    }
};
