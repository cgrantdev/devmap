<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'page_type', 'path', 'route_name', 'brand_id', 'product_id',
        'user_id', 'ip_hash', 'session_id', 'user_agent', 'referrer',
        'is_bot', 'created_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function scopeHumans($q) { return $q->where('is_bot', false); }

    public function brand()   { return $this->belongsTo(Brand::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
