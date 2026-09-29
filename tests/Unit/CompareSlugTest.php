<?php

namespace Tests\Unit;

use App\Support\CompareSlug;
use PHPUnit\Framework\TestCase;

class CompareSlugTest extends TestCase
{
    public function test_canonical_folds_case_spaces_and_slashes(): void
    {
        $this->assertSame('bpc-157', CompareSlug::canonical('BPC-157'));
        $this->assertSame('cjc-1295-ipamorelin', CompareSlug::canonical('CJC-1295-Ipamorelin'));
        $this->assertSame('ghk-cu', CompareSlug::canonical('GHK-Cu'));
        $this->assertSame('retatrutide', CompareSlug::canonical('Retatrutide'));
        $this->assertSame('vitamin-b12', CompareSlug::canonical('Vitamin B12'));
        $this->assertSame('mic-b12', CompareSlug::canonical('MIC + B12'));
        $this->assertSame(
            'bpc-157-tb500-cartalax',
            CompareSlug::canonical('BPC-157 / TB500 / Cartalax')
        );
        $this->assertSame(
            'retatrutide-cagrilintide-blend',
            CompareSlug::canonical('Retatrutide / Cagrilintide Blend')
        );
        $this->assertSame('retatrutide', CompareSlug::canonical('retatrutide'));
        $this->assertNull(CompareSlug::canonical('   '));
        $this->assertNull(CompareSlug::canonical(null));
    }
}
