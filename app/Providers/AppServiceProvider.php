<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
    }
}
