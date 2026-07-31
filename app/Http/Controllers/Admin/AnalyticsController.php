<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductClick;
use App\Models\BannerEvent;
use App\Models\PageView;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        $since30 = now()->subDays(30);
        $since60 = now()->subDays(60);

        $baseClicks = ProductClick::humans();

        $clicks30 = (clone $baseClicks)->where('created_at', '>=', $since30)->count();
        $clicksPrev30 = (clone $baseClicks)
            ->whereBetween('created_at', [$since60, $since30])
            ->count();
        $clicksChange = $this->percentChange($clicksPrev30, $clicks30);

        $uniqueVisitors30 = (clone $baseClicks)
            ->where('created_at', '>=', $since30)
            ->distinct('ip_hash')
            ->count('ip_hash');

        $activeVendors = Brand::where('is_active', true)->count();
        $vendorSignups30 = Brand::where('created_at', '>=', $since30)->count();
        $productsListed = Product::where('hidden', false)->count();

        $stats = [
            [
                'label' => 'Outbound Clicks (30d)',
                'value' => number_format($clicks30),
                'change' => $clicksChange,
            ],
            [
                'label' => 'Unique Visitors (30d)',
                'value' => number_format($uniqueVisitors30),
                'change' => null,
            ],
            [
                'label' => 'Vendor Signups (30d)',
                'value' => number_format($vendorSignups30),
                'change' => null,
            ],
            [
                'label' => 'Products Listed',
                'value' => number_format($productsListed),
                'change' => null,
            ],
        ];

        // Top vendors by outbound clicks in last 30 days
        $topVendors = ProductClick::humans()
            ->where('created_at', '>=', $since30)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as click_count')
            ->groupBy('brand_id')
            ->orderByDesc('click_count')
            ->take(5)
            ->with('brand:id,name')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->brand_id,
                'name' => $row->brand?->name ?? 'Unknown brand',
                'clicks' => (int) $row->click_count,
            ])
            ->values()
            ->toArray();

        // Top products by outbound clicks in last 30 days
        $topProducts = ProductClick::humans()
            ->where('created_at', '>=', $since30)
            ->selectRaw('product_id, COUNT(*) as click_count')
            ->groupBy('product_id')
            ->orderByDesc('click_count')
            ->take(5)
            ->with('product:id,name,brand_id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->product_id,
                'name' => $row->product?->name ?? 'Unknown product',
                'clicks' => (int) $row->click_count,
            ])
            ->values()
            ->toArray();

        // Daily click timeseries for a chart
        $clicksByDay = ProductClick::humans()
            ->where('created_at', '>=', $since30)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as click_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => (string) $row->day,
                'clicks' => (int) $row->click_count,
            ])
            ->values()
            ->toArray();

        $banners = $this->bannerAnalytics();
        $pages = $this->pageViewAnalytics();
        $vendorBreakdown = $this->vendorBreakdown();

        return Inertia::render('Admin/Analytics', [
            'stats' => $stats,
            'topVendors' => $topVendors,
            'topProducts' => $topProducts,
            'clicksByDay' => $clicksByDay,
            'bannerBanners' => $banners['banners'],
            'bannerByDay' => $banners['byDay'],
            'pageTypes' => $pages['types'],
            'pageViewsByDay' => $pages['byDay'],
            'vendorBreakdown' => $vendorBreakdown,
        ]);
    }

    /**
     * Page-view totals grouped by page_type for 7d/30d/all-time and a 30d daily series.
     */
    private function pageViewAnalytics(): array
    {
        $since7 = now()->subDays(7);
        $since30 = now()->subDays(30);

        $agg = function ($since = null) {
            $q = PageView::humans()
                ->selectRaw('page_type, COUNT(*) as n')
                ->groupBy('page_type');
            if ($since) $q->where('created_at', '>=', $since);
            return $q->pluck('n', 'page_type')->toArray();
        };

        $t7 = $agg($since7);
        $t30 = $agg($since30);
        $tAll = $agg();
        $names = array_unique(array_merge(array_keys($t7), array_keys($t30), array_keys($tAll)));
        sort($names);

        $types = [];
        foreach ($names as $name) {
            $types[] = [
                'page_type' => $name,
                'views_7d'  => (int) ($t7[$name] ?? 0),
                'views_30d' => (int) ($t30[$name] ?? 0),
                'views_all' => (int) ($tAll[$name] ?? 0),
            ];
        }
        usort($types, fn($a, $b) => $b['views_30d'] <=> $a['views_30d']);

        $byDay = PageView::humans()
            ->where('created_at', '>=', $since30)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as n')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn($r) => ['day' => (string) $r->day, 'views' => (int) $r->n])
            ->values()
            ->toArray();

        return ['types' => $types, 'byDay' => $byDay];
    }

    /**
     * Per-vendor breakdown (30d) — storefront page views, storefront outbound clicks
     * (banner_events with slot='brand_storefront_visit'), and product clicks.
     */
    private function vendorBreakdown(): array
    {
        $since30 = now()->subDays(30);

        $views = PageView::humans()
            ->where('page_type', 'brand')
            ->where('created_at', '>=', $since30)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as n, COUNT(DISTINCT ip_hash) as uniq')
            ->groupBy('brand_id')
            ->get()
            ->keyBy('brand_id');

        $storefrontClicks = BannerEvent::humans()->clicks()
            ->where('slot', 'brand_storefront_visit')
            ->where('created_at', '>=', $since30)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as n')
            ->groupBy('brand_id')
            ->pluck('n', 'brand_id')
            ->toArray();

        $couponCopies = BannerEvent::humans()->clicks()
            ->where('slot', 'brand_storefront_coupon')
            ->where('created_at', '>=', $since30)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as n')
            ->groupBy('brand_id')
            ->pluck('n', 'brand_id')
            ->toArray();

        $productClicks = ProductClick::humans()
            ->where('created_at', '>=', $since30)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, COUNT(*) as n')
            ->groupBy('brand_id')
            ->pluck('n', 'brand_id')
            ->toArray();

        $brandIds = array_unique(array_merge(
            $views->keys()->all(),
            array_keys($storefrontClicks),
            array_keys($couponCopies),
            array_keys($productClicks),
        ));
        if (!$brandIds) return [];

        $brands = Brand::whereIn('id', $brandIds)->get(['id', 'name', 'slug'])->keyBy('id');

        $rows = [];
        foreach ($brandIds as $bid) {
            $v = (int) ($views[$bid]->n ?? 0);
            $sc = (int) ($storefrontClicks[$bid] ?? 0);
            $pc = (int) ($productClicks[$bid] ?? 0);
            $rows[] = [
                'brand_id'          => (int) $bid,
                'name'              => $brands[$bid]->name ?? '(deleted)',
                'slug'              => $brands[$bid]->slug ?? null,
                'page_views_30d'    => $v,
                'unique_visitors_30d' => (int) ($views[$bid]->uniq ?? 0),
                'storefront_clicks_30d' => $sc,
                'coupon_copies_30d' => (int) ($couponCopies[$bid] ?? 0),
                'product_clicks_30d' => $pc,
                'visit_ctr_30d'     => $this->ctr($v, $sc),
                'product_ctr_30d'   => $this->ctr($v, $pc),
            ];
        }
        usort($rows, fn($a, $b) => $b['page_views_30d'] <=> $a['page_views_30d']);
        return $rows;
    }

    /**
     * Aggregate banner-slot metrics for the admin analytics page:
     *   - per-slot impressions/clicks/CTR for 7d, 30d, all-time
     *   - per-slide breakdown for each slot in the last 30d
     *   - daily impression + click totals across all slots (last 30d)
     */
    private function bannerAnalytics(): array
    {
        $since7  = now()->subDays(7);
        $since30 = now()->subDays(30);

        // One row per (slot, banner_key, event_type, window). Windows are
        // stitched together in PHP so each banner ends up as a single flat row.
        $agg = function ($since = null) {
            $q = BannerEvent::humans()
                ->selectRaw("
                    slot,
                    COALESCE(banner_key, '(unknown)') as banner_key,
                    event_type,
                    COUNT(*) as n,
                    MAX(brand_id) as brand_id,
                    MAX(JSON_UNQUOTE(JSON_EXTRACT(meta, '\$.label'))) as meta_label,
                    MAX(JSON_UNQUOTE(JSON_EXTRACT(meta, '\$.title'))) as meta_title,
                    MAX(JSON_UNQUOTE(JSON_EXTRACT(meta, '\$.url'))) as meta_url
                ")
                ->groupBy('slot', 'banner_key', 'event_type');
            if ($since) $q->where('created_at', '>=', $since);
            return $q->get();
        };

        $rows7   = $agg($since7);
        $rows30  = $agg($since30);
        $rowsAll = $agg();

        // Collect brand ids from any row so we can resolve names in bulk.
        $brandIds = $rowsAll->pluck('brand_id')->filter()->unique()->all();
        $brandsById = $brandIds
            ? Brand::whereIn('id', $brandIds)->pluck('name', 'id')->all()
            : [];

        // Bucket by "slot|banner_key" so we can merge counts + metadata from
        // all three time-window queries into a single row per banner.
        $byBanner = [];
        $ingest = function ($rows, string $windowSuffix) use (&$byBanner) {
            foreach ($rows as $r) {
                $id = $r->slot . '|' . $r->banner_key;
                if (!isset($byBanner[$id])) {
                    $byBanner[$id] = [
                        'slot' => $r->slot,
                        'banner_key' => $r->banner_key,
                        'meta_label' => null,
                        'meta_title' => null,
                        'meta_url'   => null,
                        'brand_id'   => null,
                        'impressions_7d'  => 0, 'clicks_7d'  => 0,
                        'impressions_30d' => 0, 'clicks_30d' => 0,
                        'impressions_all' => 0, 'clicks_all' => 0,
                    ];
                }
                $key = ($r->event_type === 'impression' ? 'impressions_' : 'clicks_') . $windowSuffix;
                $byBanner[$id][$key] += (int) $r->n;
                // Metadata: last-write-wins, but any non-null real value wins over "null" string / null.
                foreach (['meta_label', 'meta_title', 'meta_url'] as $mk) {
                    $v = $r->{$mk} ?? null;
                    if ($v !== null && $v !== '' && $v !== 'null') $byBanner[$id][$mk] = $v;
                }
                if ($r->brand_id) $byBanner[$id]['brand_id'] = (int) $r->brand_id;
            }
        };
        $ingest($rows7, '7d');
        $ingest($rows30, '30d');
        $ingest($rowsAll, 'all');

        $banners = [];
        foreach ($byBanner as $b) {
            // Prefer the admin-defined analytics_label; then brand name; then the slide's title.
            $label = null;
            if (!empty($b['meta_label'])) $label = $b['meta_label'];
            elseif (!empty($b['brand_id']) && isset($brandsById[$b['brand_id']])) $label = $brandsById[$b['brand_id']];
            elseif (!empty($b['meta_title'])) $label = $b['meta_title'];
            else $label = $this->humanizeKey($b['banner_key']);

            $banners[] = [
                'slot'             => $b['slot'],
                'banner_key'       => $b['banner_key'],
                'label'            => $label,
                'url'              => $b['meta_url'] ?? null,
                'impressions_7d'   => $b['impressions_7d'],
                'clicks_7d'        => $b['clicks_7d'],
                'impressions_30d'  => $b['impressions_30d'],
                'clicks_30d'       => $b['clicks_30d'],
                'impressions_all'  => $b['impressions_all'],
                'clicks_all'       => $b['clicks_all'],
                'ctr_30d'          => $this->ctr($b['impressions_30d'], $b['clicks_30d']),
            ];
        }
        // Most 30d impressions first.
        usort($banners, fn($a, $b) => $b['impressions_30d'] <=> $a['impressions_30d']);

        // Daily aggregate for the 30-day chart.
        $byDayRows = BannerEvent::humans()
            ->where('created_at', '>=', $since30)
            ->selectRaw('DATE(created_at) as day, event_type, COUNT(*) as n')
            ->groupBy('day', 'event_type')
            ->orderBy('day')
            ->get();

        $byDay = [];
        foreach ($byDayRows as $r) {
            $d = (string) $r->day;
            if (!isset($byDay[$d])) $byDay[$d] = ['day' => $d, 'impressions' => 0, 'clicks' => 0];
            $byDay[$d][$r->event_type === 'impression' ? 'impressions' : 'clicks'] = (int) $r->n;
        }

        return [
            'banners' => $banners,
            'byDay'   => array_values($byDay),
        ];
    }

    private function ctr(int $impressions, int $clicks): ?string
    {
        if ($impressions <= 0) return null;
        return number_format(($clicks / $impressions) * 100, 2) . '%';
    }

    /**
     * "certified-peptides" → "Certified Peptides" — fallback label when we
     * have no brand row or meta.title to lean on.
     */
    private function humanizeKey(string $key): string
    {
        $s = trim(str_replace(['-', '_'], ' ', $key));
        return $s === '' ? $key : ucwords($s);
    }

    private function percentChange(int $previous, int $current): ?string
    {
        if ($previous === 0) {
            return $current > 0 ? '+100%' : null;
        }

        $change = (($current - $previous) / $previous) * 100;
        $sign = $change >= 0 ? '+' : '';

        return $sign . number_format($change, 1) . '%';
    }
}
