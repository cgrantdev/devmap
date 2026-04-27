<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks any non-GET/HEAD request when the authenticated user has the
 * "admin_viewer" role — staff view-only accounts can browse the admin
 * panel but cannot create, update, or delete anything.
 *
 * The logout endpoint is whitelisted so viewers can sign out.
 */
class BlockAdminViewerWrites
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'admin_viewer') {
            $method = strtoupper($request->method());

            if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
                // Whitelist the logout endpoint so viewers can sign out
                if ($request->routeIs('admin.logout') || $request->routeIs('logout')) {
                    return $next($request);
                }

                $message = 'Your account is view-only. You cannot make changes to data.';

                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json(['message' => $message], 403);
                }

                return back()->with('error', $message);
            }
        }

        return $next($request);
    }
}
