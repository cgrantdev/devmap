<?php

namespace App\Models;

use App\Models\Scopes\DemoScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'is_active',
        'is_demo',
        'rating_average',
        'rating_count',
        'shipping_time',
        'customer_service',
        'quality',
        'cost',
        'packaging',
        'affiliate_url_template',
        'affiliate_tag',
        'connection_token',
    ];

    public function clicks()
    {
        return $this->hasMany(ProductClick::class);
    }

    protected $casts = [
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'is_demo' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new DemoScope());

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

            // Auto-generate a per-brand connection token for the WP plugin
            if (empty($brand->connection_token)) {
                do {
                    $token = 'pmap_' . Str::random(40);
                } while (static::where('connection_token', $token)->exists());
                $brand->connection_token = $token;
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
        return $this->hasMany(VendorReview::class)
            ->where('status', 'approved');
    }

    public function promotions()
    {
        return $this->hasMany(VendorPromotion::class);
    }

    public function livePromotions()
    {
        return $this->hasMany(VendorPromotion::class)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }
}
