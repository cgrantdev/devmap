<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicVendorController extends Controller
{
    public function show($vendor_name)
    {
        // Convert URL-friendly name back to company name
        $company_name = str_replace('-', ' ', $vendor_name);
        
        $vendorSetting = VendorSetting::where('company_name', 'LIKE', $company_name)
            ->where('status', 1) // Only show active vendors
            ->firstOrFail();
            
        $user = $vendorSetting->user;
        
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
        ]);
    }
} 