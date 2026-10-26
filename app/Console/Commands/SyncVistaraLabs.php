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
 * Vistara Labs exposes a central price-library HTML table at
 * /peptide-library/prices/ with every product, size, and price in
 * one place. Vendor-recommended as "the easiest place to refresh
 * our listing from" (Julia 10/27).
 *
 * Table columns: Product (linked) | Size | List price | First order
 * (25% off) | Every order after (10% off). We store the list price
 * as `price` and the "every order after" discount as `discount_price`
 * (the realistic repeat-buyer rate). SKUs are synthesized from
 * (slug + size) since the HTML doesn't expose them.
 */
class SyncVistaraLabs extends Command
{
    protected $signature = 'vistara:sync {--dry-run : Fetch + parse without writing} {--limit= : Cap rows}';
    protected $description = 'Scrape vistaralabs.ca/peptide-library/prices/ and upsert all products.';

    private const BASE = 'https://vistaralabs.ca';
    private const PRICE_URL = self::BASE . '/peptide-library/prices/';
    private const BRAND_SLUG = 'vistara-labs';

    public function handle(): int
    {
        $brand = Brand::where('slug', self::BRAND_SLUG)->first();
        if (!$brand) {
            $this->error("Brand '" . self::BRAND_SLUG . "' not found.");
            return self::FAILURE;
        }

        $this->info('Fetching Vistara price library…');
        $resp = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
                'Accept' => 'text/html',
            ])->get(self::PRICE_URL);

        if (!$resp->successful()) {
            $this->error("HTTP {$resp->status()} from {$resp->body()}");
            return self::FAILURE;
        }

        $rows = $this->parseRows($resp->body());
        $this->info('Parsed ' . count($rows) . ' rows.');
        if ($limit = (int) $this->option('limit')) {
            $rows = array_slice($rows, 0, $limit);
        }

        $new = 0; $updated = 0; $skipped = 0;
        foreach ($rows as $r) {
            try {
                $result = $this->upsert($brand->id, $r);
                if ($result === 'new') $new++;
                elseif ($result === 'updated') $updated++;
                else $skipped++;
            } catch (\Throwable $e) {
                Log::warning('vistara:sync error', ['sku' => $r['sku'] ?? '?', 'err' => $e->getMessage()]);
                $this->warn("  ✗ {$r['sku']}: {$e->getMessage()}");
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

    private function parseRows(string $html): array
    {
        $rows = [];
        if (!preg_match_all('~<tr[^>]*>(.*?)</tr>~s', $html, $m)) return [];
        foreach ($m[1] as $rowHtml) {
            // Skip header + rows without a product link
            if (!preg_match('~<a\s+href="([^"]+)"[^>]*>([^<]+)</a>~i', $rowHtml, $link)) continue;
            $productUrl = $link[1];
            $name = html_entity_decode(trim($link[2]), ENT_QUOTES | ENT_HTML5);

            // Cells after the product header
            preg_match_all('~<t[dh][^>]*>(.*?)</t[dh]>~s', $rowHtml, $cells);
            $texts = array_map(
                fn ($c) => trim(html_entity_decode(strip_tags($c), ENT_QUOTES | ENT_HTML5)),
                $cells[1] ?? []
            );
            // Expected: [Product-name, Size, List price, First-order price, Every-order-after price]
            if (count($texts) < 5) continue;

            $size = preg_replace('/\s+/', '', $texts[1]);
            $listPrice = $this->priceFromText($texts[2]);
            $repeatPrice = $this->priceFromText($texts[4]);
            if ($listPrice <= 0) continue;

            // size often appears as ?attribute_size=50mg query — honor it over the cell if present
            if (preg_match('~attribute_size=([^&"\']+)~', $productUrl, $sm)) {
                $size = preg_replace('/\s+/', '', urldecode($sm[1]));
            }

            $rows[] = [
                'name' => $name,
                'size' => $size,
                'price' => $listPrice,
                'discount_price' => $repeatPrice > 0 && $repeatPrice < $listPrice ? $repeatPrice : null,
                'product_url' => $productUrl,
                'sku' => 'VL-' . Str::slug($name) . '-' . Str::slug($size),
            ];
        }
        return $rows;
    }

    private function priceFromText(string $text): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $text);
    }

    private function upsert(int $brandId, array $r): string
    {
        $sku = $r['sku'];
        $existing = Product::where('brand_id', $brandId)
            ->where(fn ($q) => $q->where('external_id', $sku)->orWhere('product_url', $r['product_url']))
            ->first();

        if ($existing) {
            // Admin-field lock: price + stock + url only. Name, size,
            // category, hidden stay whatever Julia set them to.
            if ($this->option('dry-run')) return 'updated';
            $existing->fill([
                'price' => $r['price'],
                'discount_price' => $r['discount_price'],
                'product_url' => $r['product_url'],
                'stock_status' => 'in_stock',
            ])->save();
            return 'updated';
        }

        if ($this->option('dry-run')) {
            $sale = $r['discount_price'] ? "/\${$r['discount_price']}" : '';
            $this->line("  → NEW {$sku} / {$r['name']} / {$r['size']} / \${$r['price']}{$sale}");
            return 'new';
        }

        Product::create([
            'brand_id' => $brandId,
            'slug' => $this->uniqueSlug(Str::slug($r['name'] . '-' . $r['size'])),
            'name' => $r['name'],
            'price' => $r['price'],
            'discount_price' => $r['discount_price'],
            'product_url' => $r['product_url'],
            'size_mg' => $r['size'],
            'stock_status' => 'in_stock',
            'external_id' => $sku,
            'hidden' => false,
            'status' => 'active',
            'product_category_id' => $this->categorize($r['name']),
            'product_type' => $this->inferType($r['name']),
        ]);
        return 'new';
    }

    private function categorize(string $name): ?int
    {
        $lower = strtolower($name);
        $compounds = [
            'bpc-157 + tb-500' => 'BPC-157 / TB-500',
            'bpc-157/tb-500' => 'BPC-157 / TB-500',
            'cjc-1295 + ipamorelin' => 'CJC-1295 / Ipamorelin',
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
            'nad+' => 'NAD+', 'nad ' => 'NAD+',
            'glutathione' => 'Glutathione',
            'selank' => 'Selank',
            'semax' => 'Semax',
            'dsip' => 'DSIP',
            'kpv' => 'KPV',
            'kisspeptin' => 'Kisspeptin',
            'epithalon' => 'Epithalon', 'epitalon' => 'Epithalon',
            'melanotan ii' => 'Melanotan', 'melanotan 2' => 'Melanotan', 'mt-2' => 'Melanotan',
            'pt-141' => 'PT-141',
            'bremelanotide' => 'Bremelanotide',
            'aod-9604' => 'AOD-9604',
            'igf-1' => 'IGF-1 LR3',
            '5-amino-1mq' => '5-AMINO-1MQ',
            'snap-8' => 'SNAP-8',
            'pinealon' => 'Pinealon',
            'cartalax' => 'Cartalax',
            'dihexa' => 'Dihexa',
        ];
        foreach ($compounds as $needle => $cat) {
            if (str_contains($lower, $needle)) {
                return ProductCategory::where('name', $cat)->value('id');
            }
        }
        return null;
    }

    private function inferType(string $name): string
    {
        $lower = strtolower($name);
        return match (true) {
            str_contains($lower, 'nasal spray'), str_contains($lower, 'spray') => 'Nasal Spray',
            str_contains($lower, 'capsule') => 'Capsule',
            default => 'Peptide',
        };
    }

    private function uniqueSlug(string $base): string
    {
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
