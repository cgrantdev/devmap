<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VendorAuthenticatedSessionController extends Controller
{
    public function create()
    {
        return Inertia::render('Vendor/Login');
    }

    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()->role !== 'vendor') {
            Auth::logout();
            return back()->withErrors([
                'email' => 'These credentials are for vendor access only.',
            ]);
        }

        // Check if email is verified
        if (!$request->user()->hasVerifiedEmail()) {
            return redirect('/email/verify')->with('info', 'Please verify your email address before accessing the vendor dashboard.');
        }

        return redirect()->intended('/vendor/dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/vendor/login');
    }
}
