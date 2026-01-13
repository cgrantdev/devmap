<?php

namespace App\Jobs;

use App\Models\ScrapingConfig;
use App\Models\ScrapedProduct;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\Process\Process;
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
        
        $process = new Process([
            'python3',
            base_path('pyscripts/script.py'),
            $payload
        ]);

        $process->setTimeout(120); // avoid hanging forever
        $process->run();
        Log::info('Python stdout', [ 'stdout' => $process->getOutput() ]);

        Log::info('Python stderr', [ 'stderr' => $process->getErrorOutput() ]);

        if (! $process->isSuccessful()) {
            $this->config->error_count++;
            $this->config->last_error = $process->getErrorOutput();
            $this->config->save();
            return;
        }

        $output = $process->getOutput();
        $data = json_decode($output, true);

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
                'price' => $product->price
            ]);
        }
        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->save();
    }
}
