<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductClick;
use App\Models\BannerEvent;
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

        return Inertia::render('Admin/Analytics', [
            'stats' => $stats,
            'topVendors' => $topVendors,
            'topProducts' => $topProducts,
            'clicksByDay' => $clicksByDay,
            'bannerSlots' => $banners['slots'],
            'bannerSlides' => $banners['slides'],
            'bannerByDay' => $banners['byDay'],
        ]);
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

        $rowsToKV = function ($rows, $keyA, $keyB, $valueKey = 'n') {
            $out = [];
            foreach ($rows as $r) {
                $a = (string) $r->{$keyA};
                $b = (string) $r->{$keyB};
                $out[$a][$b] = (int) $r->{$valueKey};
            }
            return $out;
        };

        $slotAgg = function ($since = null) {
            $q = BannerEvent::humans()
                ->selectRaw('slot, event_type, COUNT(*) as n')
                ->groupBy('slot', 'event_type');
            if ($since) $q->where('created_at', '>=', $since);
            return $q->get();
        };

        $slots7   = $rowsToKV($slotAgg($since7), 'slot', 'event_type');
        $slots30  = $rowsToKV($slotAgg($since30), 'slot', 'event_type');
        $slotsAll = $rowsToKV($slotAgg(), 'slot', 'event_type');

        $allSlotNames = array_unique(array_merge(
            array_keys($slots7), array_keys($slots30), array_keys($slotsAll),
        ));
        sort($allSlotNames);

        $slotsPayload = [];
        foreach ($allSlotNames as $slot) {
            $slotsPayload[] = [
                'slot'         => $slot,
                'impressions_7d'   => (int) ($slots7[$slot]['impression'] ?? 0),
                'clicks_7d'        => (int) ($slots7[$slot]['click'] ?? 0),
                'impressions_30d'  => (int) ($slots30[$slot]['impression'] ?? 0),
                'clicks_30d'       => (int) ($slots30[$slot]['click'] ?? 0),
                'impressions_all'  => (int) ($slotsAll[$slot]['impression'] ?? 0),
                'clicks_all'       => (int) ($slotsAll[$slot]['click'] ?? 0),
                'ctr_30d'          => $this->ctr(
                    (int) ($slots30[$slot]['impression'] ?? 0),
                    (int) ($slots30[$slot]['click'] ?? 0),
                ),
            ];
        }
        usort($slotsPayload, fn($a, $b) => $b['impressions_30d'] <=> $a['impressions_30d']);

        // Per-slide (banner_key) breakdown within each slot for last 30d.
        $slideRows = BannerEvent::humans()
            ->where('created_at', '>=', $since30)
            ->selectRaw('slot, COALESCE(banner_key, \'(unknown)\') as banner_key, event_type, COUNT(*) as n')
            ->groupBy('slot', 'banner_key', 'event_type')
            ->get();

        $bySlotSlide = [];
        foreach ($slideRows as $r) {
            $bySlotSlide[$r->slot][$r->banner_key][$r->event_type] = (int) $r->n;
        }
        $slidesPayload = [];
        foreach ($bySlotSlide as $slot => $slides) {
            $items = [];
            foreach ($slides as $key => $counts) {
                $imp = (int) ($counts['impression'] ?? 0);
                $clk = (int) ($counts['click'] ?? 0);
                $items[] = [
                    'banner_key'  => $key,
                    'impressions' => $imp,
                    'clicks'      => $clk,
                    'ctr'         => $this->ctr($imp, $clk),
                ];
            }
            usort($items, fn($a, $b) => $b['impressions'] <=> $a['impressions']);
            $slidesPayload[] = ['slot' => $slot, 'items' => $items];
        }
        usort($slidesPayload, fn($a, $b) => strcmp($a['slot'], $b['slot']));

        // Daily aggregate for a chart (last 30d).
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
            'slots'  => $slotsPayload,
            'slides' => $slidesPayload,
            'byDay'  => array_values($byDay),
        ];
    }

    private function ctr(int $impressions, int $clicks): ?string
    {
        if ($impressions <= 0) return null;
        return number_format(($clicks / $impressions) * 100, 2) . '%';
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
