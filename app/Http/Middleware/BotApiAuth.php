<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BotApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.bot_api.token');
        if (empty($expected)) {
            abort(500, 'Bot API not configured.');
        }

        $auth = $request->header('Authorization', '');
        $token = str_starts_with($auth, 'Bearer ') ? substr($auth, 7) : null;

        if (!$token || !hash_equals($expected, $token)) {
            abort(401);
        }

        return $next($request);
    }
}
