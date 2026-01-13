<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedProduct extends Model
{
    protected $fillable = [
        'scraping_config_id',
        'name',
        'price',
        'dosage',
        'image_url',
        'source_url',
        'stock_status',
        'raw_data',
        'last_scraped_at',
        'manual_override',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'raw_data' => 'array',
        'last_scraped_at' => 'datetime',
        'manual_override' => 'boolean',
    ];

    /**
     * Get the scraping config that this product belongs to
     */
    public function scrapingConfig(): BelongsTo
    {
        return $this->belongsTo(ScrapingConfig::class);
    }

    /**
     * Get the vendor name from the scraping config
     */
    public function getVendorNameAttribute()
    {
        return $this->scrapingConfig?->vendor_name ?? $this->scrapingConfig?->vendor?->name ?? 'Unknown';
    }

    /**
     * Get the decoded product name (HTML entities decoded)
     */
    public function getNameAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
