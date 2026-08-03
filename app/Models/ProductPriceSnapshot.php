<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPriceSnapshot extends Model
{
    // snapshot_at is the only timestamp — Laravel's created_at/updated_at
    // pair is redundant here.
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'price',
        'discount_price',
        'snapshot_at',
    ];

    protected $casts = [
        'price' => 'float',
        'discount_price' => 'float',
        'snapshot_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
