<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PublicVendorController extends Controller
{
    public function show($vendor_name)
    {
        // Convert URL-friendly name back to vendor name
        $name = str_replace('-', ' ', $vendor_name);
        
        $user = User::where('name', 'LIKE', $name)
            ->where('role', 'vendor')
            ->firstOrFail();
            
        $vendorSetting = $user->vendorSetting;
        
        // Check if current user is admin or the vendor themselves
        $currentUser = Auth::user();
        $isAdmin = $currentUser && $currentUser->role === 'admin';
        $isOwnPage = $currentUser && $currentUser->id === $user->id;
        
        // Only show if vendor has active status, unless user is admin or the vendor themselves
        if (!$vendorSetting || ($vendorSetting->status !== 1 && !$isAdmin && !$isOwnPage)) {
            abort(404);
        }
        
        // Get real products for this vendor
        $items = $user->products()->latest()->get(['id', 'name', 'price', 'image_url', 'product_url', 'description']);
        
        return Inertia::render('Vendor/Public', [
            'settings' => $vendorSetting,
            'vendor' => $user,
            'items' => $items,
            'isAdmin' => $isAdmin,
            'isOwnPage' => $isOwnPage,
        ]);
    }
} 