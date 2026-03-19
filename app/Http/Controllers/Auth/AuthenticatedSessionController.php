<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request)
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            // Support both customer + vendor logins here.
            // Admins should use the admin login page.
            if ($request->user()->role === 'admin') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Please use the admin login page for admin access.',
                ])->withInput();
            }

            if (!in_array($request->user()->role, ['customer', 'vendor'], true)) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Please use the correct login page for your account type.',
                ])->withInput();
            }

            if (!$request->user()->hasVerifiedEmail()) {
                return redirect('/email/verify')->with('info', 'Please verify your email address before continuing.');
            }

            return $request->user()->role === 'vendor'
                ? redirect()->intended('/vendor/dashboard')
                : redirect()->intended('/');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'An error occurred during login. Please try again.',
            ])->withInput();
        }
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
} 