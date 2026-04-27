<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = $request->user()?->role;

        if (!$request->user() || !in_array($userRole, $roles, true)) {
            // If any of the allowed roles is admin/admin_viewer, send unauthorized users to admin login
            if (in_array('admin', $roles, true) || in_array('admin_viewer', $roles, true)) {
                return redirect('/admin/login');
            }
            return redirect('/login');
        }

        return $next($request);
    }
} 