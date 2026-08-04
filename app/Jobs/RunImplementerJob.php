<?php

namespace App\Jobs;

use App\Models\ImplementerRun;
use App\Models\SeoRecommendation;
use App\Services\Implementer\ImplementerRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs the SEO implementer autonomously on one recommendation.
 * Dispatched from the CEO dashboard when the user clicks "Start work" on
 * a rec (or "Start building" on a new-agent rec).
 *
 * Routed to a dedicated 'implementer' queue so we can attach a long-timeout
 * Forge Daemon without touching the existing default-queue worker (which
 * has --timeout=90, too short for agent runs).
 *
 * Retries disabled — if the run fails halfway (mid-commit, mid-PR), a retry
 * would compound the mess. Better to surface the failure and let the user
 * decide manually.
 */
class RunImplementerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    // 30 min hard ceiling — matches the Forge Daemon --timeout we'll set.
    public int $timeout = 1800;
    public string $queue = 'implementer';

    public function __construct(
        public readonly int $implementerRunId,
    ) {}

    public function handle(ImplementerRunner $runner): void
    {
        $run = ImplementerRun::with('recommendation')->find($this->implementerRunId);
        if (!$run) return; // Deleted before pickup — nothing to do.
        $runner->execute($run);
    }

    public function failed(?Throwable $e): void
    {
        $run = ImplementerRun::find($this->implementerRunId);
        if (!$run) return;
        $run->update([
            'status' => 'failed',
            'error' => 'Job failed at queue level: ' . ($e?->getMessage() ?? 'unknown'),
            'finished_at' => now(),
        ]);
    }
}
