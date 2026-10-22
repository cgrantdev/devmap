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
        $this->assertSame('Tirzepatide (10mg) — Amino Club Listing — Peptidemap', $title);
        $this->assertSame(52, mb_strlen($title), $title);
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

    public function test_own_copy_drops_claim_words_and_never_ends_with_an_ellipsis(): void
    {
        $claim = 'Honest Peptide offers 100mg of GHK-Cu, a tripeptide-copper complex studied for its role in wound healing and tissue remodeling pathways.';
        $meta = ProductPageSeo::fromOwnCopy($claim, 'GHK-Cu (100mg)', 'Honest Peptide');

        $this->assertLessThanOrEqual(155, mb_strlen($meta), $meta);
        $this->assertStringNotContainsString('…', $meta);
        $this->assertFalse(ProductPageSeo::hasClaim($meta), $meta);
        $this->assertStringContainsString('Honest Peptide lists GHK-Cu (100mg)', $meta);
        $this->assertStringContainsString('tripeptide-copper complex', $meta);
        $this->assertStringContainsString('Peptidemap sells nothing', $meta);

        $long = 'Ipamorelin from ExoLabz is a selective ghrelin receptor agonist supplied as a research compound in 10mg quantity, designed for in vitro and animal model work that continues well past the meta limit without ever reaching a full stop so the old cutter would have appended an ellipsis';
        $clipped = ProductPageSeo::fromOwnCopy($long, 'Ipamorelin (10mg)', 'ExoLabz');
        $this->assertLessThanOrEqual(155, mb_strlen($clipped), $clipped);
        $this->assertStringNotContainsString('…', $clipped);
        $this->assertMatchesRegularExpression('/[.!?]$/u', $clipped);
    }

    public function test_short_own_copy_gains_the_publisher_tail(): void
    {
        $source = 'Pure Peptides UK supplies BPC-157 in 5mg lyophilized form for research applications.';
        $meta = ProductPageSeo::fromOwnCopy($source, 'BPC-157 (5mg)', 'Pure Peptides UK');

        $this->assertSame(84, mb_strlen($source));
        $this->assertStringEndsWith('RUO catalog data only; Peptidemap sells nothing.', $meta);
        $this->assertLessThanOrEqual(155, mb_strlen($meta), $meta);
        $this->assertGreaterThanOrEqual(120, mb_strlen($meta), $meta);
        $this->assertStringNotContainsString('…', $meta);
    }

    public function test_titles_normalize_dose_casing_and_keep_no_dac(): void
    {
        $brackets = ProductPageSeo::title('GLP-2TZ [10mg]', 'Pinnacle Peptide Labs');
        $this->assertSame('GLP-2TZ (10mg) — Pinnacle Peptide Labs Listing — Peptidemap', $brackets);
        $this->assertSame(59, mb_strlen($brackets));

        $spaced = ProductPageSeo::title('Retatrutide 10 mg', 'Zeus Peptides');
        $this->assertSame('Retatrutide (10mg) — Zeus Peptides Listing — Peptidemap', $spaced);
        $this->assertLessThanOrEqual(60, mb_strlen($spaced));

        $caps = ProductPageSeo::title('PANCRAGEN 20mg', 'Certified Pep');
        $this->assertStringContainsString('Pancragen (20mg)', $caps);
        $this->assertStringNotContainsString('PANCRAGEN', $caps);

        $amino = ProductPageSeo::title('5-AMINO-1MQ', 'Amino Club');
        $this->assertStringStartsWith('5-Amino-1MQ — Amino Club', $amino);

        $noDac = ProductPageSeo::title('CJC-1295 (10mg)', 'ALPHAPEP LLC', 'CJC-1295 no DAC 10 mg');
        $this->assertSame('CJC-1295 no DAC (10mg) — ALPHAPEP LLC Listing — Peptidemap', $noDac);
        $this->assertSame(58, mb_strlen($noDac));
        $this->assertStringContainsString('GLP3-R', ProductPageSeo::title('GLP3-R 10mg', 'Zeus Peptides'));
        $this->assertStringNotContainsString('Retatrutide', ProductPageSeo::title('GLP3-R [10mg]', 'Zeus Peptides'));
    }

    public function test_long_vendor_names_drop_the_legal_suffix_before_the_vendor(): void
    {
        $title = ProductPageSeo::title('Epitalon (10mg)', 'Australian Research Peptides Pty Ltd');

        $this->assertSame('Epitalon (10mg) — Australian Research Peptides — Peptidemap', $title);
        $this->assertSame(59, mb_strlen($title));
        $this->assertStringNotContainsString('Pty Ltd', $title);
        $this->assertStringNotContainsString('Listing', $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title));

        $withLlc = ProductPageSeo::title('MOTS-c (40mg)', 'Southern Aminos LLC');
        $this->assertStringContainsString('Southern Aminos LLC Listing', $withLlc);
        $this->assertLessThanOrEqual(60, mb_strlen($withLlc));
    }

    public function test_schema_description_strips_verified_and_this_site(): void
    {
        $source = 'Supplied for laboratory research. COA-verified purity; certificates of analysis are published on this site. The material is laboratory-verified.';
        $schema = ProductPageSeo::schemaDescription($source, 'Ipamorelin (10mg)', 'ExoLabz');

        $this->assertSame('Supplied for laboratory research.', $schema);
        $this->assertStringNotContainsString('verified', strtolower($schema));
        $this->assertStringNotContainsString('this site', strtolower($schema));

        $onlyClaim = ProductPageSeo::schemaDescription(
            'Studied for wound healing and promoted as a therapy.',
            'GHK-Cu (100mg)',
            'Honest Peptide'
        );
        $this->assertFalse(ProductPageSeo::hasClaim($onlyClaim), $onlyClaim);
        $this->assertStringNotContainsString('verified', strtolower($onlyClaim));
        $this->assertStringContainsString('Peptidemap sells nothing', $onlyClaim);
    }
}
