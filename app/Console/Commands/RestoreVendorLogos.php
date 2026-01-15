<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Brand;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RestoreVendorLogos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vendor:restore-logos {--force : Force re-download even if file exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore vendor logos by downloading them from vendor websites';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting vendor logo restoration...');
        $this->newLine();

        $brands = Brand::with('vendorSetting')
            ->where('is_active', true)
            ->get();

        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($brands as $brand) {
            if (!$brand->vendorSetting || !$brand->vendorSetting->logo) {
                $this->warn("Skipping {$brand->name}: No logo path in database");
                $skippedCount++;
                continue;
            }

            $logoPath = $brand->vendorSetting->logo;
            $filename = basename($logoPath);
            $directory = dirname($logoPath);

            // Check if file already exists
            if (Storage::disk('public')->exists($logoPath) && !$this->option('force')) {
                $this->info("✓ {$brand->name}: Logo already exists");
                $successCount++;
                continue;
            }

            $shopUrl = $brand->vendorSetting->shop_url;
            if (!$shopUrl) {
                $this->error("✗ {$brand->name}: No shop URL available");
                $failedCount++;
                continue;
            }

            $this->info("Downloading logo for {$brand->name} from {$shopUrl}...");

            // Try to download logo from common locations
            $logoUrl = $this->findLogoUrl($shopUrl);

            if (!$logoUrl) {
                $this->error("✗ {$brand->name}: Could not find logo URL");
                $failedCount++;
                continue;
            }

            try {
                $response = Http::timeout(10)->get($logoUrl);

                if ($response->successful()) {
                    // Ensure directory exists
                    Storage::disk('public')->makeDirectory($directory);

                    // Save the file
                    Storage::disk('public')->put($logoPath, $response->body());

                    $this->info("✓ {$brand->name}: Logo restored successfully");
                    $successCount++;
                } else {
                    $this->error("✗ {$brand->name}: Failed to download (HTTP {$response->status()})");
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $this->error("✗ {$brand->name}: Error - {$e->getMessage()}");
                $failedCount++;
            }
        }

        $this->newLine();
        $this->info("Restoration complete!");
        $this->info("Success: {$successCount}");
        $this->info("Failed: {$failedCount}");
        $this->info("Skipped: {$skippedCount}");

        return Command::SUCCESS;
    }

    /**
     * Try to find logo URL from vendor website
     */
    private function findLogoUrl($shopUrl)
    {
        $baseUrl = rtrim($shopUrl, '/');
        
        // Common logo locations to try
        $commonPaths = [
            '/wp-content/uploads/',
            '/images/logo',
            '/logo',
            '/assets/images/logo',
            '/img/logo',
            '/static/logo',
            '/assets/logo',
            '/images/',
            '/img/',
            '/assets/img/',
            '/static/images/',
        ];

        // Common logo filenames
        $commonFilenames = [
            'logo.svg',
            'logo.png',
            'logo.webp',
            'logo.jpg',
            'logo.jpeg',
            'brand-logo.svg',
            'brand-logo.png',
            'site-logo.svg',
            'site-logo.png',
            'header-logo.svg',
            'header-logo.png',
            'main-logo.svg',
            'main-logo.png',
        ];
        
        // Vendor-specific logo paths (for known vendors)
        $vendorSpecificPaths = [
            'purerawz.co' => [
                '/wp-content/uploads/',
                '/wp-content/themes/',
            ],
            'aminousa.com' => [
                '/wp-content/uploads/',
                '/assets/',
            ],
        ];
        
        $domain = parse_url($baseUrl, PHP_URL_HOST);
        if (isset($vendorSpecificPaths[$domain])) {
            $commonPaths = array_merge($vendorSpecificPaths[$domain], $commonPaths);
        }

        // Try to fetch the homepage and look for logo in HTML
        try {
            $response = Http::timeout(10)->get($baseUrl);
            
            if ($response->successful()) {
                $html = $response->body();
                
                // Look for logo in common HTML patterns
                $patterns = [
                    '/<img[^>]+logo[^>]+src=["\']([^"\']+)["\']/i',
                    '/<img[^>]+src=["\']([^"\']*logo[^"\']*)["\']/i',
                    '/logo["\']?\s*:\s*["\']([^"\']+)["\']/i',
                    '/<link[^>]+rel=["\']icon["\'][^>]+href=["\']([^"\']+)["\']/i',
                    '/<link[^>]+rel=["\']apple-touch-icon["\'][^>]+href=["\']([^"\']+)["\']/i',
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match_all($pattern, $html, $matches)) {
                        foreach ($matches[1] as $logoUrl) {
                            // Skip data URIs and very small images
                            if (strpos($logoUrl, 'data:') === 0 || strpos($logoUrl, 'icon') !== false) {
                                continue;
                            }
                            
                            // Convert relative URLs to absolute
                            if (strpos($logoUrl, 'http') !== 0) {
                                if (strpos($logoUrl, '/') === 0) {
                                    $logoUrl = $baseUrl . $logoUrl;
                                } else {
                                    $logoUrl = $baseUrl . '/' . $logoUrl;
                                }
                            }
                            
                            // Verify it's an image URL
                            if (preg_match('/\.(svg|png|jpg|jpeg|webp)$/i', $logoUrl)) {
                                return $logoUrl;
                            }
                        }
                    }
                }
                
                // Try to find all img tags and look for logo-like images
                if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $imgMatches)) {
                    foreach ($imgMatches[1] as $imgUrl) {
                        // Skip small icons and data URIs
                        if (strpos($imgUrl, 'data:') === 0 || 
                            strpos($imgUrl, 'icon') !== false ||
                            strpos($imgUrl, 'favicon') !== false) {
                            continue;
                        }
                        
                        // Look for logo-like filenames
                        if (preg_match('/logo|brand|header/i', $imgUrl)) {
                            // Convert relative URLs to absolute
                            if (strpos($imgUrl, 'http') !== 0) {
                                if (strpos($imgUrl, '/') === 0) {
                                    $imgUrl = $baseUrl . $imgUrl;
                                } else {
                                    $imgUrl = $baseUrl . '/' . $imgUrl;
                                }
                            }
                            
                            // Verify it's an image URL
                            if (preg_match('/\.(svg|png|jpg|jpeg|webp)$/i', $imgUrl)) {
                                return $imgUrl;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Continue to try common paths
        }

        // Try common logo paths
        foreach ($commonPaths as $path) {
            foreach ($commonFilenames as $filename) {
                $testUrl = $baseUrl . $path . $filename;
                
                try {
                    $response = Http::timeout(5)->head($testUrl);
                    if ($response->successful()) {
                        return $testUrl;
                    }
                } catch (\Exception $e) {
                    // Continue trying
                }
            }
        }

        return null;
    }
}
