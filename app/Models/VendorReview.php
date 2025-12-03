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
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
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

        $brand->update([
            'rating_average' => round($ratingAverage, 2),
            'rating_count' => $ratingCount,
        ]);
    }
}
