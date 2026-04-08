<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapingConfig extends Model
{
    public const TYPE_CSS_SCRAPE = 'css_scrape';
    public const TYPE_WOO_API = 'woo_api';
    public const TYPE_WP_REST = 'wp_rest';
    public const TYPE_XML_FEED = 'xml_feed';

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
        }
    }
}
