<?php

namespace App\Models;

use App\Jobs\RunBigCommerceIngestJob;
use App\Jobs\RunJsonFeedIngestJob;
use App\Jobs\RunJsonLdIngestJob;
use App\Jobs\RunMedusaIngestJob;
use App\Jobs\RunWooCommerceIngestJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapingConfig extends Model
{
    public const TYPE_CSS_SCRAPE = 'css_scrape';
    // "Page Scraper" in the admin vendor form. Its "Update Products" button
    // runs RunPageScraperJob, which refreshes the page of each product we
    // already list and never adds products.
    public const TYPE_PAGE_SCRAPE = 'page_scrape';
    public const TYPE_WOO_API = 'woo_api';
    public const TYPE_WP_REST = 'wp_rest';
    public const TYPE_XML_FEED = 'xml_feed';
    // Newer integration paths for non-WooCommerce vendors.
    // Generic JSON feed = our canonical schema (Mr. Peps style custom builds,
    // or any vendor who publishes a /products.json on their own server).
    // Medusa = the Medusa.js Store API at {store_url}/store/products.
    // BigCommerce = V3 REST at api.bigcommerce.com/stores/{hash}/v3/catalog.
    public const TYPE_JSON_FEED = 'json_feed';
    public const TYPE_MEDUSA_STORE = 'medusa_store';
    public const TYPE_BIGCOMMERCE = 'bigcommerce';
    // Generic JSON-LD scraper — reads Product schema.org markup off any
    // storefront that emits it (Peptiva/Medusa+Next, most Shopify themes,
    // most WooCommerce themes). No API key required, no CSS selectors to
    // maintain — the schema is standard. See RunJsonLdIngestJob for shape.
    public const TYPE_JSON_LD = 'json_ld';

    protected $fillable = [
        'vendor_id',
        'vendor_name',
        'type',
        'products_url',
        'store_url',
        'auth_credentials',
        'auto_promote',
        'product_category_id',
        'enabled',
        'frequency',
        'last_run_at',
        'next_run_at',
        'success_count',
        'error_count',
        'last_error',
        'selectors',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_promote' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'selectors' => 'array',
        'auth_credentials' => 'encrypted:array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'vendor_id');
    }

    public function scrapedProducts(): HasMany
    {
        return $this->hasMany(ScrapedProduct::class);
    }

    /**
     * The importer for a config with its own adapter, or null for the
     * generic scrapers (page_scrape, css_scrape).
     */
    public function dedicatedIngestJob(): ?ShouldQueue
    {
        return match ($this->type) {
            self::TYPE_WOO_API => new RunWooCommerceIngestJob($this),
            self::TYPE_JSON_FEED => new RunJsonFeedIngestJob($this),
            self::TYPE_MEDUSA_STORE => new RunMedusaIngestJob($this),
            self::TYPE_JSON_LD => new RunJsonLdIngestJob($this),
            self::TYPE_BIGCOMMERCE => new RunBigCommerceIngestJob($this),
            default => null,
        };
    }

    public function calculateNextRunAt(): void
    {
        $now = now();
        switch ($this->frequency) {
            case 'hourly':
                $this->next_run_at = $now->copy()->addHour();
                break;
            case 'daily':
                $this->next_run_at = $now->copy()->addDay();
                break;
            case 'weekly':
                $this->next_run_at = $now->copy()->addWeek();
                break;
            default:
                // Never leave next_run_at in the past: the scheduler checks
                // every minute and would dispatch this config every minute.
                $this->next_run_at = $now->copy()->addHour();
        }
    }

    /**
     * Move next_run_at forward before an ingest is dispatched.
     *
     * Jobs also set it when they finish, but a run that is still queued,
     * slow, or killed before it finished left the config "due", and the
     * every-minute scheduler dispatched it again each minute. Southern
     * Aminos saw about 1,300 requests an hour from that (23 Nov 2026).
     *
     * Returns false when another process claimed this run first.
     */
    public function claimScheduledRun(): bool
    {
        $due = $this->getRawOriginal('next_run_at');
        $this->calculateNextRunAt();

        $query = static::query()->whereKey($this->getKey());
        $due === null ? $query->whereNull('next_run_at') : $query->where('next_run_at', $due);
        $claimed = $query->update(['next_run_at' => $this->next_run_at]) === 1;

        $this->syncOriginalAttribute('next_run_at');

        return $claimed;
    }

    /**
     * Push the next run out after the vendor refused us (revoked key,
     * rate limit), so a dead integration is not retried every hour.
     * Never sooner than the regular schedule: a six-hour rate-limit wait
     * must not make a daily config run again early.
     */
    public function deferNextRun(\DateTimeInterface $until): void
    {
        if ($this->next_run_at === null || $this->next_run_at->lessThan($until)) {
            $this->next_run_at = $until;
        }
        $this->save();
    }
}
