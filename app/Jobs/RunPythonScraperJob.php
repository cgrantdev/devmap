<?php

namespace App\Jobs;

use App\Models\ScrapingConfig;
use App\Services\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunPythonScraperJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        Log::info('Running Python scraper job for config: ' . $this->config->id);

        $payload = json_encode([
            'products_url' => $this->config->products_url,
            'selectors'    => $this->config->selectors,
        ]);

        $pythonBin = dirname(base_path()) . '/venv/bin/python3.12';
        $pythonScript = base_path() . '/pyscripts/script.py';
        $escapedPayload = escapeshellarg($payload);

        $scrapedoKey = escapeshellarg(config('services.scrapedo.key', env('SCRAPEDO_API_KEY', '')));
        $command = 'sudo -u devuser SCRAPEDO_API_KEY=' . $scrapedoKey . ' '
            . $pythonBin . ' ' . escapeshellarg($pythonScript) . ' ' . $escapedPayload . ' 2>&1';

        Log::info('Running Python scraper', [
            'config_id' => $this->config->id,
            'url' => $this->config->products_url,
        ]);

        $output = shell_exec($command);

        if (empty($output)) {
            $this->recordFailure('Python scraper returned empty output');
            return;
        }

        $data = json_decode($output, true);

        if (isset($data['error'])) {
            $this->recordFailure($data['error']);
            return;
        }

        if (!isset($data['products']) || !is_array($data['products'])) {
            $this->recordFailure('Invalid scraper output format', ['output' => $output]);
            return;
        }

        Log::info('Products scraped', ['config_id' => $this->config->id, 'count' => count($data['products'])]);

        $stagedCount = 0;
        foreach ($data['products'] as $p) {
            if (empty($p['name'])) {
                continue;
            }

            $staged = $ingestion->upsertStaged($this->config, [
                'source_type' => \App\Models\ScrapedProduct::SOURCE_CSS_SCRAPE,
                'source_url' => $p['product_url'] ?? null,
                'name' => $p['name'],
                'price' => $p['price'] ?? null,
                'discount_price' => $p['discount_price'] ?? null,
                'image_url' => $p['image_url'] ?? null,
                'raw_data' => $p,
            ]);

            if ($staged) {
                $stagedCount++;
            }
        }

        Log::info('Scraper staging upsert complete', [
            'config_id' => $this->config->id,
            'staged_count' => $stagedCount,
        ]);

        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->last_error = null;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }

    protected function recordFailure(string $message, array $context = []): void
    {
        Log::error('Python scraper failed: ' . $message, array_merge(
            ['config_id' => $this->config->id],
            $context
        ));
        $this->config->error_count++;
        $this->config->last_error = $message;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
