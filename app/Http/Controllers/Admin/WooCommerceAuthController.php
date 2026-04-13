<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ScrapingConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WooCommerceAuthController extends Controller
{
    /**
     * Generate the WooCommerce auth URL for a vendor.
     * The vendor clicks this and approves read access on their WooCommerce site.
     */
    public function generateAuthUrl($vendorId)
    {
        $brand = Brand::with('vendorSetting')->findOrFail($vendorId);
        $storeUrl = rtrim($brand->vendorSetting->shop_url ?? '', '/');

        if (!$storeUrl) {
            return back()->with('error', 'Store URL is required. Set it in the Integration tab first.');
        }

        $callbackUrl = url("/api/woo-auth-callback");
        $returnUrl = url("/admin/vendors/{$vendorId}/edit");

        $authUrl = $storeUrl . '/woocommerce-auth/v1/authorize?' . http_build_query([
            'app_name' => 'PeptideMaps',
            'scope' => 'read',
            'user_id' => $vendorId,
            'return_url' => $returnUrl,
            'callback_url' => $callbackUrl,
        ]);

        return redirect()->away($authUrl);
    }

    /**
     * Callback from WooCommerce after the vendor approves access.
     * WooCommerce POSTs: consumer_key, consumer_secret, key_permissions
     */
    public function handleCallback(Request $request)
    {
        Log::info('WooCommerce auth callback received', $request->all());

        $vendorId = $request->input('user_id');
        $consumerKey = $request->input('consumer_key');
        $consumerSecret = $request->input('consumer_secret');

        if (!$vendorId || !$consumerKey || !$consumerSecret) {
            Log::error('WooCommerce callback missing required fields', $request->all());
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        $brand = Brand::with('vendorSetting')->find($vendorId);
        if (!$brand) {
            Log::error('WooCommerce callback: vendor not found', ['vendor_id' => $vendorId]);
            return response()->json(['error' => 'Vendor not found'], 404);
        }

        // Update vendor settings
        if ($brand->vendorSetting) {
            $brand->vendorSetting->update([
                'api_platform' => 'woocommerce',
                'api_key' => $consumerKey,
            ]);
        }

        // Create or update scraping config with both keys
        $config = ScrapingConfig::updateOrCreate(
            ['vendor_id' => $brand->id],
            [
                'vendor_name' => $brand->name,
                'type' => 'woo_api',
                'store_url' => $brand->vendorSetting->shop_url ?? '',
                'products_url' => $brand->vendorSetting->shop_url ?? '',
                'auth_credentials' => [
                    'consumer_key' => $consumerKey,
                    'consumer_secret' => $consumerSecret,
                ],
                'enabled' => true,
                'frequency' => 'daily',
                'auto_promote' => true,
            ]
        );

        // Auto-trigger first product sync
        try {
            \App\Jobs\RunWooCommerceIngestJob::dispatch($config);
            Log::info('WooCommerce auto-sync triggered', ['vendor_id' => $vendorId]);
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch WooCommerce sync', ['error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }
}
