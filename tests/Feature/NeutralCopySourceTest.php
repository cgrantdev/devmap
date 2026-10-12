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
            'app/Http/Controllers/Frontend/CompareController.php',
            'app/Http/Controllers/Frontend/HomeController.php',
            'config/seo.php',
            'resources/views/og/brand.blade.php',
        ];
        $phrases = [
            'Cheapest',
            'Best deals',
            'best deals',
            'Best price',
            'best vendors',
            'sorted cheapest-first',
            'Save up to',
            'usually 10',
            'From $',
            'coupon codes included',
        ];

        foreach ($files as $path) {
            $contents = file_get_contents(base_path($path));
            $this->assertNotFalse($contents, $path);
            foreach ($phrases as $phrase) {
                $this->assertStringNotContainsString($phrase, $contents, "{$path} still contains \"{$phrase}\"");
            }
        }
    }
}
