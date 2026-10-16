<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Thin wrapper over Cache::remember with a global version counter so
 * every cached frontend view busts in one shot when a brand, product,
 * vendor_setting, or promotion changes.
 *
 * Driver-agnostic — doesn't rely on cache tags, which the database
 * driver (current default) doesn't support. All keys get prefixed with
 * `fe:v{N}:` where N is the current version. Bumping the version makes
 * the old keys unreachable; they expire naturally.
 *
 * Guests only — authenticated requests skip the cache entirely so admin
 * edits reflect immediately for Julia / Alec / Colin.
 *
 * Colin 10/17 — added so / and /brands stop running the heavy brand
 * list query on every anonymous pageload.
 */
class FrontendCache
{
    private const VERSION_KEY = 'frontend_cache_version';

    public static function remember(string $key, int $ttlSeconds, \Closure $callback)
    {
        // Logged-in users bypass the cache so admin edits are live.
        if (auth()->check()) {
            return $callback();
        }

        $version = Cache::get(self::VERSION_KEY, 1);
        return Cache::remember("fe:v{$version}:{$key}", $ttlSeconds, $callback);
    }

    /**
     * Bump the version. All existing cached frontend entries become
     * unreachable (their keys contain the old version) and expire on
     * their own TTLs. Called from FrontendCacheBuster observer on any
     * brand/product/vendor_setting/vendor_promotion write.
     */
    public static function bust(): void
    {
        try {
            Cache::increment(self::VERSION_KEY);
        } catch (\Throwable $e) {
            // Key doesn't exist yet or driver can't increment — set from 1.
            Cache::put(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
        }
    }
}
