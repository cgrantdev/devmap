<?php

namespace App\Console\Commands;

use App\Jobs\DiscoverProductsJob;
use App\Models\Brand;
use Illuminate\Console\Command;

/**
 * Trigger the generic page-scraper discovery job for one brand.
 * Mirrors the admin "Discover Products" button but driveable from
 * /admin/dev/console. Used for vendors without an API or clean feed
 * (Bench Grade, future page_scrape vendors).
 */
class DiscoverVendorProducts extends Command
{
    protected $signature = 'vendor:discover {brand : slug or id}';
    protected $description = 'Dispatch DiscoverProductsJob for one brand (uses vendor_settings.shop_url).';

    public function handle(): int
    {
        $arg = $this->argument('brand');
        $brand = ctype_digit((string) $arg)
            ? Brand::with('vendorSetting')->find((int) $arg)
            : Brand::with('vendorSetting')->where('slug', $arg)->first();

        if (!$brand) {
            $this->error("Brand '{$arg}' not found.");
            return self::FAILURE;
        }

        $url = $brand->vendorSetting?->shop_url;
        if (!$url) {
            $this->error("Brand {$brand->name} has no shop_url set.");
            return self::FAILURE;
        }

        DiscoverProductsJob::dispatch($brand, $url);
        $this->info("Dispatched DiscoverProductsJob for {$brand->name} against {$url}.");
        $this->line("Products will populate as the queue worker processes. Check progress with vendor:inspect {$brand->slug}.");
        return self::SUCCESS;
    }
}
