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
        // Convert URL-friendly name back to vendor name
        $name = str_replace('-', ' ', $vendor_name);
        
        $user = User::where('name', 'LIKE', $name)
            ->where('role', 'vendor')
            ->firstOrFail();
            
        $vendorSetting = $user->vendorSetting;
        
        // Check if current user is admin or the vendor themselves
        $currentUser = Auth::user();
        $isAdmin = $currentUser && $currentUser->role === 'admin';
        $isOwnPage = $currentUser && $currentUser->id === $user->id;
        
        // Only show if vendor has active status, unless user is admin or the vendor themselves
        if (!$vendorSetting || ($vendorSetting->status !== 1 && !$isAdmin && !$isOwnPage)) {
            abort(404);
        }

        // Define price ranges (add 'all')
        $ranges = [
            'all' => [null, null],
            '0-50' => [0, 50],
            '50-100' => [50, 100],
            '100-150' => [100, 150],
            '150-200' => [150, 200],
            '200+' => [200, null],
        ];

        // Get counts for each range
        $counts = [];
        // All products count
        $counts['all'] = $user->products()->count();
        foreach ($ranges as $key => [$min, $max]) {
            if ($key === 'all') continue;
            $query = $user->products();
            if ($max === null) {
                $query->where('price', '>=', $min);
            } else {
                $query->where('price', '>=', $min)->where('price', '<', $max);
            }
            $counts[$key] = $query->count();
        }

        // Get search query
        $search = $request->query('search', '');
        // Get selected cost filter
        $selected = $request->query('cost', 'all');
        $selectedRange = $ranges[$selected] ?? $ranges['all'];

        // Build products query
        $productsQuery = $user->products()->latest();
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
        $items = $productsQuery->get(['id', 'name', 'price', 'discount_price', 'second_price', 'image_url', 'product_url', 'description']);

        return Inertia::render('Vendor/Public', [
            'settings' => $vendorSetting,
            'vendor' => $user,
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