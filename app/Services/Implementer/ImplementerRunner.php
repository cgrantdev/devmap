<?php

namespace App\Services\Implementer;

use App\Models\ImplementerRun;

/**
 * SEO implementer runner — placeholder until the Anthropic agent loop
 * lands in task #23. Currently marks the run failed so nothing hangs
 * queued while we wire the API integration.
 */
class ImplementerRunner
{
    public function execute(ImplementerRun $run): void
    {
        $run->update([
            'status' => 'failed',
            'started_at' => now(),
            'finished_at' => now(),
            'error' => 'Agent loop not yet implemented (task #23).',
        ]);
        $run->appendLog('Runner reached but agent loop is not built yet.');

        // Flip the parent rec back to open so it doesn't sit stuck in progress.
        if ($run->recommendation) {
            $run->recommendation->update(['status' => 'open']);
        }
    }
}
