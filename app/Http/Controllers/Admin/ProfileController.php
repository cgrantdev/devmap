<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * In-admin profile editor — change name, email, password without
 * leaving the admin UI. Added so the SEO agency (and anyone else)
 * can rotate the password Colin generated at account creation
 * without routing through forgot-password email.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $u = $request->user();
        return Inertia::render('Admin/Profile', [
            'account' => [
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
            ],
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:191|unique:users,email,' . $u->id,
        ]);
        $u->fill($data)->save();
        return back()->with('flash_success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $u = $request->user();
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($data['current_password'], $u->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $u->forceFill(['password' => Hash::make($data['password'])])->save();
        return back()->with('flash_success', 'Password changed.');
    }
}
