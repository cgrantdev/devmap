<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Targeted product sync for Purgo Labs (brand_id 149).
 *
 * Purgo isn't on Woo/Shopify — it's a bespoke Next.js storefront —
 * so the generic feed importer and page_scrape both undercount
 * (11 of ~53 SKUs). Every product page emits clean schema.org
 * `Product` JSON-LD though, so we can walk /products for the slug
 * list and hit each page for a canonical name/price/sku/image.
 *
 * Multi-size variants live on `?variant={size}` URLs; this pass
 * captures the default variant per product. A follow-up pass can
 * enrich by scraping the size selector.
 *
 * Cron: routes/console.php (daily, off-peak).
 */
class SyncPurgoLabs extends Command
{
    protected $signature = 'purgo:sync
                            {--dry-run : Fetch + parse without writing}
                            {--limit= : Cap number of products (for testing)}';

    protected $description = 'Scrape purgolabs.com JSON-LD and upsert products under brand 149.';

    private const BASE = 'https://www.purgolabs.com';
    private const BRAND_SLUG = 'purgolabs';

    public function handle(): int
    {
        $brand = Brand::where('slug', self::BRAND_SLUG)->orWhere('id', 149)->first();
        if (!$brand) {
            $this->error('Purgo brand row not found (slug=purgolabs / id=149).');
            return self::FAILURE;
        }

        $slugs = $this->fetchProductSlugs();
        if (empty($slugs)) {
            $this->error('No product slugs found on /products.');
            return self::FAILURE;
        }
        $this->info('Found ' . count($slugs) . ' product slug(s).');

        if ($limit = (int) $this->option('limit')) {
            $slugs = array_slice($slugs, 0, $limit);
        }

        $new = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($slugs as $slug) {
            try {
                $product = $this->fetchProduct($slug);
                if (!$product) {
                    $this->warn("  ✗ {$slug} — no JSON-LD");
                    $skipped++;
                    continue;
                }

                $line = "  → {$slug}: {$product['name']} · \${$product['price']}";
                if (!empty($product['size'])) $line .= " · {$product['size']}";
                $this->line($line);

                if ($this->option('dry-run')) continue;

                $result = $this->upsert($brand->id, $slug, $product);
                $result === 'new' ? $new++ : $updated++;
            } catch (\Throwable $e) {
                Log::warning('purgo:sync error', ['slug' => $slug, 'err' => $e->getMessage()]);
                $this->warn("  ✗ {$slug} — {$e->getMessage()}");
                $skipped++;
            }
            usleep(400_000); // polite
        }

        // Stamp sync time so admin can see freshness at a glance.
        if (!$this->option('dry-run') && $brand->vendorSetting) {
            $brand->vendorSetting->forceFill(['feed_last_synced_at' => now()])->save();
        }

        $this->newLine();
        $this->info("Done. {$new} new, {$updated} updated, {$skipped} skipped.");
        return self::SUCCESS;
    }

    private function fetchProductSlugs(): array
    {
        $resp = Http::timeout(30)->withHeaders([
            'User-Agent' => 'PeptidemapBot/1.0 (+https://peptidemap.com)',
        ])->get(self::BASE . '/products');

        if (!$resp->successful()) {
            throw new \RuntimeException("Index HTTP {$resp->status()}");
        }

        // Match every /products/{slug} anchor href. Skip the index itself
        // and query strings (variant URLs).
        preg_match_all('~/products/([a-z0-9][a-z0-9-]+)(?:["\?#/])~i', $resp->body(), $m);
        $slugs = array_values(array_unique(array_filter($m[1] ?? [], fn ($s) =>
            $s !== '' && strtolower($s) !== 'products'
        )));
        return $slugs;
    }

    /**
     * Fetches a product page and pulls the first schema.org Product
     * JSON-LD block. Returns null if no product block is present.
     */
    private function fetchProduct(string $slug): ?array
    {
        $resp = Http::timeout(30)->withHeaders([
            'User-Agent' => 'PeptidemapBot/1.0 (+https://peptidemap.com)',
        ])->get(self::BASE . "/products/{$slug}");

        if (!$resp->successful()) return null;

        $html = $resp->body();
        // Grab every ld+json block. Some pages emit duplicates; take the
        // first one that parses as a Product.
        preg_match_all('~<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>~is', $html, $m);
        foreach ($m[1] ?? [] as $raw) {
            $data = json_decode(html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5), true);
            if (!$data || ($data['@type'] ?? '') !== 'Product') continue;

            $offer = $data['offers'] ?? [];
            $price = null;
            if (is_array($offer)) {
                $price = $offer['price'] ?? null;
                // Some emit offers as an array of offers.
                if ($price === null && isset($offer[0]['price'])) $price = $offer[0]['price'];
            }
            $sku = $data['sku'] ?? ($data['productID'] ?? null);

            // Size is usually embedded in the name (e.g. "BPC-157 10mg")
            // or on the sku suffix. Extract the mg from name where present.
            $size = null;
            if (!empty($data['name']) && preg_match('~(\d+(?:\.\d+)?)\s*mg\b~i', $data['name'], $sm)) {
                $size = $sm[1] . 'mg';
            } elseif ($sku && preg_match('~(\d+(?:\.\d+)?)\s*MG~i', (string) $sku, $sm)) {
                $size = $sm[1] . 'mg';
            }

            return [
                'name' => trim((string) $data['name']),
                'price' => $price ? (float) $price : null,
                'sku' => $sku ? (string) $sku : null,
                'image' => $data['image'] ?? null,
                'description' => $data['description'] ?? null,
                'size' => $size,
            ];
        }
        return null;
    }

    private function upsert(int $brandId, string $slug, array $p): string
    {
        $productUrl = self::BASE . "/products/{$slug}";
        $extId = $p['sku'] ?: $slug;

        $existing = Product::where('brand_id', $brandId)
            ->where(function ($q) use ($extId, $productUrl) {
                $q->where('external_id', $extId)
                  ->orWhere('product_url', $productUrl);
            })
            ->first();

        $attrs = [
            'name' => $p['name'],
            'price' => $p['price'],
            'image_url' => $p['image'],
            'product_url' => $productUrl,
            'size_mg' => $p['size'],
            'stock_status' => 'in_stock',
            'description' => $p['description'],
            'external_id' => $extId,
            // Julia 10/2: scraped Purgo rows kept landing hidden because
            // DiscoverProductsJob's auto-hide for uncategorized rows set
            // hidden=true and this scraper never cleared it. Purgo is an
            // approved vendor — unhide + status active on every sync.
            'hidden' => false,
            'status' => 'active',
            'product_category_id' => $this->categorize($slug, $p['name']),
        ];

        if ($existing) {
            $existing->fill($attrs)->save();
            return 'updated';
        }

        $attrs['brand_id'] = $brandId;
        $attrs['slug'] = $this->uniqueSlug($brandId, $p['name'], $slug);
        Product::create($attrs);
        return 'new';
    }

    /**
     * Map a Purgo slug/name to a ProductCategory id, falling back to
     * the uncategorized bucket if nothing matches. Purgo's catalog uses
     * in-house pseudonyms ("Glp 2 T" → Tirzepatide, "Klow" → KLOW blend,
     * "AHK Cu" → AHK-Cu copper peptide) that the generic matcher misses.
     */
    private function categorize(string $slug, string $name): ?int
    {
        static $cache = [];
        $key = strtolower($slug);
        if (array_key_exists($key, $cache)) return $cache[$key];

        // slug-based pseudonym map (keys match the URL token on purgolabs.com)
        $pseudonyms = [
            'glp-1-s' => 'Semaglutide',
            'glp-2-t' => 'Tirzepatide',
            'glp-3-r' => 'Retatrutide',
            'glp-4-metatrutide' => 'Retatrutide', // closest match until metatrutide has its own category
            'klow' => 'KLOW',
            'glow-complex' => 'GLOW',
            'glow-caps' => 'GLOW',
            'ahk-cu' => 'AHK-Cu',
            'ghkcu' => 'GHK-Cu',
            'ghk-cu-spray' => 'GHK-Cu',
            'ghk-cu-copper-peptide-shampoo' => 'GHK-Cu',
            'ghk-cu-copper-peptide-capsule-cream' => 'GHK-Cu',
            'ghk-cu-lift-cream' => 'GHK-Cu',
            'ghk-cu-whipped-tallow-honey-balm' => 'GHK-Cu',
            'cjc-ipamorelin-no-dac' => 'CJC-1295 / Ipamorelin',
            'ipamorlin' => 'Ipamorelin',
            'melanoton-1' => 'Melanotan',
            'tesamorlin' => 'Tesamorelin',
            'selank-semax-blend' => 'Selank',
            'semax-selank-spray' => 'Semax',
            'pt-141-oxytocin-xo-spray' => 'PT-141',
            'pt-141-spray' => 'PT-141',
            'nad-plus' => 'NAD+',
            'nad-plus-spray' => 'NAD+',
            'dsip-pinealon-zzz-spray' => 'DSIP',
            'adamax-research-spray' => 'Adamax',
        ];

        $target = $pseudonyms[$key] ?? null;

        // Fallback: match on normalized name (strip size suffix, lowercase)
        if (!$target) {
            $normalized = strtolower(preg_replace('/\s*\(.*?\)|\s*\d+(?:mcg|mg|g|ml|oz|iu).*$/i', '', $name));
            $cat = ProductCategory::whereRaw('LOWER(name) = ?', [trim($normalized)])->first();
            return $cache[$key] = $cat?->id;
        }

        $cat = ProductCategory::where('name', $target)->first();
        return $cache[$key] = $cat?->id;
    }

    private function uniqueSlug(int $brandId, string $name, string $fallback): string
    {
        $base = Str::slug($name) ?: Str::slug($fallback);
        $candidate = $base;
        $i = 1;
        // Slug is globally unique in products; suffix with brand slug first,
        // then a numeric counter if that still collides.
        while (Product::where('slug', $candidate)->exists()) {
            $candidate = $i === 1
                ? "{$base}-" . self::BRAND_SLUG
                : "{$base}-" . self::BRAND_SLUG . "-{$i}";
            $i++;
            if ($i > 20) break;
        }
        return $candidate;
    }
}
