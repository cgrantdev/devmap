<?php

namespace Tests\Unit;

use App\Http\Controllers\Frontend\CompareController;
use App\Support\CompoundDisplay;
use App\Support\VsPageSeo;
use PHPUnit\Framework\TestCase;

class VsPageSeoTest extends TestCase
{
    /**
     * Category names behind FEATURED_VS_PAIRS slugs. GLP entries stay
     * on CompoundDisplay code names; this list does not rename them.
     */
    private const RAW_NAMES = [
        'semaglutide' => 'Semaglutide',
        'tirzepatide' => 'Tirzepatide',
        'bpc-157' => 'BPC-157',
        'tb-500' => 'TB-500',
        'cjc-1295' => 'CJC-1295',
        'ipamorelin' => 'Ipamorelin',
        'ghk-cu' => 'GHK-Cu',
        'retatrutide' => 'Retatrutide',
        'mots-c' => 'MOTS-c',
        'nad' => 'NAD+',
        'tesamorelin' => 'Tesamorelin',
        'sermorelin' => 'Sermorelin',
        'aod-9604' => 'AOD-9604',
        '5-amino-1mq' => '5-AMINO-1MQ',
        'kisspeptin' => 'Kisspeptin',
        'pt-141' => 'PT-141',
        'cagrilintide' => 'Cagrilintide',
    ];

    public function test_featured_vs_titles_stay_within_60_and_keep_glp_codes(): void
    {
        $this->assertCount(15, CompareController::FEATURED_VS_PAIRS);

        foreach (CompareController::FEATURED_VS_PAIRS as $pair) {
            $rawA = self::RAW_NAMES[$pair['a']];
            $rawB = self::RAW_NAMES[$pair['b']];
            $a = CompoundDisplay::label($rawA);
            $b = CompoundDisplay::label($rawB);
            $title = VsPageSeo::renderedTitle($a, $b);
            $description = VsPageSeo::description($a, $b);
            $label = "{$a} vs {$b}";

            $this->assertLessThanOrEqual(60, mb_strlen($title), $title);
            $this->assertLessThanOrEqual(155, mb_strlen($description), $description);
            $this->assertStringNotContainsString('$', $title, $label);
            $this->assertStringNotContainsString('$', $description, $label);
            $this->assertStringNotContainsString('best', strtolower($title), $label);
            $this->assertStringNotContainsString('best', strtolower($description), $label);
            $this->assertStringNotContainsString('Cheapest', $title, $label);
            $this->assertStringEndsWith(' — Peptidemap', $title, $label);
            $this->assertStringNotContainsString(' — Peptidemap', VsPageSeo::title($a, $b), $label);
        }

        $glp = VsPageSeo::renderedTitle(
            CompoundDisplay::label('Retatrutide'),
            CompoundDisplay::label('Tirzepatide')
        );
        $this->assertSame(
            'GLP3-R vs GLP2-T — Vendor & Price Comparison — Peptidemap',
            $glp
        );
        $this->assertStringNotContainsString('Retatrutide', $glp);
        $this->assertStringNotContainsString('Tirzepatide', $glp);

        $sema = VsPageSeo::renderedTitle(
            CompoundDisplay::label('Cagrilintide'),
            CompoundDisplay::label('Semaglutide')
        );
        $this->assertStringContainsString('GLP1-S', $sema);
        $this->assertStringNotContainsString('Semaglutide', $sema);

        $long = 'Ipamorelin vs Tesamorelin — Vendor & Price Comparison — Peptidemap';
        $this->assertSame(66, mb_strlen($long));
        $short = VsPageSeo::renderedTitle('Ipamorelin', 'Tesamorelin');
        $this->assertSame('Ipamorelin vs Tesamorelin — Price Comparison — Peptidemap', $short);
        $this->assertSame(57, mb_strlen($short));
    }

    public function test_a_long_pair_ellipsis_keeps_the_shorter_name(): void
    {
        $title = VsPageSeo::renderedTitle(
            'A very long investigational compound name that will not fit',
            'NAD+'
        );

        $this->assertLessThanOrEqual(60, mb_strlen($title), $title);
        $this->assertStringContainsString('NAD+', $title);
        $this->assertStringContainsString('…', $title);
        $this->assertStringEndsWith(' — Peptidemap', $title);
    }
}
