<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Backfill products.vendor_description by fetching each product's
 * retail URL and extracting the vendor's own copy.
 *
 * Context (Colin 10/20): Google's October core update dedup'd our
 * product pages hard — 3,820 indexed, only ~72 shown in SERPs because
 * every product page used identical boilerplate ("SA-3R is a synthetic
 * research peptide supplied by Southern Aminos..."). Using the vendor's
 * actual product description makes each page genuinely distinct and
 * breaks the dedup cluster.
 *
 * Extraction order per page:
 *   1. schema.org Product JSON-LD "description"
 *   2. og:description meta
 *   3. meta name="description"
 *
 * Nothing destructive — the existing `description` column stays as
 * the fallback. ProductDetail frontend prefers vendor_description
 * when set.
 *
 * Pacing: 500ms between requests, so hitting 2,500 products takes
 * ~20 minutes. --limit for testing.
 */
class FetchVendorDescriptions extends Command
{
    protected $signature = 'products:fetch-vendor-descriptions
                            {--limit= : Cap number of products (for testing)}
                            {--force : Re-fetch even if vendor_description already set}
                            {--brand= : Only process one brand slug}
                            {--dry-run : Fetch + parse without writing}';

    protected $description = 'Scrape vendor product pages and backfill vendor-authored descriptions.';

    public function handle(): int
    {
        $query = Product::query()
            ->whereNotNull('product_url')
            ->where('product_url', '!=', '')
            ->where('hidden', false)
            ->where('status', 'active');

        if (!$this->option('force')) {
            $query->whereNull('vendor_description');
        }
        if ($slug = $this->option('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $slug));
        }
        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info('No products to process.');
            return self::SUCCESS;
        }

        $this->info("Fetching vendor descriptions for {$total} product(s)…");

        $ok = 0;
        $empty = 0;
        $failed = 0;

        $query->chunk(50, function ($chunk) use (&$ok, &$empty, &$failed) {
            foreach ($chunk as $product) {
                try {
                    $desc = $this->extractDescription($product->product_url);
                    if (!$desc) {
                        $empty++;
                        $this->line("  · {$product->id} ({$product->name}) — no description extractable");
                    } else {
                        $this->line("  → {$product->id} ({$product->name}) — " . mb_substr($desc, 0, 60) . '…');
                        if (!$this->option('dry-run')) {
                            $product->forceFill([
                                'vendor_description' => $desc,
                                'vendor_description_fetched_at' => now(),
                            ])->save();
                        }
                        $ok++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('products:fetch-vendor-descriptions error', [
                        'id' => $product->id,
                        'url' => $product->product_url,
                        'err' => $e->getMessage(),
                    ]);
                    $this->warn("  ✗ {$product->id} — {$e->getMessage()}");
                }
                usleep(500_000);
            }
        });

        $this->newLine();
        $this->info("Done. {$ok} fetched, {$empty} empty/unextractable, {$failed} failed.");
        return self::SUCCESS;
    }

    /**
     * Extract the best available vendor-authored description from a
     * product page. Tries JSON-LD Product.description first (richest
     * and usually full product copy), then og:description, then the
     * generic meta description.
     */
    private function extractDescription(string $url): ?string
    {
        $resp = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'PeptidemapBot/1.0 (+https://peptidemap.com/bot)',
                'Accept' => 'text/html,application/xhtml+xml',
            ])
            ->get($url);

        if (!$resp->successful()) return null;
        $html = $resp->body();

        // 1. schema.org Product JSON-LD
        preg_match_all('~<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>~is', $html, $m);
        foreach ($m[1] ?? [] as $raw) {
            $data = json_decode(html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5), true);
            if (!$data) continue;
            // Could be a single Product or an array / @graph
            $products = $this->findProducts($data);
            foreach ($products as $p) {
                if (!empty($p['description']) && mb_strlen($p['description']) > 40) {
                    return trim($p['description']);
                }
            }
        }

        // 2. og:description
        if (preg_match('~<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']~i', $html, $og)) {
            $desc = trim(html_entity_decode($og[1], ENT_QUOTES | ENT_HTML5));
            if (mb_strlen($desc) > 40) return $desc;
        }

        // 3. meta description
        if (preg_match('~<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']~i', $html, $md)) {
            $desc = trim(html_entity_decode($md[1], ENT_QUOTES | ENT_HTML5));
            if (mb_strlen($desc) > 40) return $desc;
        }

        return null;
    }

    /**
     * Walk a decoded JSON-LD blob and return every node whose @type is
     * Product. Handles single-object, array, and @graph shapes.
     */
    private function findProducts(array $data): array
    {
        $out = [];
        if (($data['@type'] ?? '') === 'Product') $out[] = $data;
        foreach (['@graph', 'itemListElement', 'hasPart'] as $k) {
            if (isset($data[$k]) && is_array($data[$k])) {
                foreach ($data[$k] as $child) {
                    if (is_array($child)) $out = array_merge($out, $this->findProducts($child));
                }
            }
        }
        // Array-of-items at top level
        if (array_is_list($data)) {
            foreach ($data as $child) {
                if (is_array($child)) $out = array_merge($out, $this->findProducts($child));
            }
        }
        return $out;
    }
}
