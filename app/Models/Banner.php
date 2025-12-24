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
        'active',
        'brand_id',
        'description',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'active' => 'boolean',
        'position' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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
