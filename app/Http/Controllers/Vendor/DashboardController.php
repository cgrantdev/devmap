<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\Brand;
use App\Models\VendorReview;
use App\Models\Product;
use App\Models\VendorSetting;
use App\Models\Location;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();

        $productsQuery = $brand
            ? Product::query()->where('brand_id', $brand->id)
            : Product::query()->whereRaw('1 = 0');

        $approvedReviewsQuery = $brand
            ? VendorReview::query()->where('brand_id', $brand->id)
            : VendorReview::query()->whereRaw('1 = 0');

        if (Schema::hasColumn('vendor_reviews', 'status')) {
            $approvedReviewsQuery->where('status', 'approved');
        } else {
            $approvedReviewsQuery->where('is_approved', true);
        }

        $flaggedReviewsQuery = $brand
            ? VendorReview::query()->where('brand_id', $brand->id)->where('flagged', true)
            : VendorReview::query()->whereRaw('1 = 0');

        $stats = [
            'totalProducts' => (clone $productsQuery)->count(),
            'activeProducts' => (clone $productsQuery)
                ->when(Schema::hasColumn('products', 'hidden'), fn ($query) => $query->where('hidden', false))
                ->count(),
            'totalViews' => 0,
            'totalReviews' => (clone $approvedReviewsQuery)->count(),
            'averageRating' => number_format((clone $approvedReviewsQuery)->avg('rating') ?? 0, 1),
            'flaggedReviews' => (clone $flaggedReviewsQuery)->count(),
        ];

        $recentProducts = (clone $productsQuery)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($product) {
                $salePrice = $product->discount_price && $product->discount_price < $product->price
                    ? $product->discount_price
                    : $product->price;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->image_url,
                    'product_url' => $product->product_url,
                    'price' => $salePrice,
                    'original_price' => $product->discount_price && $product->discount_price < $product->price
                        ? $product->price
                        : null,
                    'status' => $product->status,
                    'hidden' => (bool) ($product->hidden ?? false),
                    'rating_average' => $product->rating_average,
                    'rating_count' => $product->rating_count,
                ];
            });

        return Inertia::render('Vendor/Dashboard', [
            'stats' => $stats,
            'recentProducts' => $recentProducts,
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
        
        // Approved review query (vendors should only see approved reviews)
        $approvedReviewsQuery = VendorReview::where('brand_id', $brand->id);
        if (Schema::hasColumn('vendor_reviews', 'status')) {
            $approvedReviewsQuery->where('status', 'approved');
        } else {
            $approvedReviewsQuery->where('is_approved', true);
        }

        // Pending review count (approval pending from admin)
        $pendingReviewsQuery = VendorReview::where('brand_id', $brand->id);
        if (Schema::hasColumn('vendor_reviews', 'status')) {
            $pendingReviewsQuery->where('status', 'pending');
        } else {
            $pendingReviewsQuery->where('is_approved', false);
        }

        $totalApprovedReviews = (clone $approvedReviewsQuery)->count();
        $averageRating = number_format((clone $approvedReviewsQuery)->avg('rating') ?? 0, 1);
        $respondedReviews = (clone $approvedReviewsQuery)
            ->whereNotNull('vendor_replied_at')
            ->count();

        $stats = [
            'totalReviews' => $totalApprovedReviews,
            'averageRating' => $averageRating,
            'pendingReviews' => (clone $pendingReviewsQuery)->count(),
            'respondedReviews' => $respondedReviews,
        ];

        // Get recent approved reviews for the list
        // Note: `vendor_reviews` currently has no `product_id` column, so we cannot eager-load a `product` relationship here.
        $reviews = (clone $approvedReviewsQuery)
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user_name,
                    'user_email' => $review->user_email,
                    'rating' => $review->rating,
                    'review' => $review->review,
                    'status' => $review->status ?? ($review->is_approved ? 'approved' : 'pending'),
                    'created_at' => $review->created_at,
                    'vendor_reply' => $review->vendor_reply,
                    'vendor_replied_at' => $review->vendor_replied_at,
                    'flagged' => $review->flagged ?? false,
                    'flag_reason' => $review->flag_reason,
                    'shipping_time' => $review->shipping_time,
                    'customer_service' => $review->customer_service,
                    'quality' => $review->quality,
                    'cost' => $review->cost,
                    'packaging' => $review->packaging,
                ];
            });
        
        return Inertia::render('Vendor/Reviews', [
            'stats' => $stats,
            'reviews' => $reviews,
        ]);
    }

    public function products()
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();

        if (!$brand) {
            return Inertia::render('Vendor/Products', [
                'stats' => [
                    'totalProducts' => 0,
                    'activeProducts' => 0,
                    'hiddenProducts' => 0,
                    'averageRating' => '0.0',
                ],
                'products' => [],
            ]);
        }

        $productsQuery = Product::query()
            ->where('brand_id', $brand->id)
            ->latest();

        $stats = [
            'totalProducts' => (clone $productsQuery)->count(),
            'activeProducts' => (clone $productsQuery)
                ->when(Schema::hasColumn('products', 'hidden'), fn ($query) => $query->where('hidden', false))
                ->count(),
            'hiddenProducts' => (clone $productsQuery)
                ->when(Schema::hasColumn('products', 'hidden'), fn ($query) => $query->where('hidden', true))
                ->count(),
            'averageRating' => number_format(
                Product::where('brand_id', $brand->id)->avg('rating_average') ?? 0,
                1
            ),
        ];

        $products = $productsQuery
            ->get()
            ->map(function ($product) {
                $salePrice = $product->discount_price && $product->discount_price < $product->price
                    ? $product->discount_price
                    : $product->price;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image_url' => $product->image_url,
                    'product_url' => $product->product_url,
                    'price' => $salePrice,
                    'original_price' => $product->discount_price && $product->discount_price < $product->price
                        ? $product->price
                        : null,
                    'status' => $product->status,
                    'hidden' => (bool) ($product->hidden ?? false),
                    'rating_average' => $product->rating_average,
                    'rating_count' => $product->rating_count,
                    'created_at' => optional($product->created_at)?->toDateTimeString(),
                ];
            });

        return Inertia::render('Vendor/Products', [
            'stats' => $stats,
            'products' => $products,
        ]);
    }

    public function reply(Request $request, $id)
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();

        if (!$brand) {
            return back()->withErrors(['error' => 'Vendor not found.']);
        }

        $review = VendorReview::where('id', $id)
            ->where('brand_id', $brand->id)
            ->firstOrFail();

        $validated = $request->validate([
            'vendor_reply' => 'required|string|max:2000',
        ]);

        $review->vendor_reply = $validated['vendor_reply'];
        $review->vendor_replied_at = now();
        $review->save();

        return back()->with('success', 'Reply submitted successfully.');
    }

    public function flag(Request $request, $id)
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();

        if (!$brand) {
            return back()->withErrors(['error' => 'Vendor not found.']);
        }

        $review = VendorReview::where('id', $id)
            ->where('brand_id', $brand->id)
            ->firstOrFail();

        $validated = $request->validate([
            'flag_reason' => 'required|string|max:1000',
        ]);

        $review->flagged = true;
        $review->flag_reason = $validated['flag_reason'];
        $review->save();

        return back()->with('success', 'Review flagged successfully.');
    }

    public function unflag($id)
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();

        if (!$brand) {
            return back()->withErrors(['error' => 'Vendor not found.']);
        }

        $review = VendorReview::where('id', $id)
            ->where('brand_id', $brand->id)
            ->firstOrFail();

        $review->flagged = false;
        $review->flag_reason = null;
        $review->save();

        return back()->with('success', 'Review unflagged successfully.');
    }

    public function profile()
    {
        $user = Auth::user();
        $brand = Brand::with(['vendorSetting.location', 'user'])->where('user_id', $user->id)->first();
        
        if (!$brand) {
            return Inertia::render('Vendor/Profile', [
                'vendor' => null,
            ]);
        }

        $vendorData = [
            'id' => $brand->id,
            'name' => $brand->name,
            'user' => [
                'name' => $brand->user->name ?? $user->name,
                'email' => $brand->user->email ?? $user->email,
            ],
            'settings' => null,
        ];

        if ($brand->vendorSetting) {
            $settings = $brand->vendorSetting;
            $vendorData['settings'] = [
                'website' => $settings->website ?? $settings->shop_url ?? null,
                'founded_year' => $settings->founded_year ?? null,
                'location_id' => $settings->location_id ?? null,
                'location' => $settings->location ? $settings->location->name : null,
                'description' => $settings->description ?? null,
                'contact_email' => $settings->contact_email ?? $user->email,
                'phone_number' => $settings->phone_number ?? null,
                'shipping_info' => $settings->shipping_info ?? null,
                'return_policy' => $settings->return_policy ?? null,
                'business_hours' => $settings->business_hours ?? null,
                'payment_methods' => $settings->payment_methods ?? [],
                'logo' => $settings->logo ? asset('storage/' . $settings->logo) : null,
                'approval_status' => $settings->approval_status ?? 'pending',
            ];
        }

        $locations = Location::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Vendor/Profile', [
            'vendor' => $vendorData,
            'locations' => $locations,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $brand = Brand::where('user_id', $user->id)->first();

        if (!$brand) {
            return back()->withErrors(['error' => 'Vendor profile not found.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'website' => 'required|url|max:255',
            'founded_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'location_id' => 'required|exists:locations,id',
            'description' => 'nullable|string|max:2000',
            'contact_email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:50',
            'shipping_info' => 'nullable|string|max:2000',
            'return_policy' => 'nullable|string|max:2000',
            'business_hours' => 'nullable|string|max:255',
            'payment_methods' => 'nullable|array',
            'payment_methods.*' => 'nullable|string|in:Credit Card,PayPal,Cryptocurrency,Bank Transfer',
            'logo' => 'nullable|mimes:png|max:2048',
        ]);

        try {
            // Update brand name
            $brand->update([
                'name' => $validated['name'],
            ]);

            // Get or create vendor settings
            $settings = $brand->vendorSetting ?: new VendorSetting(['brand_id' => $brand->id]);

            // Handle logo upload (PNG only)
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($settings->logo) {
                    ImageHelper::deleteImage(basename($settings->logo), 'vendor_logos');
                }
                
                $logoFile = $request->file('logo');
                $extension = strtolower($logoFile->getClientOriginalExtension());
                $mimeType = $logoFile->getMimeType();
                
                // Validate PNG file
                if ($extension !== 'png' && $mimeType !== 'image/png') {
                    return back()->withErrors(['logo' => 'Only PNG files are allowed for company logo.'])->withInput();
                }
                
                // Convert PNG to WebP
                try {
                    $logoFilename = ImageHelper::convertToWebP($logoFile, 'vendor_logos');
                    $settings->logo = 'vendor_logos/' . $logoFilename;
                } catch (\Exception $e) {
                    // If WebP conversion fails, save as PNG
                    $logoFilename = \Illuminate\Support\Str::random(40) . '.png';
                    $logoFile->storeAs('vendor_logos', $logoFilename, 'public');
                    $settings->logo = 'vendor_logos/' . $logoFilename;
                }
            }

            // Update settings
            $settings->website = $validated['website'];
            $settings->shop_url = $validated['website'];
            $settings->founded_year = $validated['founded_year'] ?? null;
            $settings->location_id = $validated['location_id'];
            $settings->description = $validated['description'] ?? null;
            $settings->contact_email = $validated['contact_email'];
            $settings->phone_number = $validated['phone_number'] ?? null;
            $settings->shipping_info = $validated['shipping_info'] ?? null;
            $settings->return_policy = $validated['return_policy'] ?? null;
            $settings->business_hours = $validated['business_hours'] ?? null;
            $settings->payment_methods = $validated['payment_methods'] ?? [];

            // Set status if this is a new settings record
            if (!$settings->exists) {
                $settings->status = 0;
                $settings->approval_status = 'pending';
            }

            $settings->save();

            return redirect()->route('vendor.profile')->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'An error occurred while updating your profile. Please try again.'])->withInput();
        }
    }
} 