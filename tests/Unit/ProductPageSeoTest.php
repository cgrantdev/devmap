<?php

namespace Tests\Unit;

use App\Support\ProductPageSeo;
use PHPUnit\Framework\TestCase;

class ProductPageSeoTest extends TestCase
{
    public function test_short_names_use_the_listing_pattern_and_stay_within_60(): void
    {
        $before = 'Buy Tirzepatide 10mg from Amino Club — $89 · Peptidemap';
        $title = ProductPageSeo::title('Tirzepatide 10mg', 'Amino Club');

        $this->assertSame(55, mb_strlen($before), $before);
        $this->assertSame('Tirzepatide 10mg — Amino Club Listing — Peptidemap', $title);
        $this->assertSame(50, mb_strlen($title), $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title));
        $this->assertStringNotContainsString('Buy', $title);
        $this->assertStringNotContainsString('$', $title);
    }

    public function test_long_names_fall_back_without_passing_60(): void
    {
        $product = str_repeat('A', 30);
        $title = ProductPageSeo::title($product, 'Amino Club');

        $this->assertSame($product.' — Amino Club — Peptidemap', $title);
        $this->assertSame(56, mb_strlen($title));

        $huge = ProductPageSeo::title(str_repeat('Long peptide name ', 8), 'Certified Research Supply');
        $this->assertLessThanOrEqual(60, mb_strlen($huge), $huge);
        $this->assertStringEndsWith(' — Peptidemap', $huge);
        $this->assertStringContainsString('…', $huge);
        $this->assertStringNotContainsString('Buy', $huge);
        $this->assertStringNotContainsString('$', $huge);
    }

    public function test_descriptions_stay_within_155_and_omit_price_and_promo_claims(): void
    {
        $before = 'BPC-157 5mg from Amino Club — $39.99 (was $49.99). Compare 2 vendors on Peptidemap, save 10% with code PMAP. Research use only.';
        $description = ProductPageSeo::description('BPC-157 5mg', 'Amino Club', 2);

        $this->assertSame(127, mb_strlen($before), $before);
        $this->assertLessThanOrEqual(155, mb_strlen($description), $description);
        $this->assertStringContainsString('Compare listings from 2 vendors', $description);
        $this->assertStringContainsString('Catalog data only: RUO, and Peptidemap sells nothing.', $description);

        $one = ProductPageSeo::description('BPC-157 5mg', 'Amino Club', 1);
        $this->assertStringContainsString('1 vendor', $one);
        $this->assertStringNotContainsString('1 vendors', $one);

        foreach ([$description, $one, ProductPageSeo::description(str_repeat('Peptide blend ', 12), 'A very long vendor name', 40)] as $meta) {
            $this->assertLessThanOrEqual(155, mb_strlen($meta), $meta);
            $this->assertStringNotContainsString('$', $meta);
            $this->assertStringNotContainsString('%', $meta);
            $this->assertDoesNotMatchRegularExpression('/\bbuy\b/i', $meta);
            $this->assertDoesNotMatchRegularExpression('/\bbest\b/i', $meta);
            $this->assertDoesNotMatchRegularExpression('/\bcheapest\b/i', $meta);
        }
    }
}
