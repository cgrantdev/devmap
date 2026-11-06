<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * No-ops unless VENDOR_PLUGIN_THROTTLE is on, so the connect route
 * keeps its current unthrottled behaviour by default.
 */
class ThrottleVendorPluginConnect
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('vendor_plugin.throttle')) {
            return $next($request);
        }

        $max = max(1, (int) config('vendor_plugin.throttle_max', 5));
        $decay = max(1, (int) config('vendor_plugin.throttle_decay_seconds', 60));
        $key = 'vendor-plugin-connect:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $max)) {
            return response()->json([
                'ok' => false,
                'message' => 'Too many attempts. Try again later.',
            ], 429);
        }

        RateLimiter::hit($key, $decay);

        return $next($request);
    }
}
