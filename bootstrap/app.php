<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web([
            \App\Http\Middleware\ComingSoon::class,
            \App\Http\Middleware\BindDemoMode::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->api([
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'email.verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'block.viewer.writes' => \App\Http\Middleware\BlockAdminViewerWrites::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle CSRF token mismatches (419) gracefully on the vendor signup
        // pages. The default Laravel 419 page looks like the app is broken —
        // instead bounce back to the form with a flash message so the user
        // can finish (their localStorage draft restores everything except
        // the password fields).
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            $path = trim($request->path(), '/');
            $host = $request->getHost();

            $isVendorSignup =
                $path === 'become-a-vendor' ||
                $path === 'join' ||
                $host === 'join.peptidemap.com';

            if ($isVendorSignup) {
                $target = $host === 'join.peptidemap.com' ? '/' : '/become-a-vendor';
                return redirect($target)->with(
                    'csrf_expired',
                    'Your session timed out. Please re-enter your password to complete registration — the rest of your details are saved.'
                );
            }

            // Admin + vendor dashboard actions: bounce back to the referring
            // page (or the section index) with a flash so the user just sees
            // a brief "session refreshed, try again" instead of the raw 419.
            if (str_starts_with($path, 'admin/') || $path === 'admin' ||
                str_starts_with($path, 'vendor/') || $path === 'vendor') {
                $referer = $request->headers->get('referer');
                $target = $referer ?: '/' . ($path ? explode('/', $path)[0] : 'admin');
                return redirect($target)->with(
                    'error',
                    'Your session refreshed — please try that action again.'
                );
            }

            return null; // default 419 page elsewhere
        });
    })->create();
