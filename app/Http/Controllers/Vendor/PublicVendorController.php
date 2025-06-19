<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicVendorController extends Controller
{
    public function show($vendor_name)
    {
        $user = User::where('name', $vendor_name)->where('role', 'vendor')->firstOrFail();
        $settings = $user->vendorSetting;
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
            'settings' => $settings,
            'vendor' => $user,
            'items' => $items,
        ]);
    }
} 