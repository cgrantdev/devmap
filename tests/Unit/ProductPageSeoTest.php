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

        $site = ProductPageSeo::schemaDescription(
            'Supplied for laboratory research. Listed on Peptidemap. COAs are published with the lot. Read more on our website.',
            'Ipamorelin (10mg)',
            'ExoLabz'
        );
        $this->assertSame('Supplied for laboratory research.', $site);
        $this->assertStringNotContainsString('on peptidemap', strtolower($site));
        $this->assertStringNotContainsString('our website', strtolower($site));
        $this->assertStringNotContainsString('published', strtolower($site));
    }

    public function test_promo_vendor_copy_is_stripped_from_meta_and_schema(): void
    {
        $source = 'Third-party verified BPC-157, lab-tested to 99% purity. Best price guaranteed — cheapest in the UK. RUO catalog data only…';
        $meta = ProductPageSeo::fromOwnCopy($source, 'BPC-157', 'Amino Club');
        $schema = ProductPageSeo::schemaDescription($source, 'BPC-157', 'Amino Club');

        foreach ([$meta, $schema] as $text) {
            $this->assertNotNull($text);
            $this->assertDoesNotMatchRegularExpression('/\bverified\b/i', $text);
            $this->assertDoesNotMatchRegularExpression('/\blab[- ]tested\b/i', $text);
            $this->assertDoesNotMatchRegularExpression('/\bbest\b/i', $text);
            $this->assertDoesNotMatchRegularExpression('/\bcheapest\b/i', $text);
            $this->assertDoesNotMatchRegularExpression('/\bguarantee/i', $text);
            $this->assertDoesNotMatchRegularExpression('/\d+(?:\.\d+)?%\s*pur/i', $text);
            $this->assertStringNotContainsString('this site', strtolower($text));
            $this->assertStringNotContainsString('on peptidemap', strtolower($text));
            $this->assertFalse(ProductPageSeo::hasClaim($text), $text);
        }

        $this->assertLessThanOrEqual(155, mb_strlen($meta), $meta);
        $this->assertStringContainsString('Peptidemap sells nothing', $meta);
        $this->assertStringContainsString('Peptidemap sells nothing', $schema);
        $this->assertStringNotContainsString('…', $meta);
        $this->assertStringNotContainsString('…', $schema);

        $this->assertTrue(ProductPageSeo::hasClaim('99% purity'));
        $this->assertTrue(ProductPageSeo::hasClaim('99.5% pure'));
        $this->assertTrue(ProductPageSeo::hasClaim('100% purity'));
        $this->assertFalse(ProductPageSeo::hasClaim('9% purity'));
        $this->assertTrue(ProductPageSeo::hasClaim('20% OFF with code'));
        $this->assertTrue(ProductPageSeo::hasClaim('free shipping over $200'));
        $this->assertTrue(ProductPageSeo::hasClaim('COA available'));
        $this->assertTrue(ProductPageSeo::hasClaim('trusted'));
        $this->assertTrue(ProductPageSeo::hasClaim('3rd party tested'));
        $this->assertTrue(ProductPageSeo::hasClaim('Coupon code PEP20'));
        $this->assertTrue(ProductPageSeo::hasClaim('Use code SAVE10 for 10% off'));
        $this->assertTrue(ProductPageSeo::hasClaim('Coupon code'));
        $this->assertTrue(ProductPageSeo::hasClaim('Certificate of analysis'));
        $this->assertTrue(ProductPageSeo::hasClaim('Lowest prices'));
        $this->assertTrue(ProductPageSeo::hasClaim('cheap'));
        $this->assertTrue(ProductPageSeo::hasClaim('Pure & Tested'));
    }

    public function test_a_vendor_named_trusted_keeps_its_name_in_the_identity_line(): void
    {
        $meta = ProductPageSeo::neutralIdentity('BPC-157', 'Trusted Peptides');

        $this->assertStringContainsString('Trusted Peptides', $meta);
        $this->assertLessThanOrEqual(155, mb_strlen($meta), $meta);
        $this->assertStringContainsString('Compare listed prices, vial sizes and vendor details across listings', $meta);
    }

    public function test_stored_overrides_are_filtered_without_rewriting_the_fallback_contract(): void
    {
        $fallbackTitle = 'BPC-157 — Amino Club Listing — Peptidemap';
        $cleanTitle = 'BPC-157 10mg — Amino Club — Peptidemap';
        $this->assertSame($cleanTitle, ProductPageSeo::storedTitle($cleanTitle, 'X'));
        $claimed = ProductPageSeo::storedTitle('Best BPC-157 guaranteed — cheapest', $fallbackTitle);
        $this->assertSame($fallbackTitle, $claimed);

        $cutOnDash = ProductPageSeo::limitTitle('BPC-157 research listing from Amino Club — extra catalog words past the sixty character limit');
        $this->assertLessThanOrEqual(60, mb_strlen($cutOnDash), $cutOnDash);
        $this->assertDoesNotMatchRegularExpression('/[—·]$/u', $cutOnDash);
        $dot = ProductPageSeo::limitTitle(str_repeat('Catalog listing ', 6).'· leftover words');
        $this->assertLessThanOrEqual(60, mb_strlen($dot), $dot);
        $this->assertDoesNotMatchRegularExpression('/[—·]$/u', $dot);

        $long = 'BPC-157 research listing from Amino Club with extra catalog wording that runs past the limit';
        $cut = ProductPageSeo::storedTitle($long, $fallbackTitle);
        $this->assertLessThanOrEqual(60, mb_strlen($cut), $cut);
        $this->assertStringStartsWith('BPC-157 research listing', $cut);
        $this->assertStringNotContainsString('…', $cut);
        $lastWord = (string) preg_replace('/^.*\s/u', '', $cut);
        $this->assertNotSame('', $lastWord);
        $this->assertStringContainsString($lastWord, $long);
        $this->assertLessThan(mb_strlen($long), mb_strlen($cut));

        $fallbackDescription = 'Amino Club lists BPC-157 for research use only (RUO). Peptidemap sells nothing.';
        $promo = 'Third-party verified BPC-157, lab-tested to 99% purity. Best price guaranteed — cheapest in the UK.';
        $this->assertSame($fallbackDescription, ProductPageSeo::storedDescription($promo, $fallbackDescription));

        $clean = 'Supplied as a lyophilized research compound in a 5mg vial for laboratory use only.';
        $this->assertSame($clean, ProductPageSeo::storedDescription($clean, $fallbackDescription));
    }

    public function test_acronyms_stay_intact_when_a_shouted_name_is_recased(): void
    {
        $this->assertSame('DSIP', ProductPageSeo::normalizeProductName('DSIP'));
        $this->assertSame('KPV', ProductPageSeo::normalizeProductName('KPV'));
        $this->assertSame('NAD+', ProductPageSeo::normalizeProductName('NAD+'));
        $this->assertSame('GHRP-6', ProductPageSeo::normalizeProductName('GHRP-6'));
        $this->assertSame('AHK-Cu', ProductPageSeo::normalizeProductName('AHK-CU'));
        $this->assertSame('TB-500', ProductPageSeo::normalizeProductName('TB-500'));
        $this->assertSame('BPC-157', ProductPageSeo::normalizeProductName('BPC-157'));
        $this->assertSame('GHK-Cu', ProductPageSeo::normalizeProductName('GHK-CU'));
        $this->assertSame('Pancragen', ProductPageSeo::normalizeProductName('PANCRAGEN'));
    }
}
