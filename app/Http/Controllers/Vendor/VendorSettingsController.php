<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorSetting;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VendorSettingsController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $settings = $user->vendorSetting;
        return Inertia::render('Vendor/Settings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_detail' => 'nullable|string|max:1000',
            'url' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:50',
            'banner' => 'nullable|image|max:2048',
            'logo' => 'nullable|image|max:1024',
        ]);

        $settings = $user->vendorSetting ?: new VendorSetting(['user_id' => $user->id]);

        // Handle banner upload
        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('vendor_banners', 'public');
            $settings->banner = $bannerPath;
        }
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('vendor_logos', 'public');
            $settings->logo = $logoPath;
        }

        $settings->company_name = $validated['company_name'] ?? $settings->company_name;
        $settings->company_detail = $validated['company_detail'] ?? $settings->company_detail;
        $settings->url = $validated['url'] ?? $settings->url;
        $settings->contact_email = $validated['contact_email'] ?? $settings->contact_email;
        $settings->phone_number = $validated['phone_number'] ?? $settings->phone_number;
        $settings->user_id = $user->id;
        
        // Set status to active if this is a new settings record
        if (!$settings->exists) {
            $settings->status = 1;
        }
        
        $settings->save();

        return redirect()->back()->with('message', 'Settings updated successfully!');
    }
} 