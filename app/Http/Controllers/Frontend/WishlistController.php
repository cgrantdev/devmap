<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WishlistController extends Controller
{
    /**
     * Add or remove a product/category from the current user's wishlist.
     * Idempotent: sending the same request toggles state on/off.
     *
     * Response: { status: 'added'|'removed', in_wishlist: bool, wishlist_count: int }
     */
    public function toggle(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:product,category',
            'id' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $type = $data['type'];
        $id = (int) $data['id'];

        // Verify the target exists so we don't create orphan wishlist rows
        // pointing at nothing (bad ids from client-side manipulation).
        if ($type === 'product') {
            $target = Product::visible()->find($id);
        } else {
            $target = ProductCategory::where('is_active', true)->find($id);
        }

        if (!$target) {
            return response()->json(['error' => 'Target not found'], 404);
        }

        $existing = Wishlist::where('user_id', $user->id)
            ->where('wishable_type', $type)
            ->when($type === 'product', fn ($q) => $q->where('product_id', $id))
            ->when($type === 'category', fn ($q) => $q->where('category_id', $id))
            ->first();

        if ($existing) {
            $existing->delete();
            $status = 'removed';
            $inWishlist = false;
        } else {
            // Snapshot the effective price at time-of-add so the /account/wishlist
            // page can show "price change since added" and the alert cron can
            // decide when to email.
            $lastSeenPrice = null;
            if ($type === 'product') {
                $lastSeenPrice = $target->discount_price && $target->discount_price < $target->price
                    ? $target->discount_price
                    : $target->price;
            }

            Wishlist::create([
                'user_id' => $user->id,
                'wishable_type' => $type,
                'product_id' => $type === 'product' ? $id : null,
                'category_id' => $type === 'category' ? $id : null,
                'alert_frequency' => 'weekly',
                'last_seen_price' => $lastSeenPrice,
            ]);
            $status = 'added';
            $inWishlist = true;
        }

        $count = Wishlist::where('user_id', $user->id)->count();

        return response()->json([
            'status' => $status,
            'in_wishlist' => $inWishlist,
            'wishlist_count' => $count,
        ]);
    }

    /**
     * /account/wishlist — dashboard listing everything the user is watching.
     * Split by product vs category (compound) with current-price + drop info.
     */
    public function index()
    {
        $user = Auth::user();

        $rows = Wishlist::where('user_id', $user->id)->latest()->get();

        // --- Watched products ---
        $productIds = $rows->where('wishable_type', 'product')->pluck('product_id')->filter()->values();
        $products = Product::visible()
            ->with(['brand.vendorSetting', 'category'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $watchedProducts = $rows
            ->where('wishable_type', 'product')
            ->map(function (Wishlist $row) use ($products) {
                $p = $products->get($row->product_id);
                if (!$p) return null;

                $current = $p->discount_price && $p->discount_price < $p->price
                    ? (float) $p->discount_price
                    : (float) $p->price;

                $lastSeen = $row->last_seen_price !== null ? (float) $row->last_seen_price : null;
                $priceChangePct = null;
                if ($lastSeen && $lastSeen > 0) {
                    $priceChangePct = round((($current - $lastSeen) / $lastSeen) * 100, 1);
                }

                return [
                    'wishlist_id' => $row->id,
                    'product' => [
                        'id' => $p->id,
                        'name' => $p->display_name ?? $p->name,
                        'slug' => $p->slug,
                        'image_url' => $p->image_url,
                        'price' => (float) $p->price,
                        'discount_price' => $p->discount_price ? (float) $p->discount_price : null,
                        'current_price' => $current,
                        'brand_discount_percent' => $p->brand_discount_percent,
                        'brand_coupon_code' => $p->brand_coupon_code,
                        'brand' => $p->brand ? [
                            'id' => $p->brand->id,
                            'name' => $p->brand->name,
                            'slug' => $p->brand->slug,
                        ] : null,
                    ],
                    'last_seen_price' => $lastSeen,
                    'price_change_pct' => $priceChangePct,
                    'added_at' => optional($row->created_at)->toIso8601String(),
                ];
            })
            ->filter()
            ->values();

        // --- Watched compounds (categories) ---
        $categoryIds = $rows->where('wishable_type', 'category')->pluck('category_id')->filter()->values();
        $categories = ProductCategory::whereIn('id', $categoryIds)->get()->keyBy('id');

        // For each watched category, grab up to 3 cheapest vendor products
        // to show inline as "top vendors" without needing a separate page.
        $categoryProducts = collect();
        if ($categoryIds->isNotEmpty()) {
            $categoryProducts = Product::visible()
                ->with(['brand.vendorSetting'])
                ->whereIn('product_category_id', $categoryIds)
                ->where('status', 'active')
                ->orderBy('product_category_id')
                ->orderBy('price', 'asc')
                ->get()
                ->groupBy('product_category_id');
        }

        $watchedCompounds = $rows
            ->where('wishable_type', 'category')
            ->map(function (Wishlist $row) use ($categories, $categoryProducts) {
                $cat = $categories->get($row->category_id);
                if (!$cat) return null;

                $prods = $categoryProducts->get($row->category_id, collect());
                $vendorCount = $prods->pluck('brand_id')->unique()->count();
                $fromPrice = $prods->min(fn ($p) => (float) ($p->discount_price && $p->discount_price < $p->price ? $p->discount_price : $p->price));

                $top = $prods->take(3)->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->display_name ?? $p->name,
                    'slug' => $p->slug,
                    'price' => (float) $p->price,
                    'discount_price' => $p->discount_price ? (float) $p->discount_price : null,
                    'brand_name' => $p->brand?->name,
                    'brand_slug' => $p->brand?->slug,
                ])->values();

                return [
                    'wishlist_id' => $row->id,
                    'category' => [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'slug' => $cat->slug,
                    ],
                    'vendor_count' => $vendorCount,
                    'from_price' => $fromPrice,
                    'top_products' => $top,
                    'added_at' => optional($row->created_at)->toIso8601String(),
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('Frontend/Account/Wishlist', [
            'watchedProducts' => $watchedProducts,
            'watchedCompounds' => $watchedCompounds,
        ]);
    }

    /**
     * DELETE /account/wishlist/{id} — remove a single wishlist row (owner only).
     */
    public function destroy($id)
    {
        $row = Wishlist::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $row->delete();
        return back()->with('success', 'Removed from wishlist.');
    }
}
