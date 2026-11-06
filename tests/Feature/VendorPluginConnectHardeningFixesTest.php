<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\ScrapingConfig;
use App\Support\StoreUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** QA follow-up tests for PR #91 fixes. Fake tokens only. */
class VendorPluginConnectHardeningFixesTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $token, string $url = 'https://shop.qa.test'): array
    {
        return [
            'connection_token' => $token,
            'store_url' => $url,
            'consumer_key' => 'ck_1234567890abcdef',
            'consumer_secret' => 'cs_1234567890abcdef',
        ];
    }

    public function test_guard_blocks_numeric_shorthand_and_non_public_ranges(): void
    {
        foreach ([
            'http://2130706433', 'http://0x7f000001', 'http://127.1', 'http://017700000001',
            'http://0177.0.0.1', 'http://100.64.0.1', 'http://198.18.0.1', 'http://224.0.0.1',
            'http://[64:ff9b::7f00:1]', 'http://[2002:7f00:1::]', 'http://[2001:0:4136:e378::1]',
            'http://[::ffff:10.0.0.1]', 'http://[::127.0.0.1]', 'http://[ff02::1]',
            'http://localhost', 'http://redis', 'http://shop.qa.test.', 'http://user:pw@8.8.8.8',
            'http://8.8.8.8:6379', 'https://8.8.8.8:22', 'ftp://8.8.8.8', 'gopher://8.8.8.8',
            'http://qa-unresolvable-host.invalid',
        ] as $url) {
            $this->assertTrue(StoreUrlGuard::isBlocked($url), "$url should be blocked");
        }
    }

    public function test_guard_allows_public_literals_and_returns_pin_target(): void
    {
        $this->assertSame(['host' => '8.8.8.8', 'port' => 443, 'ips' => ['8.8.8.8']], StoreUrlGuard::resolveAllowed('https://8.8.8.8/shop'));
        $this->assertSame(8080, StoreUrlGuard::resolveAllowed('http://1.1.1.1:8080')['port']);
        $this->assertFalse(StoreUrlGuard::isBlocked('http://[2606:4700:4700::1111]/'));
    }

    public function test_connect_rejects_numeric_loopback_when_flag_on_without_probing(): void
    {
        config(['vendor_plugin.reject_private_store_urls' => true]);
        $brand = Brand::create(['name' => 'Fix Lab', 'slug' => 'fix-lab', 'is_active' => true]);
        Http::fake(['*' => Http::response([], 200)]);

        $this->postJson('/api/vendor-plugin/connect', $this->payload($brand->plainConnectionToken, 'http://2130706433:8080'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'That store URL is not allowed.');
        Http::assertNothingSent();
    }

    public function test_new_brand_stores_hash_when_hash_flag_on_and_can_connect(): void
    {
        config(['vendor_plugin.hash_tokens' => true]);
        $brand = Brand::create(['name' => 'Hash Lab', 'slug' => 'hash-lab', 'is_active' => true]);
        $plain = $brand->plainConnectionToken;

        $this->assertNotNull($plain);
        $this->assertStringStartsWith('pmap_', $plain);
        $stored = (string) Brand::withoutGlobalScopes()->whereKey($brand->id)->value('connection_token');
        $this->assertSame(hash('sha256', $plain), $stored);
        $this->assertFalse(str_contains(json_encode($brand->makeVisible('connection_token')->toArray()), $plain));
        $this->assertNull(Brand::find($brand->id)->plainConnectionToken);

        Http::fake(['*' => Http::response([], 200)]);
        $this->postJson('/api/vendor-plugin/connect', $this->payload($plain))->assertOk()->assertJsonPath('ok', true);
    }

    public function test_new_brand_stores_plaintext_when_hash_flag_off(): void
    {
        $brand = Brand::create(['name' => 'Plain Lab', 'slug' => 'plain-lab', 'is_active' => true]);
        $stored = (string) Brand::withoutGlobalScopes()->whereKey($brand->id)->value('connection_token');
        $this->assertTrue(hash_equals($brand->plainConnectionToken, $stored));
    }

    public function test_single_use_is_claimed_before_writes_and_second_use_changes_nothing(): void
    {
        config(['vendor_plugin.single_use_tokens' => true]);
        $brand = Brand::create(['name' => 'Once Lab', 'slug' => 'once-lab', 'is_active' => true]);
        Http::fake(['*' => Http::response([], 200)]);

        $this->postJson('/api/vendor-plugin/connect', $this->payload($brand->plainConnectionToken))->assertOk();
        $this->assertNotNull($brand->fresh()->connection_token_used_at);

        $this->postJson('/api/vendor-plugin/connect', $this->payload($brand->plainConnectionToken, 'https://attacker.qa.test'))
            ->assertStatus(422);
        $this->assertSame('https://shop.qa.test', ScrapingConfig::where('vendor_id', $brand->id)->value('store_url'));
    }

    public function test_alert_mail_failure_does_not_fail_connect_or_leave_token_reusable(): void
    {
        config([
            'vendor_plugin.single_use_tokens' => true,
            'vendor_plugin.alert_on_url_change' => true,
            'vendor_plugin.alert_mail' => 'alerts@qa.test',
        ]);
        $brand = Brand::create(['name' => 'Alert Lab', 'slug' => 'alert-lab', 'is_active' => true]);
        ScrapingConfig::create([
            'vendor_id' => $brand->id, 'vendor_name' => 'Alert Lab', 'type' => 'woo_api',
            'store_url' => 'https://old.qa.test', 'products_url' => 'https://old.qa.test',
            'auth_credentials' => ['consumer_key' => 'ck_old', 'consumer_secret' => 'cs_old'],
            'enabled' => true, 'frequency' => 'daily', 'auto_promote' => true,
        ]);
        Http::fake(['*' => Http::response([], 200)]);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp down'));

        $this->postJson('/api/vendor-plugin/connect', $this->payload($brand->plainConnectionToken))->assertOk();
        $this->assertNotNull($brand->fresh()->connection_token_used_at);
    }
}
