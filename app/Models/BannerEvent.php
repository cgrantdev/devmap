<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_type', 'slot', 'banner_key', 'banner_id', 'brand_id',
        'user_id', 'ip_hash', 'session_id', 'user_agent', 'referrer',
        'destination_url', 'page_url', 'is_bot', 'meta', 'created_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function scopeHumans($q) { return $q->where('is_bot', false); }
    public function scopeImpressions($q) { return $q->where('event_type', 'impression'); }
    public function scopeClicks($q) { return $q->where('event_type', 'click'); }

    public function brand() { return $this->belongsTo(Brand::class); }
    public function banner() { return $this->belongsTo(Banner::class); }
}
