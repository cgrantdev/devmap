<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductClick;
use App\Models\VendorSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundClickTest extends TestCase
{
    use RefreshDatabase;

    public function test_go_route_redirects_to_raw_product_url_when_no_template(): void
    {
        $brand = Brand::create([
            'name' => 'Acme Peptides',
            'slug' => 'acme-peptides',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'brand_id' => $brand->id,
            'price' => 49.99,
            'product_url' => 'https://acme.example.com/products/bpc-157',
        ]);

        $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}");

        $expected = 'https://acme.example.com/products/bpc-157?utm_source=peptidemap&utm_medium=affiliate&utm_campaign=acme-peptides';

        $response->assertStatus(302);
        $response->assertRedirect($expected);

        $this->assertDatabaseCount('product_clicks', 1);
        $click = ProductClick::first();
        $this->assertEquals($product->id, $click->product_id);
        $this->assertEquals($brand->id, $click->brand_id);
        $this->assertFalse($click->is_bot);
        $this->assertEquals($expected, $click->destination_url);
    }

    public function test_go_route_substitutes_affiliate_url_template_placeholders(): void
    {
        $brand = Brand::create([
            'name' => 'Acme Peptides',
            'slug' => 'acme-peptides',
            'is_active' => true,
            'affiliate_url_template' => 'https://track.acme.com/product/{slug}?ref={affiliate_tag}&id={id}',
            'affiliate_tag' => 'peptidemap',
        ]);

        $product = Product::create([
            'name' => 'TB-500',
            'slug' => 'tb-500',
            'brand_id' => $brand->id,
            'price' => 59.99,
            'product_url' => 'https://acme.example.com/products/tb-500',
        ]);

        $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}");

        $response->assertStatus(302);
        $expected = "https://track.acme.com/product/tb-500?ref=peptidemap&id={$product->id}&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=acme-peptides";
        $response->assertRedirect($expected);

        $this->assertDatabaseHas('product_clicks', [
            'product_id' => $product->id,
            'destination_url' => $expected,
            'is_bot' => false,
        ]);
    }

    public function test_bot_user_agents_are_flagged_but_still_redirected(): void
    {
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $product = Product::create([
            'name' => 'Peptide',
            'slug' => 'peptide',
            'brand_id' => $brand->id,
            'product_url' => 'https://acme.example.com/p',
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->get("/go/{$product->id}");

        $response->assertStatus(302);
        $this->assertDatabaseHas('product_clicks', [
            'product_id' => $product->id,
            'is_bot' => true,
        ]);
    }

    public function test_ip_address_is_hashed_not_stored_raw(): void
    {
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $product = Product::create([
            'name' => 'Peptide',
            'slug' => 'peptide',
            'brand_id' => $brand->id,
            'product_url' => 'https://acme.example.com/p',
        ]);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get("/go/{$product->id}");

        $click = ProductClick::first();
        $this->assertNotNull($click->ip_hash);
        // SHA-256 hex digest is 64 chars
        $this->assertEquals(64, strlen($click->ip_hash));
        // Should not contain dots or colons (so it's not a raw IP)
        $this->assertStringNotContainsString('.', $click->ip_hash);
        $this->assertStringNotContainsString(':', $click->ip_hash);
    }

    public function test_go_route_returns_404_for_nonexistent_product(): void
    {
        $response = $this->get('/go/999999');
        $response->assertStatus(404);
        $this->assertDatabaseCount('product_clicks', 0);
    }

    public function test_go_route_falls_back_to_product_page_when_no_destination(): void
    {
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $product = Product::create([
            'name' => 'No URL Product',
            'slug' => 'no-url-product',
            'brand_id' => $brand->id,
            'product_url' => null,
        ]);

        $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get("/go/{$product->id}");

        $response->assertStatus(302);
        // Should redirect to the internal product detail page, not log a "real" click
        $this->assertStringContainsString('/product/', $response->headers->get('Location'));
    }

    public function test_utm_params_are_captured(): void
    {
        $brand = Brand::create(['name' => 'Acme', 'slug' => 'acme']);
        $product = Product::create([
            'name' => 'Peptide',
            'slug' => 'peptide',
            'brand_id' => $brand->id,
            'product_url' => 'https://acme.example.com/p',
        ]);

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get("/go/{$product->id}?utm_source=reddit&utm_medium=post&utm_campaign=launch");

        $this->assertDatabaseHas('product_clicks', [
            'product_id' => $product->id,
            'utm_source' => 'reddit',
            'utm_medium' => 'post',
            'utm_campaign' => 'launch',
        ]);
    }

    /**
     * Sampled live before-fix behavior (Sep 29 2026, bot UA, no redirect follow):
     *   /go/935  product_url https://hydroresearchpeptides.com/product/h-t-100-mg/
     *            302 https://hydroresearchpeptides.com/?ref=PeptideMap&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=hydro-research
     *   /go/2588 product_url https://nurapeptide.com/product/copper-peptide-cream/?attribute_quantity=10+Units
     *            302 https://nurapeptide.com/?ref=pmap&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=nura-systems-llc
     * The referral link was the vendor homepage. The hop must keep the
     * product path and the same-site affiliate query.
     */
    public function test_product_url_wins_over_same_host_homepage_referral_and_keeps_affiliate_params(): void
    {
        $product = $this->makeGoProduct(
            brand: ['name' => 'Hydro Research', 'slug' => 'hydro-research'],
            product: [
                'name' => 'Teduglutide (100mg)',
                'slug' => 'h-t-100-mg',
                'product_url' => 'https://hydro.example.com/product/h-t-100-mg/',
            ],
            settings: ['referral_url' => 'https://www.hydro.example.com/?ref=PeptideMap'],
        );

        $expected = 'https://hydro.example.com/product/h-t-100-mg/?ref=PeptideMap&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=hydro-research';

        $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}");

        $response->assertStatus(302);
        $response->assertRedirect($expected);
        $this->assertStringNotContainsString('https://www.hydro.example.com/?', $response->headers->get('Location'));
        $this->assertDatabaseHas('product_clicks', [
            'product_id' => $product->id,
            'destination_url' => $expected,
        ]);
    }

    /**
     * Sampled /go/1258 before the fix: product_url
     * https://biolongevitylabs.com/product/follistatin/ redirected to
     * https://affiliates.biolongevitylabs.com/signup/2468 (affiliate
     * signup on another host). A cross-host referral URL is not a product
     * deep link and is not merged. PeptideMap UTMs are still appended.
     */
    public function test_product_url_wins_over_cross_host_referral_landing_page(): void
    {
        $product = $this->makeGoProduct(
            brand: ['name' => 'BioLongevity Labs', 'slug' => 'biolongevity-labs'],
            product: [
                'name' => 'Follistatin (10mg)',
                'slug' => 'follistatin',
                'product_url' => 'https://biolongevity.example.com/product/follistatin/',
            ],
            settings: ['referral_url' => 'https://affiliates.biolongevity.example.com/signup/2468'],
        );

        $expected = 'https://biolongevity.example.com/product/follistatin/?utm_source=peptidemap&utm_medium=affiliate&utm_campaign=biolongevity-labs';

        $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}");

        $response->assertRedirect($expected);
        $location = $response->headers->get('Location');
        $this->assertStringNotContainsString('affiliates.biolongevity.example.com', $location);
        $this->assertStringNotContainsString('/signup/', $location);
    }

    public function test_product_query_and_empty_affiliate_params_survive_referral_merge(): void
    {
        $product = $this->makeGoProduct(
            brand: ['slug' => 'nura-systems-llc'],
            product: [
                'product_url' => 'https://nura.example.com/product/copper-peptide-cream/?attribute_quantity=10+Units',
            ],
            settings: ['referral_url' => 'https://nura.example.com/?_ef_transaction_id=&oid=1&affid=42'],
        );

        $expected = 'https://nura.example.com/product/copper-peptide-cream/?_ef_transaction_id=&oid=1&affid=42&attribute_quantity=10+Units&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=nura-systems-llc';

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}")
            ->assertRedirect($expected);
    }

    public function test_same_host_referral_without_query_does_not_replace_product_path(): void
    {
        $product = $this->makeGoProduct(
            product: ['product_url' => 'https://acme.example.com/products/bpc-157'],
            settings: ['referral_url' => 'https://acme.example.com/'],
        );

        $expected = 'https://acme.example.com/products/bpc-157?utm_source=peptidemap&utm_medium=affiliate&utm_campaign=acme-peptides';

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}")
            ->assertRedirect($expected);
    }

    public function test_affiliate_template_still_builds_product_link_when_homepage_referral_is_set(): void
    {
        $product = $this->makeGoProduct(
            brand: [
                'affiliate_url_template' => 'https://track.acme.com/product/{slug}?url={product_url}&ref={affiliate_tag}',
                'affiliate_tag' => 'peptidemap',
            ],
            product: [
                'slug' => 'tb-500',
                'product_url' => 'https://acme.example.com/products/tb-500',
            ],
            settings: ['referral_url' => 'https://acme.example.com/?ref=HOME'],
        );

        $expected = 'https://track.acme.com/product/tb-500?url=https%3A%2F%2Facme.example.com%2Fproducts%2Ftb-500&ref=peptidemap&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=acme-peptides';

        $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}");

        $response->assertRedirect($expected);
        $this->assertStringNotContainsString('ref=HOME', $response->headers->get('Location'));
    }

    public function test_shopify_coupon_redirect_keeps_product_path_and_referral_param(): void
    {
        $product = $this->makeGoProduct(
            product: ['product_url' => 'https://acme.example.com/products/bpc-157'],
            settings: [
                'referral_url' => 'https://acme.example.com/?ref=PMAP',
                'coupon_code' => 'PMAP',
                'coupon_discount_percent' => 10,
            ],
        );

        $expected = 'https://acme.example.com/discount/PMAP?redirect=%2Fproducts%2Fbpc-157%3Fref%3DPMAP&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=acme-peptides';

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}")
            ->assertRedirect($expected);
    }

    /**
     * Fallback when product_url is empty:
     *   1. referral_url (vendor affiliate landing)
     *   2. affiliate_url_template that does not need {product_url}
     *   3. internal /product/{vendor}/{slug}/{id} page (no click log)
     */
    public function test_empty_product_url_falls_back_to_referral_url(): void
    {
        $product = $this->makeGoProduct(
            product: ['product_url' => null, 'slug' => 'no-url-product'],
            settings: ['referral_url' => 'https://acme.example.com/?ref=PeptideMap'],
        );

        $expected = 'https://acme.example.com/?ref=PeptideMap&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=acme-peptides';

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}")
            ->assertRedirect($expected);
    }

    public function test_empty_product_url_prefers_referral_over_affiliate_template(): void
    {
        $product = $this->makeGoProduct(
            brand: [
                'affiliate_url_template' => 'https://track.acme.com/brand/{slug}?ref={affiliate_tag}',
                'affiliate_tag' => 'peptidemap',
            ],
            product: ['product_url' => null, 'slug' => 'missing-url'],
            settings: ['referral_url' => 'https://acme.example.com/?ref=PeptideMap'],
        );

        $expected = 'https://acme.example.com/?ref=PeptideMap&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=acme-peptides';

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}")
            ->assertRedirect($expected);
    }

    public function test_empty_product_url_uses_template_when_referral_is_missing(): void
    {
        $product = $this->makeGoProduct(
            brand: [
                'affiliate_url_template' => 'https://track.acme.com/brand/{slug}?ref={affiliate_tag}',
                'affiliate_tag' => 'peptidemap',
            ],
            product: ['product_url' => null, 'slug' => 'missing-url'],
        );

        $expected = 'https://track.acme.com/brand/missing-url?ref=peptidemap&utm_source=peptidemap&utm_medium=affiliate&utm_campaign=acme-peptides';

        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Test)'])
            ->get("/go/{$product->id}")
            ->assertRedirect($expected);
    }

    private function makeGoProduct(array $brand = [], array $product = [], ?array $settings = null): Product
    {
        $brandModel = Brand::create(array_merge([
            'name' => 'Acme Peptides',
            'slug' => 'acme-peptides',
            'is_active' => true,
        ], $brand));

        if ($settings !== null) {
            VendorSetting::create(array_merge([
                'brand_id' => $brandModel->id,
            ], $settings));
        }

        return Product::create(array_merge([
            'name' => 'BPC-157',
            'slug' => 'bpc-157',
            'brand_id' => $brandModel->id,
            'price' => 49.99,
            'product_url' => 'https://acme.example.com/products/bpc-157',
        ], $product));
    }
}
