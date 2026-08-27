<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\ExternalReview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Pulls reviews from a vendor's Pep Review Pro store page. Their SPA calls
 * a public Supabase edge function that returns clean JSON (no auth), which
 * is what we hit here — same shape as reviews:import-reviews-io.
 *
 * Usage:
 *   php artisan reviews:import-pepreviewpro hydro-research
 *   php artisan reviews:import-pepreviewpro hydro-research --url=https://pepreviewpro.com/store/e19605ed-...
 */
class ImportPepReviewProReviews extends Command
{
    protected $signature = 'reviews:import-pepreviewpro
                            {brand : Brand slug or numeric id}
                            {--url= : Pep Review Pro store URL (only needed if vendor_settings.pepreviewpro_url is not set)}
                            {--all : Fetch ALL reviews (defaults to first 500)}
                            {--limit=500 : Hard cap when --all is not set}';

    protected $description = 'Import individual Pep Review Pro reviews for a vendor via their public endpoint.';

    private const API_PER_PAGE = 100;
    private const API_BASE = 'https://qzlfuygefaocfmjdoueg.supabase.co/functions/v1/get-reviews';

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
        $storedUrl = $vs->pepreviewpro_url ?? null;
        $sourceUrl = $urlArg ?: $storedUrl;

        if (!$sourceUrl) {
            $this->error('No Pep Review Pro URL. Pass --url= or set vendor_settings.pepreviewpro_url first.');
            return self::FAILURE;
        }

        if ($urlArg && $vs && !$storedUrl) {
            $vs->update(['pepreviewpro_url' => $urlArg]);
        }

        $siteId = $this->extractSiteId($sourceUrl);
        if (!$siteId) {
            $this->error("Could not parse Pep Review Pro site_id from URL: {$sourceUrl}");
            return self::FAILURE;
        }
        $this->line("site_id: {$siteId}");

        $limit = $this->option('all') ? PHP_INT_MAX : max(1, (int) $this->option('limit'));
        $totalNew = 0; $totalUpdated = 0; $totalSeen = 0;
        $page = 1;

        while ($totalSeen < $limit) {
            $pageUrl = self::API_BASE . '?site_id=' . urlencode($siteId)
                . '&page=' . $page . '&per_page=' . self::API_PER_PAGE . '&sort=newest';
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
                $existing = ExternalReview::where('source', 'pepreviewpro')
                    ->where('source_review_id', $normalized['id'])
                    ->first();

                $attrs = [
                    'brand_id' => $brand->id,
                    'source' => 'pepreviewpro',
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

            $total = (int) ($data['total'] ?? 0);
            if ($total > 0 && $page * self::API_PER_PAGE >= $total) break;
            if (count($reviews) < self::API_PER_PAGE) break;
            $page++;
            usleep(500_000);
        }

        Cache::forget("external_reviews_summary:{$brand->id}");

        $this->newLine();
        $this->info("Done. {$totalNew} new, {$totalUpdated} updated for {$brand->name} ({$totalSeen} seen).");
        return self::SUCCESS;
    }

    /**
     * Extract the UUID site_id from a Pep Review Pro URL.
     * https://pepreviewpro.com/store/e19605ed-5c1d-4417-b307-53a2f582f7ba
     */
    private function extractSiteId(string $url): ?string
    {
        if (preg_match('~/store/([0-9a-f-]{36})~i', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    private function normalize(array $r): array
    {
        return [
            'id' => (string) ($r['id'] ?? sha1(json_encode($r))),
            'author' => trim((string) ($r['reviewer_name'] ?? 'Anonymous')),
            'title' => $r['title'] ?? null,
            'body' => $r['body'] ?? null,
            'rating' => isset($r['rating']) ? (int) round((float) $r['rating']) : null,
            'published_at' => !empty($r['created_at'])
                ? \Carbon\Carbon::parse($r['created_at'])->toDateTimeString()
                : null,
        ];
    }
}
