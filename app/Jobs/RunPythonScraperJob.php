<?php

namespace App\Jobs;

use App\Models\ScrapingConfig;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class RunPythonScraperJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public ScrapingConfig $config) {}

    public function handle()
    {
        Log::info('Running Python scraper job for config: ' . $this->config->id);
        
        $payload = json_encode([
            'products_url' => $this->config->products_url,
            'selectors'    => $this->config->selectors,
        ]);
        Log::info('Scraper payload', [ 'payload' => $payload ]);
        
        // Use the same pattern as the working VendorsController::runPythonScraper method
        // $pythonBin = dirname(base_path()) . '/venv/bin/python3.12';
        $pythonBin = '/usr/bin/python3';
        $pythonScript = base_path() . '/pyscripts/script.py';        
        
        $escapedPayload = escapeshellarg($payload);
        
        $command = 'sudo ' . $pythonBin . ' ' . escapeshellarg($pythonScript) . ' ' . $escapedPayload . ' 2>&1';
        
        Log::info('Running Python scraper', ['command' => $command, 'url' => $this->config->products_url]);
        
        $output = shell_exec($command);
        
        Log::info('Python stdout', [ 'stdout' => $output ]);
        
        if (empty($output)) {
            Log::error('Python scraper returned empty output');
            $this->config->error_count++;
            $this->config->last_error = 'Python scraper returned empty output';
            $this->config->save();
            return;
        }
        
        $data = json_decode($output, true);
        
        if (isset($data['error'])) {
            Log::error('Python scraper error', ['error' => $data['error']]);
            $this->config->error_count++;
            $this->config->last_error = $data['error'];
            $this->config->save();
            return;
        }

        // safety check
        if (! isset($data['products']) || ! is_array($data['products'])) {
            Log::error('Invalid scraper output format', ['output' => $output]);
            return;
        }

        Log::info('Products to save', ['count' => count($data['products'])]);

        foreach ($data['products'] as $p) {
            Log::info('Processing product', ['product' => $p]);
            
            $productData = [
                'brand_id' => $this->config->vendor_id,
                'product_category_id' => $this->config->product_category_id ?? null,
                'price' => $p['price'] ?? null,
                'image_url' => $p['image_url'] ?? null,
                'product_url' => $p['product_url'] ?? null,
                'auto_scraped' => true,
                'last_scraped_at' => now(),
            ];
            
            // Add discount_price if it exists
            if (isset($p['discount_price']) && $p['discount_price'] !== null) {
                $productData['discount_price'] = $p['discount_price'];
            }
            
            $product = Product::updateOrCreate(
                ['name' => $p['name'] ?? null],
                $productData
            );
            
            Log::info('Product saved', [
                'id' => $product->id,
                'name' => $product->name,
                'product_category_id' => $product->product_category_id,
                'price' => $product->price
            ]);
        }
        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
