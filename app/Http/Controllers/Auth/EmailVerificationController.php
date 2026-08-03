<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    public function show()
    {
        // If user is already verified, redirect to the right home base
        if (Auth::check() && Auth::user()->hasVerifiedEmail()) {
            return redirect($this->postVerifyDestination(Auth::user()));
        }

        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(Request $request, $id = null, $hash = null)
    {
        \Log::info('Verification attempt', [
            'url' => $request->url(),
            'route_id' => $id,
            'route_hash' => $hash,
            'has_expires' => $request->has('expires'),
            'has_signature' => $request->has('signature'),
            'auth_check' => Auth::check(),
            'auth_id' => Auth::id()
        ]);

        // Handle signed URL verification (when expires and signature are present)
        if ($request->has('expires') && $request->has('signature')) {
            if (!\Illuminate\Support\Facades\URL::hasValidSignature($request)) {
                return redirect('/login')->with('error', 'Invalid or expired verification link.');
            }

            // Use route parameter id
            if (!$id) {
                return redirect('/login')->with('error', 'Invalid verification link.');
            }

            $user = \App\Models\User::find($id);
        } 
        // Handle route parameter verification (when id and hash are provided as route parameters)
        else if (!empty($id) && !empty($hash)) {
            $user = \App\Models\User::find($id);

            if (!$user) {
                \Log::error('Verification failed: User not found', ['id' => $id]);
                return redirect('/login')->with('error', 'Invalid verification link.');
            }

            // Check if the hash matches
            if (!hash_equals(
                (string) $hash,
                sha1($user->getEmailForVerification())
            )) {
                \Log::error('Verification failed: Hash mismatch', [
                    'user_id' => $user->id,
                    'request_hash' => $hash,
                    'expected_hash' => sha1($user->getEmailForVerification())
                ]);
                return redirect('/login')->with('error', 'Invalid verification link.');
            }
        } else {
            return redirect('/login')->with('error', 'Invalid verification link format.');
        }

        if (!$user) {
            return redirect('/login')->with('error', 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/login')->with('info', 'Email already verified.');
        }

        // Mark email as verified
        $user->markEmailAsVerified();
        \Log::info('Email verified successfully', ['user_id' => $user->id]);

        // If user is logged in, redirect to appropriate destination
        if (Auth::check() && Auth::id() == $user->id) {
            return redirect($this->postVerifyDestination($user))->with('success', 'Email verified successfully!');
        }

        // If user is not logged in, redirect to login page
        return redirect('/login')->with('success', 'Email verified successfully! You can now log in.');
    }

    /**
     * Where a user lands right after verifying their email. Honors a
     * pending `post_verify_redirect` (set when they arrived via a
     * "sign in to leave a review" CTA) before falling back to role defaults.
     */
    protected function postVerifyDestination($user): string
    {
        $pending = session()->pull('post_verify_redirect');
        if ($pending && str_starts_with($pending, '/') && !str_starts_with($pending, '//')) {
            return $pending;
        }

        if ($user->canAccessAdmin()) {
            return '/admin/dashboard';
        }
        if ($user->isVendor()) {
            return '/vendor/dashboard';
        }

        return '/';
    }

    public function resend(Request $request)
    {
        // If user is logged in, use their email
        if (Auth::check()) {
            $user = Auth::user();
        } else {
            $request->validate([
                'email' => 'required|email',
            ]);
            $user = \App\Models\User::where('email', $request->email)->first();
        }

        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        if ($user->hasVerifiedEmail()) {
            return back()->with('info', 'Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent!');
    }
} 