<?php

use Database\Seeders\Cjc1295DacIdentitySeeder;
use Database\Seeders\FiveAmino1mqNotAPeptideSeeder;
use Database\Seeders\RetatrutideTimelineLiteracySeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deepens three live encyclopedia rows: CJC-1295 DAC vs no-DAC identity,
 * retatrutide filing/timeline literacy, and 5-Amino-1MQ as a non-peptide.
 *
 * Each seeder no-ops when its category or post is missing, or when more
 * than one category shares the lowercase slug. A second migrate does not
 * write again. amino_acid_stability is widened on MySQL so the CJC-1295
 * molecular note fits; formula, weight, and CAS columns are not rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE education_posts MODIFY amino_acid_stability TEXT NULL');
        }

        (new Cjc1295DacIdentitySeeder)->run();
        (new RetatrutideTimelineLiteracySeeder)->run();
        (new FiveAmino1mqNotAPeptideSeeder)->run();
    }

    public function down(): void
    {
        // The previous CJC-1295 copy mixed DAC pharmacokinetics into the
        // no-DAC catalog lane, and the previous retatrutide note did not
        // separate a Q1 2027 filing plan from approval. Those sentences
        // are not restored. The stability column stays text.
    }
};
