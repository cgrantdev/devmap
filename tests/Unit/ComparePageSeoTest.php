<?php

namespace Tests\Unit;

use App\Support\ComparePageSeo;
use PHPUnit\Framework\TestCase;

class ComparePageSeoTest extends TestCase
{
    public function test_pages_without_a_per_mg_table_do_not_promise_one(): void
    {
        $this->assertSame(
            'GHK-Cu Prices: 44 Vendors Compared',
            ComparePageSeo::title('GHK-Cu', 44)
        );
        $this->assertSame(
            'GHK-Cu Prices: 44 Vendors Compared — Peptidemap',
            ComparePageSeo::renderedTitle('GHK-Cu', 44)
        );
        $this->assertSame(
            'BPC-157 Prices: 43 Vendors Compared — Peptidemap',
            ComparePageSeo::renderedTitle('BPC-157', 43)
        );
        $this->assertSame(
            'Sterile Water Prices: 3 Vendors Compared — Peptidemap',
            ComparePageSeo::renderedTitle('Sterile Water', 3)
        );
        $this->assertSame(
            'Compare GHK-Cu research listings from 44 vendors by vial size and listed price. Catalog data only: RUO, and Peptidemap sells nothing.',
            ComparePageSeo::description('GHK-Cu', 44)
        );
        $this->assertStringNotContainsString('per mg', ComparePageSeo::renderedTitle('GHK-Cu', 44));
        $this->assertStringNotContainsString('per mg', ComparePageSeo::description('BPC-157', 43));
    }

    public function test_retatrutide_keeps_the_per_mg_wording(): void
    {
        $this->assertSame(
            'Retatrutide Price per mg: 43 Vendors Compared — Peptidemap',
            ComparePageSeo::renderedTitle('Retatrutide', 43, true)
        );
        $this->assertSame(
            'Retatrutide Price per mg: 1 Vendor Compared — Peptidemap',
            ComparePageSeo::renderedTitle('Retatrutide', 1, true)
        );
        $this->assertSame(
            'Compare Retatrutide research listings from 43 vendors by vial price and price per mg. Catalog data only: RUO, and Peptidemap sells nothing.',
            ComparePageSeo::description('Retatrutide', 43, true)
        );
    }

    public function test_empty_catalogs_use_a_vendor_comparison_title(): void
    {
        $title = 'Retatrutide Vendor Comparison — Peptidemap';
        $meta = 'There are no priced Retatrutide listings on Peptidemap right now. Catalog data only: RUO, and Peptidemap sells nothing.';

        $this->assertSame($title, ComparePageSeo::renderedTitle('Retatrutide', 0, true));
        $this->assertSame($title, ComparePageSeo::renderedTitle('Retatrutide', 0));
        $this->assertSame($meta, ComparePageSeo::description('Retatrutide', 0, true));
        $this->assertSame(
            'There are no priced Sterile Water listings on Peptidemap right now. Catalog data only: RUO, and Peptidemap sells nothing.',
            ComparePageSeo::description('Sterile Water', 0)
        );
        $this->assertStringNotContainsString('per mg', $title);
        $this->assertStringNotContainsString('$', $meta);
        $this->assertLessThanOrEqual(60, mb_strlen($title));
        $this->assertLessThanOrEqual(155, mb_strlen($meta));
    }

    public function test_long_names_fall_back_to_prices_before_an_ellipsis(): void
    {
        $this->assertSame(
            'Thymosin Beta-4 Fragment 1-4 Prices: 2 Vendors — Peptidemap',
            ComparePageSeo::renderedTitle('Thymosin Beta-4 Fragment 1-4', 2)
        );
        $this->assertSame(
            'Retatrutide / Cagrilintide Blend Prices — Peptidemap',
            ComparePageSeo::renderedTitle('Retatrutide / Cagrilintide Blend', 4)
        );
        $this->assertSame(
            'Retatrutide / Tirzepatide Blend Prices — Peptidemap',
            ComparePageSeo::renderedTitle('Retatrutide / Tirzepatide Blend', 4, true)
        );
        $this->assertSame(
            str_repeat('A', 39).'… Prices',
            ComparePageSeo::title(str_repeat('A', 80), 4)
        );
    }

    public function test_ellipsis_does_not_leave_a_dangling_separator(): void
    {
        $name = 'CJC-1295 + '.str_repeat('W', 27).'+More blend';
        $title = ComparePageSeo::renderedTitle($name, 4);

        $this->assertLessThanOrEqual(60, mb_strlen($title));
        $this->assertStringNotContainsString('+…', $title);
        $this->assertStringEndsWith('Prices — Peptidemap', $title);
    }

    public function test_metas_use_the_neutral_template_and_a_shorter_fallback(): void
    {
        $ghk = 'Compare GHK-Cu research listings from 44 vendors by vial size and listed price. Catalog data only: RUO, and Peptidemap sells nothing.';
        $bpc = 'Compare BPC-157 research listings from 43 vendors by vial size and listed price. Catalog data only: RUO, and Peptidemap sells nothing.';
        $thymosin = 'Compare Thymosin Beta-4 Fragment 1-4 research listings from 2 vendors by vial size and listed price. Catalog data only: RUO, and Peptidemap sells nothing.';
        $blend = 'Compare Retatrutide / Tirzepatide Blend listings from 4 vendors by vial size and listed price. Catalog data only: RUO, and Peptidemap sells nothing.';

        $this->assertSame($ghk, ComparePageSeo::description('GHK-Cu', 44));
        $this->assertSame($bpc, ComparePageSeo::description('BPC-157', 43));
        $this->assertSame($thymosin, ComparePageSeo::description('Thymosin Beta-4 Fragment 1-4', 2));
        $this->assertSame($blend, ComparePageSeo::description('Retatrutide / Tirzepatide Blend', 4));
        $this->assertSame(154, mb_strlen($thymosin));
        $this->assertStringNotContainsString('research listings', $blend);

        $shortest = ComparePageSeo::description(str_repeat('N', 80), 12);
        $this->assertStringStartsWith('Compare ', $shortest);
        $this->assertStringContainsString('RUO. Peptidemap sells nothing.', $shortest);
        $this->assertLessThanOrEqual(155, mb_strlen($shortest));
    }

    public function test_social_tags_reuse_the_unsuffixed_title_and_the_meta(): void
    {
        $copy = ComparePageSeo::forCompound('BPC-157', 1);

        $this->assertSame('BPC-157 Prices: 1 Vendor Compared', $copy['title']);
        $this->assertSame($copy['title'], $copy['og_title']);
        $this->assertSame($copy['description'], $copy['og_description']);
        $this->assertStringNotContainsString(' — Peptidemap', $copy['og_title']);
        $this->assertStringContainsString('1 vendor', $copy['description']);
        $this->assertStringNotContainsString('1 vendors', $copy['description']);
        $this->assertStringContainsString('vial size and listed price', $copy['description']);
    }

    public function test_titles_and_metas_stay_inside_the_limits_for_many_names(): void
    {
        $names = [
            'GHK-Cu',
            'BPC-157',
            'Retatrutide',
            'Sterile Water',
            'BPC-157 / TB-500',
            'CJC-1295 + Ipamorelin',
            'Thymosin Beta-4 Fragment 1-4',
            'BPC-157 / TB500 / Cartalax',
            'Retatrutide / Cagrilintide Blend',
            'Retatrutide / Tirzepatide Blend',
            str_repeat('A', 40),
            str_repeat('B', 90),
        ];

        foreach ([false, true] as $perMg) {
            foreach ($names as $name) {
                foreach ([0, 1, 2, 9, 10, 43, 99, 100] as $count) {
                    $title = ComparePageSeo::renderedTitle($name, $count, $perMg);
                    $stored = ComparePageSeo::title($name, $count, $perMg);
                    $description = ComparePageSeo::description($name, $count, $perMg);
                    $label = "{$name} / {$count} / ".($perMg ? 'per-mg' : 'vial');

                    $this->assertLessThanOrEqual(60, mb_strlen($title), $label.' title '.$title);
                    $this->assertLessThanOrEqual(155, mb_strlen($description), $label.' meta '.$description);
                    $this->assertStringNotContainsString('Cheapest', $title, $label);
                    $this->assertStringNotContainsString('Cheapest', $description, $label);
                    $this->assertStringNotContainsString('$', $title, $label);
                    $this->assertStringNotContainsString('$', $description, $label);
                    $this->assertDoesNotMatchRegularExpression('/verified/i', $title, $label);
                    $this->assertDoesNotMatchRegularExpression('/verified/i', $description, $label);
                    $this->assertStringEndsWith(' — Peptidemap', $title, $label);
                    $this->assertStringNotContainsString(' — Peptidemap', $stored, $label);
                    $this->assertStringNotContainsString('+…', $title, $label);

                    if (! $perMg || $count === 0) {
                        $this->assertStringNotContainsString('per mg', $title, $label);
                        $this->assertStringNotContainsString('per mg', $description, $label);
                    }

                    if ($count === 0) {
                        $this->assertStringContainsString('Vendor Comparison', $stored, $label);
                        $this->assertMatchesRegularExpression('/\b(are|Compare)\b/', $description, $label);
                    }

                    if ($count === 1 && str_contains($stored, 'Vendor')) {
                        $this->assertStringContainsString('1 Vendor', $stored, $label);
                        $this->assertStringNotContainsString('1 Vendors', $stored, $label);
                        $this->assertStringContainsString('1 vendor', $description, $label);
                        $this->assertStringNotContainsString('1 vendors', $description, $label);
                    }
                }
            }
        }
    }

    public function test_bacteriostatic_water_meta_uses_a_per_ml_basis(): void
    {
        $description = ComparePageSeo::description('Bacteriostatic Water', 42, false, true);

        $this->assertLessThanOrEqual(155, mb_strlen($description), $description);
        $this->assertStringContainsString('listed price per mL', $description);
        $this->assertStringNotContainsString('$', $description);
        $this->assertStringNotContainsString('per mg', $description);
    }
}
