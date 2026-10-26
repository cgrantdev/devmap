<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Julia 10/27 — Recherche Logistique has two products that our
 * discovery crawler keeps missing: Kisspeptin 10mg and Tirzepatide
 * 20mg (listed on their site as GLP-2 20mg). This command fetches
 * each URL, extracts price + stock, and inserts them under the
 * matching category.
 *
 * Idempotent: re-running does nothing if the SKU is already there.
 */
class AddRechercheMissingProducts extends Command
{
    protected $signature = 'recherche:add-missing';
    protected $description = 'Add Recherche Logistique Kisspeptin 10mg + Tirzepatide 20mg from vendor URLs.';

    private const TARGETS = [
        [
            'url' => 'https://recherchelogistique.ca/product/kisspeptin-10-mg/',
            'name' => 'Kisspeptin 10mg',
            'size' => '10mg',
            'category_slug' => 'kisspeptin',
            'sku' => 'RL-kisspeptin-10mg',
        ],
        [
            'url' => 'https://recherchelogistique.ca/product/glp-2-20-mg/',
            'name' => 'Tirzepatide 20mg',
            'size' => '20mg',
            'category_slug' => 'tirzepatide',
            'sku' => 'RL-tirzepatide-20mg',
        ],
    ];

    public function handle(): int
    {
        $brand = Brand::where('slug', 'recherche-logistique')->first();
        if (!$brand) {
            $this->error("Brand 'recherche-logistique' not found.");
            return self::FAILURE;
        }

        foreach (self::TARGETS as $t) {
            $existing = Product::where('brand_id', $brand->id)
                ->where(fn ($q) => $q->where('external_id', $t['sku'])->orWhere('product_url', $t['url']))
                ->first();
            if ($existing) {
                $this->line("  ✓ Already present: {$t['name']} (id={$existing->id})");
                continue;
            }

            $price = $this->fetchPrice($t['url']);
            if ($price === null) {
                $this->warn("  ✗ Could not fetch price for {$t['name']} ({$t['url']})");
                continue;
            }

            $cat = ProductCategory::where('slug', $t['category_slug'])->first();
            $product = Product::create([
                'brand_id' => $brand->id,
                'slug' => $this->uniqueSlug(Str::slug($t['name'] . '-recherche')),
                'name' => $t['name'],
                'price' => $price,
                'product_url' => $t['url'],
                'size_mg' => $t['size'],
                'stock_status' => 'in_stock',
                'external_id' => $t['sku'],
                'hidden' => false,
                'status' => 'active',
                'product_category_id' => $cat?->id,
                'product_type' => 'Peptide',
            ]);
            $this->info("  → Added {$t['name']} @ \${$price} (id={$product->id})");
        }

        return self::SUCCESS;
    }

    private function fetchPrice(string $url): ?float
    {
        $resp = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/129.0.0.0 Safari/537.36',
            ])->get($url);
        if (!$resp->successful()) return null;
        $html = $resp->body();
        // Woo meta: <meta property="product:price:amount" content="155.00">
        if (preg_match('~product:price:amount"\s+content="([0-9.]+)"~', $html, $m)) {
            return (float) $m[1];
        }
        // Fallback: first woocommerce-Price-amount
        if (preg_match('~woocommerce-Price-amount[^>]*>.*?([0-9]+(?:\.[0-9]+)?)~s', $html, $m)) {
            return (float) $m[1];
        }
        return null;
    }

    private function uniqueSlug(string $base): string
    {
        $candidate = $base;
        $i = 2;
        while (Product::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $i++;
            if ($i > 20) break;
        }
        return $candidate;
    }
}
