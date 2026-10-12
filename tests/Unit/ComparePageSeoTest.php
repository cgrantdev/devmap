<?php

namespace Tests\Unit;

use App\Support\ComparePageSeo;
use PHPUnit\Framework\TestCase;

class ComparePageSeoTest extends TestCase
{
    public function test_short_names_use_the_price_per_mg_title(): void
    {
        $this->assertSame(
            'GHK-Cu Price per mg: 44 Vendors Compared',
            ComparePageSeo::title('GHK-Cu', 44)
        );
        $this->assertSame(
            'GHK-Cu Price per mg: 44 Vendors Compared — Peptidemap',
            ComparePageSeo::renderedTitle('GHK-Cu', 44)
        );
        $this->assertSame(
            'BPC-157 Price per mg: 43 Vendors Compared — Peptidemap',
            ComparePageSeo::renderedTitle('BPC-157', 43)
        );
        $this->assertSame(
            'Retatrutide Price per mg: 43 Vendors Compared — Peptidemap',
            ComparePageSeo::renderedTitle('Retatrutide', 43)
        );
        $this->assertSame(
            'Retatrutide Price per mg: 1 Vendor Compared — Peptidemap',
            ComparePageSeo::renderedTitle('Retatrutide', 1)
        );
        $this->assertSame(
            'Retatrutide Price per mg: 0 Vendors Compared — Peptidemap',
            ComparePageSeo::renderedTitle('Retatrutide', 0)
        );
    }

    public function test_long_names_fall_back_without_passing_the_title_limit(): void
    {
        $this->assertSame(
            'Thymosin Beta-4 Fragment 1-4 Prices: 2 Vendors — Peptidemap',
            ComparePageSeo::renderedTitle('Thymosin Beta-4 Fragment 1-4', 2)
        );
        $this->assertSame(
            'Retatrutide / Tirzepatide… Price Comparison — Peptidemap',
            ComparePageSeo::renderedTitle('Retatrutide / Tirzepatide Blend', 4)
        );
        $this->assertSame(
            'AAAAAAAAAAAAAAAAAAAAAAAAAAAAA… Price Comparison',
            ComparePageSeo::title(str_repeat('A', 80), 4)
        );
    }

    public function test_metas_use_the_neutral_template_and_a_shorter_fallback(): void
    {
        $ghk = 'Compare GHK-Cu research listings from 44 vendors by vial price and price per mg. Catalog data only: RUO, and Peptidemap sells nothing.';
        $bpc = 'Compare BPC-157 research listings from 43 vendors by vial price and price per mg. Catalog data only: RUO, and Peptidemap sells nothing.';
        $reta = 'Compare Retatrutide research listings from 43 vendors by vial price and price per mg. Catalog data only: RUO, and Peptidemap sells nothing.';
        $thymosin = 'Compare Thymosin Beta-4 Fragment 1-4 research listings from 2 vendors by vial price and price per mg. Catalog data only: RUO, and Peptidemap sells nothing.';
        $blend = 'Compare Retatrutide / Tirzepatide Blend listings from 4 vendors by vial price and price per mg. Catalog data only: RUO, and Peptidemap sells nothing.';

        $this->assertSame($ghk, ComparePageSeo::description('GHK-Cu', 44));
        $this->assertSame($bpc, ComparePageSeo::description('BPC-157', 43));
        $this->assertSame($reta, ComparePageSeo::description('Retatrutide', 43));
        $this->assertSame($thymosin, ComparePageSeo::description('Thymosin Beta-4 Fragment 1-4', 2));
        $this->assertSame($blend, ComparePageSeo::description('Retatrutide / Tirzepatide Blend', 4));
        $this->assertSame(155, mb_strlen($thymosin));
        $this->assertStringNotContainsString('research listings', $blend);

        $shortest = ComparePageSeo::description(str_repeat('N', 80), 12);
        $this->assertStringContainsString('RUO. Peptidemap sells nothing.', $shortest);
        $this->assertStringStartsWith('NNNN', $shortest);
        $this->assertLessThanOrEqual(155, mb_strlen($shortest));
    }

    public function test_social_tags_reuse_the_unsuffixed_title_and_the_meta(): void
    {
        $copy = ComparePageSeo::forCompound('BPC-157', 1);

        $this->assertSame('BPC-157 Price per mg: 1 Vendor Compared', $copy['title']);
        $this->assertSame($copy['title'], $copy['og_title']);
        $this->assertSame($copy['description'], $copy['og_description']);
        $this->assertStringNotContainsString(' — Peptidemap', $copy['og_title']);
        $this->assertStringContainsString('1 vendor', $copy['description']);
        $this->assertStringNotContainsString('1 vendors', $copy['description']);
    }

    public function test_titles_and_metas_stay_inside_the_limits_for_many_names(): void
    {
        $names = [
            'GHK-Cu',
            'BPC-157',
            'Retatrutide',
            'BPC-157 / TB-500',
            'CJC-1295 / Ipamorelin',
            'Thymosin Beta-4 Fragment 1-4',
            'BPC-157 / TB500 / Cartalax',
            'Retatrutide / Tirzepatide Blend',
            str_repeat('A', 40),
            str_repeat('B', 90),
        ];

        foreach ($names as $name) {
            foreach ([0, 1, 2, 9, 10, 43, 99, 100] as $count) {
                $title = ComparePageSeo::renderedTitle($name, $count);
                $stored = ComparePageSeo::title($name, $count);
                $description = ComparePageSeo::description($name, $count);
                $label = "{$name} / {$count}";

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

                if ($count === 1) {
                    $this->assertStringContainsString('1 vendor', $description, $label);
                    $this->assertStringNotContainsString('1 vendors', $description, $label);
                    if (! str_contains($stored, 'Price Comparison')) {
                        $this->assertStringContainsString('1 Vendor', $stored, $label);
                        $this->assertStringNotContainsString('1 Vendors', $stored, $label);
                    }
                }
            }
        }
    }
}
