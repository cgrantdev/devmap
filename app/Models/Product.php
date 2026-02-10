<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'second_price',
        'brand_id',
        'location_id',
        'product_category_id',
        'size_mg',
        'purity',
        'availability',
        'status',
        'hidden',
        'featured',
        'lab_tested',
        'first_timer_deals',
        'auto_update',
        'verified',
        'rating_average',
        'rating_count',
        'image_url',
        'product_url',
        'auto_scraped',
        'last_scraped_at',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function types()
    {
        return $this->belongsToMany(Type::class, 'product_types');
    }

    public function puses()
    {
        return $this->belongsToMany(Puse::class, 'product_uses');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    protected $casts = [
        'last_scraped_at' => 'datetime',
        'hidden' => 'boolean',
        'featured' => 'boolean',
        'lab_tested' => 'boolean',
        'first_timer_deals' => 'boolean',
        'auto_update' => 'boolean',
    ];

    public function scopeVisible($query)
    {
        return $query->where('hidden', false);
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

    /**
     * Get the original price (before discount)
     * When discount_price exists, price is the original and discount_price is the sale price
     */
    public function getOriginalPriceAttribute()
    {
        if ($this->discount_price && $this->discount_price < $this->price) {
            return $this->price; // price is original, discount_price is sale
        }
        return null;
    }
}
