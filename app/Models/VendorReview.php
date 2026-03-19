<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'user_id',
        'user_name',
        'user_email',
        'rating',
        'review',
        'is_approved',
        'status',
        'verified',
        'shipping_time',
        'customer_service',
        'quality',
        'cost',
        'packaging',
        'vendor_reply',
        'vendor_replied_at',
        'flagged',
        'flag_reason',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'verified' => 'boolean',
        'flagged' => 'boolean',
        'vendor_replied_at' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update brand rating when review is created/updated/deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($review) {
            if ($review->is_approved) {
                static::updateBrandRating($review->brand_id);
            }
        });

        static::deleted(function ($review) {
            static::updateBrandRating($review->brand_id);
        });
    }

    /**
     * Recalculate and update brand's rating average and count
     */
    public static function updateBrandRating($brandId)
    {
        $brand = Brand::find($brandId);
        if (!$brand) {
            return;
        }

        $approvedReviews = static::where('brand_id', $brandId)
            ->where('is_approved', true)
            ->get();

        $ratingCount = $approvedReviews->count();
        $ratingAverage = $ratingCount > 0 
            ? $approvedReviews->avg('rating') 
            : 0;

        // Calculate averages for each grading category
        $shippingTime = $ratingCount > 0 && $approvedReviews->whereNotNull('shipping_time')->count() > 0
            ? round($approvedReviews->whereNotNull('shipping_time')->avg('shipping_time'), 1)
            : 0;
        $customerService = $ratingCount > 0 && $approvedReviews->whereNotNull('customer_service')->count() > 0
            ? round($approvedReviews->whereNotNull('customer_service')->avg('customer_service'), 1)
            : 0;
        $quality = $ratingCount > 0 && $approvedReviews->whereNotNull('quality')->count() > 0
            ? round($approvedReviews->whereNotNull('quality')->avg('quality'), 1)
            : 0;
        $cost = $ratingCount > 0 && $approvedReviews->whereNotNull('cost')->count() > 0
            ? round($approvedReviews->whereNotNull('cost')->avg('cost'), 1)
            : 0;
        $packaging = $ratingCount > 0 && $approvedReviews->whereNotNull('packaging')->count() > 0
            ? round($approvedReviews->whereNotNull('packaging')->avg('packaging'), 1)
            : 0;

        $brand->update([
            'rating_average' => round($ratingAverage, 2),
            'rating_count' => $ratingCount,
            'shipping_time' => $shippingTime,
            'customer_service' => $customerService,
            'quality' => $quality,
            'cost' => $cost,
            'packaging' => $packaging,
        ]);
    }
}
