<?php

namespace App\Jobs\Concerns;

/**
 * At most one queued or running ingest per scraping config.
 *
 * A second dispatch for the same config (the every-minute scheduler, the
 * admin "run now" button, a vendor's "sync now") is dropped while the
 * first is queued or running. The lock lapses after an hour, so a run
 * killed mid-way cannot block that vendor for longer.
 *
 * Added 23 Nov 2026 after Southern Aminos revoked our API key over about
 * 1,300 requests an hour. Classes using this must implement
 * Illuminate\Contracts\Queue\ShouldBeUnique.
 */
trait OnePerScrapingConfig
{
    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return 'scraping-config-'.$this->config->id;
    }
}
