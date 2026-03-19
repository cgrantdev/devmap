<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'is_active',
        'rating_average',
        'rating_count',
        'shipping_time',
        'customer_service',
        'quality',
        'cost',
        'packaging',
    ];

    protected $casts = [
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $baseSlug = Str::slug($brand->name);
                $slug = $baseSlug;
                $counter = 1;
                
                // Ensure unique slug
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $brand->slug = $slug;
            }
        });

        static::updating(function ($brand) {
            if ($brand->isDirty('name') && empty($brand->slug)) {
                $baseSlug = Str::slug($brand->name);
                $slug = $baseSlug;
                $counter = 1;
                
                // Ensure unique slug
                while (static::where('slug', $slug)->where('id', '!=', $brand->id)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }
                
                $brand->slug = $slug;
            }
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendorSetting()
    {
        return $this->hasOne(VendorSetting::class);
    }

    public function reviews()
    {
        return $this->hasMany(VendorReview::class);
    }

    public function approvedReviews()
    {
        $query = $this->hasMany(VendorReview::class);

        // Prefer the newer schema which uses `status`.
        if (Schema::hasColumn('vendor_reviews', 'status')) {
            return $query->where('status', 'approved');
        }

        // Fallback for older data/schema.
        return $query->where('is_approved', true);
    }
}
