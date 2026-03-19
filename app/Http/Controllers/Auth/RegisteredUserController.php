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
    public function create()
    {
        return Inertia::render('Auth/Register');
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
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                // Default to customer if not explicitly provided
                'role' => $validated['role'] ?? 'customer',
            ]);

            event(new Registered($user));

            // // Send verification email
            // $user->sendEmailVerificationNotification();
            $user->email_verified_at = now();
            $user->save();

            // Log the user in
            Auth::login($user);

            return redirect('/email/verify')->with('success', 'Registration successful! Please check your email to verify your account.');
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