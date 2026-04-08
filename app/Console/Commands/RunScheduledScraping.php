<?php

namespace App\Console\Commands;

use App\Jobs\RunPythonScraperJob;
use App\Jobs\RunWooCommerceIngestJob;
use App\Models\ScrapingConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledScraping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraping:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for scraping configs that are due to run and dispatch jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        
        // Find all enabled scraping configs that are due to run
        $configsToRun = ScrapingConfig::where('enabled', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', $now);
            })
            ->get();

        if ($configsToRun->isEmpty()) {
            $this->info('No scraping configs are due to run.');
            return Command::SUCCESS;
        }

        $this->info("Found {$configsToRun->count()} scraping config(s) due to run.");

        foreach ($configsToRun as $config) {
            $type = $config->type ?? ScrapingConfig::TYPE_CSS_SCRAPE;
            $this->info("Dispatching {$type} job for config ID: {$config->id} (Vendor: {$config->vendor_name})");
            Log::info('Auto-scheduling ingestion job', [
                'config_id' => $config->id,
                'type' => $type,
                'vendor_name' => $config->vendor_name,
                'next_run_at' => $config->next_run_at?->toIso8601String(),
            ]);

            match ($type) {
                ScrapingConfig::TYPE_WOO_API => RunWooCommerceIngestJob::dispatch($config),
                default => RunPythonScraperJob::dispatch($config),
            };
        }

        $this->info('All due scraping jobs have been dispatched.');
        return Command::SUCCESS;
    }
}
