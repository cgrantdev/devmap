<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Targeted catalog sync for Zenith Bioscience.
 *
 * Zenith exposes a public REST endpoint (PRICETOOL_CATALOG_API_CONTRACT)
 * that returns their full catalog as JSON, one entry per SKU/variant,
 * with price, salePrice (nullable when not on sale), inStock, size,
 * category, and productUrl. We treat it like any other authoritative
 * feed: upsert on (brand_id, external_id=sku).
 *
 * Auth: Bearer <token> in the Authorization header. Token is stored on
 * vendor_settings.api_key (encrypted cast), set via /admin/vendors
 * Integration tab > API Key field. The sync fails loudly if the token
 * is missing so Colin knows to fill it in.
 *
 * Admin-field lock on existing rows: same pattern as SyncPurgoLabs —
 * price / discount / image / stock / productUrl get refreshed every
 * run; name / size / type / category / hidden / status / description
 * stay whatever Julia set them to.
 *
 * Referral attribution: `?ref=PMAP` is appended to every productUrl
 * before storing. Zenith detects the param and auto-applies the
 * associated discount at checkout.
 */
class SyncZenithBioscience extends Command
{
    protected $signature = 'zenith:sync
                            {--dry-run : Fetch + parse without writing}
                            {--limit= : Cap number of SKUs}';

    protected $description = 'Pull Zenith Bioscience catalog via their REST API.';

    private const ENDPOINT = 'https://api.zenithbioscience.com/api/public/catalog';
    private const BRAND_SLUG = 'zenith-bioscience';
    private const REF_CODE = 'PMAP';

    public function handle(): int
    {
        $brand = Brand::with('vendorSetting')->where('slug', self::BRAND_SLUG)->first();
        if (!$brand) {
            $this->error("Brand '" . self::BRAND_SLUG . "' not found. Create it in /admin/vendors first.");
            return self::FAILURE;
        }

        $token = $this->resolveToken($brand);
        if (!$token) {
            $this->error('Zenith bearer token not set. Paste it into /admin/vendors Integration tab > API Key.');
            return self::FAILURE;
        }

        $this->info("Fetching Zenith catalog…");
        $resp = Http::timeout(60)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'User-Agent' => 'PeptidemapBot/1.0 (+https://peptidemap.com)',
            ])
            ->get(self::ENDPOINT);

        if ($resp->status() === 401) {
            $this->error('Zenith returned 401 — token is wrong or expired.');
            return self::FAILURE;
        }
        if ($resp->status() === 503) {
            $this->warn('Zenith returned 503 — their integration is administratively disabled right now.');
            return self::SUCCESS;
        }
        if (!$resp->successful()) {
            $this->error("Zenith HTTP {$resp->status()}: " . mb_substr($resp->body(), 0, 200));
            return self::FAILURE;
        }

        $data = $resp->json();
        $products = $data['products'] ?? [];
        if (!is_array($products) || empty($products)) {
            $this->warn('Zenith returned an empty products array.');
            return self::SUCCESS;
        }

        $this->info("Got " . count($products) . " SKU(s). Updated at: " . ($data['updated'] ?? 'n/a'));

        if ($limit = (int) $this->option('limit')) {
            $products = array_slice($products, 0, $limit);
        }

        $new = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($products as $p) {
            try {
                $r = $this->upsert($brand->id, $p);
                if ($r === 'new') $new++;
                elseif ($r === 'updated') $updated++;
                else $skipped++;
            } catch (\Throwable $e) {
                Log::warning('zenith:sync error', [
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

    /**
     * Pulls the bearer token from the brand's api_key field (encrypted
     * at rest via the Eloquent cast). Falls back to env ZENITH_API_TOKEN
     * for local testing before Colin wires it up through the admin UI.
     */
    private function resolveToken(Brand $brand): ?string
    {
        $enc = $brand->vendorSetting?->api_key;
        if ($enc) {
            try {
                // Model has 'api_key' => 'encrypted' cast — attribute getter
                // already decrypts. Just return as-is.
                return $enc;
            } catch (\Throwable $e) {
                Log::warning('zenith:sync token decrypt failed', ['err' => $e->getMessage()]);
            }
        }
        return env('ZENITH_API_TOKEN') ?: null;
    }

    private function upsert(int $brandId, array $p): string
    {
        $sku = (string) ($p['sku'] ?? '');
        if ($sku === '') return 'skipped';

        $productUrl = $this->appendRef((string) $p['productUrl']);

        $existing = Product::where('brand_id', $brandId)
            ->where(function ($q) use ($sku, $productUrl) {
                $q->where('external_id', $sku)
                  ->orWhere('product_url', $productUrl);
            })
            ->first();

        $regularPrice = (float) ($p['price'] ?? 0);
        // Per spec: salePrice omitted unless onSale=true. Missing key = not on sale.
        $salePrice = (isset($p['onSale']) && $p['onSale'] && isset($p['salePrice']))
            ? (float) $p['salePrice']
            : null;
        $inStock = (bool) ($p['inStock'] ?? false);

        if ($existing) {
            // Colin 10/21 — auto-refresh is strictly price, sale price,
            // and stock. Everything else (name, size, url, type, category,
            // description, hidden, status) is admin-curated and never
            // touched by sync. Zenith's contract has no image field.
            $existing->fill([
                'price' => $regularPrice,
                'discount_price' => $salePrice,
                'stock_status' => $inStock ? 'in_stock' : 'out_of_stock',
            ])->save();
            return 'updated';
        }

        if ($this->option('dry-run')) {
            $sale = $salePrice ? "/\${$salePrice}" : '';
            $this->line("  → NEW {$sku} / {$p['name']} / \${$regularPrice}{$sale} / " . ($inStock ? 'in stock' : 'OOS'));
            return 'new';
        }

        Product::create([
            'brand_id' => $brandId,
            'slug' => $this->uniqueSlug((string) $p['name'], $sku),
            'name' => (string) $p['name'],
            'price' => $regularPrice,
            'discount_price' => $salePrice,
            'product_url' => $productUrl,
            'size_mg' => $p['size'] ?? null,
            'stock_status' => $inStock ? 'in_stock' : 'out_of_stock',
            'external_id' => $sku,
            'hidden' => false,
            'status' => 'active',
            'product_category_id' => $this->categorize($p['name'] ?? '', $p['category'] ?? null),
            'product_type' => $this->inferType($p['category'] ?? null, (string) $p['name']),
        ]);
        return 'new';
    }

    /**
     * Append `?ref=PMAP` to the product URL for referral attribution.
     * The contract says to put it BEFORE the `#` fragment, so split on
     * the fragment, inject the query, re-attach the fragment.
     */
    private function appendRef(string $url): string
    {
        if (!$url) return $url;
        [$base, $frag] = array_pad(explode('#', $url, 2), 2, null);
        $sep = str_contains($base, '?') ? '&' : '?';
        $out = $base . $sep . 'ref=' . self::REF_CODE;
        return $frag !== null ? $out . '#' . $frag : $out;
    }

    /**
     * Match Zenith product names to our ProductCategory rows. Zenith's
     * `category` field in the API is coarse (peptide / blend / bac_water
     * / capsule) so we primarily match on the product name.
     */
    private function categorize(string $name, ?string $apiCategory): ?int
    {
        $lower = strtolower($name);
        // Direct compound name matches, longest first so "BPC-157/TB-500"
        // beats "BPC-157".
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
            'nad' => 'NAD+',
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
            'igf-1 lr3' => 'IGF-1 LR3',
            'igf-1' => 'IGF-1 LR3',
            '5-amino-1mq' => '5-AMINO-1MQ',
        ];
        foreach ($compounds as $needle => $cat) {
            if (str_contains($lower, $needle)) {
                return ProductCategory::where('name', $cat)->value('id');
            }
        }
        if ($apiCategory === 'bac_water') {
            return ProductCategory::where('name', 'Bacteriostatic Water')->value('id');
        }
        return null;
    }

    private function inferType(?string $apiCategory, string $name): string
    {
        return match ($apiCategory) {
            'capsule' => 'Capsule',
            'bac_water' => 'Bacteriostatic Water',
            'blend' => 'Peptide',
            'peptide' => 'Peptide',
            default => 'Peptide',
        };
    }

    private function uniqueSlug(string $name, string $sku): string
    {
        $base = Str::slug($name . '-' . $sku) ?: Str::slug($sku);
        $candidate = $base;
        $i = 1;
        while (Product::where('slug', $candidate)->exists()) {
            $candidate = "{$base}-" . self::BRAND_SLUG . ($i > 1 ? "-{$i}" : '');
            $i++;
            if ($i > 20) break;
        }
        return $candidate;
    }
}
