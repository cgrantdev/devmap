<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Source guard for the publisher-wording pass. These phrases claimed
 * Peptidemap verifies vendors, tests products, or checks purchases.
 * Email verification, PubChem registry notes, and the cGMP facility
 * selling point are not in this list.
 */
class PublisherWordingTest extends TestCase
{
    public function test_removed_verification_claims_do_not_reappear(): void
    {
        $forbidden = [
            'app/Http/Controllers/Frontend/VendorBadgeController.php' => [
                'Verified vendor',
            ],
            'app/Http/Controllers/Frontend/CompareController.php' => [
                'verified vendor',
                'lab-testing status',
                'verified research-peptide',
            ],
            'app/Http/Controllers/Frontend/HomeController.php' => [
                '99% HPLC-verified',
                'Verified on PeptideMap',
                '40+ verified vendors',
                'inspect COAs',
                'verified discount codes',
            ],
            'app/Http/Controllers/Admin/BannersController.php' => [
                '99% HPLC-verified',
                'â€”',
            ],
            'resources/js/Pages/Frontend/WelcomeV2.vue' => [
                '+ verified',
            ],
            'resources/js/Pages/Layouts/ModernLayout.vue' => [
                'Verified vendors, lab-tested',
            ],
            'resources/js/Pages/Components/Footer.vue' => [
                'verified vendors',
            ],
            'resources/js/components/ui/SearchPalette.vue' => [
                'Verified vendors',
            ],
            'resources/js/components/ui/VerifiedShield.vue' => [
                "default: 'Verified'",
            ],
            'resources/js/Pages/Frontend/SearchResults.vue' => [
                'Verified Only',
                '>Verified<',
            ],
            'resources/views/og/compound.blade.php' => [
                'Lab verified',
            ],
            'resources/views/og/product.blade.php' => [
                'Lab verified',
            ],
            'resources/js/Pages/Frontend/BrandProducts.vue' => [
                'Verified customer reviews',
                'Verified via PMAP',
                'Not Verified',
            ],
            'app/Http/Controllers/Frontend/ProductsController.php' => [
                'Verified customer',
                'verified vendors',
                'verified COAs',
                'verified peptide vendors',
            ],
            'app/Http/Controllers/Frontend/VsCompetitorController.php' => [
                'Verified customer reviews',
            ],
            'resources/js/Pages/Frontend/Deals.vue' => [
                'Verified Exclusive Discounts',
                'verified by our team',
                'discounts verified',
            ],
            'resources/js/Pages/Frontend/Welcome.vue' => [
                '99%+ purity guaranteed',
                'verified discount codes',
            ],
            'resources/views/app.blade.php' => [
                'verified suppliers',
                'inspect lab testing',
            ],
            'resources/js/Pages/Auth/Login.vue' => [
                'verified suppliers',
                'inspect lab testing',
            ],
            'public/coming-soon.html' => [
                'verified suppliers',
                'inspect lab testing',
                'Verified vendors',
                'Lab-tested compounds',
            ],
            'resources/js/Pages/Frontend/ProductDetail.vue' => [
                'Verified via PMAP',
                'COA available',
                'undergoes third-party testing',
            ],
            'resources/js/Pages/Frontend/Account/Reviews.vue' => [
                'Verified via PMAP',
            ],
            'resources/js/Pages/Admin/Reviews.vue' => [
                'Verified purchase',
            ],
            'resources/js/Pages/Auth/Register.vue' => [
                'verified reviews',
                'verified peptide vendors',
            ],
            'resources/js/Pages/Frontend/Coupon.vue' => [
                'Verified coupon',
            ],
            'app/Http/Controllers/Frontend/CouponController.php' => [
                'Verified discount',
                'verified and up-to-date',
            ],
            'app/Http/Controllers/Frontend/DealsController.php' => [
                'verified coupon codes',
            ],
            'config/seo.php' => [
                'verified discount codes',
            ],
            'resources/js/data/uspOptions.js' => [
                '99%+ purity guaranteed',
                "'Money-back guarantee'",
            ],
            'app/Http/Controllers/Frontend/BacteriostaticWaterController.php' => [
                'verified vendors',
            ],
            'app/Http/Controllers/Frontend/LandingPageController.php' => [
                'verified vendors',
            ],
            'resources/js/Pages/Frontend/LandingTestingLabs.vue' => [
                'verified vendors',
                'Trust & Verification',
            ],
            'resources/js/Pages/Frontend/ProductListing.vue' => [
                'verified vendors',
            ],
            'app/Http/Controllers/Frontend/JoinController.php' => [
                'verified vendor',
            ],
            'database/seeders/PagesSeeder.php' => [
                'Connect with verified vendors',
            ],
            'resources/js/Pages/Frontend/EncyclopediaDetail.vue' => [
                'Verified Purchase',
                'userExperiences',
            ],
            'app/Http/Controllers/Frontend/EncyclopediaController.php' => [
                'getUserExperiences',
                'Helped with my tendonitis',
            ],
        ];

        foreach ($forbidden as $path => $phrases) {
            $contents = file_get_contents(base_path($path));
            $this->assertNotFalse($contents, $path);
            foreach ($phrases as $phrase) {
                $this->assertStringNotContainsString($phrase, $contents, "{$path} still contains \"{$phrase}\"");
            }
        }
    }

    public function test_replacement_publisher_wording_is_present(): void
    {
        $required = [
            'app/Http/Controllers/Frontend/VendorBadgeController.php' => [
                'Listed on Peptidemap',
                'No reviews yet',
            ],
            'app/Http/Controllers/Frontend/CompareController.php' => [
                'Live prices and coupon codes. Check each vendor\'s site for COAs.',
                'a listed vendor',
            ],
            'app/Http/Controllers/Frontend/HomeController.php' => [
                'Live prices and coupon codes. Check each vendor\'s site for COAs.',
                'Listed on Peptidemap.',
                'Sponsored — vendor states HPLC COAs are published per batch',
                'noindex, follow',
            ],
            'resources/js/components/ui/VerifiedShield.vue' => [
                'Listed on Peptidemap',
            ],
            'resources/js/Pages/Frontend/WelcomeV2.vue' => [
                '+ listed',
            ],
            'resources/js/Pages/Layouts/ModernLayout.vue' => [
                'Compare research-peptide vendors, prices, coupons and vendor-published COAs.',
            ],
            'resources/js/components/ui/SearchPalette.vue' => [
                'Vendor directory',
            ],
            'resources/views/og/compound.blade.php' => [
                'Live prices · Coupons',
            ],
            'resources/views/og/product.blade.php' => [
                'Live prices',
                'PMAP coupons',
            ],
            'app/Support/OgImageRevision.php' => [
                "public const COPY = '20261008'",
            ],
            'app/Http/Controllers/Frontend/ProductOgImageController.php' => [
                'OgImageRevision::COPY',
            ],
            'app/Http/Controllers/Frontend/CompoundOgImageController.php' => [
                'OgImageRevision::COPY',
            ],
            'resources/js/Pages/Frontend/SearchResults.vue' => [
                'Has reviews',
                'Sponsored',
                'Paid placement — not a quality rating',
            ],
            'resources/js/Pages/Frontend/Deals.vue' => [
                'Codes are supplied by vendors; prices refresh from vendor sites — confirm at checkout.',
            ],
            'app/Http/Controllers/Frontend/CouponController.php' => [
                'Peptidemap code',
                'Code supplied by',
            ],
            'resources/js/data/uspOptions.js' => [
                '99%+ purity (vendor-stated)',
                'Money-back guarantee (vendor-stated)',
                '3rd-party lab tested (vendor-stated)',
                'Full COA per batch (vendor-stated)',
                'cGMP facility',
            ],
            'resources/js/Pages/Frontend/BrandProducts.vue' => [
                'Customer reviews from other platforms',
                'Visited via Peptidemap',
                'reviewSourceLabel',
            ],
        ];

        foreach ($required as $path => $phrases) {
            $contents = file_get_contents(base_path($path));
            foreach ($phrases as $phrase) {
                $this->assertStringContainsString($phrase, $contents, "{$path} is missing \"{$phrase}\"");
            }
        }
    }

    public function test_content_qa_must_fixes_are_present(): void
    {
        $required = [
            'app/Http/Controllers/Frontend/CompareController.php' => [
                'Live prices and coupon codes. Check each vendor\'s site for COAs.',
                'OgImageRevision::COPY',
            ],
            'app/Http/Controllers/Frontend/ProductsController.php' => [
                'Live prices and coupon codes. Check each vendor\'s site for COAs.',
            ],
            'app/Http/Controllers/Frontend/HomeController.php' => [
                'Live prices and coupon codes. Check each vendor\'s site for COAs.',
                'og-default-v8.png',
                "'robots' => 'noindex, follow'",
            ],
            'resources/views/app.blade.php' => [
                'og-default-v8.png',
            ],
            'app/Http/Controllers/Frontend/BlogsController.php' => [
                'og-default-v8.png',
            ],
            'app/Http/Controllers/Frontend/GuidesController.php' => [
                'og-default-v8.png',
            ],
            'app/Http/Controllers/Frontend/CompoundOgImageController.php' => [
                'og-default-v8.png',
            ],
            'app/Http/Controllers/Frontend/ProductOgImageController.php' => [
                'og-default-v8.png',
            ],
            'public/og-source.html' => [
                '40+ vendors · 2,500+ products · Live prices · Coupons',
                'Compare research-peptide vendors, prices and coupon codes.',
            ],
            'resources/js/Pages/Frontend/Coupon.vue' => [
                'Peptidemap code',
                'is the code',
                'supplied to Peptidemap',
                'Peptidemap does not test codes',
            ],
            'app/Http/Controllers/Frontend/CouponController.php' => [
                'Peptidemap code',
            ],
            'app/Http/Controllers/Frontend/LandingPageController.php' => [
                'Listed vendors grouped by the third-party lab named on their storefront (Janoshik, Certara, KryoLabs). Peptidemap does not test products.',
            ],
            'resources/js/Pages/Frontend/Welcome.vue' => [
                'Compare live prices',
                'Coupon codes included',
                'Compare research-peptide vendors',
                'Browse top-rated vendors.',
            ],
            'resources/js/Pages/Frontend/SearchResults.vue' => [
                'Sponsored',
                'Paid placement — not a quality rating',
            ],
            'resources/js/Pages/Frontend/BrandProducts.vue' => [
                "Not specified — check",
            ],
            'database/seeders/PagesSeeder.php' => [
                'research-peptide vendors',
                'multiple research-peptide vendors',
                '<h2>Listings</h2>',
                'Peptidemap lists vendors and prices. We do not test products or verify vendors.',
                'Vendor directory',
            ],
            'resources/js/data/uspOptions.js' => [
                '3rd-party lab tested (vendor-stated)',
                'Full COA per batch (vendor-stated)',
            ],
            'resources/js/components/ui/VerifiedShield.vue' => [
                'Listed on Peptidemap',
            ],
            'resources/js/Pages/Frontend/ProductDetail.vue' => [
                'Tested (vendor-reported)',
            ],
            'resources/js/components/ui/ProductCard.vue' => [
                'Tested (vendor-reported)',
            ],
            'resources/js/Pages/Components/Footer.vue' => [
                'Get listed',
            ],
            'resources/js/Pages/Frontend/Join.vue' => [
                'published COAs',
            ],
            'app/Http/Controllers/Frontend/BrandsController.php' => [
                'compare vendors for your research needs',
            ],
            'resources/views/emails/vendor-welcome.blade.php' => [
                'once your store is approved',
            ],
            'resources/js/Pages/Auth/Register.vue' => [
                "bought from",
            ],
            'resources/js/Pages/Frontend/Deals.vue' => [
                'Use code PMAP at checkout',
            ],
            'resources/js/components/ui/SearchPalette.vue' => [
                'Vendor directory',
            ],
        ];

        $forbidden = [
            'app/Http/Controllers/Frontend/CompareController.php' => [
                'vendor-published COA links where available',
            ],
            'app/Http/Controllers/Frontend/ProductsController.php' => [
                'vendor-published COA links where available',
                "'@type' => 'Review'",
            ],
            'app/Http/Controllers/Frontend/HomeController.php' => [
                'vendor-published COAs',
            ],
            'public/og-source.html' => [
                'Lab Verified',
                'Verified suppliers',
                'Lab-Tested',
            ],
            'resources/js/Pages/Frontend/Coupon.vue' => [
                'Exclusive Peptidemap code',
            ],
            'app/Http/Controllers/Frontend/CouponController.php' => [
                'Exclusive Peptidemap code',
            ],
            'resources/js/Pages/Frontend/SearchResults.vue' => [
                '>Featured<',
            ],
            'database/seeders/PagesSeeder.php' => [
                'trusted peptide suppliers',
                'Trusted Peptide Suppliers',
                'multiple verified peptide suppliers',
                'Quality and Verification',
                'Trusted Vendors',
            ],
            'resources/js/Pages/Components/Footer.vue' => [
                'Get verified, listed',
            ],
            'resources/js/Pages/Frontend/Join.vue' => [
                'verifiable quality',
            ],
            'app/Http/Controllers/Frontend/VsCompetitorController.php' => [
                'No verified customer reviews',
            ],
            'resources/views/emails/vendor-welcome.blade.php' => [
                'store is verified',
            ],
            'resources/js/Pages/Auth/Register.vue' => [
                'visit a vendor via Peptidemap',
            ],
            'resources/views/og/product.blade.php' => [
                'Live prices · Coupons',
            ],
            'resources/js/components/ui/VerifiedShield.vue' => [
                'M9.5 12.5l2 2 4-4.5',
            ],
            'resources/js/components/ui/SearchPalette.vue' => [
                'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
            ],
            'resources/js/Pages/Frontend/ProductListing.vue' => [
                '>Lab tested<',
                'Min purity',
            ],
        ];

        foreach ($required as $path => $phrases) {
            $contents = file_get_contents(base_path($path));
            $this->assertNotFalse($contents, $path);
            foreach ($phrases as $phrase) {
                $this->assertStringContainsString($phrase, $contents, "{$path} is missing \"{$phrase}\"");
            }
        }

        foreach ($forbidden as $path => $phrases) {
            $contents = file_get_contents(base_path($path));
            foreach ($phrases as $phrase) {
                $this->assertStringNotContainsString($phrase, $contents, "{$path} still contains \"{$phrase}\"");
            }
        }

        $listing = file_get_contents(base_path('resources/js/Pages/Frontend/ProductListing.vue'));
        $template = strstr($listing, '<script', true);
        $this->assertStringNotContainsString('Lab tested', $template);
        $this->assertStringNotContainsString('min purity', strtolower($template));

        $this->assertFileDoesNotExist(base_path('resources/js/Pages/Product/Public.vue'));
        $this->assertFileDoesNotExist(base_path('public/images/og-default-v7.png'));
        $this->assertFileExists(base_path('public/images/og-default-v8.png'));
    }
}
