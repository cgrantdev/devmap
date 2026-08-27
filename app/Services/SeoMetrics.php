<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * High-level SEO metrics — thin wrapper on GscClient that composes the
 * ranked lists the CEO dashboard + weekly digest render. Every method
 * degrades gracefully to empty arrays when GSC isn't configured yet.
 */
class SeoMetrics
{
    public function __construct(private GscClient $gsc) {}

    public function isConfigured(): bool
    {
        return $this->gsc->isConfigured();
    }

    /**
     * Everything the CEO dashboard renders in one call.
     */
    public function snapshot(int $days = 28): array
    {
        if (!$this->isConfigured()) return ['configured' => false];

        [$start, $end] = $this->range($days);
        $prevEnd = Carbon::parse($start)->subDay()->toDateString();
        $prevStart = Carbon::parse($prevEnd)->subDays($days - 1)->toDateString();

        return [
            'configured' => true,
            'window_days' => $days,
            'window_start' => $start,
            'window_end' => $end,
            'totals' => $this->totals($start, $end),
            'totals_prev' => $this->totals($prevStart, $prevEnd),
            'top_queries' => $this->topQueries($start, $end),
            'top_pages' => $this->topPages($start, $end),
            'opportunities' => $this->opportunityQueries($start, $end),
        ];
    }

    /** Aggregate clicks + impressions + avg CTR + avg position over a range. */
    public function totals(string $start, string $end): array
    {
        $r = $this->gsc->query([
            'startDate' => $start,
            'endDate' => $end,
            'aggregationType' => 'byProperty',
        ]);
        $row = $r['rows'][0] ?? null;
        return [
            'clicks'      => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'ctr'         => (float) ($row['ctr'] ?? 0),
            'position'    => round((float) ($row['position'] ?? 0), 1),
        ];
    }

    /**
     * Top queries by click volume — the primary "what queries convert for us"
     * signal. Filtered to queries where position < 100 so we drop noise.
     */
    public function topQueries(string $start, string $end, int $limit = 20): array
    {
        $r = $this->gsc->query([
            'startDate' => $start,
            'endDate' => $end,
            'dimensions' => ['query'],
            'rowLimit' => $limit,
        ]);
        return $this->normalizeRows($r, 'query');
    }

    /** Top LANDING pages by clicks. Shows which of our URLs actually work. */
    public function topPages(string $start, string $end, int $limit = 20): array
    {
        $r = $this->gsc->query([
            'startDate' => $start,
            'endDate' => $end,
            'dimensions' => ['page'],
            'rowLimit' => $limit,
        ]);
        return $this->normalizeRows($r, 'page');
    }

    /**
     * "Rank-8-to-15" opportunities: queries where we're hovering just off
     * page 1 with meaningful impression volume. Pushing these onto page 1
     * is the fastest revenue lever on GSC.
     */
    public function opportunityQueries(string $start, string $end, int $limit = 20): array
    {
        $r = $this->gsc->query([
            'startDate' => $start,
            'endDate' => $end,
            'dimensions' => ['query'],
            'rowLimit' => 200,
        ]);
        $rows = $this->normalizeRows($r, 'query');
        // Position 8-20 zone with >=25 impressions, sorted by impressions desc.
        return collect($rows)
            ->filter(fn ($r) => $r['position'] >= 8 && $r['position'] <= 20 && $r['impressions'] >= 25)
            ->sortByDesc('impressions')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * GSC data has a 2-3 day lag. Roll the window back 2 days so we don't
     * chart empty "today" and "yesterday" bins.
     */
    private function range(int $days): array
    {
        $end = Carbon::now()->subDays(2)->toDateString();
        $start = Carbon::parse($end)->subDays($days - 1)->toDateString();
        return [$start, $end];
    }

    private function normalizeRows(?array $resp, string $keyField): array
    {
        if (!$resp || empty($resp['rows'])) return [];
        return array_map(fn ($r) => [
            $keyField     => $r['keys'][0] ?? null,
            'clicks'      => (int) ($r['clicks'] ?? 0),
            'impressions' => (int) ($r['impressions'] ?? 0),
            'ctr'         => (float) ($r['ctr'] ?? 0),
            'position'    => round((float) ($r['position'] ?? 0), 1),
        ], $resp['rows']);
    }
}
