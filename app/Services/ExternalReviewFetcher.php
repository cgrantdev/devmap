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
        // Existing stored data. Preserved when a scrape returns nothing so
        // manually-entered ratings + counts survive refreshes (Trustpilot /
        // Google / PepReviewPro can't be scraped from our datacenter IP —
        // Julia enters those numbers via `reviews:set-manual` or admin UI).
        $existing = is_array($vs->external_ratings_json) ? $vs->external_ratings_json : [];

        $platforms = [
            ['key' => 'reviews_io',   'url' => $vs->reviews_io_url,   'label' => 'Reviews.io',    'scrape' => true],
            ['key' => 'trustpilot',   'url' => $vs->trustpilot_url,   'label' => 'Trustpilot',    'scrape' => true],
            ['key' => 'google',       'url' => $vs->google_reviews_url, 'label' => 'Google Reviews', 'scrape' => false],
            ['key' => 'pepreviewpro', 'url' => $vs->pepreviewpro_url, 'label' => 'PepReviewPro',  'scrape' => true],
        ];

        foreach ($platforms as $p) {
            if (!$p['url']) continue;
            $entry = ['url' => $p['url'], 'platform' => $p['label']];

            // Try to scrape schema.org rating from the URL when supported.
            $scraped = $p['scrape'] ? $this->fetchSchemaOrgRating($p['url']) : null;
            if ($scraped) {
                $entry['rating'] = $scraped['rating'];
                $entry['count'] = $scraped['count'];
                $entry['manual'] = false;
            } else {
                // Fall back to any previously-stored numbers so manual
                // entries survive refreshes.
                $prev = $existing[$p['key']] ?? [];
                if (!empty($prev['rating'])) $entry['rating'] = $prev['rating'];
                if (!empty($prev['count']))  $entry['count'] = $prev['count'];
                if (!empty($prev['manual'])) $entry['manual'] = true;
            }

            $sources[$p['key']] = $entry;
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
