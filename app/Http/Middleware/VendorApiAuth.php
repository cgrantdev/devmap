<?php

namespace App\Http\Middleware;

use App\Models\VendorApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization', '');
        $plain = str_starts_with($auth, 'Bearer ') ? substr($auth, 7) : null;

        if (!$plain) {
            return response()->json(['error' => 'Missing Bearer token'], 401);
        }

        $token = VendorApiToken::findByPlaintext($plain);
        if (!$token) {
            return response()->json(['error' => 'Invalid or revoked token'], 401);
        }

        // Best-effort audit trail. Skip the model event to keep updated_at
        // stable for cache-busting elsewhere.
        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        // Downstream controllers pull the authenticated brand off the request.
        $request->attributes->set('vendor_api_token', $token);
        $request->attributes->set('vendor_brand_id', $token->brand_id);

        return $next($request);
    }
}
