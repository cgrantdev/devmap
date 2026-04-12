<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\VendorSetting;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class VendorDiscoveryController extends Controller
{
    private array $knownSites = [
        'https://www.peptidesciences.com',
        'https://www.biotech-peptides.com',
        'https://www.extremepeptides.com',
        'https://www.paradigmpeptides.com',
        'https://www.purerawz.co',
        'https://www.swisschems.is',
        'https://www.chemyo.com',
        'https://www.limitlesslifenootropics.com',
        'https://corepeptides.com',
        'https://www.americanresearchlabs.com',
        'https://primalpeptides.com',
        'https://www.aminoclub.com',
        'https://www.peptidesforresearch.com',
        'https://manticore-labs.com',
        'https://www.direct-peptides.com',
        'https://www.researchpeptides.net',
        'https://peptidepros.net',
        'https://www.narrowlabs.com',
    ];

    public function index()
    {
        $results = Cache::get('vendor_discovery_results', []);
        $existingSlugs = Brand::pluck('slug')->toArray();

        // Mark existing vendors
        foreach ($results as &$r) {
            $r['already_exists'] = in_array($r['slug'] ?? Str::slug($r['name'] ?? ''), $existingSlugs);
        }

        return Inertia::render('Admin/VendorDiscovery', [
            'results' => array_values($results),
            'scanning' => Cache::get('vendor_discovery_scanning', false),
            'lastScanAt' => Cache::get('vendor_discovery_last_scan'),
        ]);
    }

    public function scan(Request $request)
    {
        if (Cache::get('vendor_discovery_scanning')) {
            return back()->with('error', 'A scan is already in progress.');
        }

        Cache::put('vendor_discovery_scanning', true, 600); // 10 min timeout
        $discovered = [];
        $existingSlugs = Brand::pluck('slug')->toArray();

        // Scan known sites
        foreach ($this->knownSites as $url) {
            $result = $this->analyzeSite($url);
            if ($result) {
                $result['already_exists'] = in_array($result['slug'], $existingSlugs);
                $discovered[$result['slug']] = $result;
            }
        }

        // Google search for more
        $queries = [
            'buy research peptides online',
            'research peptide vendor USA',
            'peptide supplier reviews 2026',
            'best peptide companies',
            'peptide affiliate program',
        ];

        foreach ($queries as $query) {
            $urls = $this->searchGoogle($query);
            foreach ($urls as $url) {
                if (count($discovered) >= 50) break 2;
                $domain = parse_url($url, PHP_URL_HOST);
                $slug = Str::slug(str_replace(['www.', '.com', '.co', '.is', '.io', '.net'], '', $domain));
                if (isset($discovered[$slug])) continue;

                $result = $this->analyzeSite($url);
                if ($result) {
                    $result['already_exists'] = in_array($result['slug'], $existingSlugs);
                    $discovered[$result['slug']] = $result;
                }
                usleep(300_000);
            }
            sleep(1);
        }

        Cache::put('vendor_discovery_results', $discovered, 86400); // 24h
        Cache::put('vendor_discovery_last_scan', now()->toIso8601String(), 86400);
        Cache::forget('vendor_discovery_scanning');

        return back()->with('success', 'Scan complete! Found ' . count($discovered) . ' potential vendors.');
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'vendors' => 'required|array|min:1',
            'vendors.*.name' => 'required|string|max:255',
            'vendors.*.url' => 'required|string',
            'vendors.*.slug' => 'required|string',
            'vendors.*.email' => 'nullable|string',
            'vendors.*.platform' => 'nullable|string',
            'vendors.*.description' => 'nullable|string',
        ]);

        $imported = 0;
        $skipped = 0;

        foreach ($validated['vendors'] as $v) {
            if (Brand::where('slug', $v['slug'])->exists()) {
                $skipped++;
                continue;
            }

            try {
                DB::transaction(function () use ($v) {
                    $location = Location::firstOrCreate(['name' => 'United States'], ['slug' => 'united-states']);

                    $brand = Brand::create([
                        'name' => $v['name'],
                        'slug' => $v['slug'],
                        'is_active' => false,
                    ]);

                    VendorSetting::create([
                        'brand_id' => $brand->id,
                        'location_id' => $location->id,
                        'description' => $v['description'] ?? null,
                        'contact_email' => $v['email'] ?? null,
                        'shop_url' => $v['url'],
                        'website' => $v['url'],
                        'status' => 0,
                        'approval_status' => 'pending',
                        'api_platform' => match ($v['platform'] ?? null) {
                            'woocommerce' => 'woocommerce',
                            'shopify' => 'page_scrape',
                            default => 'page_scrape',
                        },
                    ]);
                });
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        return back()->with('success', "Imported {$imported} vendors. Skipped {$skipped}.");
    }

    private function analyzeSite(string $url): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'PeptideMapBot/1.0 (+https://peptidemap.com)'])
                ->get($url);

            if (!$response->successful()) return null;

            $html = $response->body();
            $domain = parse_url($url, PHP_URL_HOST);
            $name = $this->extractName($html, $domain);
            $slug = Str::slug($name);

            return [
                'name' => $name,
                'slug' => $slug,
                'url' => $url,
                'domain' => $domain,
                'platform' => $this->detectPlatform($html, $url),
                'has_affiliate' => $this->detectAffiliate($html, $url),
                'email' => $this->extractEmail($html),
                'description' => $this->extractDescription($html),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function searchGoogle(string $query): array
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
                ->get('https://www.google.com/search', ['q' => $query, 'num' => 15]);

            if (!$response->successful()) return [];

            $html = $response->body();
            $urls = [];

            // Extract URLs from various Google result formats
            preg_match_all('/href="\/url\?q=(https?:\/\/[^&"]+)/', $html, $m1);
            preg_match_all('/data-href="(https?:\/\/[^"]+)"/', $html, $m2);

            foreach (array_merge($m1[1] ?? [], $m2[1] ?? []) as $u) {
                $u = urldecode($u);
                $d = parse_url($u, PHP_URL_HOST);
                if (!$this->isSkippable($d)) {
                    $urls[] = parse_url($u, PHP_URL_SCHEME) . '://' . $d;
                }
            }

            return array_unique($urls);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function detectPlatform(string $html, string $url): ?string
    {
        $l = strtolower($html);
        if (str_contains($l, 'woocommerce') || str_contains($l, 'wc-block') || str_contains($l, 'wp-content/plugins/woo')) return 'woocommerce';
        if (str_contains($l, 'cdn.shopify.com') || str_contains($l, 'myshopify')) return 'shopify';
        if (str_contains($l, 'medusajs')) return 'medusa';
        if (str_contains($l, 'bigcommerce') || str_contains($l, 'cdn11.bigcommerce')) return 'bigcommerce';
        if (str_contains($l, 'wp-content') || str_contains($l, 'wordpress')) return 'wordpress';
        return null;
    }

    private function detectAffiliate(string $html, string $url): bool
    {
        $l = strtolower($html);
        if (str_contains($l, 'affiliate program') || (str_contains($l, 'affiliate') && str_contains($l, 'commission'))) return true;
        if (preg_match('/href=["\'][^"\']*\/affiliate[s]?["\']/', $l)) return true;
        return false;
    }

    private function extractEmail(string $html): ?string
    {
        if (preg_match('/mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $html, $m)) {
            $e = strtolower($m[1]);
            if (!str_contains($e, 'noreply') && !str_contains($e, 'wordpress')) return $e;
        }
        return null;
    }

    private function extractName(string $html, string $domain): string
    {
        if (preg_match('/<meta[^>]*property=["\']og:site_name["\'][^>]*content=["\']([^"\']+)/', $html, $m)) {
            return html_entity_decode(trim($m[1]));
        }
        if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
            $t = html_entity_decode(trim($m[1]));
            $t = preg_replace('/\s*[|–—-]\s*.+$/', '', $t);
            if (strlen($t) > 2 && strlen($t) < 60) return $t;
        }
        return ucwords(str_replace(['.com','.co','.is','.io','.net','www.','-','_'], ['',' ',' ',' ',' ',' ',' ',' '], $domain));
    }

    private function extractDescription(string $html): ?string
    {
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)/', $html, $m)) {
            return html_entity_decode(trim($m[1]));
        }
        return null;
    }

    private function isSkippable(?string $d): bool
    {
        if (!$d) return true;
        foreach (['google.','youtube.','facebook.','twitter.','reddit.','wikipedia.','amazon.','ebay.','nih.gov','pubmed.','fda.gov','linkedin.','tiktok.','pinterest.','quora.','peptidemap.com'] as $s) {
            if (str_contains($d, $s)) return true;
        }
        return false;
    }
}
