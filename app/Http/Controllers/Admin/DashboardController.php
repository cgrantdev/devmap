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
        
        $pendingReviews = VendorReview::where('status', 'pending')->count();
        
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

        // Live + scheduled coupon boosts across every vendor (Colin Sep 14
        // — Julia's PMAP feedback: 'add a visual below this showing any
        // active promotions with start and end dates / times'). Gives
        // Julia one place to see what's running instead of clicking
        // through every vendor edit page.
        $now = now();
        $activeBoosts = VendorSetting::with('brand')
            ->whereNotNull('coupon_boost_expires_at')
            ->where('coupon_boost_expires_at', '>', $now)
            ->orderBy('coupon_boost_expires_at')
            ->get()
            ->map(function ($vs) use ($now) {
                $isScheduled = $vs->coupon_boost_starts_at && $vs->coupon_boost_starts_at->isFuture();
                return [
                    'brand_id' => $vs->brand?->id,
                    'brand_name' => $vs->brand?->name,
                    'brand_slug' => $vs->brand?->slug,
                    'status' => $isScheduled ? 'scheduled' : 'active',
                    'percent' => $isScheduled
                        ? (float) $vs->coupon_boost_percent
                        : (float) $vs->coupon_discount_percent,
                    'starts_at' => $vs->coupon_boost_starts_at?->toIso8601String(),
                    'expires_at' => $vs->coupon_boost_expires_at?->toIso8601String(),
                    'reverts_to_percent' => $vs->coupon_discount_previous_percent
                        ? (float) $vs->coupon_discount_previous_percent
                        : null,
                    'coupon_code' => $isScheduled && $vs->coupon_boost_code
                        ? $vs->coupon_boost_code
                        : $vs->coupon_code,
                    'reverts_to_code' => $vs->coupon_code_previous,
                ];
            })
            ->values();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'activeBoosts' => $activeBoosts,
        ]);
    }
} 