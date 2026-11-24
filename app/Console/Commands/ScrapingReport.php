<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use Illuminate\Console\Command;

/**
 * Read-only: how each vendor's products are imported. For each brand,
 * prints the platform and URLs from vendor settings, every scraping config
 * (type, URLs, schedule, last run, last error, staged rows by status, a
 * few rejected rows), and product counts. Credentials are reported only
 * as set or not set.
 *
 *   php artisan scraping:report vancouver-island-peptides peptix-usa-llc
 */
class ScrapingReport extends Command
{
    protected $signature = 'scraping:report {brands* : Brand slugs}';

    protected $description = 'Read-only import report per vendor: configs, last run and error, staged and live product counts.';

    public function handle(): int
    {
        foreach ($this->argument('brands') as $slug) {
            $brand = Brand::with('vendorSetting')->where('slug', $slug)->first();
            if (!$brand) {
                $this->warn("== {$slug}: no brand with this slug");
                continue;
            }

            $settings = $brand->vendorSetting;
            $this->line("== {$brand->name} ({$brand->slug}, brand #{$brand->id}, " . ($brand->is_active ? 'active' : 'inactive') . ')');
            $this->line('   platform: ' . ($settings?->api_platform ?: '-')
                . ' | shop_url: ' . ($settings?->shop_url ?: '-')
                . ' | feed_url: ' . ($settings?->feed_url ?: '-')
                . ' | feed synced: ' . ($settings?->feed_last_synced_at ?: '-'));

            $configs = ScrapingConfig::where('vendor_id', $brand->id)->orderBy('id')->get();
            if ($configs->isEmpty()) {
                $this->line('   no scraping config');
            }
            foreach ($configs as $config) {
                $staged = ScrapedProduct::where('scraping_config_id', $config->id)
                    ->selectRaw('status, COUNT(*) AS n')
                    ->groupBy('status')
                    ->pluck('n', 'status')
                    ->all();
                $this->line("   config #{$config->id}: {$config->type}, " . ($config->enabled ? 'enabled' : 'disabled')
                    . ", {$config->frequency}, auto_promote " . ($config->auto_promote ? 'on' : 'off'));
                $this->line('     products_url: ' . ($config->products_url ?: '-') . ' | store_url: ' . ($config->store_url ?: '-'));
                $this->line('     selectors: ' . json_encode($config->selectors)
                    . ' | credentials: ' . (empty($config->getRawOriginal('auth_credentials')) ? 'none' : 'set'));
                $this->line('     last run: ' . ($config->last_run_at ?: 'never') . ' | next: ' . ($config->next_run_at ?: '-')
                    . " | ok {$config->success_count} / errors {$config->error_count}");
                $this->line('     last error: ' . ($config->last_error ?: '-'));
                $this->line('     staged: ' . ($staged === [] ? 'none' : json_encode($staged)));
                $rejected = ScrapedProduct::where('scraping_config_id', $config->id)
                    ->where('status', ScrapedProduct::STATUS_REJECTED)
                    ->orderByDesc('updated_at')
                    ->limit(3)
                    ->get(['name', 'price', 'source_url']);
                foreach ($rejected as $row) {
                    $this->line("       rejected: {$row->name} | {$row->price} | {$row->source_url}");
                }
            }

            $all = Product::where('brand_id', $brand->id);
            $this->line('   products: ' . (clone $all)->count() . ' total, '
                . (clone $all)->where('hidden', false)->count() . ' not hidden, '
                . Product::visible()->where('brand_id', $brand->id)->count() . ' visible');
            foreach ((clone $all)->orderByDesc('id')->limit(3)->get(['id', 'name', 'price', 'hidden', 'product_url']) as $product) {
                $this->line("     #{$product->id} {$product->name} | {$product->price}" . ($product->hidden ? ' | hidden' : '') . " | {$product->product_url}");
            }
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
