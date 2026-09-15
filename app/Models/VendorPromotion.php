<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One marketing promotion on a vendor's storefront. Four shapes
 * (see migration): nocode_sitewide, coupon_sitewide, bogo, category.
 * All stack with the affiliate coupon by default so Julia's promo
 * flow never conflicts with our referral tracking.
 */
class VendorPromotion extends Model
{
    public const TYPE_NOCODE_SITEWIDE = 'nocode_sitewide';
    public const TYPE_COUPON_SITEWIDE = 'coupon_sitewide';
    public const TYPE_BOGO = 'bogo';
    public const TYPE_CATEGORY = 'category';

    public const TYPES = [
        self::TYPE_NOCODE_SITEWIDE => 'Sitewide sale (no code)',
        self::TYPE_COUPON_SITEWIDE => 'Sitewide coupon',
        self::TYPE_BOGO => 'BOGO / bundle',
        self::TYPE_CATEGORY => 'Category discount',
    ];

    protected $fillable = [
        'brand_id',
        'promo_type',
        'title',
        'description',
        'percent',
        'code',
        'product_category_id',
        'terms',
        'stacks_with_affiliate',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'percent' => 'float',
        'stacks_with_affiliate' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Live right now — active flag on, start (if set) passed, end
     * (if set) still in the future.
     */
    public function isLive(): bool
    {
        if (!$this->is_active) return false;
        $now = now();
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        return true;
    }

    public function isScheduled(): bool
    {
        return $this->is_active
            && $this->starts_at
            && $this->starts_at->isFuture();
    }
}
