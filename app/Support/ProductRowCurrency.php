<?php

namespace App\Support;

use App\Models\Product;

/**
 * Attaches currency_code + currency_symbol to a product row before it
 * ships to the frontend grid, derived from the brand's vendor-settings
 * country name via App\Support\Currency.
 *
 * The product itself doesn't know its own currency — prices are stored
 * as raw numbers in whatever currency the vendor's checkout uses.
 * Without this enrichment every price falls back to '$', which is
 * wrong for every non-US vendor (OXpeptides EUR, VialVerse GBP,
 * Vancouver Island / Vistara CAD, Capital Precision AUD).
 *
 * Caller must eager-load brand.vendorSetting.location or we miss the
 * country and default to USD.
 *
 * Also hosts the listing-row helpers shared by the product listing and
 * compare pages: the lean card row, price-per-mg, duplicate collapse
 * and best-value ranking.
 */
class ProductRowCurrency
{
    /**
     * Resolve ['USD','$'] for a product.
     *
     * @return array{0: string, 1: string}
     */
    public static function resolve(Product $product): array
    {
        $country = $product->brand?->vendorSetting?->location?->name;

        return Currency::forCountry($country);
    }

    /**
     * Enrich a single product (array|Model) with currency_code and
     * currency_symbol fields. Accepts either so callers can use the
     * paginator ->through() pattern without hand-converting.
     *
     * @param  Product|array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public static function tag(Product|array $product): array
    {
        if ($product instanceof Product) {
            [$code, $symbol] = self::resolve($product);
            $row = $product->toArray();
        } else {
            $row = $product;
            $country = $row['brand']['vendor_setting']['location']['name']
                ?? $row['brand']['vendorSetting']['location']['name']
                ?? null;
            [$code, $symbol] = Currency::forCountry($country);
        }

        $row['currency_code'] = $code;
        $row['currency_symbol'] = $symbol;

        // Prefer the locally-mirrored image when we have one, falling back
        // to the vendor's own URL otherwise. Vendors (Bioedge, Certified-Pep)
        // run hotlink / geo / UA firewalls that 403 cross-origin requests
        // from some viewer networks, so hotlinking is unreliable. The mirror
        // lives on /storage/vendor-mirror and is populated by
        // products:sync-external-images.
        if (!empty($row['mirrored_image_url'])) {
            $row['image_url'] = $row['mirrored_image_url'];
        }

        return $row;
    }

    /**
     * The fields a product card renders, and nothing else. Full model
     * serialization shipped descriptions, per-product SEO text and the
     * whole category row (including agency FAQ/intro copy) on every card.
     * product_url is included for duplicate detection; callers strip it
     * before the row leaves the server.
     *
     * Expects brand (id, name, slug), brand.vendorSetting (coupon fields
     * and location_id), brand.vendorSetting.location and category (id,
     * name, slug) to be eager-loaded.
     *
     * @return array<string, mixed>
     */
    public static function cardRow(Product $product): array
    {
        [$code, $symbol] = self::resolve($product);
        $brand = $product->brand;
        $category = $product->relationLoaded('category') ? $product->category : null;

        return self::withPricing([
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'display_name' => $product->display_name,
            'image_url' => $product->mirrored_image_url ?: $product->image_url,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'size_mg' => $product->size_mg,
            'availability' => $product->availability,
            'purity' => $product->purity,
            'product_type' => $product->product_type,
            'rating_average' => $product->rating_average,
            'rating_count' => $product->rating_count,
            'product_url' => $product->product_url,
            'brand_id' => $product->brand_id,
            'brand_discount_percent' => $product->brand_discount_percent,
            'brand_coupon_code' => $product->brand_coupon_code,
            'currency_code' => $code,
            'currency_symbol' => $symbol,
            'brand' => $brand ? [
                'id' => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug,
            ] : null,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ] : null,
        ]);
    }

    /**
     * Adds the price the visitor pays (vendor sale price, then the brand's
     * Peptidemap code), its approximate USD value, and per-mg figures.
     * Per-mg is only set for single vials with a parseable size; kits,
     * packs, blends and non-vial formats get null rather than a misleading
     * number. Existing final_price values are respected.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function withPricing(array $row): array
    {
        $price = isset($row['price']) && is_numeric($row['price']) ? (float) $row['price'] : 0.0;
        $sale = isset($row['discount_price']) && is_numeric($row['discount_price']) ? (float) $row['discount_price'] : 0.0;
        $listed = ($sale > 0 && ($price <= 0 || $sale < $price)) ? $sale : $price;

        if (isset($row['final_price']) && is_numeric($row['final_price']) && (float) $row['final_price'] > 0) {
            $final = (float) $row['final_price'];
        } else {
            $pct = isset($row['brand_discount_percent']) && is_numeric($row['brand_discount_percent'])
                ? (float) $row['brand_discount_percent']
                : null;
            $final = ($pct !== null && $pct > 0 && $pct < 100 && $listed > 0)
                ? round($listed * (1 - $pct / 100), 2)
                : $listed;
        }

        $code = (string) ($row['currency_code'] ?? 'USD');
        $usdFinal = $final > 0 ? Currency::toUsd($final, $code) : null;
        $mg = ComparePriceStats::isMultiUnitListing($row)
            ? null
            : ComparePriceStats::parseSizeMg(isset($row['size_mg']) ? (string) $row['size_mg'] : null);

        $row['final_price'] = $final > 0 ? round($final, 2) : null;
        $row['usd_final_price'] = $usdFinal !== null ? round($usdFinal, 2) : null;
        $row['size_mg_value'] = $mg;
        $row['price_per_mg'] = ($mg !== null && $final > 0) ? round($final / $mg, 2) : null;
        $row['usd_per_mg'] = ($mg !== null && $usdFinal !== null) ? round($usdFinal / $mg, 4) : null;

        return $row;
    }

    /**
     * Drop repeat rows for one offer: same vendor, outbound URL, size and
     * price. Scrapers sometimes store a listing many times (16 identical
     * rows for one Instant Peptides SKU). The first row wins, so sort
     * before collapsing. Rows without a URL are kept as-is.
     *
     * @param  iterable<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function collapseDuplicates(iterable $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $url = strtolower(trim((string) ($row['product_url'] ?? '')));
            if ($url === '') {
                $out[] = $row;
                continue;
            }
            $vendor = (string) ($row['brand_id'] ?? $row['brand']['id'] ?? $row['brand_slug'] ?? $row['brand_name'] ?? '');
            $size = strtolower((string) preg_replace('/\s+/', '', (string) ($row['size_mg'] ?? '')));
            $amount = $row['final_price'] ?? $row['discount_price'] ?? $row['price'] ?? 0;
            $key = implode("\x1e", [$vendor, $url, $size, number_format((float) $amount, 2, '.', '')]);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Flags single-vial rows priced under a quarter of the median for their
     * size (USD, rows of one compound) when that size has at least four
     * priced rows. A $4.95 10mg retatrutide among ~$100 listings is a stale
     * or mis-scraped price: the row still renders, but it cannot win a
     * badge, the cheapest slot or a schema lowPrice, and its $/mg is hidden.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function flagPriceOutliers(array $rows): array
    {
        $groups = [];
        foreach ($rows as $i => $row) {
            $rows[$i]['price_outlier'] = false;
            $mg = $row['size_mg_value'] ?? null;
            $usd = $row['usd_final_price'] ?? null;
            if ($mg === null || $usd === null || (float) $usd <= 0) {
                continue;
            }
            $groups[number_format((float) $mg, 4, '.', '')][] = $i;
        }

        foreach ($groups as $indexes) {
            if (count($indexes) < 4) {
                continue;
            }
            $prices = array_map(fn ($i) => (float) $rows[$i]['usd_final_price'], $indexes);
            sort($prices, SORT_NUMERIC);
            $count = count($prices);
            $mid = intdiv($count, 2);
            $median = $count % 2 === 1 ? $prices[$mid] : ($prices[$mid - 1] + $prices[$mid]) / 2;
            $floor = $median * 0.25;
            foreach ($indexes as $i) {
                if ((float) $rows[$i]['usd_final_price'] < $floor) {
                    $rows[$i]['price_outlier'] = true;
                    $rows[$i]['price_per_mg'] = null;
                    $rows[$i]['usd_per_mg'] = null;
                }
            }
        }

        return $rows;
    }

    /**
     * Flags price outliers, clears per-mg figures below a quarter of the
     * median $/mg (almost always a mislabeled size, the same floor
     * ComparePriceStats uses), then flags the lowest $/mg listing in every
     * size that has at least two priced single-vial listings. Comparison
     * runs on USD values.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function rankValue(array $rows): array
    {
        $rows = self::flagPriceOutliers($rows);
        $values = [];
        foreach ($rows as $row) {
            if (isset($row['usd_per_mg']) && $row['usd_per_mg'] !== null) {
                $values[] = (float) $row['usd_per_mg'];
            }
        }

        if ($values !== []) {
            sort($values, SORT_NUMERIC);
            $count = count($values);
            $mid = intdiv($count, 2);
            $median = $count % 2 === 1 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
            $floor = $median * 0.25;
            foreach ($rows as $i => $row) {
                if (isset($row['usd_per_mg']) && $row['usd_per_mg'] !== null && (float) $row['usd_per_mg'] < $floor) {
                    $rows[$i]['usd_per_mg'] = null;
                    $rows[$i]['price_per_mg'] = null;
                }
            }
        }

        $best = [];
        $counts = [];
        foreach ($rows as $i => $row) {
            $rows[$i]['best_value'] = false;
            $perMg = $row['usd_per_mg'] ?? null;
            $mg = $row['size_mg_value'] ?? null;
            if ($perMg === null || $mg === null) {
                continue;
            }
            $key = number_format((float) $mg, 4, '.', '');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            if (! isset($best[$key]) || (float) $perMg < (float) $rows[$best[$key]]['usd_per_mg']) {
                $best[$key] = $i;
            }
        }
        foreach ($best as $key => $i) {
            if ($counts[$key] >= 2) {
                $rows[$i]['best_value'] = true;
            }
        }

        return $rows;
    }

    /**
     * Cheapest-first ordering on what the visitor pays, compared in USD so
     * a GBP or RON price is not ranked as if it were dollars. Rows without
     * a USD figure sort after priced rows; price outliers sort last.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function sortByUsdPrice(array $rows): array
    {
        usort($rows, function (array $a, array $b): int {
            $outlier = (int) ! empty($a['price_outlier']) <=> (int) ! empty($b['price_outlier']);
            if ($outlier !== 0) {
                return $outlier;
            }
            $av = $a['usd_final_price'] ?? null;
            $bv = $b['usd_final_price'] ?? null;
            if ($av === null && $bv === null) {
                return 0;
            }
            if ($av === null) {
                return 1;
            }
            if ($bv === null) {
                return -1;
            }

            return (float) $av <=> (float) $bv;
        });

        return $rows;
    }

    /**
     * Best value first: lowest USD $/mg, then rows without a per-mg figure
     * by USD price; price outliers sort last.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public static function sortByValue(array $rows): array
    {
        usort($rows, function (array $a, array $b): int {
            $outlier = (int) ! empty($a['price_outlier']) <=> (int) ! empty($b['price_outlier']);
            if ($outlier !== 0) {
                return $outlier;
            }
            $av = $a['usd_per_mg'] ?? null;
            $bv = $b['usd_per_mg'] ?? null;
            if ($av !== null && $bv !== null) {
                return (float) $av <=> (float) $bv;
            }
            if ($av !== null) {
                return -1;
            }
            if ($bv !== null) {
                return 1;
            }
            $ap = $a['usd_final_price'] ?? INF;
            $bp = $b['usd_final_price'] ?? INF;

            return (float) $ap <=> (float) $bp;
        });

        return $rows;
    }
}
