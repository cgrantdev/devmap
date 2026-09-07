<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal GoAffPro affiliate-side client. Each vendor's affiliate portal
 * gives you an X-Access-Token in Account → Integrations → API. Store that
 * token in vendor_settings.affiliate_credentials, then call fetchStats()
 * to get current clicks / sales / revenue / commission.
 *
 * We only READ — no writes. Docs (approx): https://help.goaffpro.com/en/articles/api
 */
class GoAffProClient
{
    private const BASE = 'https://api.goaffpro.com/v1/data';

    /**
     * Pull the affiliate's current dashboard snapshot from GoAffPro.
     * Returns a normalized array of the numbers we care about, or null
     * if the request fails (network / bad token / rate-limited).
     */
    public function fetchStats(string $accessToken): ?array
    {
        try {
            $resp = Http::withHeaders(['X-Access-Token' => $accessToken])
                ->timeout(15)
                ->acceptJson()
                ->get(self::BASE . '/affiliate');

            if (!$resp->successful()) {
                Log::info('GoAffPro fetchStats non-200', ['status' => $resp->status()]);
                return null;
            }

            $body = $resp->json();
            $aff = $body['affiliate'] ?? $body ?? [];

            // GoAffPro's response shape has moved around historically.
            // Accept a couple of common keys so this doesn't silently
            // start returning zeros if they rename a field.
            return [
                'clicks_total' => (int) ($aff['visitors'] ?? $aff['clicks'] ?? $aff['total_visits'] ?? 0),
                'orders_total' => (int) ($aff['orders_count'] ?? $aff['orders'] ?? 0),
                'revenue_total' => (float) ($aff['total_sales'] ?? $aff['sales'] ?? 0),
                'commission_earned' => (float) ($aff['total_commissions'] ?? $aff['commissions'] ?? 0),
                'commission_pending' => (float) ($aff['pending_commissions'] ?? $aff['pending'] ?? 0),
                'commission_paid' => (float) ($aff['paid_commissions'] ?? $aff['paid'] ?? 0),
                'balance' => (float) ($aff['balance'] ?? 0),
                'raw' => $aff, // keep the raw payload in case fields change
                'fetched_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::warning('GoAffPro fetchStats exception', ['err' => $e->getMessage()]);
            return null;
        }
    }
}
