<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVendorSecretLeakTest extends TestCase
{
    use RefreshDatabase;

    private const SENTINELS = [
        'pmap_TESTSECRET',
        'APIKEY_TESTSECRET',
        'AFF_TESTSECRET',
        'FEED_TESTSECRET',
        'APIROUTE_TESTSECRET',
        'TEMPLATE_TESTSECRET',
        'TAG_TESTSECRET',
        'STATS_TESTSECRET',
        'PLATFORM_TESTSECRET',
        'AFFPLATFORM_TESTSECRET',
        'BOOST_TESTSECRET',
        'PREV_TESTSECRET',
        '2031-04-04',
    ];

    private const SECRET_COLUMNS = [
        'connection_token',
        'api_key',
        'api_route',
        'feed_url',
        'affiliate_credentials',
        'affiliate_stats_json',
        'affiliate_url_template',
        'affiliate_tag',
        'commission_rate_pct',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_pages_do_not_serialize_vendor_or_brand_secrets(): void
    {
        $brand = Brand::create([
            'name' => 'Sentinel Labs',
            'slug' => 'sentinel-labs',
            'is_active' => true,
            'connection_token' => 'pmap_TESTSECRET',
            'affiliate_url_template' => 'https://track.example/hook?secret=TEMPLATE_TESTSECRET',
            'affiliate_tag' => 'TAG_TESTSECRET',
        ]);

        VendorSetting::create([
            'brand_id' => $brand->id,
            'approval_status' => 'approved',
            'status' => 1,
            'description' => 'Ships research peptides with a public coupon.',
            'shop_url' => 'https://shop.sentinel-labs.test',
            'referral_url' => 'https://shop.sentinel-labs.test/welcome',
            'contact_email' => 'hello@sentinel-labs.test',
            'coupon_code' => 'SAVE10',
            'coupon_discount_percent' => 10,
            'api_key' => 'APIKEY_TESTSECRET',
            'api_route' => 'APIROUTE_TESTSECRET',
            'api_platform' => 'PLATFORM_TESTSECRET',
            'feed_url' => 'https://feed.example/webhook?token=FEED_TESTSECRET',
            'affiliate_credentials' => 'AFF_TESTSECRET',
            'affiliate_platform' => 'AFFPLATFORM_TESTSECRET',
            'affiliate_stats_json' => ['token' => 'STATS_TESTSECRET'],
            'commission_rate_pct' => 17.25,
            'coupon_boost_code' => 'BOOST_TESTSECRET',
            'coupon_code_previous' => 'PREV_TESTSECRET',
            'coupon_boost_starts_at' => '2031-04-04 04:04:04',
            'coupon_discount_previous_percent' => 66.66,
            'coupon_boost_percent' => 77.77,
        ]);

        $bpc = ProductCategory::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'is_active' => true,
        ]);
        $tb = ProductCategory::create([
            'name' => 'TB-500',
            'slug' => 'tb-500',
            'is_active' => true,
        ]);

        $this->listing($bpc, $brand, 'bpc-157-5mg', 42.00);
        $this->listing($tb, $brand, 'tb-500-5mg', 48.00);

        $routes = [
            '/product/bpc-157',
            '/compare',
            '/compare/bpc-157',
            '/compare/bpc-157-vs-tb-500',
            '/brand/sentinel-labs',
            '/coupon/sentinel-labs',
            '/deals',
            '/',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertOk();
            $body = $response->getContent();
            $this->assertNoSecrets($body, $route);

            $props = $this->inertiaProps($body, $route);
            $encoded = json_encode($props);
            $this->assertIsString($encoded);
            $this->assertNoSecrets($encoded, $route.' props');
            $this->assertArrayHasKey('auth', $props, $route.' shared props');
            $this->assertArrayHasKey('site_name', $props, $route.' shared props');
        }

        $listingProps = $this->inertiaProps($this->get('/product/bpc-157')->assertOk()->getContent(), 'listing display');
        $cards = collect($listingProps['products']['data'] ?? []);
        $this->assertTrue($cards->pluck('brand.name')->contains('Sentinel Labs'));
        $this->assertTrue($cards->pluck('brand_coupon_code')->contains('SAVE10'));
        $this->assertTrue($cards->contains(fn ($card) => (float) $card['brand_discount_percent'] === 10.0));

        $brandProps = $this->inertiaProps($this->get('/brand/sentinel-labs')->assertOk()->getContent(), 'brand display');
        $this->assertSame('Sentinel Labs', $brandProps['brand']['name']);
        $this->assertSame('SAVE10', $brandProps['brand']['coupon_code']);
        $this->assertSame('Ships research peptides with a public coupon.', $brandProps['brand']['description']);
        $this->assertSame('hello@sentinel-labs.test', $brandProps['brand']['contact_email']);
        $this->assertSame('https://shop.sentinel-labs.test', $brandProps['brand']['shop_url']);

        $couponProps = $this->inertiaProps($this->get('/coupon/sentinel-labs')->assertOk()->getContent(), 'coupon display');
        $this->assertSame('Sentinel Labs', $couponProps['brand']['name']);
        $this->assertSame('SAVE10', $couponProps['coupon']['code']);
    }

    private function listing(ProductCategory $category, Brand $brand, string $slug, float $price): void
    {
        Product::create([
            'name' => $category->name.' '.$slug,
            'slug' => $slug,
            'brand_id' => $brand->id,
            'product_category_id' => $category->id,
            'price' => $price,
            'status' => 'active',
            'hidden' => false,
        ]);
    }

    private function assertNoSecrets(string $body, string $label): void
    {
        foreach (self::SENTINELS as $sentinel) {
            $this->assertStringNotContainsString($sentinel, $body, $label.' leaked '.$sentinel);
        }
        foreach (self::SECRET_COLUMNS as $column) {
            $this->assertStringNotContainsString($column, $body, $label.' leaked column '.$column);
        }
    }

    private function inertiaProps(string $html, string $label): array
    {
        $this->assertSame(
            1,
            preg_match('/data-page="([^"]*)"/', $html, $matches),
            $label.' is missing the Inertia page payload'
        );
        $page = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);
        $this->assertIsArray($page, $label.' Inertia payload did not decode');

        return $page['props'] ?? [];
    }
}
