<?php

namespace Tests\Feature;

use App\Jobs\RunWooCommerceIngestJob;
use App\Models\Brand;
use App\Models\ScrapingConfig;
use App\Services\IngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Southern Aminos revoked our API key on 23 Nov 2026 over about 1,300
 * requests an hour. A vendor is polled at most once per scheduled run.
 */
class VendorPollingRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_due_config_is_rescheduled_before_dispatch_so_the_next_minute_does_not_dispatch_it_again(): void
    {
        Queue::fake();
        $config = $this->makeConfig(['frequency' => 'hourly', 'next_run_at' => now()->subMinute()]);

        Artisan::call('scraping:run-scheduled');
        Queue::assertPushed(RunWooCommerceIngestJob::class, 1);
        $this->assertTrue($config->fresh()->next_run_at->greaterThan(now()->addMinutes(59)));

        // The job has not run yet (still queued). The next ticks must not
        // dispatch it again.
        Artisan::call('scraping:run-scheduled');
        Artisan::call('scraping:run-scheduled');
        Queue::assertPushed(RunWooCommerceIngestJob::class, 1);
    }

    public function test_a_second_dispatch_for_the_same_config_is_dropped_while_the_first_is_queued(): void
    {
        Queue::fake();
        $config = $this->makeConfig();

        RunWooCommerceIngestJob::dispatch($config);
        RunWooCommerceIngestJob::dispatch($config);

        Queue::assertPushed(RunWooCommerceIngestJob::class, 1);
    }

    public function test_ingest_jobs_get_one_attempt(): void
    {
        $job = new RunWooCommerceIngestJob($this->makeConfig());

        $this->assertSame(1, $job->tries);
        $this->assertSame('scraping-config-'.$job->config->id, $job->uniqueId());
    }

    public function test_an_unknown_frequency_never_leaves_the_config_due(): void
    {
        $config = new ScrapingConfig(['frequency' => 'not-a-frequency']);
        $config->calculateNextRunAt();

        $this->assertNotNull($config->next_run_at);
        $this->assertTrue($config->next_run_at->greaterThan(now()->addMinutes(59)));
    }

    public function test_a_revoked_key_waits_a_day_before_the_next_try(): void
    {
        $config = $this->makeConfig(['frequency' => 'hourly']);
        Http::fake([
            'acme.example.com/wp-json/wc/v3/products*' => Http::response(['code' => 'woocommerce_rest_cannot_view'], 401),
        ]);

        (new RunWooCommerceIngestJob($config))->handle(app(IngestionService::class));

        $fresh = $config->fresh();
        $this->assertStringContainsString('Authentication failed', $fresh->last_error);
        $this->assertTrue($fresh->next_run_at->greaterThan(now()->addHours(23)));
    }

    public function test_a_refused_variation_call_stops_the_run(): void
    {
        $config = $this->makeConfig(['frequency' => 'hourly']);
        $variable = fn (int $id) => ['id' => $id, 'name' => 'P'.$id, 'type' => 'variable', 'variations' => [$id * 10], 'permalink' => 'https://acme.example.com/p/'.$id];
        Http::fake([
            'acme.example.com/wp-json/wc/v3/products/*/variations*' => Http::response(['code' => 'rate_limited'], 429),
            'acme.example.com/wp-json/wc/v3/products*' => Http::response([$variable(1), $variable(2), $variable(3)], 200, ['X-WP-TotalPages' => '1']),
        ]);

        (new RunWooCommerceIngestJob($config))->handle(app(IngestionService::class));

        // One products page and one variation call, not one per product.
        Http::assertSentCount(2);
        $fresh = $config->fresh();
        $this->assertStringContainsString('Rate limited (429)', $fresh->last_error);
        $this->assertTrue($fresh->next_run_at->greaterThan(now()->addHours(5)));
    }

    private function makeConfig(array $overrides = []): ScrapingConfig
    {
        $brand = Brand::create(['name' => 'Acme Peptides', 'slug' => 'acme-peptides', 'is_active' => true]);

        return ScrapingConfig::create(array_merge([
            'vendor_id' => $brand->id,
            'vendor_name' => $brand->name,
            'type' => ScrapingConfig::TYPE_WOO_API,
            'products_url' => 'https://acme.example.com',
            'store_url' => 'https://acme.example.com',
            'auth_credentials' => ['consumer_key' => 'ck_test', 'consumer_secret' => 'cs_test'],
            'frequency' => 'daily',
            'enabled' => true,
            'auto_promote' => false,
        ], $overrides));
    }
}
