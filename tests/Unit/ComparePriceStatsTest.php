<?php

namespace Tests\Unit;

use App\Support\ComparePriceStats;
use PHPUnit\Framework\TestCase;

class ComparePriceStatsTest extends TestCase
{
    public function test_snapshot_fixture_matches_the_qa_counts(): void
    {
        $stats = ComparePriceStats::fromProducts($this->snapshotProducts());

        $this->assertSame(126, $stats['listing_count']);
        $this->assertSame(28, $stats['vendor_count']);
        $this->assertSame(126, $stats['per_mg']['eligible_count']);
        $this->assertSame(28, $stats['per_mg']['eligible_vendor_count']);
        $this->assertSame(0, $stats['per_mg']['excluded_count']);
        $this->assertEquals(6.0, $stats['per_mg']['median_usd_per_mg']);
        $this->assertEquals(1.5, $stats['per_mg']['lowest']['usd_per_mg']);
        $this->assertSame('S1Research', $stats['per_mg']['lowest']['vendor']);
        $this->assertSame(100, $stats['per_mg']['lowest']['size_mg']);
        $this->assertEquals(150.0, $stats['per_mg']['lowest']['listed_usd']);
        $this->assertEquals(27.0, $stats['lowest_single_vial_listed']['listed_usd']);
        $this->assertSame(10, $stats['lowest_single_vial_listed']['size_mg']);
        $this->assertSame('S1Research', $stats['lowest_single_vial_listed']['vendor']);
        $this->assertSame(['USD'], $stats['currencies_present']);

        $this->assertSame(10, $stats['common_sizes'][0]['size_mg']);
        $this->assertSame(30, $stats['common_sizes'][0]['listings']);
        $this->assertSame(28, $stats['common_sizes'][0]['vendors']);
        $this->assertEquals(27.0, $stats['common_sizes'][0]['min_listed_usd']);
        $this->assertEquals(74.75, $stats['common_sizes'][0]['median_listed_usd']);
        $this->assertSame(30, $stats['common_sizes'][1]['size_mg']);
        $this->assertSame(25, $stats['common_sizes'][1]['listings']);
        $this->assertSame(22, $stats['common_sizes'][1]['vendors']);
        $this->assertEquals(70.0, $stats['common_sizes'][1]['min_listed_usd']);
        $this->assertEquals(170.0, $stats['common_sizes'][1]['median_listed_usd']);
        $this->assertSame(20, $stats['common_sizes'][2]['size_mg']);
        $this->assertSame(21, $stats['common_sizes'][2]['listings']);
        $this->assertSame(20, $stats['common_sizes'][2]['vendors']);
        $this->assertEquals(50.0, $stats['common_sizes'][2]['min_listed_usd']);
        $this->assertEquals(125.0, $stats['common_sizes'][2]['median_listed_usd']);

        $counts = [];
        $previous = -1.0;
        foreach ($stats['per_mg']['rows'] as $row) {
            $this->assertGreaterThanOrEqual($previous, $row['usd_per_mg']);
            $previous = $row['usd_per_mg'];
            $counts[$row['size_mg']] = ($counts[$row['size_mg']] ?? 0) + 1;
            $this->assertSame('USD', $row['currency_code']);
        }
        $chips = array_keys(array_filter($counts, fn (int $n) => $n >= 3));
        sort($chips);
        $this->assertSame([5, 10, 15, 20, 30, 40, 50, 60, 100], $chips);
        $this->assertSame(2, $counts[6]);
    }

    public function test_rules_run_in_order_and_each_reason_is_reported(): void
    {
        $products = [
            $this->row(['id' => 1, 'currency_code' => 'GBP', 'currency_symbol' => '£', 'product_type' => 'Kit', 'name' => 'Retatrutide blend 10mg', 'size_mg' => '10mg/2mg']),
            $this->row(['id' => 2, 'product_type' => 'Kit', 'name' => 'Retatrutide 10mg kit', 'size_mg' => '10mg']),
            $this->row(['id' => 3, 'name' => 'GLP-3R / CAG 12.5MG / 2.5MG', 'size_mg' => '12.5mg/2.5mg']),
            $this->row(['id' => 4, 'name' => 'Retatrutide 10mg × 10 pack', 'size_mg' => '10mg']),
            $this->row(['id' => 5, 'name' => 'Retatrutide Pen 10mg', 'size_mg' => '10mg']),
            $this->row(['id' => 6, 'name' => 'GLP-3 RT', 'size_mg' => '3mL']),
            $this->row(['id' => 7, 'name' => 'SA-3R', 'size_mg' => '10mg', 'effective_price' => 4.95]),
            $this->row(['id' => 8, 'name' => 'PG-3RT — 20mg', 'size_mg' => '10mg', 'effective_price' => 109.99]),
            $this->row(['id' => 5918, 'name' => 'GLP-4 Meta 12mg', 'size_mg' => '12mg', 'effective_price' => 99]),
            $this->row(['id' => 9, 'name' => 'SA-3R — 10mg Single Vial', 'size_mg' => '10mg', 'effective_price' => 52]),
            $this->row(['id' => 10, 'name' => 'R-GLP3 — 100mg', 'size_mg' => '100mg', 'effective_price' => 150]),
            $this->row(['id' => 11, 'name' => 'R-GLP3 — 100mg', 'size_mg' => '100mg', 'effective_price' => 150, 'product_url' => 'https://vendor.example/same']),
            $this->row(['id' => 12, 'name' => 'R-GLP3 — 100mg', 'size_mg' => '100mg', 'effective_price' => 150, 'product_url' => 'https://vendor.example/same']),
            $this->row(['id' => 13, 'name' => 'Cheap — 10mg', 'size_mg' => '10mg', 'effective_price' => 1.00]),
        ];

        // Give the floor check a median that the $1 / 10mg row falls under,
        // without moving the real single-vial rows.
        $products[] = $this->row(['id' => 14, 'name' => 'Anchor A — 10mg', 'size_mg' => '10mg', 'effective_price' => 80]);
        $products[] = $this->row(['id' => 15, 'name' => 'Anchor B — 10mg', 'size_mg' => '10mg', 'effective_price' => 80]);
        $products[] = $this->row(['id' => 16, 'name' => 'Anchor C — 10mg', 'size_mg' => '10mg', 'effective_price' => 80]);

        $stats = ComparePriceStats::fromProducts($products);
        $reasons = $stats['per_mg']['excluded_reasons'];

        $this->assertSame(1, $reasons['non_usd']);
        $this->assertSame(1, $reasons['kit_or_other_type']);
        $this->assertSame(3, $reasons['multi_unit_or_blend']);
        $this->assertSame(1, $reasons['size_unparsable']);
        $this->assertSame(1, $reasons['no_mg_in_name']);
        $this->assertSame(1, $reasons['name_size_mismatch']);
        $this->assertSame(1, $reasons['other']);
        $this->assertSame(1, $reasons['below_median_floor']);

        $ids = array_column($stats['per_mg']['rows'], 'id');
        $this->assertNotContains(5918, $ids);
        $this->assertNotContains(7, $ids);
        $this->assertContains(9, $ids);
        $this->assertContains(10, $ids);
        $this->assertSame(1, count(array_filter($ids, fn ($id) => in_array($id, [11, 12], true))));
        $this->assertEquals(52.0, $stats['lowest_single_vial_listed']['listed_usd']);
        $this->assertNotEquals(4.95, $stats['lowest_single_vial_listed']['listed_usd']);

        $gbp = $products[0];
        $this->assertSame('£', $gbp['currency_symbol']);
        $this->assertNotContains(1, $ids);
    }

    public function test_single_vial_name_is_not_treated_as_a_multi_vial_pack(): void
    {
        $stats = ComparePriceStats::fromProducts([
            $this->row(['id' => 1089, 'name' => 'SA-3R — 10mg Single Vial', 'size_mg' => '10mg', 'effective_price' => 52]),
        ]);

        $this->assertSame(1, $stats['per_mg']['eligible_count']);
        $this->assertSame(0, $stats['per_mg']['excluded_reasons']['multi_unit_or_blend']);
    }

    public function test_footnotes_use_the_confirmed_size_wording(): void
    {
        $stats = ComparePriceStats::fromProducts([
            $this->row(['id' => 1, 'name' => 'Example — 10mg', 'size_mg' => '10mg', 'effective_price' => 40]),
            $this->row(['id' => 5918, 'name' => 'GLP-4 Meta 12mg', 'size_mg' => '12mg', 'effective_price' => 99]),
        ]);
        $notes = ComparePriceStats::footnotes($stats, '2 hours ago');

        $this->assertSame(
            'Per-mg figures use USD single-vial listings whose size we could confirm (1 of 2 listings). Multi-vial kits, blends, pens, non-USD listings, and rows without a confirmed vial size are left out of per-mg math but stay in the full table. Prices last checked 2 hours ago. Listed prices come from vendor catalogs and can change at any time.',
            $notes['summary']
        );
        $this->assertStringContainsString('1 listing awaiting a category review', $notes['table']);
        $this->assertStringContainsString('Prices last checked 2 hours ago.', $notes['table']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function row(array $overrides): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'Example — 10mg',
            'product_type' => 'Peptide',
            'slug' => 'example-10mg',
            'effective_price' => 40,
            'currency_code' => 'USD',
            'currency_symbol' => '$',
            'product_url' => 'https://vendor.example/p/'.$overrides['id'],
            'brand_name' => 'Example Research',
            'brand_slug' => 'example-research',
            'size_mg' => '10mg',
        ], $overrides);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function snapshotProducts(): array
    {
        $path = dirname(__DIR__).'/Fixtures/retatrutide-price-per-mg-snapshot-2026-10-08.csv';
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $record = array_combine($header, $data);
            $path = parse_url($record['peptidemap_url'], PHP_URL_PATH);
            $parts = explode('/', trim((string) $path, '/'));
            $size = (float) $record['vial_mg'];
            $sizeField = (fmod($size, 1.0) === 0.0 ? (string) (int) $size : (string) $size).'mg';
            $id = (int) $parts[3];
            $rows[] = [
                'id' => $id,
                'name' => $record['listing'],
                'product_type' => 'Peptide',
                'slug' => $parts[2],
                'effective_price' => (float) $record['listed_usd'],
                'currency_code' => 'USD',
                'currency_symbol' => '$',
                'product_url' => 'https://vendor.example/p/'.$id,
                'brand_name' => $record['vendor'],
                'brand_slug' => $parts[1],
                'size_mg' => $sizeField,
            ];
        }
        fclose($handle);

        return $rows;
    }
}
