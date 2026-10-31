<?php

namespace Tests\Feature;

use App\Mail\VendorPluginUrlChanged;
use App\Models\Brand;
use App\Models\ScrapingConfig;
use App\Support\StoreUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class VendorPluginConnectFlagsTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'pmap_connectflagtoken0123456789abcd';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('vendor-plugin-connect:127.0.0.1');
    }

    public function test_defaults_keep_plaintext_lookup_and_specific_errors(): void
    {
        $this->assertFalse(config('vendor_plugin.throttle'));
        $this->assertFalse(config('vendor_plugin.hash_tokens'));
        $this->assertFalse(config('vendor_plugin.single_use_tokens'));
        $this->assertNull(config('vendor_plugin.token_ttl_minutes'));
        $this->assertFalse(config('vendor_plugin.reject_private_store_urls'));
        $this->assertFalse(config('vendor_plugin.generic_errors'));
        $this->assertFalse(config('vendor_plugin.alert_on_url_change'));

        $brand = $this->brand();
        Http::fake(['*' => Http::response([], 200)]);

        $this->postJson('/api/vendor-plugin/connect', $this->payload('pmap_wrong'))
            ->assertStatus(404)
            ->assertJsonPath('message', 'Connection token not recognized. Check the token PeptideMap gave you and try again.');

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))
            ->assertOk()
            ->assertJson(['ok' => true, 'brand_name' => 'Flag Lab']);

        $this->assertTrue(ScrapingConfig::where('vendor_id', $brand->id)->where('type', 'woo_api')->exists());
        $this->assertNull($brand->fresh()->connection_token_used_at);
    }

    public function test_throttle_flag_off_does_not_limit_and_on_returns_429(): void
    {
        $this->brand();
        Http::fake(['*' => Http::response([], 200)]);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();
        }

        config(['vendor_plugin.throttle' => true, 'vendor_plugin.throttle_max' => 2]);
        RateLimiter::clear('vendor-plugin-connect:127.0.0.1');

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many attempts. Try again later.');
    }

    public function test_hash_tokens_flag_compares_sha256_with_hash_equals(): void
    {
        $brand = $this->brand();
        Http::fake(['*' => Http::response([], 200)]);

        config(['vendor_plugin.hash_tokens' => true]);
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertStatus(404);

        $brand->connection_token = hash('sha256', self::TOKEN);
        $brand->save();

        $this->postJson('/api/vendor-plugin/connect', $this->payload('pmap_wrong'))->assertStatus(404);
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))
            ->assertOk()
            ->assertJson(['ok' => true]);

        config(['vendor_plugin.hash_tokens' => false]);
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertStatus(404);
    }

    public function test_single_use_flag_blocks_a_second_connect_only_when_enabled(): void
    {
        $brand = $this->brand();
        Http::fake(['*' => Http::response([], 200)]);

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();
        $this->assertNull($brand->fresh()->connection_token_used_at);

        config(['vendor_plugin.single_use_tokens' => true]);
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();
        $this->assertNotNull($brand->fresh()->connection_token_used_at);
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Connection token has already been used.');
    }

    public function test_token_ttl_rejects_stale_tokens_only_when_configured(): void
    {
        $brand = $this->brand();
        Http::fake(['*' => Http::response([], 200)]);

        $brand->connection_token_issued_at = now()->subHours(2);
        $brand->save();

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();

        config(['vendor_plugin.token_ttl_minutes' => 30]);
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Connection token is no longer valid.');

        $brand->connection_token_issued_at = now()->subMinutes(5);
        $brand->save();
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();

        $brand->connection_token_issued_at = null;
        $brand->save();
        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN))->assertOk();
    }

    public function test_private_store_urls_are_rejected_only_when_the_flag_is_on(): void
    {
        $this->assertTrue(StoreUrlGuard::isBlocked('http://127.0.0.1/shop'));
        $this->assertTrue(StoreUrlGuard::isBlocked('http://10.1.2.3/shop'));
        $this->assertTrue(StoreUrlGuard::isBlocked('http://192.168.1.9/shop'));
        $this->assertTrue(StoreUrlGuard::isBlocked('http://172.16.4.4/shop'));
        $this->assertTrue(StoreUrlGuard::isBlocked('http://169.254.1.1/shop'));
        $this->assertTrue(StoreUrlGuard::isBlocked('http://[::1]/shop'));
        $this->assertTrue(StoreUrlGuard::isBlocked('http://[fe80::1]/shop'));
        $this->assertFalse(StoreUrlGuard::isBlocked('http://8.8.8.8/shop'));

        $this->brand();
        Http::fake(['*' => Http::response([], 200)]);

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN, 'http://127.0.0.1'))
            ->assertOk();

        config(['vendor_plugin.reject_private_store_urls' => true]);
        Http::fake(['*' => Http::response([], 200)]);

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN, 'http://127.0.0.1'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'That store URL is not allowed.');
        $this->postJson('/api/vendor-plugin/connect', $this->payload('pmap_wrong', 'http://192.168.0.5'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'That store URL is not allowed.');
        Http::assertNothingSent();

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN, 'https://shop.example.test'))
            ->assertOk();
    }

    public function test_generic_errors_hide_whether_a_token_exists(): void
    {
        $brand = $this->brand();
        Http::fake(['*' => Http::response([], 401)]);
        config(['vendor_plugin.generic_errors' => true]);

        $unknown = $this->postJson('/api/vendor-plugin/connect', $this->payload('pmap_wrong'));
        $known = $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN));

        $unknown->assertStatus(422)->assertExactJson([
            'ok' => false,
            'message' => 'Could not connect this store.',
        ]);
        $known->assertExactJson($unknown->json());
        $this->assertStringNotContainsString($brand->name, $known->getContent());
        $this->assertStringNotContainsString('not recognized', $unknown->getContent());
        $this->assertStringNotContainsString('401', $known->getContent());
    }

    public function test_url_change_alert_fires_only_when_enabled_and_the_url_differs(): void
    {
        $brand = $this->brand();
        ScrapingConfig::create([
            'vendor_id' => $brand->id,
            'vendor_name' => $brand->name,
            'type' => 'woo_api',
            'store_url' => 'https://old.example.test',
            'products_url' => 'https://old.example.test/feed',
            'enabled' => true,
            'frequency' => 'daily',
        ]);
        Http::fake(['*' => Http::response([], 200)]);
        Mail::fake();
        $logged = $this->captureLogs();

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN, 'https://new.example.test'))
            ->assertOk();
        $this->assertSame([], $this->urlChangeAlerts($logged));
        Mail::assertNothingSent();

        config([
            'vendor_plugin.alert_on_url_change' => true,
            'vendor_plugin.alert_mail' => 'ops@peptidemap.test',
        ]);

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN, 'https://new.example.test'))
            ->assertOk();
        $this->assertSame([], $this->urlChangeAlerts($logged));
        Mail::assertNothingSent();

        $this->postJson('/api/vendor-plugin/connect', $this->payload(self::TOKEN, 'https://other.example.test'))
            ->assertOk();
        $alerts = $this->urlChangeAlerts($logged);
        $this->assertCount(1, $alerts);
        $this->assertSame($brand->id, $alerts[0]->context['brand_id']);
        $this->assertSame('https://other.example.test', $alerts[0]->context['store_url']);
        $this->assertArrayNotHasKey('connection_token', $alerts[0]->context);
        $this->assertArrayNotHasKey('consumer_secret', $alerts[0]->context);
        Mail::assertSent(VendorPluginUrlChanged::class, function (VendorPluginUrlChanged $mail) use ($brand) {
            return $mail->hasTo('ops@peptidemap.test')
                && $mail->brandSlug === $brand->slug
                && $mail->storeUrl === 'https://other.example.test';
        });
    }

    public function test_rotate_command_is_dry_run_safe_and_hashes_only_when_that_flag_is_on(): void
    {
        $brand = $this->brand();
        $original = $brand->connection_token;
        $logged = $this->captureLogs();

        $this->artisan('vendors:rotate-connection-tokens', ['--dry-run' => true, '--all' => true])
            ->expectsOutputToContain('flag-lab')
            ->doesntExpectOutputToContain($original)
            ->assertSuccessful();
        $this->assertSame($original, $brand->fresh()->connection_token);

        $this->artisan('vendors:rotate-connection-tokens')
            ->expectsOutputToContain('--brand')
            ->assertFailed();

        $this->artisan('vendors:rotate-connection-tokens', ['--brand' => ['flag-lab']])
            ->expectsConfirmation('Rotate connection tokens for 1 brand? Existing plugin tokens for those brands will stop working.', 'no')
            ->assertSuccessful();
        $this->assertSame($original, $brand->fresh()->connection_token);

        $this->artisan('vendors:rotate-connection-tokens', ['--brand' => ['flag-lab']])
            ->expectsConfirmation('Rotate connection tokens for 1 brand? Existing plugin tokens for those brands will stop working.', 'yes')
            ->expectsOutputToContain('token=pmap_')
            ->assertSuccessful();

        $rotated = $brand->fresh();
        $this->assertNotSame($original, $rotated->connection_token);
        $this->assertMatchesRegularExpression('/^pmap_[A-Za-z0-9]{40}$/', $rotated->connection_token);
        $this->assertNull($rotated->connection_token_used_at);
        $this->assertNotNull($rotated->connection_token_issued_at);
        $this->assertCount(0, $logged);

        config(['vendor_plugin.hash_tokens' => true]);
        $command = $this->app->make(\App\Console\Commands\RotateConnectionTokens::class);
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $tester->setInputs(['yes']);
        $tester->execute(['--brand' => [(string) $brand->id]]);
        $this->assertSame(0, $tester->getStatusCode());
        $output = $tester->getDisplay();
        $this->assertSame(1, preg_match('/token=(pmap_[A-Za-z0-9]{40})/', $output, $matches));
        $plaintext = $matches[1];
        $stored = $brand->fresh()->connection_token;
        $this->assertSame(hash('sha256', $plaintext), $stored);
        $this->assertNotSame($plaintext, $stored);
        $this->assertTrue(hash_equals($stored, hash('sha256', $plaintext)));

        Http::fake(['*' => Http::response([], 200)]);
        $this->postJson('/api/vendor-plugin/connect', $this->payload($plaintext))->assertOk();
        foreach ($logged as $event) {
            $this->assertStringNotContainsString($plaintext, $event->message.' '.json_encode($event->context));
        }
    }

    private function captureLogs(): \ArrayObject
    {
        $logged = new \ArrayObject();
        Log::listen(function (MessageLogged $event) use ($logged) {
            $logged->append($event);
        });

        return $logged;
    }

    /**
     * @return list<MessageLogged>
     */
    private function urlChangeAlerts(\ArrayObject $logged): array
    {
        return array_values(array_filter(
            $logged->getArrayCopy(),
            fn (MessageLogged $event) => $event->message === 'Plugin connect changed a vendor store URL'
        ));
    }

    private function brand(): Brand
    {
        return Brand::create([
            'name' => 'Flag Lab',
            'slug' => 'flag-lab',
            'is_active' => true,
            'connection_token' => self::TOKEN,
        ]);
    }

    private function payload(string $token, string $storeUrl = 'https://shop.example.test'): array
    {
        return [
            'connection_token' => $token,
            'store_url' => $storeUrl,
            'consumer_key' => 'ck_qa_0123456789',
            'consumer_secret' => 'cs_qa_0123456789',
        ];
    }

}
