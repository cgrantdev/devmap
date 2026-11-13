<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendLayerTransport;
use App\Models\Setting;
use App\Support\FrontendCache;

class AppServiceProvider extends ServiceProvider
{
    private const FRONTEND_BUST_LOCK = 'frontend_cache_bust_lock';
    private const FRONTEND_BUST_PENDING = 'frontend_cache_bust_pending';
    private const SITE_SETTINGS_KEY = 'site_settings_v1';

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

        // Frontend cache busting. Scrapers save products every minute, so
        // bumping the cache version on every save meant cached pages never
        // lived long enough to help. Writes now bust at most once a minute;
        // a write inside that window marks a pending bust that the
        // every-minute flush in routes/console.php applies, so no change
        // waits much more than a minute to reach guests.
        foreach ([
            \App\Models\Brand::class,
            \App\Models\Product::class,
            \App\Models\VendorSetting::class,
            \App\Models\VendorPromotion::class,
            \App\Models\VendorCertificationClaim::class,
        ] as $model) {
            $model::saved(fn () => self::queueFrontendBust());
            $model::deleted(fn () => self::queueFrontendBust());
        }

        // Site name / description / contact email are read on every request
        // (shared Inertia props and the Blade head), so they're cached under
        // one key and dropped whenever any setting changes. hero_slides is a
        // setting too, so the cached homepage is busted as well.
        $settingChanged = function () {
            Cache::forget(self::SITE_SETTINGS_KEY);
            self::queueFrontendBust();
        };
        Setting::saved($settingChanged);
        Setting::deleted($settingChanged);

        // Register SendLayer HTTP API mail transport
        Mail::extend('sendlayer', function (array $config = []) {
            return new SendLayerTransport(
                apiKey: $config['api_key'] ?? config('services.sendlayer.api_key', ''),
            );
        });
    }

    /**
     * Bust the frontend cache now if no bust happened in the last minute,
     * otherwise mark one as pending for the scheduled flush.
     */
    public static function queueFrontendBust(): void
    {
        try {
            if (Cache::add(self::FRONTEND_BUST_LOCK, 1, 60)) {
                self::bustFrontendNow();
            } elseif (!Cache::get(self::FRONTEND_BUST_PENDING)) {
                // Read before write: scraper runs save thousands of rows a
                // minute and only the first one needs to set the flag.
                Cache::put(self::FRONTEND_BUST_PENDING, 1, 600);
            }
        } catch (\Throwable $e) {
            // Cache store trouble: fall back to the old bust-per-write, but
            // never let it break the model save that triggered it.
            try {
                FrontendCache::bust();
            } catch (\Throwable $inner) {
                report($inner);
            }
        }
    }

    /**
     * Apply a pending bust. Runs every minute from the scheduler.
     */
    public static function flushPendingFrontendBust(): void
    {
        if (Cache::pull(self::FRONTEND_BUST_PENDING)) {
            self::bustFrontendNow();
            Cache::put(self::FRONTEND_BUST_LOCK, 1, 60);
        }
    }

    /**
     * FrontendCache::bust() increments the version counter, but the
     * database cache store's increment() returns false without creating
     * the row when the key is missing, which would leave every bust a
     * no-op. Seed the counter first (same key as FrontendCache's private
     * VERSION_KEY; add() is a no-op when it already exists).
     */
    private static function bustFrontendNow(): void
    {
        Cache::add('frontend_cache_version', 1);
        FrontendCache::bust();
    }

    /**
     * site_name, site_description and contact_email keyed by setting name.
     * Cleared on any Setting save/delete (see boot()).
     */
    public static function siteSettings(): array
    {
        return Cache::remember(self::SITE_SETTINGS_KEY, 3600, fn () => Setting::query()
            ->whereIn('key', ['site_name', 'site_description', 'contact_email'])
            ->pluck('value', 'key')
            ->all());
    }
}
