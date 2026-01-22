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
        'availability',
        'status',
        'hidden',
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
}
