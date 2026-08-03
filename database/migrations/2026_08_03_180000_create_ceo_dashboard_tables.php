<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ceo_agent_runs', function (Blueprint $t) {
            $t->id();
            // Which agent ran (seo-strategist | seo-implementer | Explore | Plan | claude | ...)
            $t->string('agent_name', 80)->index();
            // One-line status label — completed | in_progress | blocked | rolled-back
            $t->string('status', 32)->default('completed')->index();
            // Human summary of what happened this run
            $t->string('title', 200);
            // Full markdown body — findings, decisions, tradeoffs
            $t->text('summary');
            // Bullet list of next steps this run produced (markdown)
            $t->text('next_steps')->nullable();
            // Related git commit SHAs — indexed at DB level via a JSON column.
            $t->json('commit_hashes')->nullable();
            // Free-form links (PR URLs, ticket URLs, doc URLs)
            $t->json('links')->nullable();
            $t->timestamps();
        });

        Schema::create('ceo_initiatives', function (Blueprint $t) {
            $t->id();
            // SEO | Community | Product | Ops | Content | Growth | ...
            $t->string('category', 40)->index();
            $t->string('title', 200);
            // idea | planned | in_progress | done | paused
            $t->string('status', 32)->default('planned')->index();
            $t->string('owner', 80)->nullable();
            $t->text('notes')->nullable();
            // Sort order within a status column
            $t->integer('position')->default(0);
            $t->boolean('pinned')->default(false);
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ceo_notes', function (Blueprint $t) {
            // Singleton row per key — free-form markdown scratchpad
            $t->id();
            $t->string('key', 60)->unique();
            $t->longText('body')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ceo_notes');
        Schema::dropIfExists('ceo_initiatives');
        Schema::dropIfExists('ceo_agent_runs');
    }
};
