<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\ExternalReview;
use App\Models\VendorSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * One-time seeder: pulls Trustpilot reviews for a vendor and stores them
 * locally. Not scheduled — run per-vendor from CLI when you want fresh data.
 *
 * Extraction strategy: Trustpilot's business page emits __NEXT_DATA__, a
 * <script> tag containing the full page state as JSON. That includes every
 * review currently rendered on the page (typically the most recent 20), with
 * author, rating, title, body, published date. Parsing this JSON is far more
 * reliable than scraping the React-rendered HTML.
 *
 * Usage:
 *   php artisan reviews:import-trustpilot certapeptides
 *   php artisan reviews:import-trustpilot 130 --url=https://www.trustpilot.com/review/example.com
 *   php artisan reviews:import-trustpilot certapeptides --pages=3
 */
class ImportTrustpilotReviews extends Command
{
    protected $signature = 'reviews:import-trustpilot
                            {brand : Brand slug or numeric id}
                            {--url= : Trustpilot review URL (only needed if vendor_settings.trustpilot_url is not set)}
                            {--pages=1 : How many pages of reviews to fetch (20 per page)}';

    protected $description = 'Import Trustpilot reviews for a vendor (one-time seed).';

    public function handle(): int
    {
        $ref = $this->argument('brand');
        $brand = is_numeric($ref)
            ? Brand::find((int) $ref)
            : Brand::where('slug', $ref)->first();

        if (!$brand) {
            $this->error("Brand not found: {$ref}");
            return self::FAILURE;
        }

        $urlArg = $this->option('url');
        $vs = $brand->vendorSetting;
        $storedUrl = $vs->trustpilot_url ?? null;
        $trustpilotUrl = $urlArg ?: $storedUrl;

        if (!$trustpilotUrl) {
            $this->error("No Trustpilot URL. Pass --url= or set vendor_settings.trustpilot_url first.");
            return self::FAILURE;
        }

        // Persist the URL if this run supplied one and none was stored yet.
        if ($urlArg && $vs && !$storedUrl) {
            $vs->update(['trustpilot_url' => $urlArg]);
        }

        $trustpilotUrl = rtrim($trustpilotUrl, '/');
        $pagesToFetch = max(1, (int) $this->option('pages'));

        $totalNew = 0;
        $totalUpdated = 0;

        for ($page = 1; $page <= $pagesToFetch; $page++) {
            $pageUrl = $page === 1 ? $trustpilotUrl : "{$trustpilotUrl}?page={$page}";
            $this->line("Fetching {$pageUrl}");

            try {
                $response = Http::withHeaders([
                    // Trustpilot serves a lightweight bot page to obvious crawlers;
                    // a real browser UA gets the same content Google indexes.
                    'User-Agent' => 'Mozilla/5.0 (compatible; Peptidemap/1.0; +https://peptidemap.com)',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ])->timeout(20)->get($pageUrl);
            } catch (\Throwable $e) {
                $this->error("Fetch failed: {$e->getMessage()}");
                continue;
            }

            if (!$response->successful()) {
                $this->error("HTTP {$response->status()} for {$pageUrl}");
                continue;
            }

            $html = $response->body();
            $reviews = $this->extractReviews($html);

            if (empty($reviews)) {
                $this->warn("  No reviews parsed from page {$page}. Trustpilot may have changed layout, or the page is empty.");
                continue;
            }

            foreach ($reviews as $r) {
                $existing = ExternalReview::where('source', 'trustpilot')
                    ->where('source_review_id', $r['id'])
                    ->first();

                $attrs = [
                    'brand_id' => $brand->id,
                    'source' => 'trustpilot',
                    'source_review_id' => $r['id'],
                    'author' => mb_substr($r['author'] ?? '', 0, 191),
                    'author_location' => mb_substr($r['author_location'] ?? '', 0, 191),
                    'rating' => $r['rating'],
                    'title' => mb_substr($r['title'] ?? '', 0, 512),
                    'body' => $r['body'] ?? null,
                    'source_url' => mb_substr($trustpilotUrl, 0, 1024),
                    'published_at' => $r['published_at'] ?? null,
                    'imported_at' => now(),
                ];

                if ($existing) {
                    $existing->update($attrs);
                    $totalUpdated++;
                } else {
                    ExternalReview::create($attrs);
                    $totalNew++;
                }
            }

            $this->info("  Page {$page}: parsed " . count($reviews) . " reviews");

            // Polite pacing between page fetches.
            if ($page < $pagesToFetch) usleep(2_000_000);
        }

        // Bust any cached rollups for this brand.
        Cache::forget("external_reviews_summary:{$brand->id}");

        $this->newLine();
        $this->info("Done. {$totalNew} new, {$totalUpdated} updated for {$brand->name}.");

        return self::SUCCESS;
    }

    /**
     * Pull the JSON blob out of Trustpilot's <script id="__NEXT_DATA__"> tag
     * and normalize each review into our shape. Falls back to schema.org
     * JSON-LD blocks if Next data isn't present (older Trustpilot pages).
     */
    private function extractReviews(string $html): array
    {
        $reviews = [];

        // Primary: __NEXT_DATA__ — reliable, holds full review objects.
        if (preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $m)) {
            $data = json_decode($m[1], true);
            if (is_array($data)) {
                // Trustpilot puts reviews at pageProps.reviews (list page)
                // or props.pageProps.reviews depending on layout version.
                $list = data_get($data, 'props.pageProps.reviews')
                     ?? data_get($data, 'pageProps.reviews')
                     ?? [];
                foreach ($list as $r) {
                    $reviews[] = [
                        'id' => (string) (data_get($r, 'id') ?: data_get($r, 'reviewId') ?: md5(json_encode($r))),
                        'author' => data_get($r, 'consumer.displayName') ?: data_get($r, 'consumer.name'),
                        'author_location' => data_get($r, 'consumer.countryCode'),
                        'rating' => (int) (data_get($r, 'rating') ?: 0) ?: null,
                        'title' => data_get($r, 'title'),
                        'body' => data_get($r, 'text') ?: data_get($r, 'content'),
                        'published_at' => $this->parseDate(data_get($r, 'dates.publishedDate') ?: data_get($r, 'createdAt')),
                    ];
                }
            }
        }

        // Fallback: schema.org Review nodes embedded as JSON-LD.
        if (empty($reviews) && preg_match_all('/<script type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $mm)) {
            foreach ($mm[1] as $blob) {
                $data = json_decode($blob, true);
                if (!is_array($data)) continue;
                $nodes = isset($data['@graph']) ? $data['@graph'] : [$data];
                foreach ($nodes as $node) {
                    if (!is_array($node)) continue;
                    $items = data_get($node, 'review');
                    if (!is_array($items)) continue;
                    foreach ($items as $r) {
                        $reviews[] = [
                            'id' => (string) (data_get($r, '@id') ?: md5(json_encode($r))),
                            'author' => data_get($r, 'author.name') ?: data_get($r, 'author'),
                            'author_location' => null,
                            'rating' => (int) (data_get($r, 'reviewRating.ratingValue') ?: 0) ?: null,
                            'title' => data_get($r, 'name') ?: data_get($r, 'headline'),
                            'body' => data_get($r, 'reviewBody') ?: data_get($r, 'description'),
                            'published_at' => $this->parseDate(data_get($r, 'datePublished')),
                        ];
                    }
                }
            }
        }

        return array_values(array_filter($reviews, fn ($r) => !empty($r['body']) || !empty($r['title'])));
    }

    private function parseDate(?string $s): ?string
    {
        if (!$s) return null;
        try { return \Carbon\Carbon::parse($s)->toDateTimeString(); }
        catch (\Throwable) { return null; }
    }
}
