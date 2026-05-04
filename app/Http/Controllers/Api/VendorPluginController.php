<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ScrapingConfig;
use App\Models\Scopes\DemoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Receives auto-generated WooCommerce REST API credentials from the
 * "PeptideMap Connect" WordPress plugin. Vendors install the plugin,
 * paste their per-brand connection_token, and the plugin POSTs the keys
 * here so they don't have to manually copy/paste anything.
 */
class VendorPluginController extends Controller
{
    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connection_token' => ['required', 'string', 'max:80'],
            'store_url' => ['required', 'url', 'max:255'],
            'consumer_key' => ['required', 'string', 'min:10', 'max:255', 'starts_with:ck_'],
            'consumer_secret' => ['required', 'string', 'min:10', 'max:255', 'starts_with:cs_'],
        ]);

        $brand = Brand::withoutGlobalScope(DemoScope::class)
            ->where('connection_token', $validated['connection_token'])
            ->first();

        if (!$brand) {
            return response()->json([
                'ok' => false,
                'message' => 'Connection token not recognized. Verify it in your PeptideMap registration email.',
            ], 404);
        }

        // Optional sanity-test the credentials against the vendor's store
        // before saving — prevents storing keys that don't actually work.
        $endpoint = rtrim($validated['store_url'], '/') . '/wp-json/wc/v3/products';
        try {
            $test = Http::withBasicAuth($validated['consumer_key'], $validated['consumer_secret'])
                ->timeout(15)
                ->acceptJson()
                ->get($endpoint, ['per_page' => 1]);

            if (!$test->successful()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Could not authenticate against your WooCommerce REST API. Status: ' . $test->status(),
                ], 422);
            }
        } catch (\Throwable $e) {
            \Log::warning('Plugin connect: HTTP test failed', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'ok' => false,
                'message' => 'Could not reach your store. Verify the URL is publicly accessible: ' . $e->getMessage(),
            ], 422);
        }

        // Save / update the ScrapingConfig
        $config = ScrapingConfig::updateOrCreate(
            ['vendor_id' => $brand->id, 'type' => 'woo_api'],
            [
                'vendor_name' => $brand->name,
                'store_url' => $validated['store_url'],
                'products_url' => $validated['store_url'],
                'auth_credentials' => [
                    'consumer_key' => $validated['consumer_key'],
                    'consumer_secret' => $validated['consumer_secret'],
                ],
                'enabled' => true,
                'frequency' => 'daily',
                'auto_promote' => true,
            ]
        );

        // Update vendor settings to reflect they're now using the API
        if ($brand->vendorSetting) {
            $brand->vendorSetting->api_platform = 'woocommerce';
            $brand->vendorSetting->save();
        }

        \Log::info('Plugin connect succeeded', [
            'brand_id' => $brand->id,
            'config_id' => $config->id,
            'store_url' => $validated['store_url'],
        ]);

        return response()->json([
            'ok' => true,
            'message' => "Connected successfully. Your products will be imported into PeptideMap shortly.",
            'brand_name' => $brand->name,
        ]);
    }
}
