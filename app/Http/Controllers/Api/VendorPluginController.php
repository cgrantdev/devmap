<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VendorPluginUrlChanged;
use App\Models\Brand;
use App\Models\ScrapingConfig;
use App\Models\Scopes\DemoScope;
use App\Support\StoreUrlGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Receives auto-generated WooCommerce REST API credentials from the
 * "PeptideMap Connect" WordPress plugin. Vendors install the plugin,
 * paste their per-brand connection_token, and the plugin POSTs the keys
 * here so they don't have to manually copy/paste anything.
 *
 * Extra checks (throttle, hashed tokens, single-use, expiry, SSRF
 * rejection, generic errors, URL-change alerts) are config flags in
 * config/vendor_plugin.php and are off unless the owner enables them.
 */
class VendorPluginController extends Controller
{
    private const GENERIC_FAILURE = 'Could not connect this store.';

    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connection_token' => ['required', 'string', 'max:80'],
            'store_url' => ['required', 'url', 'max:255'],
            'consumer_key' => ['required', 'string', 'min:10', 'max:255', 'starts_with:ck_'],
            'consumer_secret' => ['required', 'string', 'min:10', 'max:255', 'starts_with:cs_'],
        ]);

        $target = null;
        if (config('vendor_plugin.reject_private_store_urls')) {
            $target = StoreUrlGuard::resolveAllowed($validated['store_url']);
            if ($target === null) {
                return $this->failure('That store URL is not allowed.', 422);
            }
        }

        $brand = $this->findBrand($validated['connection_token']);

        if (!$brand) {
            return $this->failure(
                'Connection token not recognized. Check the token PeptideMap gave you and try again.',
                404
            );
        }

        if ($this->tokenExpired($brand)) {
            return $this->failure('Connection token is no longer valid.', 422);
        }

        if (config('vendor_plugin.single_use_tokens') && $brand->connection_token_used_at) {
            return $this->failure('Connection token has already been used.', 422);
        }

        // Optional sanity-test the credentials against the vendor's store
        // before saving — prevents storing keys that don't actually work.
        $endpoint = rtrim($validated['store_url'], '/') . '/wp-json/wc/v3/products';
        try {
            $pending = Http::withBasicAuth($validated['consumer_key'], $validated['consumer_secret'])
                ->timeout(15)
                ->acceptJson();
            if ($target !== null) {
                // No redirects, and pin the connection to the vetted IP so a
                // DNS change between the check and the request cannot
                // redirect the probe to an internal address.
                $options = ['allow_redirects' => false];
                if (!filter_var($target['host'], FILTER_VALIDATE_IP)) {
                    $ip = $target['ips'][0];
                    $options['curl'] = [CURLOPT_RESOLVE => [
                        sprintf('%s:%d:%s', $target['host'], $target['port'], str_contains($ip, ':') ? '['.$ip.']' : $ip),
                    ]];
                }
                $pending = $pending->withOptions($options);
            }
            $test = $pending->get($endpoint, ['per_page' => 1]);

            if (!$test->successful()) {
                return $this->failure(
                    'Could not authenticate against your WooCommerce REST API. Status: ' . $test->status(),
                    422
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Plugin connect: HTTP test failed', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);
            return $this->failure(
                'Could not reach your store. Verify the URL is publicly accessible: ' . $e->getMessage(),
                422
            );
        }

        // Claim a single-use token atomically before writing anything, so
        // two concurrent requests cannot both use it.
        if (config('vendor_plugin.single_use_tokens')) {
            $claimed = Brand::withoutGlobalScope(DemoScope::class)
                ->whereKey($brand->id)
                ->whereNull('connection_token_used_at')
                ->update(['connection_token_used_at' => now()]);
            if ($claimed === 0) {
                return $this->failure('Connection token has already been used.', 422);
            }
        }

        $existing = ScrapingConfig::where('vendor_id', $brand->id)
            ->where('type', 'woo_api')
            ->first();
        $previousStore = $existing?->store_url;
        $previousFeed = $existing?->products_url;

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

        try {
            $this->alertIfUrlChanged($brand, $existing !== null, $previousStore, $previousFeed, $validated['store_url']);
        } catch (\Throwable $e) {
            // A mail or log-channel failure must not turn a completed
            // connect into a 500 for the vendor.
            Log::warning('Plugin connect: URL-change alert failed', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Update vendor settings to reflect they're now using the API
        if ($brand->vendorSetting) {
            $brand->vendorSetting->api_platform = 'woocommerce';
            $brand->vendorSetting->save();
        }

        Log::info('Plugin connect succeeded', [
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

    private function findBrand(string $presented): ?Brand
    {
        $query = Brand::withoutGlobalScope(DemoScope::class);

        if (!config('vendor_plugin.hash_tokens')) {
            return $query->where('connection_token', $presented)->first();
        }

        $digest = hash('sha256', $presented);
        $brand = $query->where('connection_token', $digest)->first();
        if (!$brand || !hash_equals((string) $brand->connection_token, $digest)) {
            return null;
        }

        return $brand;
    }

    private function tokenExpired(Brand $brand): bool
    {
        $ttl = config('vendor_plugin.token_ttl_minutes');
        if (!$ttl) {
            return false;
        }

        // Legacy rows have no issue time. They stay valid until rotated,
        // which is what stamps connection_token_issued_at.
        if (!$brand->connection_token_issued_at) {
            return false;
        }

        return $brand->connection_token_issued_at->lte(now()->subMinutes((int) $ttl));
    }

    private function alertIfUrlChanged(Brand $brand, bool $hadConfig, ?string $previousStore, ?string $previousFeed, string $storeUrl): void
    {
        if (!config('vendor_plugin.alert_on_url_change') || !$hadConfig) {
            return;
        }

        $storeChanged = (string) $previousStore !== $storeUrl;
        $feedChanged = (string) $previousFeed !== $storeUrl;
        if (!$storeChanged && !$feedChanged) {
            return;
        }

        $context = [
            'brand_id' => $brand->id,
            'brand_slug' => $brand->slug,
            'previous_store_url' => $previousStore,
            'previous_products_url' => $previousFeed,
            'store_url' => $storeUrl,
        ];

        Log::channel((string) config('vendor_plugin.alert_log_channel', 'stack'))
            ->warning('Plugin connect changed a vendor store URL', $context);

        $to = config('vendor_plugin.alert_mail');
        if (is_string($to) && $to !== '') {
            Mail::to($to)->send(VendorPluginUrlChanged::forBrand(
                $brand,
                $previousStore,
                $previousFeed,
                $storeUrl
            ));
        }
    }

    private function failure(string $specific, int $status): JsonResponse
    {
        if (config('vendor_plugin.generic_errors')) {
            return response()->json([
                'ok' => false,
                'message' => self::GENERIC_FAILURE,
            ], 422);
        }

        return response()->json([
            'ok' => false,
            'message' => $specific,
        ], $status);
    }
}
