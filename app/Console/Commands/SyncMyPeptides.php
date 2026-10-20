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
 * My Peptides (UK) catalog sync via their public JSON API. No auth,
 * paginated. Spec:
 *
 *   GET https://api.my-peptides.co.uk/products/?page=N&page_size=100
 *   -> { items: [ { id, slug, name, price, compare_at_price,
 *                   stock_status, sku, category, product_type,
 *                   is_unlisted, specifications, ... } ] }
 *
 * Skip is_unlisted products per Julia 10/21 (those aren't shown on
 * their own site). Same admin-field lock pattern as every other
 * sync — on update, only touch what the vendor owns.
 *
 * GBP currency on their end; we store the raw number, display
 * handles the £ prefix per vendor location.
 */
class SyncMyPeptides extends Command
{
    protected $signature = 'mypeptides:sync
                            {--dry-run : Fetch + parse without writing}
                            {--limit= : Cap number of SKUs}';

    protected $description = 'Pull My Peptides UK catalog via api.my-peptides.co.uk.';

    private const BASE = 'https://api.my-peptides.co.uk';
    private const BRAND_SLUG = 'my-peptides';
    private const PAGE_SIZE = 100;

    public function handle(): int
    {
        $brand = Brand::where('slug', self::BRAND_SLUG)->first();
        if (!$brand) {
            $this->error("Brand '" . self::BRAND_SLUG . "' not found. Create it in /admin/vendors first.");
            return self::FAILURE;
        }

        $items = $this->fetchAllPages();
        $this->info("Fetched " . count($items) . " item(s). Dropping is_unlisted…");

        $items = array_values(array_filter($items, fn ($p) => !($p['is_unlisted'] ?? false)));
        $this->info(count($items) . " listed item(s) remain.");

        if ($limit = (int) $this->option('limit')) {
            $items = array_slice($items, 0, $limit);
        }

        $new = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $p) {
            try {
                $r = $this->upsert($brand->id, $p);
                if ($r === 'new') $new++;
                elseif ($r === 'updated') $updated++;
                else $skipped++;
            } catch (\Throwable $e) {
                Log::warning('mypeptides:sync error', [
                    'sku' => $p['sku'] ?? '?',
                    'err' => $e->getMessage(),
                ]);
                $this->warn("  ✗ {$p['sku']}: {$e->getMessage()}");
                $skipped++;
            }
        }

        if (!$this->option('dry-run') && $brand->vendorSetting) {
            $brand->vendorSetting->forceFill(['feed_last_synced_at' => now()])->save();
        }

        $this->newLine();
        $this->info("Done. {$new} new, {$updated} updated, {$skipped} skipped.");
        return self::SUCCESS;
    }

    private function fetchAllPages(): array
    {
        $all = [];
        $page = 1;
        while (true) {
            $resp = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'PeptidemapBot/1.0 (+https://peptidemap.com)',
                ])
                ->get(self::BASE . '/products/', [
                    'page' => $page,
                    'page_size' => self::PAGE_SIZE,
                ]);

            if (!$resp->successful()) {
                throw new \RuntimeException("My Peptides HTTP {$resp->status()} on page {$page}");
            }
            $batch = $resp->json('items') ?? [];
            if (empty($batch)) break;
            $all = array_merge($all, $batch);
            if (count($batch) < self::PAGE_SIZE) break; // last page
            $page++;
            if ($page > 50) break; // sanity cap — 5000 items
        }
        return $all;
    }

    private function upsert(int $brandId, array $p): string
    {
        $sku = (string) ($p['sku'] ?? '');
        $slug = (string) ($p['slug'] ?? '');
        if ($sku === '' || $slug === '') return 'skipped';

        $productUrl = 'https://my-peptides.co.uk/products/' . $slug;

        $existing = Product::where('brand_id', $brandId)
            ->where(function ($q) use ($sku, $productUrl) {
                $q->where('external_id', $sku)->orWhere('product_url', $productUrl);
            })
            ->first();

        $regular = (float) ($p['price'] ?? 0);
        // compare_at_price is their original/MSRP, price is what they
        // sell for. If compare_at_price > price, we're on sale: price
        // becomes the discounted figure, compare_at_price the strike.
        $compare = $p['compare_at_price'] !== null ? (float) $p['compare_at_price'] : null;
        $discountPrice = ($compare && $compare > $regular) ? $regular : null;
        $retail = ($compare && $compare > $regular) ? $compare : $regular;
        $inStock = ($p['stock_status'] ?? 'in_stock') === 'in_stock' && ((int) ($p['stock_quantity'] ?? 0) > 0);

        if ($existing) {
            // Colin 10/21 — only price / sale / stock on auto-refresh.
            // Everything else admin-curated.
            $existing->fill([
                'price' => $retail,
                'discount_price' => $discountPrice,
                'stock_status' => $inStock ? 'in_stock' : 'out_of_stock',
            ])->save();
            return 'updated';
        }

        if ($this->option('dry-run')) {
            $this->line("  → NEW {$sku} / {$p['name']} / £{$retail}" . ($discountPrice ? " (sale £{$discountPrice})" : '') . ' / ' . ($inStock ? 'in stock' : 'OOS'));
            return 'new';
        }

        Product::create([
            'brand_id' => $brandId,
            'slug' => $this->uniqueSlug($slug),
            'name' => (string) $p['name'],
            'description' => (string) ($p['description'] ?? ''),
            'price' => $retail,
            'discount_price' => $discountPrice,
            'product_url' => $productUrl,
            'stock_status' => $inStock ? 'in_stock' : 'out_of_stock',
            'external_id' => $sku,
            'hidden' => false,
            'status' => 'active',
            'product_category_id' => $this->categorize((string) $p['name']),
            'product_type' => $this->inferType((string) ($p['product_type'] ?? ''), (string) $p['name']),
        ]);
        return 'new';
    }

    private function categorize(string $name): ?int
    {
        $lower = strtolower($name);
        $compounds = [
            'bpc-157/tb-500' => 'BPC-157 / TB-500',
            'cjc-1295/ipamorelin' => 'CJC-1295 / Ipamorelin',
            'retatrutide' => 'Retatrutide',
            'tirzepatide' => 'Tirzepatide',
            'semaglutide' => 'Semaglutide',
            'cagrilintide' => 'Cagrilintide',
            'tesamorelin' => 'Tesamorelin',
            'sermorelin' => 'Sermorelin',
            'ipamorelin' => 'Ipamorelin',
            'cjc-1295' => 'CJC-1295',
            'bpc-157' => 'BPC-157',
            'tb-500' => 'TB-500',
            'ghk-cu' => 'GHK-Cu',
            'mots-c' => 'MOTS-c',
            'nad+' => 'NAD+',
            ' nad ' => 'NAD+',
            'glutathione' => 'Glutathione',
            'selank' => 'Selank',
            'semax' => 'Semax',
            'dsip' => 'DSIP',
            'kpv' => 'KPV',
            'epithalon' => 'Epithalon',
            'epitalon' => 'Epithalon',
            'melanotan' => 'Melanotan',
            'pt-141' => 'PT-141',
            'bremelanotide' => 'Bremelanotide',
            'aod-9604' => 'AOD-9604',
            'igf-1' => 'IGF-1 LR3',
            '5-amino-1mq' => '5-AMINO-1MQ',
            'bacteriostatic' => 'Bacteriostatic Water',
            'bac water' => 'Bacteriostatic Water',
        ];
        foreach ($compounds as $needle => $cat) {
            if (str_contains($lower, $needle)) {
                return ProductCategory::where('name', $cat)->value('id');
            }
        }
        return null;
    }

    private function inferType(string $apiType, string $name): string
    {
        return match (strtolower($apiType)) {
            'accessory', 'kit' => 'Kit',
            'capsule' => 'Capsule',
            'spray', 'nasal_spray' => 'Nasal Spray',
            'topical' => 'Topical',
            default => str_contains(strtolower($name), 'spray') ? 'Nasal Spray' : 'Peptide',
        };
    }

    private function uniqueSlug(string $base): string
    {
        $candidate = Str::slug($base);
        $i = 1;
        while (Product::where('slug', $candidate)->exists()) {
            $candidate = Str::slug($base) . '-' . self::BRAND_SLUG . ($i > 1 ? "-{$i}" : '');
            $i++;
            if ($i > 20) break;
        }
        return $candidate;
    }
}
