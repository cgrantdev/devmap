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
        Schema::table('research', function (Blueprint $table) {
            $table->string('evidence_type')->nullable()->after('evidence_level');
            $table->string('journal_type')->nullable()->after('source');
            $table->string('study_type')->nullable()->after('journal_type');
            $table->text('study_summary')->nullable()->after('study_type');
            $table->text('background')->nullable()->after('study_summary');
            $table->json('key_findings')->nullable()->after('background');
            $table->text('methodology')->nullable()->after('key_findings');
            $table->text('clinical_implications')->nullable()->after('methodology');
            $table->text('limitations')->nullable()->after('clinical_implications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn([
                'evidence_type',
                'journal_type',
                'study_type',
                'study_summary',
                'background',
                'key_findings',
                'methodology',
                'clinical_implications',
                'limitations',
            ]);
        });
    }
};
