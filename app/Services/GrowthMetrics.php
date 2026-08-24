<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Growth-metrics aggregator. Single source of truth for:
 *   - the CEO dashboard's growth panel
 *   - the weekly Discord digest
 *   - any future automated report
 *
 * Reads only from our own tables (page_views + product_clicks) — no external
 * dependencies, no API keys, no rate limits.
 */
class GrowthMetrics
{
    /**
     * Full snapshot. Everything the dashboard renders + everything the
     * digest post needs, computed in one pass.
     */
    public function snapshot(): array
    {
        return [
            'sessions_trend'       => $this->dailySessionsTrend(30),
            'clicks_trend'         => $this->dailyClicksTrend(30),
            'week_over_week'       => $this->weekOverWeek(),
            'top_pages'            => $this->topInternalPagesByClicks(7, 10),
            'top_compounds'        => $this->topCompoundsByClicks(7, 10),
            'top_vendors'          => $this->topVendorsByClicks(7, 10),
            'top_referrers'        => $this->topExternalReferrers(30, 10),
            'vendor_pipeline'      => $this->vendorPipeline(),
            'attribution_health'   => $this->attributionHealth(),
        ];
    }

    /* -------- daily trends -------- */

    public function dailySessionsTrend(int $days = 30): array
    {
        if (!Schema::hasTable('page_views')) return [];
        $since = now()->subDays($days)->startOfDay();
        $rows = DB::table('page_views')
            ->where('created_at', '>=', $since)
            ->where('is_bot', 0)
            ->selectRaw('DATE(created_at) as d, COUNT(DISTINCT session_id) as sessions, COUNT(*) as views')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        return $this->fillDaily($rows, $since, ['sessions', 'views']);
    }

    public function dailyClicksTrend(int $days = 30): array
    {
        if (!Schema::hasTable('product_clicks')) return [];
        $since = now()->subDays($days)->startOfDay();
        $rows = DB::table('product_clicks')
            ->where('created_at', '>=', $since)
            ->where('is_bot', 0)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as clicks')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        return $this->fillDaily($rows, $since, ['clicks']);
    }

    /**
     * Fill zero-count days in a daily aggregate so the chart doesn't get a
     * misleading gap when a day had no activity.
     */
    private function fillDaily($rows, Carbon $since, array $numericFields): array
    {
        $indexed = collect($rows)->keyBy('d');
        $out = [];
        $cursor = $since->copy();
        $today = now()->startOfDay();
        while ($cursor <= $today) {
            $key = $cursor->toDateString();
            $row = ['date' => $key];
            $existing = $indexed->get($key);
            foreach ($numericFields as $f) {
                $row[$f] = $existing ? (int) $existing->$f : 0;
            }
            $out[] = $row;
            $cursor->addDay();
        }
        return $out;
    }

    /* -------- week over week -------- */

    public function weekOverWeek(): array
    {
        $now = now();
        $thisWeekStart = $now->copy()->subDays(7);
        $prevWeekStart = $now->copy()->subDays(14);

        $q = fn ($tbl, $start, $end) => Schema::hasTable($tbl)
            ? DB::table($tbl)->where('created_at', '>=', $start)->where('created_at', '<', $end)->where('is_bot', 0)->count()
            : 0;

        $qSessions = fn ($start, $end) => Schema::hasTable('page_views')
            ? DB::table('page_views')->where('created_at', '>=', $start)->where('created_at', '<', $end)->where('is_bot', 0)->distinct('session_id')->count('session_id')
            : 0;

        $sessionsThis = $qSessions($thisWeekStart, $now);
        $sessionsPrev = $qSessions($prevWeekStart, $thisWeekStart);
        $clicksThis   = $q('product_clicks', $thisWeekStart, $now);
        $clicksPrev   = $q('product_clicks', $prevWeekStart, $thisWeekStart);
        $viewsThis    = $q('page_views', $thisWeekStart, $now);
        $viewsPrev    = $q('page_views', $prevWeekStart, $thisWeekStart);

        return [
            'sessions_this'  => $sessionsThis,
            'sessions_prev'  => $sessionsPrev,
            'sessions_delta' => $this->pctDelta($sessionsPrev, $sessionsThis),
            'clicks_this'    => $clicksThis,
            'clicks_prev'    => $clicksPrev,
            'clicks_delta'   => $this->pctDelta($clicksPrev, $clicksThis),
            'views_this'     => $viewsThis,
            'views_prev'     => $viewsPrev,
            'views_delta'    => $this->pctDelta($viewsPrev, $viewsThis),
        ];
    }

    private function pctDelta(int $prev, int $curr): ?int
    {
        if ($prev === 0) return $curr > 0 ? null : 0; // avoid divide-by-zero (null = "no prev baseline")
        return (int) round((($curr - $prev) / $prev) * 100);
    }

    /* -------- ranked lists -------- */

    public function topInternalPagesByClicks(int $days = 7, int $limit = 10): array
    {
        if (!Schema::hasTable('product_clicks')) return [];
        return DB::table('product_clicks')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('is_bot', 0)
            ->where('referrer', 'like', 'internal:%')
            ->selectRaw("REPLACE(referrer, 'internal:', '') as page, COUNT(*) as clicks")
            ->groupBy('page')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['page' => $r->page, 'clicks' => (int) $r->clicks])
            ->all();
    }

    public function topCompoundsByClicks(int $days = 7, int $limit = 10): array
    {
        if (!Schema::hasTable('product_clicks')) return [];
        return DB::table('product_clicks')
            ->join('products', 'product_clicks.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->where('product_clicks.created_at', '>=', now()->subDays($days))
            ->where('product_clicks.is_bot', 0)
            ->selectRaw("COALESCE(product_categories.name, products.name) as name, product_categories.slug as slug, COUNT(*) as clicks")
            ->groupBy('name', 'slug')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'slug' => $r->slug, 'clicks' => (int) $r->clicks])
            ->all();
    }

    public function topVendorsByClicks(int $days = 7, int $limit = 10): array
    {
        if (!Schema::hasTable('product_clicks')) return [];
        return DB::table('product_clicks')
            ->join('brands', 'product_clicks.brand_id', '=', 'brands.id')
            ->where('product_clicks.created_at', '>=', now()->subDays($days))
            ->where('product_clicks.is_bot', 0)
            ->selectRaw("brands.name, brands.slug, COUNT(*) as clicks")
            ->groupBy('brands.name', 'brands.slug')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'slug' => $r->slug, 'clicks' => (int) $r->clicks])
            ->all();
    }

    public function topExternalReferrers(int $days = 30, int $limit = 10): array
    {
        if (!Schema::hasTable('page_views')) return [];
        return DB::table('page_views')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('is_bot', 0)
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->where('referrer', 'not like', '%peptidemap%')
            ->selectRaw("SUBSTRING_INDEX(SUBSTRING_INDEX(referrer, '/', 3), '://', -1) as host, COUNT(*) as hits")
            ->groupBy('host')
            ->orderByDesc('hits')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['host' => $r->host, 'hits' => (int) $r->hits])
            ->all();
    }

    /* -------- pipeline + health -------- */

    public function vendorPipeline(): array
    {
        return [
            'approved' => DB::table('brands')
                ->join('vendor_settings', 'vendor_settings.brand_id', '=', 'brands.id')
                ->where('brands.is_active', 1)
                ->where('vendor_settings.approval_status', 'approved')
                ->count(),
            'pending' => DB::table('vendor_settings')->where('approval_status', 'pending')->count(),
            'new_this_week' => DB::table('brands')->where('created_at', '>=', now()->subDays(7))->count(),
            'new_this_month' => DB::table('brands')->where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    public function attributionHealth(): array
    {
        if (!Schema::hasTable('product_clicks')) return [];
        $since = now()->subDays(7);
        $total = DB::table('product_clicks')->where('created_at', '>=', $since)->where('is_bot', 0)->count();
        $withInternal = DB::table('product_clicks')->where('created_at', '>=', $since)->where('is_bot', 0)->where('referrer', 'like', 'internal:%')->count();
        $withUtm = DB::table('product_clicks')->where('created_at', '>=', $since)->where('is_bot', 0)->whereNotNull('utm_source')->count();
        return [
            'total' => $total,
            'with_internal_src' => $withInternal,
            'internal_src_pct' => $total > 0 ? (int) round(($withInternal / $total) * 100) : 0,
            'with_utm' => $withUtm,
        ];
    }
}
