<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\ExternalReview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * One-time seeder: pulls individual Reviews.io reviews for a vendor and
 * stores them locally in external_reviews (source='reviews_io'). Sister
 * command to reviews:import-trustpilot — same shape, different source.
 *
 * Reviews.io emits schema.org Review nodes on their public pages (20 per
 * page), each with author.name, datePublished, reviewBody, and
 * reviewRating.ratingValue. This scrapes those.
 *
 * Usage:
 *   php artisan reviews:import-reviews-io certified-pep
 *   php artisan reviews:import-reviews-io certified-pep --pages=5
 *   php artisan reviews:import-reviews-io certified-pep --url=https://www.reviews.io/company-reviews/store/other-slug
 */
class ImportReviewsIoReviews extends Command
{
    protected $signature = 'reviews:import-reviews-io
                            {brand : Brand slug or numeric id}
                            {--url= : Reviews.io URL (only needed if vendor_settings.reviews_io_url is not set)}
                            {--pages=1 : How many pages of reviews to fetch (20 per page)}';

    protected $description = 'Import individual Reviews.io reviews for a vendor.';

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
        $storedUrl = $vs->reviews_io_url ?? null;
        $baseUrl = $urlArg ?: $storedUrl;

        if (!$baseUrl) {
            $this->error('No Reviews.io URL. Pass --url= or set vendor_settings.reviews_io_url first.');
            return self::FAILURE;
        }

        if ($urlArg && $vs && !$storedUrl) {
            $vs->update(['reviews_io_url' => $urlArg]);
        }

        // Strip trailing query so pagination assembly stays clean.
        $baseUrl = preg_replace('/\?.*$/', '', rtrim($baseUrl, '/'));
        $pagesToFetch = max(1, (int) $this->option('pages'));

        $totalNew = 0; $totalUpdated = 0;

        for ($page = 1; $page <= $pagesToFetch; $page++) {
            $pageUrl = $page === 1 ? $baseUrl : "{$baseUrl}?page={$page}";
            $this->line("Fetching {$pageUrl}");

            try {
                $resp = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; Peptidemap/1.0; +https://peptidemap.com)',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])->timeout(20)->get($pageUrl);
            } catch (\Throwable $e) {
                $this->error("Fetch failed: {$e->getMessage()}");
                continue;
            }

            if (!$resp->successful()) {
                $this->error("HTTP {$resp->status()} for {$pageUrl}");
                continue;
            }

            $reviews = $this->extractReviews($resp->body(), $baseUrl);
            if (empty($reviews)) {
                $this->warn("  No reviews parsed from page {$page}.");
                continue;
            }

            foreach ($reviews as $r) {
                $existing = ExternalReview::where('source', 'reviews_io')
                    ->where('source_review_id', $r['id'])
                    ->first();

                $attrs = [
                    'brand_id' => $brand->id,
                    'source' => 'reviews_io',
                    'source_review_id' => $r['id'],
                    'author' => mb_substr($r['author'] ?? '', 0, 191),
                    'author_location' => null,
                    'rating' => $r['rating'],
                    'title' => mb_substr($r['title'] ?? '', 0, 512),
                    'body' => $r['body'] ?? null,
                    'source_url' => mb_substr($baseUrl, 0, 1024),
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
            if ($page < $pagesToFetch) usleep(2_000_000);
        }

        Cache::forget("external_reviews_summary:{$brand->id}");

        $this->newLine();
        $this->info("Done. {$totalNew} new, {$totalUpdated} updated for {$brand->name}.");
        return self::SUCCESS;
    }

    /**
     * Extract schema.org Review nodes from the HTML. Each Reviews.io page
     * has ~20 reviews as JSON-LD Review nodes embedded in <script> tags.
     */
    private function extractReviews(string $html, string $sourceUrl): array
    {
        $reviews = [];
        if (!preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $mm)) {
            return $reviews;
        }

        foreach ($mm[1] as $blob) {
            $data = json_decode(trim($blob), true);
            if (!is_array($data)) continue;

            $nodes = isset($data['@graph']) ? $data['@graph'] : [$data];
            foreach ($nodes as $node) {
                if (!is_array($node)) continue;

                // Node's `review` field is an array of Review objects when
                // this is the LocalBusiness / Organization node.
                $items = data_get($node, 'review');
                if (is_array($items)) {
                    foreach ($items as $r) {
                        if (!is_array($r) || ($r['@type'] ?? null) !== 'Review') continue;
                        $reviews[] = $this->normalize($r, $sourceUrl);
                    }
                }

                // Or the node itself IS a Review.
                if (($node['@type'] ?? null) === 'Review') {
                    $reviews[] = $this->normalize($node, $sourceUrl);
                }
            }
        }

        return array_values(array_filter($reviews, fn ($r) => !empty($r['body']) || !empty($r['title'])));
    }

    private function normalize(array $r, string $sourceUrl): array
    {
        $body = data_get($r, 'reviewBody') ?: data_get($r, 'description');
        $author = data_get($r, 'author.name') ?: (is_string(data_get($r, 'author')) ? data_get($r, 'author') : null);
        $date = data_get($r, 'datePublished');
        $rating = data_get($r, 'reviewRating.ratingValue');

        // Reviews.io doesn't always emit a stable per-review @id; hash the
        // combination of author + date + body-prefix for a deterministic
        // dedupe key so re-imports don't create dupes.
        $id = data_get($r, '@id') ?: data_get($r, 'reviewId');
        if (!$id) {
            $id = 'rio_' . substr(sha1(($author ?? '') . '|' . ($date ?? '') . '|' . mb_substr((string) $body, 0, 80)), 0, 32);
        }

        return [
            'id' => (string) $id,
            'author' => trim((string) ($author ?: 'Anonymous')),
            'title' => data_get($r, 'name') ?: data_get($r, 'headline'),
            'body' => is_string($body) ? trim($body) : null,
            'rating' => $rating ? (int) round((float) $rating) : null,
            'published_at' => $date ? \Carbon\Carbon::parse($date)->toDateTimeString() : null,
        ];
    }
}
