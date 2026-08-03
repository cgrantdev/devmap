<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_recommendations', function (Blueprint $t) {
            $t->id();
            $t->string('title', 220);
            // technical | content | on-page | structured-data | link-building |
            // internal-linking | performance | new-agent | other
            $t->string('category', 40)->index();
            $t->string('impact', 12)->default('medium')->index();  // high | medium | low
            $t->string('effort', 12)->default('medium');            // small | medium | large
            // open | in_progress | shipped | rejected | deferred
            $t->string('status', 16)->default('open')->index();
            $t->text('rationale')->nullable();          // why this matters (strategist's reasoning)
            $t->text('expected_impact')->nullable();    // what we expect to get out of it
            // Whoever surfaced it — usually 'seo-strategist' but can be 'colin' or 'claude' too
            $t->string('source', 40)->default('seo-strategist');
            $t->string('shipped_by', 40)->nullable();
            $t->timestamp('shipped_at')->nullable();
            $t->json('commit_hashes')->nullable();
            $t->integer('position')->default(0);        // manual sort within a status
            $t->boolean('pinned')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_recommendations');
    }
};
