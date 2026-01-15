<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        // Get general settings
        $settings = [];
        $settingsKeys = [
            'site_name',
            'site_description',
            'contact_email',
            'email_new_vendor_registrations',
            'email_new_product_submissions',
            'email_review_moderation_alerts',
            'require_email_verification',
            'enable_2fa_for_admins',
            'maintenance_mode',
            'allow_registration',
            'require_email_verification_platform',
            'banner_ad_price',
            'top_vendor_spot_price',
        ];

        foreach ($settingsKeys as $key) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                $value = $setting->value;
                // Convert string booleans and numbers
                if ($value === 'true' || $value === '1') {
                    $value = true;
                } elseif ($value === 'false' || $value === '0') {
                    $value = false;
                } elseif (is_numeric($value)) {
                    $value = (float) $value;
                }
                $settings[$key] = $value;
            }
        }

        return Inertia::render('Admin/Settings', [
            'settings' => $settings,
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'contact_email' => 'required|email|max:255',
            'email_new_vendor_registrations' => 'boolean',
            'email_new_product_submissions' => 'boolean',
            'email_review_moderation_alerts' => 'boolean',
            'require_email_verification' => 'boolean',
            'enable_2fa_for_admins' => 'boolean',
            'maintenance_mode' => 'boolean',
            'allow_registration' => 'boolean',
            'require_email_verification_platform' => 'boolean',
            'banner_ad_price' => 'nullable|numeric|min:0',
            'top_vendor_spot_price' => 'nullable|numeric|min:0',
        ]);

        // Save each setting
        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }

        return redirect()->route('admin.settings')
            ->with('success', 'Settings updated successfully.');
    }
}
