<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductClick;
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

        return Inertia::render('Admin/Analytics', [
            'stats' => $stats,
            'topVendors' => $topVendors,
            'topProducts' => $topProducts,
            'clicksByDay' => $clicksByDay,
        ]);
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
