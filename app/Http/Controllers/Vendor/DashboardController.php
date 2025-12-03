<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $vendorSettings = $user->vendorSetting;
        
        // Get statistics
        $stats = [
            'totalProducts' => $user->products()->count(),
            'publicPageStatus' => $vendorSettings ? ($vendorSettings->status === 1 ? 'Active' : 'Inactive') : 'Not Configured',
            'hasDescription' => $vendorSettings && $vendorSettings->description ? true : false,
            'hasLogo' => $vendorSettings && $vendorSettings->logo ? true : false,
            'hasBanner' => $vendorSettings && $vendorSettings->banner ? true : false,
        ];
        
        // Get recent products
        $recentProducts = $user->products()
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'price', 'image_url', 'created_at']);
        
        return Inertia::render('Vendor/Dashboard', [
            'stats' => $stats,
            'recentProducts' => $recentProducts,
            'vendorSettings' => $vendorSettings
        ]);
    }
} 