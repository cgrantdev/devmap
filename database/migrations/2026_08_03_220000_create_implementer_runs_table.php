<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('implementer_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('seo_recommendation_id')->constrained()->cascadeOnDelete();
            // queued | running | succeeded | failed | cancelled
            $t->string('status', 24)->default('queued')->index();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->text('error')->nullable();
            $t->string('branch', 200)->nullable();
            $t->string('pr_url', 400)->nullable();
            $t->unsignedInteger('pr_number')->nullable();
            // Cost tracking so the dashboard can show what each run cost.
            $t->unsignedInteger('input_tokens')->default(0);
            $t->unsignedInteger('output_tokens')->default(0);
            $t->unsignedInteger('cache_read_tokens')->default(0);
            $t->unsignedInteger('cache_write_tokens')->default(0);
            $t->decimal('cost_usd', 8, 4)->default(0);
            $t->unsignedInteger('iterations')->default(0);
            // Full agent-loop log (bounded — first 100KB kept, older truncated).
            $t->longText('log')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('implementer_runs');
    }
};
