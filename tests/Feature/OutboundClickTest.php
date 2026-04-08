<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductClick;
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

        $response->assertStatus(302);
        $response->assertRedirect('https://acme.example.com/products/bpc-157');

        $this->assertDatabaseCount('product_clicks', 1);
        $click = ProductClick::first();
        $this->assertEquals($product->id, $click->product_id);
        $this->assertEquals($brand->id, $click->brand_id);
        $this->assertFalse($click->is_bot);
        $this->assertEquals('https://acme.example.com/products/bpc-157', $click->destination_url);
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
        $expected = "https://track.acme.com/product/tb-500?ref=peptidemap&id={$product->id}";
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
        $this->assertStringContainsString("/product/", $response->headers->get('Location'));
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
}
