<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Centralizes ingestion: upserts incoming normalized product data into the
 * scraped_products staging table, and promotes staging rows into live products
 * either automatically (for trusted configs) or via admin review.
 *
 * Adapters (CSS scraper, WooCommerce API, XML feed) should normalize their
 * data into the shape accepted by upsertStaged() and call into this service.
 * They should never write to the products table directly.
 */
class IngestionService
{
    /**
     * Upsert a single normalized product into the staging table.
     *
     * $data keys (all optional unless noted):
     *   external_id (string)  - vendor-side unique id (required for stable upsert)
     *   source_url  (string)  - product page URL (fallback dedupe key)
     *   name        (string)  - product name (required)
     *   description (string)
     *   price       (numeric)
     *   discount_price (numeric)
     *   image_url   (string)
     *   stock_status(string)
     *   dosage      (string)
     *   raw_data    (array)
     */
    public function upsertStaged(ScrapingConfig $config, array $data): ?ScrapedProduct
    {
        if (empty($data['name'])) {
            Log::warning('IngestionService: skipping staged product with empty name', [
                'config_id' => $config->id,
            ]);
            return null;
        }

        $matchKeys = [
            'scraping_config_id' => $config->id,
        ];

        if (!empty($data['external_id'])) {
            $matchKeys['external_id'] = $data['external_id'];
        } elseif (!empty($data['source_url'])) {
            $matchKeys['source_url'] = $data['source_url'];
        } else {
            $matchKeys['name'] = $data['name'];
        }

        $existing = ScrapedProduct::where($matchKeys)->first();

        $attributes = [
            'scraping_config_id' => $config->id,
            'brand_id' => $config->vendor_id, // vendor_id is treated as brand_id
            'source_type' => $data['source_type'] ?? $config->type ?? ScrapedProduct::SOURCE_CSS_SCRAPE,
            'external_id' => $data['external_id'] ?? null,
            'source_url' => $data['source_url'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $this->normalizePrice($data['price'] ?? null),
            'discount_price' => $this->normalizePrice($data['discount_price'] ?? null),
            'image_url' => $data['image_url'] ?? null,
            'stock_status' => $data['stock_status'] ?? null,
            'dosage' => $data['dosage'] ?? null,
            'raw_data' => $data['raw_data'] ?? null,
            'last_scraped_at' => now(),
        ];

        if ($existing) {
            // Detect price change so analytics / promotion can react
            if ($existing->price !== null && $attributes['price'] !== null
                && (string) $existing->price !== (string) $attributes['price']) {
                $attributes['previous_price'] = $existing->price;
                $attributes['price_changed_at'] = now();
            }

            // Don't overwrite admin overrides
            if (!$existing->manual_override) {
                $existing->fill($attributes)->save();
            }

            $staged = $existing;
        } else {
            $attributes['status'] = ScrapedProduct::STATUS_PENDING;
            $staged = ScrapedProduct::create($attributes);
        }

        // Auto-promote trusted configs
        if ($config->auto_promote) {
            $this->promote($staged);
        }

        return $staged;
    }

    /**
     * Promote a staged product into the live products table.
     * If the staged row already has a product_id, updates the existing product.
     * Otherwise creates a new Product and links it.
     */
    public function promote(ScrapedProduct $staged): Product
    {
        return DB::transaction(function () use ($staged) {
            $productData = [
                'name' => $staged->name,
                'description' => $staged->description,
                'brand_id' => $staged->brand_id ?? $staged->scrapingConfig?->vendor_id,
                'product_category_id' => $staged->scrapingConfig?->product_category_id ?? null,
                'price' => $staged->price,
                'discount_price' => $staged->discount_price,
                'image_url' => $staged->image_url,
                'product_url' => $staged->source_url,
                'auto_scraped' => true,
                'last_scraped_at' => $staged->last_scraped_at ?? now(),
            ];

            if ($staged->product_id && $product = Product::find($staged->product_id)) {
                // Respect manual auto_update flag on existing products
                if ($product->auto_update || $staged->manual_override) {
                    $product->update($productData);
                }
            } else {
                $productData['slug'] = $this->uniqueSlug($staged->name);
                $product = Product::create($productData);
                $staged->product_id = $product->id;
            }

            $staged->status = ScrapedProduct::STATUS_APPROVED;
            $staged->save();

            return $product;
        });
    }

    public function reject(ScrapedProduct $staged, ?string $reason = null): void
    {
        $staged->status = ScrapedProduct::STATUS_REJECTED;
        $staged->save();

        Log::info('Staged product rejected', [
            'scraped_product_id' => $staged->id,
            'reason' => $reason,
        ]);
    }

    protected function normalizePrice($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return number_format((float) $value, 2, '.', '');
        }
        // Strip currency symbols, thousands separators
        $clean = preg_replace('/[^\d.]/', '', (string) $value);
        return $clean !== '' ? number_format((float) $clean, 2, '.', '') : null;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'product-' . Str::random(6);
        }
        if (strlen($base) > 180) {
            $base = substr($base, 0, 180);
        }

        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
            if ($i > 500) {
                $slug = $base . '-' . Str::random(6);
                break;
            }
        }
        return $slug;
    }
}
