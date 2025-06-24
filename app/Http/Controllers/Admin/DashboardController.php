<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorSetting;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalVendors = User::where('role', 'vendor')->count();
        $activeVendors = VendorSetting::where('status', 1)->count();
        $inactiveVendors = VendorSetting::where('status', 0)->count();

        $stats = [
            'totalVendors' => $totalVendors,
            'activeVendors' => $activeVendors,
            'inactiveVendors' => $inactiveVendors,
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }
} 