<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ProductClick;
use App\Services\GoAffProClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Single-pane affiliate stats across every vendor. Combines:
 *   - Cached per-vendor snapshot pulled from each affiliate program's
 *     API (GoAffPro today; Refersion + Impact stubs to add)
 *   - Our own click log rolled up per-vendor for the same window,
 *     so we can compare "clicks we sent" vs "sales they credited us"
 *     and spot mismatches (e.g. tracking not wired up).
 *
 * See docs/affiliate-management.md for setup.
 */
class AffiliatesController extends Controller
{
    public function index(Request $request)
    {
        $windowDays = (int) ($request->get('days', 30));
        $since = now()->subDays($windowDays);

        // Rollup: clicks + estimated commission by brand, from our DB.
        $ourClicks = ProductClick::query()
            ->where('created_at', '>=', $since)
            ->where('is_bot', false)
            ->select(
                'brand_id',
                DB::raw('COUNT(*) as clicks'),
                DB::raw('COALESCE(SUM(estimated_commission_usd), 0) as est_commission')
            )
            ->groupBy('brand_id')
            ->get()
            ->keyBy('brand_id');

        $rows = Brand::query()
            ->where('is_active', true)
            ->with('vendorSetting')
            ->orderBy('name')
            ->get()
            ->map(function ($b) use ($ourClicks) {
                $vs = $b->vendorSetting;
                $stats = is_array($vs?->affiliate_stats_json) ? $vs->affiliate_stats_json : null;
                $own = $ourClicks->get($b->id);

                return [
                    'id' => $b->id,
                    'slug' => $b->slug,
                    'name' => $b->name,
                    'platform' => $vs?->affiliate_platform ?? 'none',
                    'commission_rate_pct' => $vs?->commission_rate_pct ? (float) $vs->commission_rate_pct : null,
                    'has_credentials' => !empty($vs?->affiliate_credentials),
                    'our_clicks' => (int) ($own->clicks ?? 0),
                    'our_est_commission' => (float) ($own->est_commission ?? 0),
                    'vendor_clicks' => $stats['clicks_total'] ?? null,
                    'vendor_orders' => $stats['orders_total'] ?? null,
                    'vendor_revenue' => $stats['revenue_total'] ?? null,
                    'commission_earned' => $stats['commission_earned'] ?? null,
                    'commission_pending' => $stats['commission_pending'] ?? null,
                    'commission_paid' => $stats['commission_paid'] ?? null,
                    'stats_updated_at' => $vs?->affiliate_stats_updated_at?->diffForHumans(),
                    'stats_stale' => $vs?->affiliate_stats_updated_at
                        ? $vs->affiliate_stats_updated_at->lt(now()->subDays(2))
                        : true,
                ];
            })
            ->sortByDesc('our_clicks')
            ->values();

        // Portfolio totals
        $totals = [
            'clicks' => $rows->sum('our_clicks'),
            'est_commission' => $rows->sum('our_est_commission'),
            'vendor_orders' => $rows->sum('vendor_orders'),
            'vendor_revenue' => $rows->sum('vendor_revenue'),
            'commission_earned' => $rows->sum('commission_earned'),
            'commission_pending' => $rows->sum('commission_pending'),
            'vendors_wired' => $rows->where('has_credentials', true)->count(),
            'vendors_total' => $rows->count(),
        ];

        return Inertia::render('Admin/Affiliates/Index', [
            'rows' => $rows,
            'totals' => $totals,
            'window_days' => $windowDays,
            'platforms' => ['none', 'goaffpro', 'refersion', 'impact', 'sharesale', 'manual'],
        ]);
    }

    /**
     * Save affiliate credentials for a vendor. Called from the row's
     * inline "Add token" form. GoAffPro today; the platform value drives
     * which client we'll use in the sync job.
     */
    public function saveCredentials(Request $request, int $brandId)
    {
        $validated = $request->validate([
            'affiliate_platform' => 'required|string|in:none,goaffpro,refersion,impact,sharesale,manual',
            'affiliate_token' => 'nullable|string|max:2048',
            'commission_rate_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        $brand = Brand::findOrFail($brandId);
        $vs = $brand->vendorSetting;
        if (!$vs) return back()->with('flash_error', 'No vendorSetting row on this brand.');

        $vs->affiliate_platform = $validated['affiliate_platform'];
        // Credentials shape is per-platform; we store the raw token
        // string under a common key and let each client interpret it.
        $vs->affiliate_credentials = !empty($validated['affiliate_token'])
            ? ['token' => $validated['affiliate_token']]
            : null;
        if (array_key_exists('commission_rate_pct', $validated)) {
            $vs->commission_rate_pct = $validated['commission_rate_pct'];
        }
        $vs->save();

        return back()->with('flash_success', "Affiliate config saved for {$brand->name}.");
    }

    /**
     * Force a fresh pull from the affiliate program's API for one vendor.
     * Idempotent — click again to re-sync. Also runs from a daily
     * scheduled job (SyncAffiliateStats command, to be wired).
     */
    public function syncOne(int $brandId, GoAffProClient $goaffpro)
    {
        $brand = Brand::findOrFail($brandId);
        $vs = $brand->vendorSetting;
        if (!$vs || !$vs->affiliate_platform) {
            return back()->with('flash_error', 'No affiliate platform configured.');
        }

        $creds = $vs->affiliate_credentials ?? [];
        $token = is_array($creds) ? ($creds['token'] ?? null) : null;
        if (!$token) return back()->with('flash_error', 'No credentials saved for this vendor.');

        $stats = null;
        if ($vs->affiliate_platform === 'goaffpro') {
            $stats = $goaffpro->fetchStats($token);
        }
        // Other platforms would branch here as clients are added.

        if (!$stats) {
            return back()->with('flash_error', "Sync failed for {$brand->name} — check the token or platform status.");
        }

        $vs->affiliate_stats_json = $stats;
        $vs->affiliate_stats_updated_at = now();
        $vs->save();

        return back()->with('flash_success', "Synced {$brand->name}: {$stats['orders_total']} orders / \${$stats['commission_earned']} earned.");
    }
}
