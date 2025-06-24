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
        
        // Dummy items
        $items = [
            [
                'id' => 1,
                'name' => 'Sample Item 1',
                'price' => 19.99,
                'image' => 'https://via.placeholder.com/150',
            ],
            [
                'id' => 2,
                'name' => 'Sample Item 2',
                'price' => 29.99,
                'image' => 'https://via.placeholder.com/150',
            ],
            [
                'id' => 3,
                'name' => 'Sample Item 3',
                'price' => 39.99,
                'image' => 'https://via.placeholder.com/150',
            ],
        ];
        
        return Inertia::render('Vendor/Public', [
            'settings' => $vendorSetting,
            'vendor' => $user,
            'items' => $items,
            'isAdmin' => $isAdmin,
            'isOwnPage' => $isOwnPage,
        ]);
    }
} 