<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapingConfig extends Model
{
    protected $fillable = [
        'vendor_id',
        'vendor_name',
        'products_url',
        'product_category_id',
        'enabled',
        'frequency',
        'last_run_at',
        'next_run_at',
        'success_count',
        'error_count',
        'last_error',
        'selectors',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'success_count' => 'integer',
        'error_count' => 'integer',
        'selectors' => 'array',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'vendor_id');
    }

    public function scrapedProducts(): HasMany
    {
        return $this->hasMany(ScrapedProduct::class);
    }

    public function calculateNextRunAt(): void
    {
        $now = now();
        switch ($this->frequency) {
            case 'hourly':
                $this->next_run_at = $now->addHour();
                break;
            case 'daily':
                $this->next_run_at = $now->addDay();
                break;
            case 'weekly':
                $this->next_run_at = $now->addWeek();
                break;
        }
    }
}
