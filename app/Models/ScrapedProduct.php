<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedProduct extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_CSS_SCRAPE = 'css_scrape';
    public const SOURCE_WOO_API = 'woo_api';
    public const SOURCE_WP_REST = 'wp_rest';
    public const SOURCE_XML_FEED = 'xml_feed';
    public const SOURCE_PAGE_SCRAPE = 'page_scrape';

    protected $fillable = [
        'scraping_config_id',
        'brand_id',
        'product_id',
        'source_type',
        'external_id',
        'status',
        'name',
        'description',
        'price',
        'discount_price',
        'previous_price',
        'price_changed_at',
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
        'discount_price' => 'decimal:2',
        'previous_price' => 'decimal:2',
        'raw_data' => 'array',
        'last_scraped_at' => 'datetime',
        'price_changed_at' => 'datetime',
        'manual_override' => 'boolean',
    ];

    public function scrapingConfig(): BelongsTo
    {
        return $this->belongsTo(ScrapingConfig::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getVendorNameAttribute()
    {
        return $this->scrapingConfig?->vendor_name ?? $this->brand?->name ?? 'Unknown';
    }

    public function getNameAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}
