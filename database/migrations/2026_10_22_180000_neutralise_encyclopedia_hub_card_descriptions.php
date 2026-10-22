<?php

use Database\Seeders\EncyclopediaHubCardsOctober22Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Replace /encyclopedia hub card descriptions that carried efficacy
 * figures, healing/fat-burning/recovery wording or other benefit claims
 * with neutral research-use-only summaries (17 slugs), and fix the IGF-1 LR3
 * substitution (Glu3→Arg). Also appends the /compare/orforglipron-vs-oral-semaglutide
 * educational compare page (#69) to the orforglipron and semaglutide references.
 *
 * Guarded and idempotent; see EncyclopediaHubCardsOctober22Seeder.
 * down() does not restore the previous copy.
 *
 * Source payload sha256 121f7c878f6e6c4bc78a77538c80a5979cac0559aee4979cda74566b10ae3c8e.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EncyclopediaHubCardsOctober22Seeder)->run();
    }

    public function down(): void
    {
        // Previous card descriptions are not restored.
    }
};
