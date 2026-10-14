<?php

namespace Tests\Feature;

use Tests\TestCase;

class NeutralCopySourceTest extends TestCase
{
    public function test_swept_files_drop_cheapest_best_deals_and_coupon_percent_copy(): void
    {
        $files = [
            'app/Http/Controllers/Frontend/BacteriostaticWaterController.php',
            'resources/js/Pages/Frontend/BacteriostaticWater.vue',
            'resources/js/Pages/Frontend/CompareCompoundVs.vue',
            'resources/js/Pages/Frontend/CompareCompound.vue',
            'resources/js/Pages/Frontend/Compare.vue',
            'resources/js/Pages/Frontend/Welcome.vue',
            'resources/js/Pages/Frontend/WelcomeV2.vue',
            'resources/js/Pages/Frontend/ProductDetail.vue',
            'resources/js/Pages/Frontend/BrandProducts.vue',
            'resources/js/Pages/Frontend/Coupon.vue',
            'resources/js/Pages/Frontend/Account/Wishlist.vue',
            'app/Http/Controllers/Frontend/CompareController.php',
            'app/Http/Controllers/Frontend/HomeController.php',
            'app/Http/Controllers/Frontend/DealsController.php',
            'app/Http/Controllers/Frontend/LandingPageController.php',
            'config/seo.php',
            'resources/views/og/brand.blade.php',
        ];
        // "Cheapest" stays case-sensitive so cheapest_price identifiers survive.
        $phrases = [
            'Cheapest',
            'Best deals',
            'Best Deals',
            'best deals',
            'best prices',
            'Best price',
            'best vendors',
            'sorted cheapest-first',
            'sorted cheapest first',
            'cheapest first',
            'cheapest product',
            'current best prices',
            'find the best deals',
            'Save up to',
            'usually 10',
            'From $',
            'coupon codes included',
        ];
        $caseInsensitive = [
            'best deals',
            'best prices',
            'best price',
            'cheapest first',
            'cheapest product',
            'current best prices',
            'find the best deals',
        ];

        foreach ($files as $path) {
            $contents = file_get_contents(base_path($path));
            $this->assertNotFalse($contents, $path);
            $folded = mb_strtolower($contents);
            foreach ($phrases as $phrase) {
                $this->assertStringNotContainsString($phrase, $contents, "{$path} still contains \"{$phrase}\"");
            }
            foreach ($caseInsensitive as $phrase) {
                $this->assertStringNotContainsString($phrase, $folded, "{$path} still contains \"{$phrase}\"");
            }
        }

        $deals = file_get_contents(base_path('app/Http/Controllers/Frontend/DealsController.php'));
        $this->assertStringContainsString(
            'Discount codes and sale prices supplied by vendors. Confirm the price on the vendor site. Catalog data only: RUO, and Peptidemap sells nothing.',
            $deals
        );
        $this->assertStringContainsString(
            'Compare listed prices across vendors. Catalog data only: RUO.',
            file_get_contents(base_path('resources/js/Pages/Frontend/ProductDetail.vue'))
        );
        $this->assertStringContainsString(
            'Compare listed prices across vendors. Catalog data only: RUO.',
            file_get_contents(base_path('resources/js/Pages/Frontend/BrandProducts.vue'))
        );
        $this->assertStringContainsString(
            'Which {{ brand.name }} listings have the lowest listed price?',
            file_get_contents(base_path('resources/js/Pages/Frontend/Coupon.vue'))
        );
    }
}
