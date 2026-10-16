<?php

namespace App\Observers;

use App\Support\FrontendCache;

/**
 * Shared observer: every model that affects public-facing content
 * calls FrontendCache::bust() on save/delete. Registered in
 * AppServiceProvider against Brand, Product, VendorSetting, and
 * VendorPromotion.
 */
class FrontendCacheBuster
{
    public function saved($model): void { FrontendCache::bust(); }
    public function deleted($model): void { FrontendCache::bust(); }
}
