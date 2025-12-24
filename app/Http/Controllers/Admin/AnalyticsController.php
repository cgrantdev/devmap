<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\VendorSetting;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $totalVisits = 24521; // This would come from analytics tracking
        $activeUsers = User::where('role', 'customer')->count();
        $vendorSignups = User::where('role', 'vendor')->count();
        $productsListed = Product::count();

        $stats = [
            [
                'label' => 'Total Visits',
                'value' => number_format($totalVisits),
                'change' => '+12.5%',
                'icon' => 'EyeIcon'
            ],
            [
                'label' => 'Active Users',
                'value' => number_format($activeUsers),
                'change' => '+8.1%',
                'icon' => 'UsersIcon'
            ],
            [
                'label' => 'Vendor Signups',
                'value' => number_format($vendorSignups),
                'change' => '+15.3%',
                'icon' => 'StoreIcon'
            ],
            [
                'label' => 'Products Listed',
                'value' => number_format($productsListed),
                'change' => '+3.2%',
                'icon' => 'PackageIcon'
            ],
        ];

        // Top vendors (mock data for now)
        $topVendors = [];
        
        // Top products (mock data for now)
        $topProducts = [];

        return Inertia::render('Admin/Analytics', [
            'stats' => $stats,
            'topVendors' => $topVendors,
            'topProducts' => $topProducts
        ]);
    }
}

