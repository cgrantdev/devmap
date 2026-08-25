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
                            {--all : Fetch ALL reviews (defaults to first 100)}
                            {--limit=100 : Hard cap on reviews to fetch when --all is not set}';

    protected $description = 'Import individual Reviews.io reviews for a vendor via their public merchant API.';

    // Reviews.io's public widget API — same endpoint their own widget calls.
    // Paginated 100 per page, returns full review objects (id, rating,
    // title, comments, dates, reviewer name).
    private const API_PER_PAGE = 100;
    private const API_BASE = 'https://api.reviews.io/merchant/reviews';

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

        $vs = $brand->vendorSetting;
        $urlArg = $this->option('url');
        $storedUrl = $vs->reviews_io_url ?? null;
        $sourceUrl = $urlArg ?: $storedUrl;

        if (!$sourceUrl) {
            $this->error('No Reviews.io URL. Pass --url= or set vendor_settings.reviews_io_url first.');
            return self::FAILURE;
        }

        if ($urlArg && $vs && !$storedUrl) {
            $vs->update(['reviews_io_url' => $urlArg]);
        }

        // Extract the store slug from the vendor's Reviews.io URL. The
        // public URL pattern is /company-reviews/store/{store-slug} — the
        // slug is what the API keys off ("certified-pep.com" for Certified Pep).
        $store = $this->extractStore($sourceUrl);
        if (!$store) {
            $this->error("Could not parse Reviews.io store slug from URL: {$sourceUrl}");
            return self::FAILURE;
        }
        $this->line("Store slug: {$store}");

        $limit = $this->option('all') ? PHP_INT_MAX : max(1, (int) $this->option('limit'));
        $totalNew = 0; $totalUpdated = 0; $totalSeen = 0;
        $page = 1;

        while ($totalSeen < $limit) {
            $pageUrl = self::API_BASE . '?store=' . urlencode($store)
                . '&per_page=' . self::API_PER_PAGE . '&page=' . $page;
            $this->line("Fetching page {$page}");

            try {
                $resp = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; Peptidemap/1.0; +https://peptidemap.com)',
                    'Accept' => 'application/json',
                ])->timeout(20)->get($pageUrl);
            } catch (\Throwable $e) {
                $this->error("Fetch failed: {$e->getMessage()}");
                break;
            }

            if (!$resp->successful()) {
                $this->error("HTTP {$resp->status()} for page {$page}");
                break;
            }

            $data = $resp->json();
            $reviews = $data['reviews'] ?? [];
            if (empty($reviews)) {
                $this->info('  (no more reviews)');
                break;
            }

            foreach ($reviews as $r) {
                if ($totalSeen >= $limit) break 2;
                $totalSeen++;

                $normalized = $this->normalize($r);
                $existing = ExternalReview::where('source', 'reviews_io')
                    ->where('source_review_id', $normalized['id'])
                    ->first();

                $attrs = [
                    'brand_id' => $brand->id,
                    'source' => 'reviews_io',
                    'source_review_id' => $normalized['id'],
                    'author' => mb_substr($normalized['author'], 0, 191),
                    'author_location' => null,
                    'rating' => $normalized['rating'],
                    'title' => mb_substr((string) $normalized['title'], 0, 512),
                    'body' => $normalized['body'],
                    'source_url' => mb_substr($sourceUrl, 0, 1024),
                    'published_at' => $normalized['published_at'],
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

            $this->info("  Page {$page}: " . count($reviews) . ' reviews (' . $totalSeen . ' total this run)');

            $totalPages = (int) ($data['total_pages'] ?? 1);
            if ($page >= $totalPages) break;
            $page++;
            usleep(750_000); // polite pacing between API calls
        }

        Cache::forget("external_reviews_summary:{$brand->id}");

        $this->newLine();
        $this->info("Done. {$totalNew} new, {$totalUpdated} updated for {$brand->name} ({$totalSeen} seen).");
        return self::SUCCESS;
    }

    /**
     * Extract the Reviews.io store slug from a public review URL.
     * https://www.reviews.io/company-reviews/store/certified-pep.com → certified-pep.com
     */
    private function extractStore(string $url): ?string
    {
        if (preg_match('~/company-reviews/store/([^/?#]+)~i', $url, $m)) {
            return $m[1];
        }
        // Fall back to the last path segment if the URL doesn't match.
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $segments = array_values(array_filter(explode('/', $path)));
        return end($segments) ?: null;
    }

    private function normalize(array $r): array
    {
        $reviewer = $r['reviewer'] ?? [];
        $author = trim((($reviewer['first_name'] ?? '') . ' ' . ($reviewer['last_name'] ?? '')));
        if ($author === '') {
            $author = $reviewer['display_name'] ?? 'Anonymous';
        }

        return [
            'id' => (string) ($r['store_review_id'] ?? sha1(json_encode($r))),
            'author' => $author,
            'title' => $r['title'] ?? null,
            'body' => $r['comments'] ?? null,
            'rating' => isset($r['rating']) ? (int) round((float) $r['rating']) : null,
            'published_at' => !empty($r['date_created'])
                ? \Carbon\Carbon::parse($r['date_created'])->toDateTimeString()
                : null,
        ];
    }
}
