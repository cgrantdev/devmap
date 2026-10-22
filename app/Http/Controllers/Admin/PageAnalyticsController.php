<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GscClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Per-page analytics — GSC + internal signals for a single frontend URL.
 *
 * URL: /admin/analytics/page?url=/some/path
 *
 * Agency uses this to answer "how is page X performing?" without
 * digging through the main CEO dashboard's site-wide top-10 lists.
 * Also deep-linked from the frontend admin bar on each page.
 */
class PageAnalyticsController extends Controller
{
    public function __construct(private GscClient $gsc) {}

    public function show(Request $request): Response
    {
        $path = $this->normalizePath((string) $request->query('url', '/'));
        $fullUrl = 'https://peptidemap.com' . $path;

        return Inertia::render('Admin/PageAnalytics', [
            'path' => $path,
            'full_url' => $fullUrl,
            'gsc' => $this->gscForUrl($fullUrl),
            'internal' => $this->internalForUrl($path),
            'recent_queries' => $this->recentQueriesForUrl($fullUrl),
        ]);
    }

    private function normalizePath(string $url): string
    {
        if (preg_match('~^https?://[^/]+(/.*)$~i', $url, $m)) {
            $url = $m[1];
        }
        if ($url === '' || $url[0] !== '/') $url = '/' . $url;
        return $url;
    }

    /**
     * GSC totals for the specific URL across the three standard windows.
     * Returns zeros when GSC isn't configured — the UI shows a hint.
     */
    private function gscForUrl(string $url): array
    {
        if (!$this->gsc->isConfigured()) return ['configured' => false, 'windows' => []];

        $end = Carbon::today()->subDays(2)->toDateString();
        $windows = [];
        foreach ([7, 28, 90] as $days) {
            $start = Carbon::parse($end)->subDays($days - 1)->toDateString();
            $prevEnd = Carbon::parse($start)->subDay()->toDateString();
            $prevStart = Carbon::parse($prevEnd)->subDays($days - 1)->toDateString();
            $windows[(string) $days] = [
                'current' => $this->totalsForUrl($url, $start, $end),
                'previous' => $this->totalsForUrl($url, $prevStart, $prevEnd),
                'start' => $start, 'end' => $end,
            ];
        }
        return ['configured' => true, 'windows' => $windows];
    }

    private function totalsForUrl(string $url, string $start, string $end): array
    {
        $r = $this->gsc->query([
            'startDate' => $start,
            'endDate' => $end,
            'dimensionFilterGroups' => [[
                'filters' => [[
                    'dimension' => 'page',
                    'operator' => 'equals',
                    'expression' => $url,
                ]],
            ]],
        ]);
        $row = ($r['rows'] ?? [[]])[0] ?? [];
        return [
            'clicks' => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'ctr' => round((float) ($row['ctr'] ?? 0) * 100, 2),
            'position' => round((float) ($row['position'] ?? 0), 1),
        ];
    }

    /**
     * Top queries driving traffic to this specific URL. GSC's richest
     * actionable signal — tells the agency which keyword angles are
     * actually working for this page.
     */
    private function recentQueriesForUrl(string $url): array
    {
        if (!$this->gsc->isConfigured()) return [];
        $end = Carbon::today()->subDays(2)->toDateString();
        $start = Carbon::parse($end)->subDays(27)->toDateString();
        $r = $this->gsc->query([
            'startDate' => $start,
            'endDate' => $end,
            'dimensions' => ['query'],
            'rowLimit' => 20,
            'dimensionFilterGroups' => [[
                'filters' => [[
                    'dimension' => 'page',
                    'operator' => 'equals',
                    'expression' => $url,
                ]],
            ]],
        ]);
        return collect($r['rows'] ?? [])->map(fn ($row) => [
            'query' => $row['keys'][0] ?? '',
            'clicks' => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'ctr' => round((float) ($row['ctr'] ?? 0) * 100, 2),
            'position' => round((float) ($row['position'] ?? 0), 1),
        ])->values()->all();
    }

    /**
     * Internal signals from our own tracking: human page views and
     * outbound affiliate clicks per URL. Fast queries — indexed on
     * created_at. Guards for missing tables so analytics renders
     * even if tracking is partially set up.
     */
    private function internalForUrl(string $path): array
    {
        $out = ['views' => null, 'outbound' => null];

        if (\Schema::hasTable('page_views')) {
            $pv = DB::table('page_views')
                ->where('url', 'like', "%{$path}")
                ->where('is_bot', 0)
                ->selectRaw("
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as d7,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as d28,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as d90
                ", [now()->subDays(7), now()->subDays(28), now()->subDays(90)])
                ->first();
            $out['views'] = ['d7' => (int) ($pv->d7 ?? 0), 'd28' => (int) ($pv->d28 ?? 0), 'd90' => (int) ($pv->d90 ?? 0)];
        }

        if (\Schema::hasTable('product_clicks')) {
            // product_clicks' internal referrer uses the format `internal:/path`
            $tag = 'internal:' . $path;
            $pc = DB::table('product_clicks')
                ->where('referrer', 'like', $tag . '%')
                ->where('is_bot', 0)
                ->selectRaw("
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as d7,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as d28,
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as d90
                ", [now()->subDays(7), now()->subDays(28), now()->subDays(90)])
                ->first();
            $out['outbound'] = ['d7' => (int) ($pc->d7 ?? 0), 'd28' => (int) ($pc->d28 ?? 0), 'd90' => (int) ($pc->d90 ?? 0)];
        }

        return $out;
    }
}
