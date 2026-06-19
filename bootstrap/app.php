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
        // In Laravel 11+, $middleware->web([...]) APPENDS to the framework's
        // default web stack rather than replacing it. Listing our custom
        // EncryptCookies / VerifyCsrfToken classes there caused cookies to be
        // double-encrypted on response (XSRF-TOKEN got wrapped twice) and
        // CSRF to be checked twice on request. Every admin form / delete
        // failed with 419 because the decrypted header still came back
        // encrypted from the second wrap.
        //
        // Append-only for actually-new middleware. Configure CSRF exceptions
        // via the framework helper instead of subclassing VerifyCsrfToken.
        $middleware->web(append: [
            \App\Http\Middleware\ComingSoon::class,
            \App\Http\Middleware\BindDemoMode::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'admin/discover',
            'admin/discover/*',
            'admin/discover/scan',
            'admin/discover/import',
            'admin/discover/activate',
            'admin/vendors/*/scrape',
            'admin/vendors/*/discover-products',
            'api/woo-auth-callback',
            'api/subscribe',
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
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            // Only handle 419 (CSRF/page-expired). Laravel converts
            // TokenMismatchException → HttpException(419) before render
            // callbacks fire, so type-hinting TokenMismatchException
            // never matches in Laravel 12+.
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            // Diagnostic: log what the framework saw vs what's in session,
            // including whether the X-XSRF-TOKEN header decrypts to the
            // same value as the session token. Helps catch APP_KEY mismatch,
            // cookie/session-cookie name collisions, etc.
            try {
                $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
                $header = (string) $request->header('X-XSRF-TOKEN');
                $decrypted = $header ? \Illuminate\Cookie\CookieValuePrefix::remove($encrypter->decrypt($header, false)) : null;
                $sessionTok = $request->hasSession() ? $request->session()->token() : null;
                \Log::warning('csrf 419 caught', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'x_inertia' => $request->header('X-Inertia') ? 'true' : null,
                    'referer' => $request->header('referer'),
                    'header_raw_len' => strlen($header),
                    'header_decrypted_len' => $decrypted !== null ? strlen($decrypted) : null,
                    'header_decrypted' => $decrypted,
                    'session_token' => $sessionTok,
                    'tokens_match' => $decrypted !== null && $sessionTok !== null && hash_equals($sessionTok, $decrypted),
                ]);
            } catch (\Throwable $diag) {
                \Log::warning('csrf 419 caught (decrypt failed)', [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'decrypt_error' => $diag->getMessage(),
                    'header_token_prefix' => substr((string) $request->header('X-XSRF-TOKEN'), 0, 16),
                    'session_token' => $request->hasSession() ? $request->session()->token() : null,
                ]);
            }
            $path = trim($request->path(), '/');
            $host = $request->getHost();

            $isVendorSignup =
                $path === 'become-a-vendor' ||
                $path === 'join' ||
                $host === 'join.peptidemap.com';

            if ($isVendorSignup) {
                $target = $host === 'join.peptidemap.com' ? '/' : '/become-a-vendor';
                if ($request->hasSession()) {
                    $request->session()->regenerateToken();
                }
                $request->session()->flash(
                    'csrf_expired',
                    'Your session timed out. Please re-enter your password to complete registration — the rest of your details are saved.'
                );
                if ($request->header('X-Inertia')) {
                    return response('', 409, ['X-Inertia-Location' => $target]);
                }
                return redirect($target);
            }

            // Admin + vendor dashboard actions: bounce back to the referring
            // page so the user gets a fresh CSRF token. Inertia ignores plain
            // 302s on XHR, so for those we return 409 with X-Inertia-Location.
            //
            // Notes:
            //   - We do NOT regenerate the session token here. The user's
            //     valid token is already in their session — they just sent
            //     a stale one in this request (browser cached old form data,
            //     double-submit, etc.). Regenerating triggered a chain of
            //     mismatches on every subsequent action.
            //   - No flash message — the reload is silent. A persistent
            //     'session refreshed' banner on every click was worse UX
            //     than the original error.
            if (str_starts_with($path, 'admin/') || $path === 'admin' ||
                str_starts_with($path, 'vendor/') || $path === 'vendor') {
                $referer = $request->headers->get('referer');
                $target = $referer ?: '/' . ($path ? explode('/', $path)[0] : 'admin');

                if ($request->header('X-Inertia')) {
                    return response('', 409, ['X-Inertia-Location' => $target]);
                }

                return redirect($target);
            }

            return null; // default 419 page elsewhere
        });
    })->create();
