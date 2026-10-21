<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;

/**
 * Quick vendor row inspector — reports the integration-relevant
 * fields for a brand slug or id so Claude can tell whether a sync
 * is ready to fire or still waiting on config.
 */
class VendorInspect extends Command
{
    protected $signature = 'vendor:inspect {brand : slug or id}';
    protected $description = 'Print integration fields for one brand (shop_url, feed_url, api_platform, api_key presence).';

    public function handle(): int
    {
        $arg = $this->argument('brand');
        $brand = ctype_digit((string) $arg)
            ? Brand::with('vendorSetting', 'vendorSetting.location')->find((int) $arg)
            : Brand::with('vendorSetting', 'vendorSetting.location')->where('slug', $arg)->first();

        if (!$brand) {
            $this->error("Brand '{$arg}' not found.");
            return self::FAILURE;
        }

        $vs = $brand->vendorSetting;
        $this->line("ID:            {$brand->id}");
        $this->line("Name:          {$brand->name}");
        $this->line("Slug:          {$brand->slug}");
        $this->line("Active:        " . ($brand->is_active ? 'yes' : 'no'));
        if ($vs) {
            $this->line("Approval:      " . ($vs->approval_status ?? 'n/a'));
            $this->line("Location:      " . ($vs->location?->name ?? 'n/a'));
            $this->line("Website:       " . ($vs->website ?: '-'));
            $this->line("Shop URL:      " . ($vs->shop_url ?: '-'));
            $this->line("Feed URL:      " . ($vs->feed_url ?: '-'));
            $this->line("API platform:  " . ($vs->api_platform ?: '-'));
            $this->line("API key set?:  " . ($vs->api_key ? 'yes (len ' . strlen($vs->api_key) . ')' : 'no'));
            $this->line("Last synced:   " . ($vs->feed_last_synced_at?->toIso8601String() ?: 'never'));
        }
        $this->line("Products:      " . $brand->products()->count());
        return self::SUCCESS;
    }
}
