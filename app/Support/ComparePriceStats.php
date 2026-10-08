<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Per-mg price stats for a compare page, computed from the rows
 * productsForCategory() already returns. No new columns, no FX.
 *
 * A row counts only when every rule below holds. Rules run in order;
 * the first one a row fails is its exclusion reason.
 *
 * 1. currency_code is USD (else non_usd). Currency comes from the vendor
 *    country via Currency::forCountry, not from the listing.
 * 2. product_type is Peptide (else kit_or_other_type).
 * 3. Blend / multi-unit check, before the size check (else multi_unit_or_blend).
 * 4. size_mg is one number plus "mg" (else size_unparsable).
 * 5. The listing name contains an mg figure equal to that size
 *    (else no_mg_in_name or name_size_mismatch).
 * 6. $/mg is not below 25% of the median $/mg of the rows left after 1–5
 *    (else below_median_floor).
 * 7. Exact duplicates (vendor, listing name, size, listed price, outbound
 *    product_url) collapse to one row. Collapsed copies are not a named reason.
 * 8. Product ids on the off-category review list (else other).
 *
 * Price basis is the listed price (effective_price): retail after a vendor
 * sale price, before Peptidemap codes.
 */
class ComparePriceStats
{
    /** @var list<int> */
    public const OFF_CATEGORY_REVIEW_IDS = [5918];

    /**
     * Blend check runs before the size check. "vial" is not "vials", so a
     * single-vial name stays eligible. The slash is written as "/" because
     * the pattern is delimited with "#".
     */
    private const MULTI_UNIT_PATTERN = '#(pack|kit|vials|bundle|box|×|\bx\s*\d|\d\s*x\s*\d|mgx|pen\b|blend|cag|/|mix|tablet|bulk)#iu';

    private const SIZE_PATTERN = '/^\s*(\d+(?:\.\d+)?)\s*mg\s*$/i';

    private const NAME_MG_PATTERN = '/(\d+(?:\.\d+)?)\s*mg/iu';

    /** @var list<string> */
    private const REASON_KEYS = [
        'non_usd',
        'kit_or_other_type',
        'multi_unit_or_blend',
        'size_unparsable',
        'no_mg_in_name',
        'name_size_mismatch',
        'below_median_floor',
        'other',
    ];

    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $products
     * @return array<string, mixed>
     */
    public static function fromProducts(Collection|array $products): array
    {
        $rows = $products instanceof Collection ? $products->all() : array_values($products);

        $reasons = array_fill_keys(self::REASON_KEYS, 0);
        $currencies = [];
        $vendors = [];
        $candidates = [];

        foreach ($rows as $product) {
            if ($product instanceof \Illuminate\Contracts\Support\Arrayable) {
                $product = $product->toArray();
            }
            $product = (array) $product;

            $code = strtoupper(trim((string) ($product['currency_code'] ?? '')));
            if ($code !== '' && ! in_array($code, $currencies, true)) {
                $currencies[] = $code;
            }

            $vendor = trim((string) ($product['brand_name'] ?? ''));
            if ($vendor !== '') {
                $vendors[$vendor] = true;
            }

            $reason = self::exclusionReason($product);
            if ($reason !== null) {
                $reasons[$reason]++;
                continue;
            }

            $candidates[] = self::eligibleRow($product);
        }

        $medianForFloor = self::median(array_column($candidates, 'usd_per_mg'));
        $floor = $medianForFloor === null ? null : $medianForFloor * 0.25;
        $afterFloor = [];
        foreach ($candidates as $row) {
            if ($floor !== null && $row['usd_per_mg'] < $floor) {
                $reasons['below_median_floor']++;
                continue;
            }
            $afterFloor[] = $row;
        }

        $afterReview = [];
        foreach ($afterFloor as $row) {
            if (in_array((int) $row['id'], self::OFF_CATEGORY_REVIEW_IDS, true)) {
                $reasons['other']++;
                continue;
            }
            $afterReview[] = $row;
        }

        $seen = [];
        $eligible = [];
        foreach ($afterReview as $row) {
            $key = implode("\x1e", [
                $row['vendor'],
                $row['listing'],
                (string) $row['size_mg'],
                number_format($row['listed_usd'], 2, '.', ''),
                $row['outbound_url'],
            ]);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            unset($row['outbound_url']);
            $eligible[] = $row;
        }

        usort($eligible, function (array $a, array $b): int {
            $cmp = $a['usd_per_mg'] <=> $b['usd_per_mg'];
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcasecmp($a['vendor'], $b['vendor']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp($a['listing'], $b['listing']);
        });

        $listingCount = count($rows);
        $eligibleCount = count($eligible);

        return [
            'listing_count' => $listingCount,
            'vendor_count' => count($vendors),
            'per_mg' => [
                'eligible_count' => $eligibleCount,
                'eligible_vendor_count' => self::uniqueVendors($eligible),
                'excluded_count' => $listingCount - $eligibleCount,
                'excluded_reasons' => $reasons,
                'lowest' => $eligible === [] ? null : self::lowestPerMg($eligible[0]),
                'median_usd_per_mg' => self::median(array_column($eligible, 'usd_per_mg')),
                'rows' => $eligible,
            ],
            'lowest_single_vial_listed' => self::lowestSingleVial($eligible),
            'common_sizes' => self::commonSizes($eligible),
            'currencies_present' => $currencies,
        ];
    }

    /**
     * Footnote sentences for the summary strip and the per-mg table.
     * Tokens are filled from the stats array. prices_updated_human is the
     * same "last checked" string the page shows elsewhere.
     *
     * @param  array<string, mixed>  $stats
     * @return array{summary: string, table: string}
     */
    public static function footnotes(array $stats, string $pricesUpdatedHuman): array
    {
        $reasons = $stats['per_mg']['excluded_reasons'];
        $eligible = (int) $stats['per_mg']['eligible_count'];
        $listings = (int) $stats['listing_count'];
        $unconfirmed = (int) $reasons['no_mg_in_name']
            + (int) $reasons['name_size_mismatch']
            + (int) $reasons['size_unparsable'];
        $other = (int) $reasons['other'];
        $otherNoun = $other === 1 ? 'listing' : 'listings';

        return [
            'summary' => "Per-mg figures use USD single-vial listings whose size we could confirm ({$eligible} of {$listings} listings). Multi-vial kits, blends, pens, non-USD listings, and rows without a confirmed vial size are left out of per-mg math but stay in the full table. Prices last checked {$pricesUpdatedHuman}. Listed prices come from vendor catalogs and can change at any time.",
            'table' => "{$eligible} of {$listings} listings are included. Left out of per-mg math: {$reasons['multi_unit_or_blend']} multi-vial or blend listings, {$reasons['non_usd']} non-USD listings, {$unconfirmed} listings without a confirmed vial size, {$reasons['kit_or_other_type']} kits, pens, or other formats, and {$other} {$otherNoun} awaiting a category review. Prices last checked {$pricesUpdatedHuman}.",
        ];
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private static function exclusionReason(array $product): ?string
    {
        $code = strtoupper(trim((string) ($product['currency_code'] ?? '')));
        if ($code !== 'USD') {
            return 'non_usd';
        }

        if (($product['product_type'] ?? null) !== 'Peptide') {
            return 'kit_or_other_type';
        }

        $name = (string) ($product['name'] ?? '');
        $sizeRaw = (string) ($product['size_mg'] ?? '');
        if (preg_match(self::MULTI_UNIT_PATTERN, $name) || str_contains($sizeRaw, '/')) {
            return 'multi_unit_or_blend';
        }

        if (! preg_match(self::SIZE_PATTERN, $sizeRaw, $sizeMatch)) {
            return 'size_unparsable';
        }

        $size = (float) $sizeMatch[1];
        if ($size <= 0) {
            return 'size_unparsable';
        }

        $listed = round((float) ($product['effective_price'] ?? 0), 2);
        if ($listed <= 0) {
            return 'size_unparsable';
        }

        $found = preg_match_all(self::NAME_MG_PATTERN, $name, $nameMatches);
        if (! $found) {
            return 'no_mg_in_name';
        }

        foreach ($nameMatches[1] as $figure) {
            if (abs((float) $figure - $size) < 0.001) {
                return null;
            }
        }

        return 'name_size_mismatch';
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private static function eligibleRow(array $product): array
    {
        preg_match(self::SIZE_PATTERN, (string) $product['size_mg'], $sizeMatch);
        $size = (float) $sizeMatch[1];
        $sizeMg = abs($size - round($size)) < 0.001 ? (int) round($size) : $size;
        $listed = round((float) $product['effective_price'], 2);
        $perMg = round($listed / $size, 2);
        $brandSlug = (string) ($product['brand_slug'] ?? '');
        $slug = (string) ($product['slug'] ?? '');
        $id = $product['id'] ?? '';

        return [
            'id' => $id,
            'vendor' => trim((string) ($product['brand_name'] ?? '')),
            'brand_slug' => $brandSlug,
            'listing' => (string) ($product['name'] ?? ''),
            'size_mg' => $sizeMg,
            'listed_usd' => $listed,
            'usd_per_mg' => $perMg,
            'currency_code' => 'USD',
            'url' => "/product/{$brandSlug}/{$slug}/{$id}",
            'outbound_url' => trim((string) ($product['product_url'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function lowestPerMg(array $row): array
    {
        return [
            'usd_per_mg' => $row['usd_per_mg'],
            'vendor' => $row['vendor'],
            'listing' => $row['listing'],
            'size_mg' => $row['size_mg'],
            'listed_usd' => $row['listed_usd'],
            'url' => $row['url'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $eligible
     * @return array<string, mixed>|null
     */
    private static function lowestSingleVial(array $eligible): ?array
    {
        if ($eligible === []) {
            return null;
        }

        $byPrice = $eligible;
        usort($byPrice, function (array $a, array $b): int {
            $cmp = $a['listed_usd'] <=> $b['listed_usd'];
            if ($cmp !== 0) {
                return $cmp;
            }
            $cmp = strcasecmp($a['vendor'], $b['vendor']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp($a['listing'], $b['listing']);
        });

        $row = $byPrice[0];

        return [
            'listed_usd' => $row['listed_usd'],
            'size_mg' => $row['size_mg'],
            'vendor' => $row['vendor'],
            'url' => $row['url'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $eligible
     * @return list<array<string, mixed>>
     */
    private static function commonSizes(array $eligible): array
    {
        $groups = [];
        foreach ($eligible as $row) {
            $size = $row['size_mg'];
            if (! isset($groups[$size])) {
                $groups[$size] = [
                    'size_mg' => $size,
                    'listings' => 0,
                    'vendors' => [],
                    'prices' => [],
                ];
            }
            $groups[$size]['listings']++;
            $groups[$size]['vendors'][$row['vendor']] = true;
            $groups[$size]['prices'][] = $row['listed_usd'];
        }

        $out = [];
        foreach ($groups as $group) {
            $out[] = [
                'size_mg' => $group['size_mg'],
                'listings' => $group['listings'],
                'vendors' => count($group['vendors']),
                'min_listed_usd' => min($group['prices']),
                'median_listed_usd' => self::median($group['prices']),
            ];
        }

        usort($out, function (array $a, array $b): int {
            $cmp = $b['listings'] <=> $a['listings'];
            if ($cmp !== 0) {
                return $cmp;
            }

            return $a['size_mg'] <=> $b['size_mg'];
        });

        return array_slice($out, 0, 3);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private static function uniqueVendors(array $rows): int
    {
        $vendors = [];
        foreach ($rows as $row) {
            if ($row['vendor'] !== '') {
                $vendors[$row['vendor']] = true;
            }
        }

        return count($vendors);
    }

    /**
     * @param  list<float|int>  $values
     */
    private static function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        $mid = intdiv($count, 2);
        if ($count % 2 === 1) {
            return round((float) $values[$mid], 2);
        }

        return round(((float) $values[$mid - 1] + (float) $values[$mid]) / 2, 2);
    }
}
