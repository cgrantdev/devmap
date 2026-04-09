<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\User;
use App\Models\VendorSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PublicVendorController extends Controller
{
    public function show(Request $request, $vendor_name)
    {
        // Find brand by slug (preferred) or by name conversion from URL
        $brand = Brand::where('slug', $vendor_name)
            ->orWhere('slug', Str::slug($vendor_name))
            ->with(['vendorSetting', 'vendorSetting.location'])
            ->first();

        // Fallback: try name-based lookup for legacy URLs
        if (!$brand) {
            $name = str_replace('-', ' ', $vendor_name);
            $brand = Brand::where('name', 'LIKE', $name)
                ->with(['vendorSetting', 'vendorSetting.location'])
                ->first();
        }

        if (!$brand || !$brand->is_active) {
            abort(404);
        }

        $vendorSetting = $brand->vendorSetting;

        // Check if current user is admin
        $currentUser = Auth::user();
        $isAdmin = $currentUser && $currentUser->role === 'admin';
        $isOwnPage = $currentUser && $brand->user_id === $currentUser->id;

        // Define price ranges
        $ranges = [
            'all' => [null, null],
            '0-50' => [0, 50],
            '50-100' => [50, 100],
            '100-150' => [100, 150],
            '150-200' => [150, 200],
            '200+' => [200, null],
        ];

        // Get counts for each range via brand products
        $counts = [];
        $counts['all'] = $brand->products()->visible()->count();
        foreach ($ranges as $key => [$min, $max]) {
            if ($key === 'all') continue;
            $query = $brand->products()->visible();
            if ($max === null) {
                $query->where('price', '>=', $min);
            } else {
                $query->where('price', '>=', $min)->where('price', '<', $max);
            }
            $counts[$key] = $query->count();
        }

        $search = $request->query('search', '');
        $selected = $request->query('cost', 'all');
        $selectedRange = $ranges[$selected] ?? $ranges['all'];

        // Build products query via Brand relationship
        $productsQuery = $brand->products()->visible()->latest();
        if ($selected !== 'all') {
            if ($selectedRange[1] === null) {
                $productsQuery->where('price', '>=', $selectedRange[0]);
            } else {
                $productsQuery->where('price', '>=', $selectedRange[0])->where('price', '<', $selectedRange[1]);
            }
        }
        if ($search) {
            $productsQuery->where('name', 'like', '%' . $search . '%');
        }
        $items = $productsQuery->get(['id', 'name', 'slug', 'price', 'discount_price', 'second_price', 'image_url', 'product_url', 'description']);

        // Build a vendor-like object the Vue page expects
        $vendor = (object) [
            'id' => $brand->id,
            'name' => $brand->name,
            'slug' => $brand->slug,
            'email' => $vendorSetting?->contact_email,
        ];

        return Inertia::render('Vendor/Public', [
            'settings' => $vendorSetting,
            'vendor' => $vendor,
            'items' => $items,
            'isAdmin' => $isAdmin,
            'isOwnPage' => $isOwnPage,
            'costCounts' => $counts,
            'selectedCost' => $selected,
            'search' => $search,
        ]);
    }

    public function list()
    {
        // Query via Brand (which has the vendorSetting FK) instead of User.
        // The old code used User::whereHas('vendorSetting') which generated
        // `vendor_settings.user_id` — a column that doesn't exist.
        $vendors = Brand::where('is_active', true)
            ->whereHas('vendorSetting', function ($q) {
                $q->where('approval_status', 'approved');
            })
            ->with(['vendorSetting' => function ($q) {
                $q->select('id', 'brand_id', 'description', 'banner', 'logo');
            }])
            ->get(['id', 'name', 'slug', 'rating_average', 'rating_count']);

        $vendors = $vendors->map(function ($brand) {
            $vs = $brand->vendorSetting;
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'description' => $vs->description ?? null,
                'banner' => $vs && $vs->banner ? asset('storage/' . $vs->banner) : null,
                'logo' => $vs && $vs->logo ? asset('storage/' . $vs->logo) : null,
                'slug' => $brand->slug ?? Str::slug($brand->name),
                'rating_average' => (float) ($brand->rating_average ?? 0),
                'rating_count' => (int) ($brand->rating_count ?? 0),
            ];
        });

        return Inertia::render('Vendor/Vendors', [
            'vendors' => $vendors
        ]);
    }
} 