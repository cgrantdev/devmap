<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'location_id',
        'banner',
        'logo',
        'description',
        'contact_email',
        'phone_number',
        'status',
        'api_route',
        'shop_url',
        'website',
        'founded_year',
        'coupon_code',
        'shipping_info',
        'return_policy',
        'business_hours',
        'banner_image_url',
        'top_vendor',
        'featured',
        'is_partner',
    ];

    protected $casts = [
        'founded_year' => 'integer',
        'top_vendor' => 'boolean',
        'featured' => 'boolean',
        'is_partner' => 'boolean',
        'status' => 'integer',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
