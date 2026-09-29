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
        $this->assertSame('/encyclopedia/Vitamin B12', EncyclopediaSlug::path('Vitamin B12'));
    }
}
