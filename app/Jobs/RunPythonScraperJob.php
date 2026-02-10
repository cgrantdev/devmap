<?php

namespace App\Jobs;

use App\Models\ScrapingConfig;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $pythonBin = dirname(base_path()) . '/venv/bin/python3.12';
        // $pythonBin = '/usr/bin/python3';
        $pythonScript = base_path() . '/pyscripts/script.py';        
        
        $escapedPayload = escapeshellarg($payload);
        
        $command = 'sudo -u devuser ' . $pythonBin . ' ' . escapeshellarg($pythonScript) . ' ' . $escapedPayload . ' 2>&1';
        
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
            
            // Generate slug from product name
            $productName = $p['name'] ?? null;
            if (empty($productName)) {
                Log::warning('Product name is empty, skipping', ['product' => $p]);
                continue;
            }
            
            // Check if product already exists
            $existingProduct = Product::where('name', $productName)->first();
            
            // If product exists and has a slug, use it; otherwise generate a new one
            if ($existingProduct && $existingProduct->slug) {
                $slug = $existingProduct->slug;
            } else {
                $baseSlug = Str::slug($productName);
                
                // If slug is empty after processing, use a fallback
                if (empty($baseSlug)) {
                    $baseSlug = 'product-' . time() . '-' . rand(1000, 9999);
                }
                
                // Truncate slug if too long (max 191 chars for MySQL)
                if (strlen($baseSlug) > 191) {
                    $baseSlug = substr($baseSlug, 0, 191);
                }
                
                // Ensure unique slug (exclude existing product if it exists)
                $slug = $baseSlug;
                $counter = 1;
                $query = Product::where('slug', $slug);
                if ($existingProduct) {
                    $query->where('id', '!=', $existingProduct->id);
                }
                
                while ($query->exists()) {
                    $suffix = '-' . $counter;
                    $maxLength = 191 - strlen($suffix);
                    $slug = substr($baseSlug, 0, $maxLength) . $suffix;
                    $counter++;
                    
                    // Rebuild query for next iteration
                    $query = Product::where('slug', $slug);
                    if ($existingProduct) {
                        $query->where('id', '!=', $existingProduct->id);
                    }
                    
                    // Safety check to prevent infinite loop
                    if ($counter > 1000) {
                        $slug = 'product-' . time() . '-' . rand(1000, 9999);
                        break;
                    }
                }
            }
            
            $productData = [
                'name' => $productName,
                'slug' => $slug,
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
            
            // Check if product exists and if auto_update is enabled
            if ($existingProduct) {
                // If product exists, only update if auto_update is true
                if ($existingProduct->auto_update) {
                    $existingProduct->update($productData);
                    $product = $existingProduct->fresh();
                    Log::info('Product updated', [
                        'id' => $product->id,
                        'name' => $product->name,
                        'product_category_id' => $product->product_category_id,
                        'price' => $product->price
                    ]);
                } else {
                    Log::info('Product skipped (auto_update is false)', [
                        'id' => $existingProduct->id,
                        'name' => $existingProduct->name
                    ]);
                    continue;
                }
            } else {
                // Create new product
                $product = Product::create($productData);
                Log::info('Product created', [
                'id' => $product->id,
                'name' => $product->name,
                'product_category_id' => $product->product_category_id,
                'price' => $product->price
            ]);
            }
        }
        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
