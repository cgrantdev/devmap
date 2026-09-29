<?php

namespace Tests\Unit;

use App\Support\EncyclopediaSlug;
use PHPUnit\Framework\TestCase;

class EncyclopediaSlugTest extends TestCase
{
    public function test_slash_slugs_are_not_resolvable_and_spaces_are(): void
    {
        $this->assertFalse(EncyclopediaSlug::isResolvable('Selank/Semax'));
        $this->assertFalse(EncyclopediaSlug::isResolvable('BPC-157 / TB500 / Cartalax'));
        $this->assertFalse(EncyclopediaSlug::isResolvable('GHK-CU/BPC 157/KPV'));
        $this->assertFalse(EncyclopediaSlug::isResolvable('Adalank /  Adamax'));
        $this->assertFalse(EncyclopediaSlug::isResolvable(''));
        $this->assertFalse(EncyclopediaSlug::isResolvable(null));

        $this->assertTrue(EncyclopediaSlug::isResolvable('Vitamin B12'));
        $this->assertTrue(EncyclopediaSlug::isResolvable('BPC-157'));
        $this->assertTrue(EncyclopediaSlug::isResolvable('BPC-157-TB-500'));
        $this->assertNull(EncyclopediaSlug::path('Selank/Semax'));
        $this->assertSame('/encyclopedia/vitamin-b12', EncyclopediaSlug::path('Vitamin B12'));
        $this->assertSame('/encyclopedia/Foo Bar', EncyclopediaSlug::path('Foo Bar'));
        $this->assertSame('/encyclopedia/hgh-191aa', EncyclopediaSlug::path('HGH 191AA'));
        $this->assertSame('/encyclopedia/phosphate-buffered-saline', EncyclopediaSlug::path('PBS'));
        $this->assertSame('/encyclopedia/sterile-water', EncyclopediaSlug::path('Sterile Water'));
        $this->assertSame('/encyclopedia/thymosin-beta-4-fragment-1-4', EncyclopediaSlug::path('Thymosin Beta-4 Fragment 1-4'));
        $this->assertSame('/encyclopedia/alpha-klotho-lr', EncyclopediaSlug::path('Alpha-Klotho LR'));
        $this->assertSame('/encyclopedia/n-acetyl-larazotide', EncyclopediaSlug::path('N-Acetyl Larazotide'));
    }
}
