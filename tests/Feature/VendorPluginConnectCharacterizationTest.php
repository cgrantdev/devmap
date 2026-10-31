<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\ScrapingConfig;
use App\Models\VendorSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Characterization of POST /api/vendor-plugin/connect with every
 * hardening flag OFF. Runs unchanged on base (pre-PR #91) and head.
 *
 * Golden file: tests/Fixtures/vendor-plugin-connect-golden.json.
 * If it does not exist the test records it (run once on BASE), then
 * every later run, on any commit, must match it byte for byte:
 * status, raw response body, write SQL + bindings, final DB rows,
 * log records, and outbound HTTP requests.
 *
 * The only allowed difference is listed in INTENDED_CHANGES (the
 * "registration email" wording fix that PR #91 makes on purpose).
 * Uses fake tokens only; no real token is ever involved.
 */
class VendorPluginConnectCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'pmap_QACHARSENTINEL000000000000000000000000';

    /** head text => base text */
    private const INTENDED_CHANGES = [
        'Connection token not recognized. Check the token PeptideMap gave you and try again.'
            => 'Connection token not recognized. Verify it in your PeptideMap registration email.',
    ];

    /** Columns added by PR #91; excluded from the golden, checked separately. */
    private const NEW_BRAND_COLUMNS = ['connection_token_issued_at', 'connection_token_used_at'];

    private const FLAG_ENVS = [
        'VENDOR_PLUGIN_THROTTLE', 'VENDOR_PLUGIN_HASH_TOKENS', 'VENDOR_PLUGIN_SINGLE_USE_TOKENS',
        'VENDOR_PLUGIN_TOKEN_TTL_MINUTES', 'VENDOR_PLUGIN_REJECT_PRIVATE_STORE_URLS',
        'VENDOR_PLUGIN_GENERIC_ERRORS', 'VENDOR_PLUGIN_ALERT_ON_URL_CHANGE',
    ];

    private array $writes = [];
    private array $logs = [];

    public function test_connect_with_all_flags_off_matches_base_golden(): void
    {
        foreach (self::FLAG_ENVS as $env) {
            $this->assertEmpty(env($env), "$env must be unset for this test");
        }

        $results = [];
        foreach ($this->scenarios() as $name => $scenario) {
            $results[$name] = $this->runScenario($scenario);
        }

        $path = base_path('tests/Fixtures/vendor-plugin-connect-golden.json');
        $json = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
        $this->assertFalse(str_contains($json, self::TOKEN), 'golden must not contain the token');

        if (!is_file($path)) {
            @mkdir(dirname($path), 0777, true);
            file_put_contents($path, $json);
            $this->markTestIncomplete('Golden recorded at '.$path.'. Record on BASE, then re-run on head.');
        }

        $golden = json_decode(file_get_contents($path), true);
        foreach ($results as $name => $actual) {
            $this->assertSame($golden[$name] ?? null, json_decode(json_encode($actual), true), "scenario '$name' differs from base");
        }
        $this->assertSame(array_keys($golden), array_keys($results));
    }

    private function scenarios(): array
    {
        $ok = fn () => Http::fake(['*' => Http::response([['id' => 1]], 200)]);

        return [
            'success_new_config' => ['http' => $ok, 'payload' => [], 'vendor_setting' => true],
            'success_reconnect_changes_url' => ['http' => $ok, 'payload' => ['store_url' => 'https://moved.qa.test'], 'vendor_setting' => true, 'existing' => true],
            'success_no_vendor_setting' => ['http' => $ok, 'payload' => [], 'vendor_setting' => false],
            'success_demo_brand' => ['http' => $ok, 'payload' => [], 'vendor_setting' => true, 'demo' => true],
            'success_trailing_slash' => ['http' => $ok, 'payload' => ['store_url' => 'https://shop.qa.test/'], 'vendor_setting' => true],
            'bad_token' => ['http' => $ok, 'payload' => ['connection_token' => 'pmap_wrongwrongwrong'], 'vendor_setting' => true],
            'woo_401' => ['http' => fn () => Http::fake(['*' => Http::response(['code' => 'woocommerce_rest_cannot_view'], 401)]), 'payload' => [], 'vendor_setting' => true],
            'woo_500' => ['http' => fn () => Http::fake(['*' => Http::response('oops', 500)]), 'payload' => [], 'vendor_setting' => true],
            'store_unreachable' => ['http' => fn () => Http::fake(function () {
                throw new ConnectionException('cURL error 6: Could not resolve host: shop.qa.test');
            }), 'payload' => [], 'vendor_setting' => true],
            'private_store_url_flags_off' => ['http' => $ok, 'payload' => ['store_url' => 'http://127.0.0.1:8080'], 'vendor_setting' => true],
            'validation_bad_url' => ['http' => $ok, 'payload' => ['store_url' => 'not a url'], 'vendor_setting' => true],
            'validation_bad_consumer_key' => ['http' => $ok, 'payload' => ['consumer_key' => 'xx_1234567890'], 'vendor_setting' => true],
            'validation_missing_token' => ['http' => $ok, 'payload' => ['connection_token' => null], 'vendor_setting' => true],
        ];
    }

    private function runScenario(array $s): array
    {
        $this->refreshTables();

        $brand = Brand::create([
            'name' => 'Char Lab',
            'slug' => 'char-lab',
            'is_active' => true,
            'is_demo' => !empty($s['demo']),
            'connection_token' => self::TOKEN,
        ]);
        if ($s['vendor_setting']) {
            VendorSetting::create(['brand_id' => $brand->id, 'status' => 1, 'approval_status' => 'approved', 'api_platform' => 'manual']);
        }
        if (!empty($s['existing'])) {
            ScrapingConfig::create([
                'vendor_id' => $brand->id, 'vendor_name' => 'Char Lab', 'type' => 'woo_api',
                'store_url' => 'https://old.qa.test', 'products_url' => 'https://old.qa.test',
                'auth_credentials' => ['consumer_key' => 'ck_old', 'consumer_secret' => 'cs_old'],
                'enabled' => true, 'frequency' => 'daily', 'auto_promote' => true,
            ]);
        }
        $brandBefore = $this->brandNewColumns($brand->id);

        ($s['http'])();
        $this->writes = [];
        $this->logs = [];
        DB::listen(function ($q) {
            if (!preg_match('/^\s*select\b/i', $q->sql)) {
                $this->writes[] = ['sql' => $q->sql, 'bindings' => array_map([$this, 'mask'], $q->bindings)];
            }
        });
        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            $this->logs[] = ['level' => $e->level, 'message' => $e->message, 'context' => $this->maskDeep($e->context)];
        });

        $payload = array_merge([
            'connection_token' => self::TOKEN,
            'store_url' => 'https://shop.qa.test',
            'consumer_key' => 'ck_1234567890abcdef',
            'consumer_secret' => 'cs_1234567890abcdef',
        ], $s['payload']);
        $payload = array_filter($payload, fn ($v) => $v !== null);

        $response = $this->postJson('/api/vendor-plugin/connect', $payload);

        $sent = [];
        foreach (Http::recorded() as [$request]) {
            /** @var HttpRequest $request */
            $sent[] = [
                'method' => $request->method(),
                'url' => $request->url(),
                'auth' => $request->header('Authorization'),
                'accept' => $request->header('Accept'),
            ];
        }

        $this->assertSame($brandBefore, $this->brandNewColumns($brand->id), 'connect must not touch the new brand columns when flags are off');

        $body = $response->getContent();
        foreach (self::INTENDED_CHANGES as $head => $base) {
            $body = str_replace(json_encode($head, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json_encode($base, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $body);
        }

        return [
            'status' => $response->getStatusCode(),
            'content_type' => $response->headers->get('Content-Type'),
            'body' => $body,
            'writes' => $this->writes,
            'logs' => $this->logs,
            'http' => $sent,
            'db' => [
                'brands' => $this->rows('brands'),
                'vendor_settings' => $this->rows('vendor_settings', ['id', 'brand_id', 'api_platform', 'status', 'approval_status']),
                'scraping_configs' => $this->rows('scraping_configs'),
            ],
        ];
    }

    private function refreshTables(): void
    {
        DB::flushQueryLog();
        app('events')->forget('Illuminate\Database\Events\QueryExecuted');
        app('events')->forget(MessageLogged::class);
        Http::swap(new \Illuminate\Http\Client\Factory(app("events")));
        foreach (['scraping_configs', 'vendor_settings', 'brands'] as $t) {
            DB::table($t)->delete();
        }
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('scraping_configs','vendor_settings','brands')");
        }
    }

    private function brandNewColumns(int $id): array
    {
        $row = (array) DB::table('brands')->where('id', $id)->first();

        return array_intersect_key($row, array_flip(self::NEW_BRAND_COLUMNS));
    }

    private function rows(string $table, ?array $cols = null): array
    {
        return DB::table($table)->orderBy('id')->get()->map(function ($r) use ($cols) {
            $r = (array) $r;
            $r = array_diff_key($r, array_flip(self::NEW_BRAND_COLUMNS));
            if ($cols) {
                $r = array_intersect_key($r, array_flip($cols));
            }
            ksort($r);

            return $this->maskDeep($r);
        })->all();
    }

    private function maskDeep($value)
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->maskDeep($v), $value);
        }

        return $this->mask($value);
    }

    private function mask($v)
    {
        if ($v instanceof \DateTimeInterface) {
            return '<ts>';
        }
        if (is_string($v)) {
            if ($v === self::TOKEN) {
                return '<token>';
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', $v)) {
                return '<ts>';
            }
            if (str_starts_with($v, 'eyJpdiI6')) {
                $plain = decrypt($v, false);

                return '<enc:'.$plain.'>';
            }
        }

        return $v;
    }
}
