<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendLayerTransport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Some servers don't have the `mbstring` extension enabled, but common dependencies
        // may still call `mb_*` functions while rendering emails.
        require_once app_path('Support/mbstring_polyfill.php');

        Schema::defaultStringLength(191);

        // Frontend cache busting — any write to a model that drives
        // public-facing content increments FrontendCache's version
        // counter so cached brand lists / compare pages / homepage
        // widgets drop stale state within one request.
        foreach ([
            \App\Models\Brand::class,
            \App\Models\Product::class,
            \App\Models\VendorSetting::class,
            \App\Models\VendorPromotion::class,
            \App\Models\VendorCertificationClaim::class,
        ] as $model) {
            $model::observe(\App\Observers\FrontendCacheBuster::class);
        }

        // Register SendLayer HTTP API mail transport
        Mail::extend('sendlayer', function (array $config = []) {
            return new SendLayerTransport(
                apiKey: $config['api_key'] ?? config('services.sendlayer.api_key', ''),
            );
        });
    }
}
