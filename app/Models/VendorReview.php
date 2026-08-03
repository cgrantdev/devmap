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
        'flag_reviewed_by',
        'flag_reviewed_at',
        'flag_resolution',
        'flag_resolution_note',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'verified' => 'boolean',
        'flagged' => 'boolean',
        'vendor_replied_at' => 'datetime',
        'flag_reviewed_at' => 'datetime',
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
     * Compute a "verified via PMAP" flag for a batch of reviews without N+1
     * queries: true when the reviewing user has an outbound product_clicks
     * row for the same brand, dated at or before the review and within the
     * preceding 90 days.
     *
     * @param  \Illuminate\Support\Collection|array  $reviews  Models/arrays with id, user_id, brand_id, created_at.
     * @return array<int, bool> keyed by review id
     */
    public static function computeVerifiedMap($reviews): array
    {
        $reviews = collect($reviews);

        $userIds = $reviews->map(fn ($r) => is_array($r) ? ($r['user_id'] ?? null) : $r->user_id)
            ->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return $reviews->mapWithKeys(fn ($r) => [(is_array($r) ? $r['id'] : $r->id) => false])->all();
        }

        $brandIds = $reviews->map(fn ($r) => is_array($r) ? ($r['brand_id'] ?? null) : $r->brand_id)
            ->filter()->unique()->values();

        $clicks = \App\Models\ProductClick::whereIn('user_id', $userIds)
            ->whereIn('brand_id', $brandIds)
            ->get(['user_id', 'brand_id', 'created_at']);

        $clicksByUserBrand = $clicks->groupBy(fn ($c) => $c->user_id . ':' . $c->brand_id);

        return $reviews->mapWithKeys(function ($r) use ($clicksByUserBrand) {
            $id = is_array($r) ? $r['id'] : $r->id;
            $userId = is_array($r) ? ($r['user_id'] ?? null) : $r->user_id;
            $brandId = is_array($r) ? ($r['brand_id'] ?? null) : $r->brand_id;
            $createdAt = is_array($r) ? \Illuminate\Support\Carbon::parse($r['created_at']) : $r->created_at;

            if (!$userId || !$createdAt) {
                return [$id => false];
            }

            $windowStart = (clone $createdAt)->subDays(90);
            $matches = $clicksByUserBrand->get($userId . ':' . $brandId, collect());

            $verified = $matches->contains(
                fn ($c) => $c->created_at <= $createdAt && $c->created_at >= $windowStart
            );

            return [$id => $verified];
        })->all();
    }

    /**
     * Update brand rating when review is created/updated/deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($review) {
            if ($review->status === 'approved') {
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
            ->where('status', 'approved')
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
