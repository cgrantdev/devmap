<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;

/**
 * One-shot setter for Zenith's bearer token. Writes to
 * vendor_settings.api_key (encrypted at rest via the cast) and
 * confirms by reading it back and masking all but the first/last 4.
 *
 * Could just do this in the admin UI, but since the whole point of
 * the dev console is zero-friction Claude-driven ops, it's a one-liner.
 */
class SetZenithToken extends Command
{
    protected $signature = 'zenith:set-token {token : The bearer token}';
    protected $description = 'Store Zenith Bioscience bearer token on their vendor row.';

    public function handle(): int
    {
        $brand = Brand::with('vendorSetting')->where('slug', 'zenith-bioscience')->first();
        if (!$brand || !$brand->vendorSetting) {
            $this->error('Zenith brand row (zenith-bioscience) or its vendorSetting missing.');
            return self::FAILURE;
        }

        $token = trim($this->argument('token'));
        if (strlen($token) < 20) {
            $this->error('Token looks malformed (too short).');
            return self::FAILURE;
        }

        $brand->vendorSetting->forceFill([
            'api_key' => $token,
            'api_platform' => 'custom',
        ])->save();

        $readback = $brand->vendorSetting->fresh()->api_key;
        $masked = substr($readback, 0, 4) . '…' . substr($readback, -4);
        $this->info("Set. Readback: {$masked} (len " . strlen($readback) . ')');
        return self::SUCCESS;
    }
}
