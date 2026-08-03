<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Blog;
use App\Models\ProductPriceSnapshot;
use App\Models\VendorReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only JSON API for the Peptidemap Discord bot.
 * Every route sits behind the `bot.api` middleware (bearer token).
 *
 * URL shape returned to the bot matches the live site:
 *   product: /product/{brandSlug}/{productSlug}/{id}
 *   brand:   /brand/{slug}
 *
 * When something new is added here, keep responses skinny — the bot embeds
 * are size-limited by Discord and we don't want to leak internal columns.
 */
class BotController extends Controller
{
    private const BASE = 'https://peptidemap.com';

    /* -------- helpers -------- */

    private function effective(Product $p): float
    {
        return ($p->discount_price !== null && $p->discount_price > 0 && $p->discount_price < $p->price)
            ? (float) $p->discount_price
            : (float) $p->price;
    }

    private function productPayload(Product $p): array
    {
        $eff = $this->effective($p);
        $orig = $p->original_price; // getter returns null when no discount
        return [
            'id' => $p->id,
            'name' => $p->display_name ?: $p->name,
            'raw_name' => $p->name,
            'size_mg' => $p->size_mg,
            'brand_id' => $p->brand_id,
            'brand_name' => $p->brand?->name,
            'brand_slug' => $p->brand?->slug,
            'category' => $p->relationLoaded('category') ? $p->category?->name : null,
            'price' => (float) $p->price,
            'discount_price' => $p->discount_price !== null ? (float) $p->discount_price : null,
            'effective_price' => $eff,
            'discount_pct' => $orig ? round((($orig - $eff) / $orig) * 100, 1) : null,
            'image_url' => $p->image_url,
            'product_url' => $p->product_url,
            'url' => self::BASE . '/product/' . ($p->brand?->slug ?? 'brand') . '/' . ($p->slug ?? $p->id) . '/' . $p->id,
        ];
    }

    private function normaliseQuery(string $q): string
    {
        return trim(preg_replace('/\s+/', ' ', $q));
    }

    /* -------- endpoints -------- */

    /**
     * GET /api/bot/products/search?q=<name>&limit=5
     * Backs the /price slash command. Case-insensitive fuzzy match on
     * display name + category name, sorted by best effective price.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $this->normaliseQuery((string) $request->query('q', ''));
        $limit = min(25, max(1, (int) $request->query('limit', 5)));
        if ($q === '') return response()->json(['results' => []]);

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';

        $rows = Product::visible()->where('price', '>', 0)
            ->with(['brand:id,name,slug', 'category:id,name'])
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
            })
            ->orderByRaw('LEAST(price, COALESCE(discount_price, price)) ASC')
            ->limit($limit)
            ->get();

        return response()->json([
            'query' => $q,
            'count' => $rows->count(),
            'results' => $rows->map(fn ($p) => $this->productPayload($p))->values(),
        ]);
    }

    /**
     * GET /api/bot/vendors?q=<peptide>&limit=10
     * Backs /vendors — cheapest N vendors carrying a given compound.
     * Groups by brand so we don't return five sizes from the same shop.
     */
    public function vendors(Request $request): JsonResponse
    {
        $q = $this->normaliseQuery((string) $request->query('q', ''));
        $limit = min(25, max(1, (int) $request->query('limit', 10)));
        if ($q === '') return response()->json(['results' => []]);

        // Resolve the category by exact-then-fuzzy match. Directs the query
        // at product_category_id rather than name-matching every product row.
        $category = ProductCategory::whereRaw('LOWER(name) = ?', [mb_strtolower($q)])->first()
            ?? ProductCategory::where('name', 'like', '%' . $q . '%')->first();

        $products = $category
            ? Product::visible()->where('price', '>', 0)->where('product_category_id', $category->id)
            : Product::visible()->where('price', '>', 0)->where('name', 'like', '%' . $q . '%');

        $rows = $products
            ->with(['brand:id,name,slug'])
            ->get();

        // One row per brand — cheapest offering wins.
        $byBrand = $rows
            ->groupBy('brand_id')
            ->map(function ($items) {
                return $items->sortBy(fn ($p) => $this->effective($p))->first();
            })
            ->values()
            ->sortBy(fn ($p) => $this->effective($p))
            ->take($limit)
            ->values();

        return response()->json([
            'query' => $q,
            'matched_category' => $category?->name,
            'count' => $byBrand->count(),
            'results' => $byBrand->map(fn ($p) => $this->productPayload($p))->values(),
        ]);
    }

    /**
     * GET /api/bot/compare?a=<pep>&b=<pep>
     * Vendor counts + price ranges for two compounds side by side.
     */
    public function compare(Request $request): JsonResponse
    {
        $a = $this->normaliseQuery((string) $request->query('a', ''));
        $b = $this->normaliseQuery((string) $request->query('b', ''));
        if ($a === '' || $b === '') abort(400, 'a and b are required');

        return response()->json([
            'a' => $this->compoundSummary($a),
            'b' => $this->compoundSummary($b),
        ]);
    }

    private function compoundSummary(string $q): array
    {
        $category = ProductCategory::whereRaw('LOWER(name) = ?', [mb_strtolower($q)])->first()
            ?? ProductCategory::where('name', 'like', '%' . $q . '%')->first();

        if (!$category) return ['query' => $q, 'found' => false];

        $products = Product::visible()->where('price', '>', 0)->where('product_category_id', $category->id)->get();
        if ($products->isEmpty()) return ['query' => $q, 'found' => false];

        $prices = $products->map(fn ($p) => $this->effective($p))->filter(fn ($v) => $v > 0);
        $vendorCount = $products->pluck('brand_id')->unique()->count();

        return [
            'query' => $q,
            'name' => $category->name,
            'slug' => $category->slug,
            'found' => true,
            'vendor_count' => $vendorCount,
            'product_count' => $products->count(),
            'min_price' => $prices->min(),
            'median_price' => $prices->sort()->values()->get(intdiv($prices->count(), 2)),
            'max_price' => $prices->max(),
            'url' => self::BASE . '/peptide/' . ($category->slug ?? ''),
        ];
    }

    /**
     * GET /api/bot/price-drops?since=<iso>&threshold=5&limit=25
     * Drives the #price-drops feed. Returns products whose current effective
     * price is >= threshold% lower than the most recent snapshot taken
     * on-or-before `since`. Any product without a baseline snapshot in the
     * last 30 days is excluded (we can't distinguish a new listing from a
     * genuine drop — SendPriceDropAlerts makes the same call).
     */
    public function priceDrops(Request $request): JsonResponse
    {
        $since = $request->query('since')
            ? Carbon::parse($request->query('since'))
            : Carbon::now()->subDay();
        $threshold = (float) $request->query('threshold', 5);
        $limit = min(50, max(1, (int) $request->query('limit', 25)));

        // Baseline: last snapshot per product where snapshot_at <= since.
        // Constrained to a 30-day window so a brand new product without
        // a real baseline doesn't show up as a "drop from $0".
        $baselineDate = $since->copy()->subDays(30);

        $latestBefore = DB::table('product_price_snapshots')
            ->selectRaw('product_id, MAX(snapshot_at) as ts')
            ->where('snapshot_at', '<=', $since)
            ->where('snapshot_at', '>=', $baselineDate)
            ->groupBy('product_id');

        $baseline = DB::table('product_price_snapshots as s')
            ->joinSub($latestBefore, 'latest', function ($j) {
                $j->on('s.product_id', '=', 'latest.product_id')
                  ->on('s.snapshot_at', '=', 'latest.ts');
            })
            ->select('s.product_id', 's.price', 's.discount_price', 's.snapshot_at')
            ->get()
            ->keyBy('product_id');

        if ($baseline->isEmpty()) {
            return response()->json(['results' => [], 'since' => $since->toIso8601String()]);
        }

        $products = Product::visible()->where('price', '>', 0)
            ->with(['brand:id,name,slug'])
            ->whereIn('id', $baseline->keys())
            ->get();

        $drops = [];
        foreach ($products as $p) {
            $b = $baseline[$p->id] ?? null;
            if (!$b) continue;

            $oldEff = ($b->discount_price !== null && $b->discount_price > 0 && $b->discount_price < $b->price)
                ? (float) $b->discount_price
                : (float) $b->price;
            if ($oldEff <= 0) continue;

            $newEff = $this->effective($p);
            if ($newEff <= 0 || $newEff >= $oldEff) continue;

            $dropPct = round((($oldEff - $newEff) / $oldEff) * 100, 1);
            if ($dropPct < $threshold) continue;

            $drops[] = [
                'product_id' => $p->id,
                'name' => $p->display_name ?: $p->name,
                'brand_name' => $p->brand?->name,
                'brand_slug' => $p->brand?->slug,
                'old_price' => $oldEff,
                'new_price' => $newEff,
                'drop_pct' => $dropPct,
                'image_url' => $p->image_url,
                'product_url' => $p->product_url,
                'url' => self::BASE . '/product/' . ($p->brand?->slug ?? 'brand') . '/' . ($p->slug ?? $p->id) . '/' . $p->id,
                'snapshot_at' => $b->snapshot_at,
            ];
        }

        // Biggest drops first.
        usort($drops, fn ($x, $y) => $y['drop_pct'] <=> $x['drop_pct']);
        $drops = array_slice($drops, 0, $limit);

        return response()->json([
            'since' => $since->toIso8601String(),
            'threshold' => $threshold,
            'count' => count($drops),
            'results' => $drops,
        ]);
    }

    /**
     * GET /api/bot/new-products?since=<iso>&limit=25
     * Feeds #new-products. Uses created_at, filtered to visible only.
     */
    public function newProducts(Request $request): JsonResponse
    {
        $since = $request->query('since')
            ? Carbon::parse($request->query('since'))
            : Carbon::now()->subDay();
        $limit = min(50, max(1, (int) $request->query('limit', 25)));

        $rows = Product::visible()->where('price', '>', 0)
            ->with(['brand:id,name,slug', 'category:id,name'])
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'since' => $since->toIso8601String(),
            'count' => $rows->count(),
            'results' => $rows->map(fn ($p) => $this->productPayload($p) + [
                'created_at' => $p->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * GET /api/bot/reviews?since=<iso>&limit=25
     * Feeds #vendor-reviews. Public-facing reviews only.
     */
    public function reviews(Request $request): JsonResponse
    {
        $since = $request->query('since')
            ? Carbon::parse($request->query('since'))
            : Carbon::now()->subDay();
        $limit = min(50, max(1, (int) $request->query('limit', 25)));

        $rows = VendorReview::query()
            ->with(['brand:id,name,slug', 'user:id,name'])
            ->where('created_at', '>=', $since)
            ->when(\Schema::hasColumn('vendor_reviews', 'status'), fn ($q) => $q->where('status', 'published'))
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        return response()->json([
            'since' => $since->toIso8601String(),
            'count' => $rows->count(),
            'results' => $rows->map(fn ($r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'title' => $r->title,
                'body' => $r->body,
                'author' => $r->user?->name ?? 'Anonymous',
                'brand_name' => $r->brand?->name,
                'brand_slug' => $r->brand?->slug,
                'brand_url' => self::BASE . '/brand/' . ($r->brand?->slug ?? ''),
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * GET /api/bot/promo-codes?limit=15
     * Active vendor coupon codes ranked by discount percent. Skips inactive
     * brands, zero-percent codes (placeholder-only), and empty strings.
     */
    public function promoCodes(Request $request): JsonResponse
    {
        $limit = min(30, max(1, (int) $request->query('limit', 15)));

        $rows = \App\Models\VendorSetting::query()
            ->with(['brand:id,name,slug,is_active'])
            ->whereNotNull('coupon_code')
            ->where('coupon_code', '!=', '')
            ->where('coupon_discount_percent', '>', 0)
            ->get()
            ->filter(fn ($v) => $v->brand && $v->brand->is_active)
            ->sortByDesc('coupon_discount_percent')
            ->take($limit)
            ->values();

        return response()->json([
            'count' => $rows->count(),
            'results' => $rows->map(fn ($v) => [
                'brand_name' => $v->brand->name,
                'brand_slug' => $v->brand->slug,
                'code' => $v->coupon_code,
                'discount_pct' => (int) $v->coupon_discount_percent,
                'url' => self::BASE . '/brand/' . $v->brand->slug,
            ])->values(),
        ]);
    }

    /**
     * GET /api/bot/blog-of-day
     * Rotates through existing blogs by day-of-year so #news has fresh
     * content every day even when nothing new is being written.
     * Only status=published (if that column exists); no draft leakage.
     */
    public function blogOfDay(Request $request): JsonResponse
    {
        $q = Blog::query();
        if (\Schema::hasColumn('blogs', 'status')) $q->where('status', 'published');
        $blogs = $q->orderBy('id')->get();
        if ($blogs->isEmpty()) return response()->json(['found' => false]);

        $dayIdx = (int) date('z'); // day of year 0-365
        $b = $blogs[$dayIdx % $blogs->count()];

        $image = $b->image;
        if ($image && !str_starts_with($image, 'http')) {
            $image = self::BASE . '/storage/' . ltrim($image, '/');
        }

        return response()->json([
            'found' => true,
            'id' => $b->id,
            'title' => $b->title,
            'slug' => $b->slug,
            'description' => $b->description,
            'image_url' => $image,
            'read_time' => $b->read_time,
            'published_at' => $b->published_at?->toIso8601String(),
            'url' => self::BASE . '/blog/' . $b->slug,
        ]);
    }

    /**
     * GET /api/bot/health — plain OK. Used by the bot to verify auth works
     * before starting its poll loop; makes token misconfigs obvious in logs.
     */
    public function health(): JsonResponse
    {
        return response()->json(['ok' => true, 'ts' => now()->toIso8601String()]);
    }
}
