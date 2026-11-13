<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\CompoundDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Typeahead for the header search palette and the homepage search box.
 * Up to 8 matches: compounds first (vendor count + lowest listed price),
 * then vendors. Cached per normalized query for 10 minutes. Lives under
 * /api/ so the location-cookie redirect middleware leaves it alone.
 */
class SearchSuggestController extends Controller
{
    private const LIMIT = 8;
    private const MAX_VENDORS = 3;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 60));
        if (mb_strlen($q) < 2) {
            return response()->json(['compounds' => [], 'vendors' => []]);
        }

        $payload = Cache::remember(
            'search_suggest:v1:' . md5(mb_strtolower($q)),
            600,
            fn () => $this->lookup($q)
        );

        // private: the web middleware group attaches session cookies, so a
        // shared cache must never store this response. The server-side
        // cache above already absorbs repeat lookups.
        return response()
            ->json($payload)
            ->header('Cache-Control', 'private, max-age=300');
    }

    private function lookup(string $q): array
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $like = '%' . $escaped . '%';
        $prefix = $escaped . '%';
        $lower = mb_strtolower($q);

        // Compounds with at least one live listing. Slugs with a slash or
        // whitespace can't be routed by /product/{slug}, so they're skipped.
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)->orWhere('slug', 'like', $like);
            })
            ->whereHas('products', fn ($p) => $p->visible()->where('status', 'active'))
            ->limit(40)
            ->get(['id', 'name', 'slug'])
            ->filter(fn ($c) => is_string($c->slug) && $c->slug !== '' && !preg_match('#[/\s]#', $c->slug))
            ->values();

        $agg = $categories->isEmpty()
            ? collect()
            : Product::visible()
                ->where('status', 'active')
                ->whereIn('product_category_id', $categories->pluck('id')->all())
                ->groupBy('product_category_id')
                ->selectRaw('product_category_id, COUNT(DISTINCT brand_id) as vendor_count, MIN(CASE WHEN discount_price > 0 THEN discount_price WHEN discount_price IS NULL AND price > 0 THEN price END) as min_price')
                ->toBase()
                ->get()
                ->keyBy('product_category_id');

        $compounds = $categories
            ->map(function ($c) use ($agg, $lower) {
                $row = $agg->get($c->id);
                $minPrice = $row?->min_price;
                $name = (string) $c->name;
                return [
                    'name' => CompoundDisplay::label($name) ?? $name,
                    'slug' => $c->slug,
                    'url' => '/product/' . rawurlencode($c->slug),
                    'vendor_count' => (int) ($row?->vendor_count ?? 0),
                    'from_price' => $minPrice ? number_format((float) $minPrice, 2, '.', '') : null,
                    // Rank: name starts with the query, then vendor depth.
                    '_rank' => str_starts_with(mb_strtolower($name), $lower) ? 0 : 1,
                ];
            })
            ->sortBy([['_rank', 'asc'], ['vendor_count', 'desc']])
            ->values();

        $vendors = Brand::query()
            ->where('is_active', true)
            ->whereNotNull('slug')
            ->where('name', 'like', $like)
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$prefix])
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'slug'])
            ->map(fn ($b) => [
                'name' => $b->name,
                'slug' => $b->slug,
                'url' => '/brand/' . rawurlencode($b->slug),
            ])
            ->values();

        // Compounds lead; keep room for a few vendors when both match.
        $compoundTake = min($compounds->count(), self::LIMIT - min($vendors->count(), self::MAX_VENDORS));
        $compounds = $compounds->take($compoundTake)
            ->map(function ($c) {
                unset($c['_rank']);
                return $c;
            })
            ->values();
        $vendors = $vendors->take(self::LIMIT - $compounds->count())->values();

        return [
            'compounds' => $compounds->all(),
            'vendors' => $vendors->all(),
        ];
    }
}
