<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'brand_id',
        'user_id',
        'ip_hash',
        'user_agent',
        'referrer',
        'destination_url',
        'is_bot',
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeHumans($query)
    {
        return $query->where('is_bot', false);
    }
}
