<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\VendorSetting;
use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DiscoverVendors extends Command
{
    protected $signature = 'vendors:discover
        {--import : Auto-import discovered vendors as pending}
        {--limit=50 : Max vendors to discover}';

    protected $description = 'Discover peptide vendors from Google search results and competitor sites';

    private array $searchQueries = [
        'buy research peptides online',
        'research peptide vendor',
        'peptide supplier USA',
        'buy BPC-157 online',
        'buy TB-500 online',
        'buy semaglutide peptide',
        'peptide vendor reviews',
        'best peptide companies 2026',
        'research peptide store',
        'peptide affiliate program',
    ];

    private array $knownCompetitorSites = [
        // Peptide review/comparison sites that list vendors
        'https://www.peptidesciences.com',
        'https://www.biotech-peptides.com',
        'https://www.extremepeptides.com',
        'https://www.paradigmpeptides.com',
        'https://www.purerawz.co',
        'https://www.swisschems.is',
        'https://www.chemyo.com',
        'https://www.usbiotechlab.com',
        'https://www.limitlesslifenootropics.com',
        'https://www.peptidewh.com',
        'https://www.canlab.com',
        'https://www.direct-peptides.com',
        'https://www.nootroo.com',
        'https://www.peptidesforresearch.com',
        'https://corepeptides.com',
        'https://www.americanresearchlabs.com',
        'https://primalpeptides.com',
        'https://manticore-labs.com',
    ];

    private array $discovered = [];
    private array $existingSlugs = [];

    public function handle(): int
    {
        $this->info('🔍 Discovering peptide vendors...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $this->existingSlugs = Brand::pluck('slug')->toArray();

        // Step 1: Check known competitor/vendor sites
        $this->info('Step 1: Checking known vendor sites...');
        $bar = $this->output->createProgressBar(count($this->knownCompetitorSites));
        foreach ($this->knownCompetitorSites as $url) {
            $this->analyzeVendorSite($url);
            $bar->advance();
            usleep(500_000); // 500ms delay
        }
        $bar->finish();
        $this->newLine(2);

        // Step 2: Google search for more vendors
        $this->info('Step 2: Searching Google for peptide vendors...');
        foreach ($this->searchQueries as $query) {
            if (count($this->discovered) >= $limit) break;
            $this->searchGoogle($query);
            sleep(2); // Be polite to Google
        }
        $this->newLine();

        // Step 3: Display results
        $this->info("✅ Discovered " . count($this->discovered) . " potential vendors:");
        $this->newLine();

        $tableData = [];
        foreach ($this->discovered as $v) {
            $tableData[] = [
                $v['name'],
                Str::limit($v['url'], 40),
                $v['platform'] ?: '?',
                $v['has_affiliate'] ? '✓' : '—',
                $v['email'] ?: '—',
                $v['already_exists'] ? 'EXISTS' : 'NEW',
            ];
        }

        $this->table(
            ['Name', 'URL', 'Platform', 'Affiliate', 'Email', 'Status'],
            $tableData
        );

        // Step 4: Import if requested
        if ($this->option('import')) {
            $newVendors = array_filter($this->discovered, fn($v) => !$v['already_exists']);
            if (empty($newVendors)) {
                $this->info('No new vendors to import.');
                return 0;
            }

            if (!$this->confirm("Import " . count($newVendors) . " new vendors as pending?")) {
                return 0;
            }

            $imported = 0;
            foreach ($newVendors as $v) {
                try {
                    $this->importVendor($v);
                    $imported++;
                    $this->line("  ✓ Imported: {$v['name']}");
                } catch (\Throwable $e) {
                    $this->error("  ✗ Failed: {$v['name']} — {$e->getMessage()}");
                }
            }
            $this->newLine();
            $this->info("Imported {$imported} vendors as pending. Review them in Admin → Vendors.");
        }

        // Step 5: Export CSV
        $csvPath = storage_path('app/discovered-vendors-' . date('Y-m-d-His') . '.csv');
        $fp = fopen($csvPath, 'w');
        fputcsv($fp, ['Name', 'URL', 'Platform', 'Has Affiliate', 'Email', 'Contact Page', 'Description', 'Status']);
        foreach ($this->discovered as $v) {
            fputcsv($fp, [
                $v['name'], $v['url'], $v['platform'], $v['has_affiliate'] ? 'Yes' : 'No',
                $v['email'], $v['contact_url'], Str::limit($v['description'], 200),
                $v['already_exists'] ? 'EXISTS' : 'NEW',
            ]);
        }
        fclose($fp);
        $this->info("📄 CSV exported: {$csvPath}");

        return 0;
    }

    private function analyzeVendorSite(string $url): void
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'PeptideMapBot/1.0'])
                ->get($url);

            if (!$response->successful()) return;

            $html = $response->body();
            $domain = parse_url($url, PHP_URL_HOST);
            $name = $this->extractSiteName($html, $domain);
            $slug = Str::slug($name);

            if (isset($this->discovered[$slug])) return;

            $this->discovered[$slug] = [
                'name' => $name,
                'url' => $url,
                'slug' => $slug,
                'platform' => $this->detectPlatform($html, $url),
                'has_affiliate' => $this->detectAffiliate($html, $url),
                'email' => $this->extractEmail($html),
                'contact_url' => $this->findContactPage($html, $url),
                'description' => $this->extractDescription($html),
                'already_exists' => in_array($slug, $this->existingSlugs),
            ];
        } catch (\Throwable $e) {
            // Skip failed sites silently
        }
    }

    private function searchGoogle(string $query): void
    {
        try {
            // Use Google's custom search or scrape (simplified — in production use Google Custom Search API)
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get('https://www.google.com/search', [
                    'q' => $query,
                    'num' => 20,
                ]);

            if (!$response->successful()) return;

            $html = $response->body();

            // Extract URLs from search results
            preg_match_all('/href="\/url\?q=([^&"]+)/', $html, $matches);
            if (empty($matches[1])) {
                // Try alternative Google result format
                preg_match_all('/data-href="(https?:\/\/[^"]+)"/', $html, $matches);
            }
            if (empty($matches[1])) {
                preg_match_all('/<a href="(https?:\/\/(?:www\.)?[a-z0-9][a-z0-9.-]+\.[a-z]{2,}[^"]*)"/', $html, $matches);
            }

            $urls = array_unique($matches[1] ?? []);
            foreach ($urls as $resultUrl) {
                $resultUrl = urldecode($resultUrl);
                $domain = parse_url($resultUrl, PHP_URL_HOST);

                // Skip non-vendor sites
                if ($this->isSkippableDomain($domain)) continue;

                // Normalize to homepage
                $homepage = parse_url($resultUrl, PHP_URL_SCHEME) . '://' . $domain;
                $slug = Str::slug(str_replace(['www.', '.com', '.co', '.is', '.io'], '', $domain));

                if (isset($this->discovered[$slug])) continue;
                if (count($this->discovered) >= (int) $this->option('limit')) break;

                $this->analyzeVendorSite($homepage);
            }
        } catch (\Throwable $e) {
            $this->warn("  Google search failed for: {$query}");
        }
    }

    private function detectPlatform(string $html, string $url): ?string
    {
        $lower = strtolower($html);

        // WooCommerce
        if (str_contains($lower, 'woocommerce') || str_contains($lower, 'wc-block') || str_contains($lower, 'wp-content')) {
            return 'woocommerce';
        }

        // Shopify
        if (str_contains($lower, 'shopify') || str_contains($lower, 'cdn.shopify.com') || str_contains($lower, 'myshopify.com')) {
            return 'shopify';
        }

        // Medusa
        if (str_contains($lower, 'medusajs') || str_contains($lower, 'medusa')) {
            return 'medusa';
        }

        // BigCommerce
        if (str_contains($lower, 'bigcommerce') || str_contains($lower, 'cdn11.bigcommerce')) {
            return 'bigcommerce';
        }

        // Squarespace
        if (str_contains($lower, 'squarespace')) {
            return 'squarespace';
        }

        // Custom/Unknown
        // Check for wp-json endpoint
        try {
            $wpCheck = Http::timeout(5)->get(rtrim($url, '/') . '/wp-json/');
            if ($wpCheck->successful() && str_contains($wpCheck->body(), 'wp-json')) {
                return 'wordpress';
            }
        } catch (\Throwable $e) {}

        return null;
    }

    private function detectAffiliate(string $html, string $url): bool
    {
        $lower = strtolower($html);

        // Check page content for affiliate indicators
        if (str_contains($lower, 'affiliate program') || str_contains($lower, 'affiliate') && str_contains($lower, 'commission')) {
            return true;
        }

        // Check common affiliate page URLs
        $affiliatePages = ['/affiliate', '/affiliates', '/affiliate-program', '/partners', '/referral'];
        foreach ($affiliatePages as $path) {
            try {
                $check = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'PeptideMapBot/1.0'])
                    ->get(rtrim($url, '/') . $path);
                if ($check->successful() && $check->status() !== 404) {
                    $body = strtolower($check->body());
                    if (str_contains($body, 'affiliate') || str_contains($body, 'commission') || str_contains($body, 'referral')) {
                        return true;
                    }
                }
            } catch (\Throwable $e) {}
        }

        return false;
    }

    private function extractEmail(string $html): ?string
    {
        // Look for mailto: links
        if (preg_match('/mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $html, $m)) {
            $email = strtolower($m[1]);
            // Skip common non-contact emails
            if (!str_contains($email, 'noreply') && !str_contains($email, 'no-reply') && !str_contains($email, 'wordpress')) {
                return $email;
            }
        }

        // Look for email patterns in text
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', strip_tags($html), $m)) {
            $email = strtolower($m[0]);
            if (!str_contains($email, 'noreply') && !str_contains($email, 'example.com') && !str_contains($email, 'wordpress')) {
                return $email;
            }
        }

        return null;
    }

    private function findContactPage(string $html, string $baseUrl): ?string
    {
        $contactPaths = ['/contact', '/contact-us', '/about/contact', '/support'];
        foreach ($contactPaths as $path) {
            if (stripos($html, $path) !== false) {
                return rtrim($baseUrl, '/') . $path;
            }
        }
        return null;
    }

    private function extractSiteName(string $html, string $domain): string
    {
        // Try og:site_name
        if (preg_match('/<meta[^>]*property=["\']og:site_name["\'][^>]*content=["\']([^"\']+)["\']/', $html, $m)) {
            return html_entity_decode(trim($m[1]));
        }
        // Try <title>
        if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
            $title = html_entity_decode(trim($m[1]));
            // Clean up common title suffixes
            $title = preg_replace('/\s*[|–—-]\s*.+$/', '', $title);
            if (strlen($title) > 3 && strlen($title) < 60) {
                return $title;
            }
        }
        // Fallback to domain name
        return ucwords(str_replace(['.com', '.co', '.is', '.io', 'www.', '-', '_'], ['',' ',' ',' ',' ',' ',' '], $domain));
    }

    private function extractDescription(string $html): ?string
    {
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\']/', $html, $m)) {
            return html_entity_decode(trim($m[1]));
        }
        if (preg_match('/<meta[^>]*property=["\']og:description["\'][^>]*content=["\']([^"\']+)["\']/', $html, $m)) {
            return html_entity_decode(trim($m[1]));
        }
        return null;
    }

    private function isSkippableDomain(?string $domain): bool
    {
        if (!$domain) return true;
        $skip = ['google.', 'youtube.', 'facebook.', 'twitter.', 'instagram.', 'reddit.', 'wikipedia.',
            'amazon.', 'ebay.', 'nih.gov', 'pubmed.', 'fda.gov', 'wada-ama.org', 'linkedin.',
            'tiktok.', 'pinterest.', 'quora.', 'yelp.', 'bbb.org', 'trustpilot.',
            'peptidemap.com', 'devmap-'];
        foreach ($skip as $s) {
            if (str_contains($domain, $s)) return true;
        }
        return false;
    }

    private function importVendor(array $vendor): void
    {
        DB::transaction(function () use ($vendor) {
            $location = Location::firstOrCreate(['name' => 'United States'], ['slug' => 'united-states']);

            $brand = Brand::create([
                'name' => $vendor['name'],
                'slug' => $vendor['slug'],
                'is_active' => false,
            ]);

            VendorSetting::create([
                'brand_id' => $brand->id,
                'location_id' => $location->id,
                'description' => $vendor['description'],
                'contact_email' => $vendor['email'],
                'shop_url' => $vendor['url'],
                'website' => $vendor['url'],
                'status' => 0,
                'approval_status' => 'pending',
                'api_platform' => $vendor['platform'] === 'woocommerce' ? 'woocommerce' : ($vendor['platform'] ? 'page_scrape' : null),
            ]);
        });
    }
}
