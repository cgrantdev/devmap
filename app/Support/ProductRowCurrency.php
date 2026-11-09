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

        return $row;
    }
}
