<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'wishable_type',
        'product_id',
        'category_id',
        'alert_frequency',
        'last_seen_price',
        'last_alerted_at',
    ];

    protected $casts = [
        'last_seen_price' => 'float',
        'last_alerted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
