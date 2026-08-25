<?php

namespace App\Services;

use App\Models\VendorSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pulls aggregate ratings from external review platforms (Reviews.io,
 * Trustpilot when accessible, PepReviewPro when scrapeable) and caches
 * them onto vendor_settings so the storefront trust panel renders from
 * a single local read instead of hitting third parties per pageview.
 *
 * Refreshed by the reviews:refresh scheduler; can be run manually.
 */
class ExternalReviewFetcher
{
    /**
     * Refresh every source configured for this vendor. Returns the
     * per-platform snapshot that was stored on the model.
     */
    public function refresh(VendorSetting $vs): array
    {
        $sources = [];

        if ($vs->reviews_io_url) {
            $r = $this->fetchSchemaOrgRating($vs->reviews_io_url);
            if ($r) {
                $sources['reviews_io'] = array_merge($r, ['url' => $vs->reviews_io_url, 'platform' => 'Reviews.io']);
            } else {
                // Even if the scrape fails, keep the link so the badge renders.
                $sources['reviews_io'] = ['url' => $vs->reviews_io_url, 'platform' => 'Reviews.io'];
            }
        }

        if ($vs->trustpilot_url) {
            $r = $this->fetchSchemaOrgRating($vs->trustpilot_url);
            if ($r) {
                $sources['trustpilot'] = array_merge($r, ['url' => $vs->trustpilot_url, 'platform' => 'Trustpilot']);
            } else {
                $sources['trustpilot'] = ['url' => $vs->trustpilot_url, 'platform' => 'Trustpilot'];
            }
        }

        if ($vs->google_reviews_url) {
            // Google's storepages URL is a search redirect — no reliable public
            // rating scrape. Link only.
            $sources['google'] = ['url' => $vs->google_reviews_url, 'platform' => 'Google Reviews'];
        }

        if ($vs->pepreviewpro_url) {
            $r = $this->fetchSchemaOrgRating($vs->pepreviewpro_url);
            if ($r) {
                $sources['pepreviewpro'] = array_merge($r, ['url' => $vs->pepreviewpro_url, 'platform' => 'PepReviewPro']);
            } else {
                $sources['pepreviewpro'] = ['url' => $vs->pepreviewpro_url, 'platform' => 'PepReviewPro'];
            }
        }

        // Aggregate: weighted mean across every source that gave us a real
        // rating + count. Sources without numeric data don't contribute.
        $totalCount = 0;
        $weightedSum = 0.0;
        foreach ($sources as $s) {
            if (!empty($s['rating']) && !empty($s['count'])) {
                $totalCount += $s['count'];
                $weightedSum += $s['rating'] * $s['count'];
            }
        }
        $aggRating = $totalCount > 0 ? round($weightedSum / $totalCount, 2) : null;

        $vs->forceFill([
            'external_ratings_json' => $sources,
            'external_rating_avg' => $aggRating,
            'external_rating_count' => $totalCount,
        ])->save();

        return $sources;
    }

    /**
     * Extract rating + count from an AggregateRating JSON-LD block or the
     * plain schema-in-attributes pattern Reviews.io / Trustpilot use.
     */
    private function fetchSchemaOrgRating(string $url): ?array
    {
        try {
            $resp = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; Peptidemap/1.0; +https://peptidemap.com)',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(15)->get($url);
        } catch (\Throwable $e) {
            Log::warning('ExternalReviewFetcher fetch failed', ['url' => $url, 'err' => $e->getMessage()]);
            return null;
        }

        if (!$resp->successful()) {
            Log::info('ExternalReviewFetcher non-200', ['url' => $url, 'status' => $resp->status()]);
            return null;
        }

        $html = $resp->body();

        // Look for JSON-LD blocks first (most reliable path).
        if (preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $m)) {
            foreach ($m[1] as $blob) {
                $data = json_decode(trim($blob), true);
                if (!is_array($data)) continue;
                $rating = $this->findAggregateRating($data);
                if ($rating) return $rating;
            }
        }

        // Fallback: some sites emit ratingValue/reviewCount as microdata
        // attributes in the DOM. Reviews.io does both — belt & suspenders.
        if (preg_match('/"ratingValue"\s*:\s*"?([0-9.]+)"?/', $html, $r)
            && preg_match('/"reviewCount"\s*:\s*"?([0-9]+)"?/', $html, $c)) {
            return ['rating' => (float) $r[1], 'count' => (int) $c[1]];
        }

        return null;
    }

    /**
     * Recursively walk a decoded JSON-LD payload for the first AggregateRating.
     */
    private function findAggregateRating(array $node): ?array
    {
        // Direct hit
        if (($node['@type'] ?? null) === 'AggregateRating'
            && isset($node['ratingValue'], $node['reviewCount'])) {
            return [
                'rating' => (float) $node['ratingValue'],
                'count' => (int) $node['reviewCount'],
            ];
        }
        // Nested under Organization / LocalBusiness / Product / Store
        if (isset($node['aggregateRating']) && is_array($node['aggregateRating'])
            && isset($node['aggregateRating']['ratingValue'], $node['aggregateRating']['reviewCount'])) {
            return [
                'rating' => (float) $node['aggregateRating']['ratingValue'],
                'count' => (int) $node['aggregateRating']['reviewCount'],
            ];
        }
        // Walk @graph / arrays
        foreach ($node as $v) {
            if (is_array($v)) {
                $found = $this->findAggregateRating($v);
                if ($found) return $found;
            }
        }
        return null;
    }
}
