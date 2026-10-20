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
 * Targeted product sync for Australian Research Peptides.
 *
 * ARP has no WooCommerce / API, but they publish an exemplary llms.txt
 * at /llms.txt that lists every product URL, and each product exposes
 * a plain-text markdown mirror at {product}/text.md with a strengths
 * table (strength | price AUD | availability | SKU) and variant URLs.
 *
 * Flow:
 *  1. GET /llms.txt, extract product URLs
 *  2. For each product, GET {product}/text.md
 *  3. Parse the "## Strengths, prices and availability" table
 *  4. Create one Product row per strength (vendor's own granularity)
 *  5. Upsert on (brand_id, external_id = SKU)
 *
 * Cron: daily (routes/console.php).
 */
class SyncAustralianResearchPeptides extends Command
{
    protected $signature = 'arp:sync
                            {--dry-run : Fetch + parse without writing}
                            {--limit= : Cap number of PRODUCTS (not strengths)}';

    protected $description = 'Scrape australianresearchpeptides.au via /llms.txt + text.md mirrors.';

    private const BASE = 'https://www.australianresearchpeptides.au';
    private const BRAND_SLUG = 'australian-research-peptides';

    public function handle(): int
    {
        $brand = Brand::where('slug', self::BRAND_SLUG)->first();
        if (!$brand) {
            $this->error("Brand '" . self::BRAND_SLUG . "' not found. Create it in /admin/vendors first.");
            return self::FAILURE;
        }

        $productUrls = $this->fetchProductUrls();
        if (empty($productUrls)) {
            $this->error('No product URLs extracted from /llms.txt.');
            return self::FAILURE;
        }
        $this->info("Found " . count($productUrls) . " product(s) in llms.txt.");

        if ($limit = (int) $this->option('limit')) {
            $productUrls = array_slice($productUrls, 0, $limit);
        }

        $new = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($productUrls as $url) {
            try {
                $parsed = $this->fetchAndParse($url);
                if (!$parsed || empty($parsed['variants'])) {
                    $skipped++;
                    $this->line("  · {$url} — no strengths parsed");
                    continue;
                }

                foreach ($parsed['variants'] as $variant) {
                    $r = $this->upsertVariant($brand->id, $parsed, $variant);
                    if ($r === 'new') $new++;
                    elseif ($r === 'updated') $updated++;
                }
            } catch (\Throwable $e) {
                Log::warning('arp:sync error', ['url' => $url, 'err' => $e->getMessage()]);
                $this->warn("  ✗ {$url} — {$e->getMessage()}");
                $skipped++;
            }
            usleep(400_000); // polite
        }

        if (!$this->option('dry-run') && $brand->vendorSetting) {
            $brand->vendorSetting->forceFill(['feed_last_synced_at' => now()])->save();
        }

        $this->newLine();
        $this->info("Done. {$new} new, {$updated} updated, {$skipped} skipped.");
        return self::SUCCESS;
    }

    private function fetchProductUrls(): array
    {
        $resp = Http::timeout(30)->withHeaders([
            'User-Agent' => 'PeptidemapBot/1.0 (+https://peptidemap.com)',
        ])->get(self::BASE . '/llms.txt');

        if (!$resp->successful()) {
            throw new \RuntimeException("llms.txt HTTP {$resp->status()}");
        }

        // Product URLs look like /products/{slug} — exclude variant URLs
        // like /products/{slug}/{strength}, and exclude the text.md mirrors.
        preg_match_all('~https?://(?:www\.)?australianresearchpeptides\.au/products/([a-z0-9-]+)(?:/text\.md)?~i', $resp->body(), $m);
        $slugs = array_values(array_unique($m[1] ?? []));
        return array_map(fn ($s) => self::BASE . "/products/{$s}", $slugs);
    }

    /**
     * Fetches {product}/text.md and parses out the title, description,
     * and the strengths table. Returns a shape we can upsert from.
     */
    private function fetchAndParse(string $productUrl): ?array
    {
        $resp = Http::timeout(30)->withHeaders([
            'User-Agent' => 'PeptidemapBot/1.0 (+https://peptidemap.com)',
            'Accept' => 'text/markdown, text/plain, text/html;q=0.5',
        ])->get($productUrl . '/text.md');
        if (!$resp->successful()) return null;
        $md = $resp->body();

        // Title: first H1
        preg_match('/^#\s+(.+)$/m', $md, $tm);
        $title = $tm ? trim(preg_replace('/^Buy\s+/i', '', $tm[1])) : null;
        $title = $title ? preg_replace('/\s+in\s+Australia\s*$/i', '', $title) : null;
        if (!$title) return null;

        // Description: first body paragraph after the H1, before any ##.
        $description = null;
        if (preg_match('/^#\s+.+$\s+((?:(?!^##).)+)/mUs', $md, $dm)) {
            $description = trim($dm[1]);
        }

        // Strengths table — a simple pipe table under "## Strengths..."
        $variants = [];
        if (preg_match('/##\s+Strengths[^\n]*\n+((?:\|[^\n]+\n?)+)/i', $md, $sm)) {
            $rows = preg_split('/\r?\n/', trim($sm[1]));
            foreach ($rows as $row) {
                $row = trim($row);
                if (!str_starts_with($row, '|')) continue;
                $cells = array_map('trim', explode('|', trim($row, '|')));
                if (count($cells) < 4) continue;
                // Skip header + separator rows
                if (strcasecmp($cells[0], 'Strength') === 0) continue;
                if (preg_match('/^-+$/', $cells[0])) continue;
                [$strength, $priceCell, $availability, $sku] = $cells;
                $price = (float) preg_replace('/[^0-9.]/', '', $priceCell);
                if ($price <= 0) continue;
                $variants[] = [
                    'size' => preg_replace('/\s+/', '', $strength), // "10 mg" -> "10mg"
                    'price_aud' => $price,
                    'in_stock' => str_contains(strtolower($availability), 'in stock'),
                    'sku' => $sku,
                ];
            }
        }

        return [
            'url' => $productUrl,
            'title' => $title,
            'description' => $description,
            'variants' => $variants,
        ];
    }

    private function upsertVariant(int $brandId, array $parsed, array $variant): string
    {
        $sku = (string) $variant['sku'];
        $slug = basename(parse_url($parsed['url'], PHP_URL_PATH));
        $variantUrl = $parsed['url'] . '/' . strtolower($variant['size']);

        $name = "{$parsed['title']} — {$variant['size']}";

        $existing = Product::where('brand_id', $brandId)
            ->where(function ($q) use ($sku, $variantUrl) {
                $q->where('external_id', $sku)->orWhere('product_url', $variantUrl);
            })
            ->first();

        $attrs = [
            'name' => $name,
            'price' => $variant['price_aud'],
            'product_url' => $variantUrl,
            'size_mg' => $variant['size'],
            'stock_status' => $variant['in_stock'] ? 'in_stock' : 'out_of_stock',
            'external_id' => $sku,
            'hidden' => false,
            'status' => 'active',
        ];

        if ($this->option('dry-run')) {
            $this->line("  → {$sku} / {$name} / \${$variant['price_aud']} AUD / " . ($variant['in_stock'] ? 'in stock' : 'OOS'));
            return $existing ? 'updated' : 'new';
        }

        if ($existing) {
            $existing->fill($attrs)->save();
            return 'updated';
        }

        $attrs['brand_id'] = $brandId;
        $attrs['slug'] = $this->uniqueSlug($slug . '-' . strtolower($variant['size']));
        $attrs['description'] = $parsed['description']; // seed description from vendor copy
        $attrs['product_category_id'] = $this->categorize($slug);
        Product::create($attrs);
        return 'new';
    }

    private function categorize(string $slug): ?int
    {
        $map = [
            'retatrutide' => 'Retatrutide',
            'tirzepatide' => 'Tirzepatide',
            'semaglutide' => 'Semaglutide',
            'bpc-157' => 'BPC-157',
            'tb-500' => 'TB-500',
            'bpc-157-tb-500' => 'BPC-157 / TB-500',
            'ghk-cu' => 'GHK-Cu',
            'mots-c' => 'MOTS-c',
            'nad' => 'NAD+',
            'kpv' => 'KPV',
            'cjc-1295' => 'CJC-1295',
            'ipamorelin' => 'Ipamorelin',
            'cjc-1295-ipamorelin' => 'CJC-1295 / Ipamorelin',
            'tesamorelin' => 'Tesamorelin',
            'sermorelin' => 'Sermorelin',
            'melanotan-2' => 'Melanotan',
            'pt-141' => 'PT-141',
            'selank' => 'Selank',
            'semax' => 'Semax',
            'epithalon' => 'Epithalon',
            'dsip' => 'DSIP',
        ];
        $target = $map[$slug] ?? null;
        if (!$target) return null;
        return ProductCategory::where('name', $target)->value('id');
    }

    private function uniqueSlug(string $base): string
    {
        $base = Str::slug($base);
        $candidate = $base;
        $i = 1;
        while (Product::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . self::BRAND_SLUG . ($i > 1 ? "-{$i}" : '');
            $i++;
            if ($i > 20) break;
        }
        return $candidate;
    }
}
