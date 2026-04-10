<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image',
        'image_url',
        'link',
        'position',
        'slot',
        'active',
        'brand_id',
        'description',
        'start_date',
        'end_date',
        'impressions',
        'clicks',
    ];

    protected $casts = [
        'active' => 'boolean',
        'position' => 'integer',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the click-through rate as a percentage.
     */
    public function getCtrAttribute(): float
    {
        if ($this->impressions === 0) return 0;
        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    /**
     * Scope: only active banners within their date window.
     */
    public function scopeLive($query)
    {
        return $query->where('active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope: banners for a specific slot.
     */
    public function scopeForSlot($query, string $slot)
    {
        return $query->where('slot', $slot);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function isActive(): bool
    {
        if (!$this->active) {
            return false;
        }

        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }
}
