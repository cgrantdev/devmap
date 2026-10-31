<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\VendorSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression guard: vendor credentials (brands.connection_token,
 * vendor_settings.api_key / affiliate_credentials) never reach public
 * pages (HTML or X-Inertia JSON), while the vendor name/logo still render.
 * Uses assertFalse(str_contains()) so a failure never dumps the body.
 */
class NoSecretPropsTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'qa-sentinel-api-key-1234';
    private const AFF_CREDS = 'qa-sentinel-affiliate-creds';

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        Storage::disk('public')->put('logos/alpha-lab.png', 'png');

        $bpc = ProductCategory::create(['name' => 'BPC-157', 'slug' => 'bpc-157', 'is_active' => true]);
        $tb = ProductCategory::create(['name' => 'TB-500', 'slug' => 'tb-500', 'is_active' => true]);

        $this->brand = Brand::create(['name' => 'Alpha Lab', 'slug' => 'alpha-lab', 'is_active' => true]);
        VendorSetting::create([
            'brand_id' => $this->brand->id,
            'status' => 1,
            'approval_status' => 'approved',
            'logo' => 'logos/alpha-lab.png',
            'api_key' => self::API_KEY,
            'affiliate_credentials' => self::AFF_CREDS,
            'why_choose_bullets' => ['Third-party tested'],
            'coupon_code' => 'ALPHA10',
            'coupon_discount_percent' => 10,
            'shop_url' => 'https://alpha.example',
        ]);

        foreach ([[$bpc, 'bpc-157-5mg-alpha'], [$tb, 'tb-500-5mg-alpha']] as [$cat, $slug]) {
            Product::create([
                'name' => $cat->name.' 5mg',
                'slug' => $slug,
                'brand_id' => $this->brand->id,
                'product_category_id' => $cat->id,
                'price' => 40,
                'status' => 'active',
                'hidden' => false,
                'product_url' => 'https://alpha.example/p/'.$slug,
            ]);
        }
    }

    public static function publicPages(): array
    {
        return [
            'home' => ['/'],
            'product listing' => ['/product/bpc-157'],
            'compare compound' => ['/compare/bpc-157'],
            'compare vs' => ['/compare/bpc-157-vs-tb-500'],
            'brand page' => ['/brand/alpha-lab'],
        ];
    }

    /** @dataProvider publicPages */
    public function test_public_page_html_has_no_vendor_secrets(string $url): void
    {
        $this->assertNoSecrets($this->get($url)->assertOk()->getContent(), $url);
    }

    /** @dataProvider publicPages */
    public function test_public_page_inertia_json_has_no_vendor_secrets(string $url): void
    {
        $version = app(\App\Http\Middleware\HandleInertiaRequests::class)->version(request());
        $json = $this->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) $version,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get($url)->assertOk()->getContent();
        $this->assertNoSecrets($json, $url.' (X-Inertia)');
    }

    public function test_vendor_name_and_logo_still_render(): void
    {
        $this->get('/product/bpc-157')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/ProductListing')
                ->where('products.data.0.brand.name', 'Alpha Lab')
                ->where('products.data.0.brand_coupon_code', 'ALPHA10')
                ->etc());

        $brandPage = $this->get('/brand/alpha-lab')->assertOk();
        $brandPage->assertInertia(fn ($page) => $page
            ->component('Frontend/BrandProducts')
            ->where('brand.name', 'Alpha Lab')
            ->where('products.data.0.brand.name', 'Alpha Lab')
            ->etc());
        $this->assertTrue(str_contains($brandPage->getContent(), 'alpha-lab.png'), 'brand logo missing');
    }

    private function assertNoSecrets(string $body, string $where): void
    {
        $token = (string) $this->brand->fresh()->connection_token;
        $this->assertNotSame('', $token, 'fixture brand should have a generated token');

        $this->assertFalse(str_contains($body, 'connection_token'), "$where exposes connection_token key");
        $this->assertFalse(str_contains($body, $token), "$where exposes a connection token value");
        $this->assertFalse(str_contains($body, self::API_KEY), "$where exposes a decrypted vendor api_key");
        $this->assertFalse(str_contains($body, self::AFF_CREDS), "$where exposes affiliate_credentials");
        $this->assertSame(0, preg_match('/"(api_key|affiliate_credentials)"/', $body), "$where exposes secret key names");

        // Product cards may carry a slim brand.vendor_setting (coupon fields
        // behind brand_coupon_code); allow it only with public keys.
        preg_match_all('/"vendor_setting":\{([^{}]*)\}/', html_entity_decode($body, ENT_QUOTES), $m);
        foreach ($m[1] as $obj) {
            $keys = array_keys(json_decode('{'.$obj.'}', true) ?? []);
            $this->assertSame([], array_values(array_diff($keys, VendorSetting::PUBLIC_COLUMNS)), "$where vendor_setting has non-public keys");
        }
    }
}
