<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Jobs\RunWooCommerceIngestJob;
use App\Models\Brand;
use App\Models\ScrapingConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class IntegrationsController extends Controller
{
    public function show()
    {
        $brand = $this->currentBrand();
        if (!$brand) {
            return Inertia::render('Vendor/Integrations', ['config' => null]);
        }

        $config = ScrapingConfig::where('vendor_id', $brand->id)
            ->where('type', ScrapingConfig::TYPE_WOO_API)
            ->first();

        return Inertia::render('Vendor/Integrations', [
            'config' => $config ? [
                'id' => $config->id,
                'store_url' => $config->store_url,
                'frequency' => $config->frequency,
                'enabled' => (bool) $config->enabled,
                // Never send credentials back
                'has_credentials' => !empty($config->auth_credentials['consumer_key'] ?? null),
                'last_run_at' => $config->last_run_at?->toDateTimeString(),
                'next_run_at' => $config->next_run_at?->toDateTimeString(),
                'last_error' => $config->last_error,
                'success_count' => $config->success_count,
                'error_count' => $config->error_count,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $brand = $this->currentBrand();
        if (!$brand) {
            return back()->withErrors(['error' => 'Vendor profile required.']);
        }

        $validated = $request->validate([
            'store_url' => 'required|url|max:500',
            'consumer_key' => 'required|string|max:191',
            'consumer_secret' => 'required|string|max:191',
            'frequency' => 'required|in:hourly,daily,weekly',
        ]);

        // Validate the credentials by hitting the WooCommerce API
        $storeUrl = rtrim($validated['store_url'], '/');
        $testEndpoint = $storeUrl . '/wp-json/wc/v3/products';

        try {
            $response = Http::withBasicAuth($validated['consumer_key'], $validated['consumer_secret'])
                ->timeout(15)
                ->acceptJson()
                ->get($testEndpoint, ['per_page' => 1]);

            if ($response->status() === 401 || $response->status() === 403) {
                return back()->withErrors([
                    'consumer_key' => 'Authentication failed. Check your consumer key and secret.',
                ])->withInput();
            }

            if (!$response->successful()) {
                return back()->withErrors([
                    'store_url' => "WooCommerce returned HTTP {$response->status()}. Is the store URL correct and WooCommerce installed?",
                ])->withInput();
            }
        } catch (\Throwable $e) {
            return back()->withErrors([
                'store_url' => 'Could not reach store: ' . $e->getMessage(),
            ])->withInput();
        }

        $config = ScrapingConfig::updateOrCreate(
            [
                'vendor_id' => $brand->id,
                'type' => ScrapingConfig::TYPE_WOO_API,
            ],
            [
                'vendor_name' => $brand->name,
                'products_url' => $storeUrl,
                'store_url' => $storeUrl,
                'auth_credentials' => [
                    'consumer_key' => $validated['consumer_key'],
                    'consumer_secret' => $validated['consumer_secret'],
                ],
                'frequency' => $validated['frequency'],
                'enabled' => true,
                'next_run_at' => now(),
            ]
        );

        // Kick off a first sync immediately
        RunWooCommerceIngestJob::dispatch($config);

        return back()->with('success', 'WooCommerce connection saved. First sync has been queued.');
    }

    public function sync()
    {
        $brand = $this->currentBrand();
        if (!$brand) {
            return back()->withErrors(['error' => 'Vendor profile required.']);
        }

        $config = ScrapingConfig::where('vendor_id', $brand->id)
            ->where('type', ScrapingConfig::TYPE_WOO_API)
            ->firstOrFail();

        RunWooCommerceIngestJob::dispatch($config);
        return back()->with('success', 'Sync queued.');
    }

    public function destroy()
    {
        $brand = $this->currentBrand();
        if (!$brand) {
            return back()->withErrors(['error' => 'Vendor profile required.']);
        }

        ScrapingConfig::where('vendor_id', $brand->id)
            ->where('type', ScrapingConfig::TYPE_WOO_API)
            ->delete();

        return back()->with('success', 'WooCommerce integration disconnected.');
    }

    protected function currentBrand(): ?Brand
    {
        return Brand::where('user_id', Auth::id())->first();
    }
}
