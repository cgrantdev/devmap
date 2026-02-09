<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunPythonScraperJob;
use App\Models\ScrapingConfig;
use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;

class ProductScrapingController extends Controller
{
    public function index()
    {
        // Check if scraping_configs table exists
        $hasScrapingConfigsTable = Schema::hasTable('scraping_configs');
        
        if ($hasScrapingConfigsTable) {
            $scrapingConfigs = ScrapingConfig::with('vendor')
                ->orderBy('vendor_name')
                ->get()
                ->map(function ($config) {
                    return [
                        'id' => $config->id,
                        'vendor_id' => $config->vendor_id,
                        'vendor_name' => $config->vendor_name,
                        'products_url' => $config->products_url,
                        'enabled' => $config->enabled,
                        'frequency' => $config->frequency,
                        'last_run_at' => $config->last_run_at?->toIso8601String(),
                        'next_run_at' => $config->next_run_at?->toIso8601String(),
                        'success_count' => $config->success_count,
                        'error_count' => $config->error_count,
                        'last_error' => $config->last_error,
                        'selectors' => $config->selectors,
                    ];
                });
        } else {
            $scrapingConfigs = [];
        }
        
        // Check if auto_scraped column exists in products table
        $hasAutoScrapedColumn = Schema::hasColumn('products', 'auto_scraped');
        
        if ($hasAutoScrapedColumn) {
            $products = Product::where('auto_scraped', true)
                ->with('brand')
                ->orderBy('last_scraped_at', 'desc')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'dosage' => $product->dosage ?? '',
                        'size_mg' => $product->size_mg ?? '',
                        'price' => $product->price,
                        'discount_price' => $product->discount_price,
                        'brand_id' => $product->brand_id,
                        'product_category_id' => $product->product_category_id,
                        'vendor_name' => $product->brand?->name ?? 'Unknown',
                        'stock_status' => $product->stock_status ?? 'in-stock',
                        'image_url' => $product->image_url,
                        'source_url' => $product->source_url ?? $product->product_url ?? null,
                        'last_scraped_at' => $product->last_scraped_at?->toIso8601String(),
                        'auto_scraped' => $product->auto_scraped,
                        'manual_override' => $product->manual_override ?? false,
                        'purity' => $product->purity,
                        'featured' => $product->featured ?? false,
                        'hidden' => $product->hidden ?? false,
                        'lab_tested' => $product->lab_tested ?? false,
                        'first_timer_deals' => $product->first_timer_deals ?? false,
                    ];
                });
        } else {
            $products = [];
        }

        $vendors = Brand::all();
        
        // Get brands and categories for product edit modal
        $brands = Brand::orderBy('name')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                ];
            });

        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                ];
            });
        
        return Inertia::render('Admin/ProductScraping', [
            'scrapingConfigs' => $scrapingConfigs,
            'products' => $products,
            'vendors' => $vendors,
            'brands' => $brands,
            'categories' => $categories,
        ]);
    }

    public function toggle($id)
    {
        if (!Schema::hasTable('scraping_configs')) {
            return redirect()->back()->with('error', 'Scraping configs table does not exist. Please run migrations.');
        }
        
        $config = ScrapingConfig::findOrFail($id);
        $config->enabled = !$config->enabled;
        $config->save();

        return redirect()->back()->with('success', 'Scraping config updated.');
    }

    public function scrape($id)
    {
        if (!Schema::hasTable('scraping_configs')) {
            return redirect()->back()->with('error', 'Scraping configs table does not exist. Please run migrations.');
        }
        
        $config = ScrapingConfig::findOrFail($id);
        
        // TODO: Dispatch scraping job here
        // Example: dispatch(new ScrapeProductsJob($config));
        RunPythonScraperJob::dispatch($config);
        
        $config->last_run_at = now();
        $config->calculateNextRunAt();
        $config->save();

        return redirect()->back()->with('success', 'Python scraping job queued.');
    }

    public function edit($id)
    {
        $config = ScrapingConfig::findOrFail($id);
        return Inertia::render('Admin/ProductScrapingEdit', [
            'config' => $config
        ]);
    }

    public function toggleOverride($id)
    {
        if (!Schema::hasTable('scraped_products')) {
            return redirect()->back()->with('error', 'Scraped products table does not exist. Please run migrations.');
        }
        
        $product = Product::findOrFail($id);
        $product->manual_override = !$product->manual_override;
        $product->save();

        return redirect()->back()->with('success', 'Product override toggled.');
    }
}

