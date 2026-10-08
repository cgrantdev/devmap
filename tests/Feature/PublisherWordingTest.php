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
                'Coupon codes and vendor-published COA links where available.',
                'a listed vendor',
            ],
            'app/Http/Controllers/Frontend/HomeController.php' => [
                'Compare 40+ research-peptide vendors, vendor-published COAs and coupons',
                'Listed on Peptidemap.',
                'Sponsored — vendor states HPLC COAs are published per batch',
            ],
            'resources/js/components/ui/VerifiedShield.vue' => [
                'Approved listing',
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
                'Live prices · Coupons',
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
                'Featured',
            ],
            'resources/js/Pages/Frontend/Deals.vue' => [
                'Codes are supplied by vendors; prices refresh from vendor sites — confirm at checkout.',
            ],
            'app/Http/Controllers/Frontend/CouponController.php' => [
                'Exclusive Peptidemap code',
                'Code supplied by',
            ],
            'resources/js/data/uspOptions.js' => [
                '99%+ purity (vendor-stated)',
                'Money-back guarantee (vendor-stated)',
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
}
