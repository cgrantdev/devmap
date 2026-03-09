<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\Brand;
use App\Models\VendorReview;
use App\Models\Product;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();
        
        // Get statistics
        $stats = [
            'totalProducts' => $brand ? $brand->products()->count() : 0,
            'totalViews' => 0, // TODO: Implement view tracking
            'totalReviews' => $brand ? VendorReview::where('brand_id', $brand->id)->count() : 0,
            'averageRating' => $brand ? number_format(VendorReview::where('brand_id', $brand->id)->avg('rating') ?? 0, 1) : '0.0',
        ];
        
        return Inertia::render('Vendor/Dashboard', [
            'stats' => $stats,
        ]);
    }

    public function storefrontAnalytics()
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();
        
        // Get storefront analytics stats
        $stats = [
            'totalViews' => 0, // TODO: Implement view tracking
            'uniqueVisitors' => 0, // TODO: Implement visitor tracking
            'productClicks' => 0, // TODO: Implement click tracking
            'avgSessionDuration' => '0:00', // TODO: Implement session tracking
        ];
        
        // Get top products by views (placeholder)
        $topProducts = [];
        if ($brand) {
            $topProducts = $brand->products()
                ->latest()
                ->take(5)
                ->get(['id', 'name'])
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'views' => 0, // TODO: Implement view tracking per product
                    ];
                });
        }
        
        return Inertia::render('Vendor/StorefrontAnalytics', [
            'stats' => $stats,
            'topProducts' => $topProducts,
        ]);
    }

    public function advertisementAnalytics()
    {
        $user = Auth::user();
        
        // Get advertisement analytics stats
        $stats = [
            'totalImpressions' => 0, // TODO: Implement impression tracking
            'totalClicks' => 0, // TODO: Implement click tracking
            'clickThroughRate' => '0.00',
            'totalSpent' => '0.00', // TODO: Implement spending tracking
        ];
        
        // Calculate CTR if we have data
        if ($stats['totalImpressions'] > 0) {
            $stats['clickThroughRate'] = number_format(($stats['totalClicks'] / $stats['totalImpressions']) * 100, 2);
        }
        
        // Get active advertisements (placeholder)
        $activeAds = []; // TODO: Implement advertisement management
        
        return Inertia::render('Vendor/AdvertisementAnalytics', [
            'stats' => $stats,
            'activeAds' => $activeAds,
        ]);
    }

    public function reviews()
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();
        
        if (!$brand) {
            return Inertia::render('Vendor/Reviews', [
                'stats' => [
                    'totalReviews' => 0,
                    'averageRating' => '0.0',
                    'pendingReviews' => 0,
                    'respondedReviews' => 0,
                ],
                'reviews' => [],
            ]);
        }
        
        // Get review statistics
        $allReviews = VendorReview::where('brand_id', $brand->id)->get();
        $pendingReviews = VendorReview::where('brand_id', $brand->id)
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(function ($q) {
                        // Fallback for old schema
                        if (Schema::hasColumn('vendor_reviews', 'is_approved')) {
                            $q->where('is_approved', false);
                        }
                    });
            })
            ->count();
        
        $stats = [
            'totalReviews' => $allReviews->count(),
            'averageRating' => number_format($allReviews->avg('rating') ?? 0, 1),
            'pendingReviews' => $pendingReviews,
            'respondedReviews' => 0, // TODO: Implement response tracking
        ];
        
        // Get recent reviews
        $reviews = VendorReview::where('brand_id', $brand->id)
            ->with('product:id,name')
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user_name,
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'status' => $review->status ?? ($review->is_approved ? 'approved' : 'pending'),
                    'created_at' => $review->created_at,
                    'product' => $review->product ? [
                        'id' => $review->product->id,
                        'name' => $review->product->name,
                    ] : null,
                ];
            });
        
        return Inertia::render('Vendor/Reviews', [
            'stats' => $stats,
            'reviews' => $reviews,
        ]);
    }
} 