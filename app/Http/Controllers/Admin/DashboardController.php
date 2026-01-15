<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Brand;
use App\Models\VendorSetting;
use App\Models\Product;
use App\Models\VendorReview;
use App\Models\Activity;
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
        $recentActivity = [];
        if (Schema::hasTable('activities')) {
            $recentActivity = Activity::orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'vendor' => $activity->entity_name,
                        'time' => $activity->created_at->diffForHumans(),
                        'created_at' => $activity->created_at->toIso8601String(),
                    ];
                })
                ->toArray();
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity
        ]);
    }
} 