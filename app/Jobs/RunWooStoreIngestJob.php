<?php

namespace App\Jobs;

use App\Jobs\Concerns\OnePerScrapingConfig;
use App\Models\ScrapedProduct;
use App\Models\ScrapingConfig;
use App\Services\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

/**
 * Ingest a WooCommerce store through its public Store API, which the
 * storefront itself uses and which needs no API keys:
 *
 *   GET {store}/wp-json/wc/store/v1/products?type=simple&per_page=100
 *   GET {store}/wp-json/wc/store/v1/products?type=variation&per_page=100
 *
 * Each simple product and each variation is one listing with its own
 * price and permalink (variations carry "?attribute_size=10mg"), so a
 * whole catalog takes a few requests. Built for Woo vendors who would not
 * issue REST keys (NextSelf Labs, 25 Nov 2026).
 *
 * Config fields used:
 *   - store_url (or the products_url origin)
 *   - selectors.max_pages  (optional, default 20 per type)
 *
 * Pages are fetched half a second apart. A 401, 403 or 429 stops the run
 * and pushes the next one out (a day, or six hours for a rate limit).
 */
class RunWooStoreIngestJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, OnePerScrapingConfig, Queueable, SerializesModels;

    // One attempt per scheduled run: the schedule is the retry.
    public int $tries = 1;
    public int $timeout = 600;

    private const USER_AGENT = 'Mozilla/5.0 (compatible; PeptideMap/1.0; +https://peptidemap.com/bot)';

    private const PER_PAGE = 100;

    private const PAUSE_MICROSECONDS = 500_000;

    public function __construct(public ScrapingConfig $config) {}

    public function handle(IngestionService $ingestion): void
    {
        $base = rtrim((string) ($this->config->store_url ?: $this->originOf((string) $this->config->products_url)), '/');
        if ($base === '' || !str_starts_with($base, 'http')) {
            $this->recordFailure('Missing store_url');
            return;
        }

        $maxPages = max(1, (int) (($this->config->selectors ?? [])['max_pages'] ?? 20));
        $staged = 0;
        $requests = 0;

        try {
            foreach (['simple', 'variation'] as $type) {
                for ($page = 1; $page <= $maxPages; $page++) {
                    if ($requests++ > 0) {
                        Sleep::usleep(self::PAUSE_MICROSECONDS);
                    }

                    $resp = Http::timeout(30)
                        ->withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'])
                        ->get($base.'/wp-json/wc/store/v1/products', [
                            'type' => $type,
                            'per_page' => self::PER_PAGE,
                            'page' => $page,
                        ]);

                    if (in_array($resp->status(), [401, 403, 429], true)) {
                        $this->refused($resp->status(), $type.' page '.$page);
                        return;
                    }
                    // WordPress answers 400 for a page past the end.
                    if ($resp->status() === 400 && $page > 1) {
                        break;
                    }
                    if (!$resp->successful()) {
                        $this->recordFailure('HTTP '.$resp->status().' from the Store API ('.$type.' page '.$page.')');
                        return;
                    }

                    $items = $resp->json();
                    if (!is_array($items) || !array_is_list($items)) {
                        $this->recordFailure('Store API did not return a product list ('.$type.' page '.$page.')');
                        return;
                    }
                    if ($items === []) {
                        break;
                    }

                    foreach ($items as $item) {
                        if (is_array($item) && $this->stage($ingestion, $item)) {
                            $staged++;
                        }
                    }

                    $totalPages = (int) ($resp->header('X-WP-TotalPages') ?: 0);
                    if (($totalPages > 0 && $page >= $totalPages) || count($items) < self::PER_PAGE) {
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->recordFailure('Unexpected error: '.$e->getMessage());
            return;
        }

        Log::info('Woo Store API ingest complete', ['config_id' => $this->config->id, 'staged_count' => $staged]);

        $this->config->last_run_at = now();
        $this->config->success_count++;
        $this->config->last_error = null;
        $this->config->calculateNextRunAt();
        $this->config->save();
    }

    /**
     * One staged listing per simple product or variation: the parent's name
     * plus the variation's option values ("NS-3RT 30mg").
     */
    private function stage(IngestionService $ingestion, array $item): bool
    {
        $name = html_entity_decode(trim((string) ($item['name'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($name === '' || empty($item['id'])) {
            return false;
        }

        $options = $this->optionValues($item);
        $label = $name;
        foreach ($options as $option) {
            if (stripos($label, $option) === false) {
                $label .= ' '.$option;
            }
        }

        $prices = is_array($item['prices'] ?? null) ? $item['prices'] : [];
        $unit = 10 ** max(0, (int) ($prices['currency_minor_unit'] ?? 2));
        $regular = $this->amount($prices['regular_price'] ?? null, $unit);
        $current = $this->amount($prices['price'] ?? null, $unit);
        $sale = $this->amount($prices['sale_price'] ?? null, $unit);
        $price = $regular ?? $current;
        if ($price === null) {
            return false;
        }
        $discount = $sale !== null && $sale < $price ? $sale : null;

        $size = null;
        foreach ($options as $option) {
            $size ??= RunJsonLdIngestJob::sizeFromName($option);
        }
        $size ??= RunJsonLdIngestJob::sizeFromName($name);

        $description = trim(strip_tags(html_entity_decode((string) ($item['short_description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return (bool) $ingestion->upsertStaged($this->config, [
            'source_type' => ScrapedProduct::SOURCE_WOO_STORE,
            'external_id' => 'wcs-'.$item['id'],
            'source_url' => is_string($item['permalink'] ?? null) ? $item['permalink'] : null,
            'name' => $label,
            'description' => $description !== '' ? $description : null,
            'price' => $price,
            'discount_price' => $discount,
            'image_url' => is_string($item['images'][0]['src'] ?? null) ? $item['images'][0]['src'] : null,
            'stock_status' => ($item['is_in_stock'] ?? true) ? 'in_stock' : 'out_of_stock',
            'dosage' => $size,
            'raw_data' => [
                'id' => $item['id'],
                'parent' => $item['parent'] ?? null,
                'currency' => $prices['currency_code'] ?? null,
            ],
        ]);
    }

    /**
     * Option values of a variation: "Vial Size: 30mg, Pack: 3" gives
     * ["30mg", "3"]. Falls back to the attributes list.
     *
     * @return list<string>
     */
    private function optionValues(array $item): array
    {
        $values = [];
        if (is_string($item['variation'] ?? null) && trim($item['variation']) !== '') {
            foreach (explode(',', $item['variation']) as $pair) {
                $value = trim(str_contains($pair, ':') ? substr($pair, strpos($pair, ':') + 1) : $pair);
                if ($value !== '') {
                    $values[] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        } elseif (is_array($item['attributes'] ?? null)) {
            foreach ($item['attributes'] as $attribute) {
                $value = is_array($attribute) ? trim((string) ($attribute['value'] ?? '')) : '';
                if ($value !== '') {
                    $values[] = $value;
                }
            }
        }

        return $values;
    }

    /** "2999" with a minor unit of 100 is 29.99. Null for empty or zero. */
    private function amount(mixed $minor, int $unit): ?float
    {
        if (!is_numeric($minor)) {
            return null;
        }
        $value = round(((float) $minor) / $unit, 2);

        return $value > 0 ? $value : null;
    }

    /**
     * The store refused us: record it and wait before trying again. A block
     * waits a day; a rate limit waits six hours.
     */
    private function refused(int $status, string $where): void
    {
        $this->recordFailure(($status === 429 ? 'Rate limited (429)' : 'Refused ('.$status.')').' at '.$where);
        $this->config->deferNextRun($status === 429 ? now()->addHours(6) : now()->addDay());
    }

    private function originOf(string $url): string
    {
        $parts = parse_url($url);

        return isset($parts['host']) ? ($parts['scheme'] ?? 'https').'://'.$parts['host'] : '';
    }

    protected function recordFailure(string $message): void
    {
        Log::error('Woo Store API ingest failed: '.$message, ['config_id' => $this->config->id]);
        $this->config->error_count++;
        $this->config->last_error = $message;
        $this->config->last_run_at = now();
        $this->config->calculateNextRunAt();
        $this->config->save();
    }
}
