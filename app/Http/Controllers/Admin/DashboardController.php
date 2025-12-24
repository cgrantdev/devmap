<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Brand;
use App\Models\VendorSetting;
use App\Models\Product;
use App\Models\VendorReview;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        // Vendors are stored in the brands table
        $totalVendors = Brand::count();
        $activeVendors = Brand::where('is_active', true)->count();
        $inactiveVendors = Brand::where('is_active', false)->count();
        $totalProducts = Product::count();
        
        // Check if status column exists, otherwise use is_approved
        $hasStatusColumn = Schema::hasColumn('vendor_reviews', 'status');
        if ($hasStatusColumn) {
            $pendingReviews = VendorReview::where('status', 'pending')->count();
        } else {
            $pendingReviews = VendorReview::where('is_approved', false)->count();
        }
        
        $totalUsers = User::where('role', 'customer')->count();

        $stats = [
            'totalVendors' => $totalVendors,
            'activeVendors' => $activeVendors,
            'inactiveVendors' => $inactiveVendors,
            'totalProducts' => $totalProducts,
            'pendingReviews' => $pendingReviews,
            'totalUsers' => $totalUsers,
        ];

        // Recent activity (last 10 activities)
        $recentActivity = [
            // This would be populated from an activity log in a real implementation
            // For now, we'll return empty array
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity
        ]);
    }
} 