<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'banner',
        'logo',
        'description',
        'contact_email',
        'phone_number',
        'status',
        'api_route',
        'shop_url'
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
