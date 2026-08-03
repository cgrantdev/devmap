<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    public function create(Request $request)
    {
        return Inertia::render('Auth/Register', [
            'redirect' => $request->query('redirect'),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($request) {
                        $role = $request->input('role', 'vendor');
                        if ($role === 'vendor' && \App\Models\User::where('name', $value)->where('role', 'vendor')->exists()) {
                            $fail('The vendor name has already been taken.');
                        }
                    },
                ],
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'role' => 'sometimes|string|in:customer,vendor,admin',
                'redirect' => 'sometimes|nullable|string|max:2048',
            ]);

            $role = $validated['role'] ?? 'customer';

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                // Default to customer if not explicitly provided
                'role' => $role,
            ]);

            event(new Registered($user));

            // Customers must verify their email before leaving a review
            // (see EnsureEmailIsVerified). Vendor/admin onboarding is
            // pre-verified so those flows aren't blocked mid-review.
            if ($role === 'customer') {
                $user->sendEmailVerificationNotification();
            } else {
                $user->email_verified_at = now();
                $user->save();
            }

            // Log the user in
            Auth::login($user);

            // Stash the post-verification destination (e.g. back to the
            // brand page's reviews section) for a safe local redirect only.
            $redirect = $validated['redirect'] ?? null;
            if ($redirect && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
                session(['post_verify_redirect' => $redirect]);
            }

            $message = $role === 'customer'
                ? 'Registration successful! Please check your email to verify your account.'
                : 'Registration successful! Your account is currently under review and should be activated shortly.';

            return redirect('/email/verify')->with('success', $message);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'An error occurred during registration. Please try again.',
            ])->withInput();
        }
    }
} 