<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount',
        'expiry_date',
        'active',
        'vendor_id',
        'usage_limit',
        'used_count',
        'minimum_purchase',
    ];

    protected $casts = [
        'discount' => 'integer',
        'active' => 'boolean',
        'expiry_date' => 'date',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'minimum_purchase' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    public function isUsable(): bool
    {
        return $this->active 
            && !$this->isExpired()
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }
}
