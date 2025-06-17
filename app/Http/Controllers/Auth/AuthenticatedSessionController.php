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
        return Inertia::render('Login');
    }

    public function store(LoginRequest $request)
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            if ($request->user()->role !== 'vendor') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'These credentials are for vendor access only.',
                ])->withInput();
            }

            return redirect()->intended('/vendor/dashboard');
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