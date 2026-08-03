<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to info@peptidemap.com — the CEO account.
 * Sits on top of the auth + role:admin gates on the admin route group.
 * The whitelist is intentionally hard-coded because the CEO is one person;
 * if that changes, promote this to a `role:ceo` DB flag.
 */
class CeoOnly
{
    private const WHITELIST = ['info@peptidemap.com'];

    public function handle(Request $request, Closure $next): Response
    {
        $email = Auth::user()?->email;
        if (!$email || !in_array(strtolower($email), self::WHITELIST, true)) {
            abort(403);
        }
        return $next($request);
    }
}
