<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'brand_id',
        'location_id',
        'verified',
        'rating_average',
        'rating_count',
        'image_url',
        'product_url',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function types()
    {
        return $this->belongsToMany(Type::class, 'product_types');
    }

    public function puses()
    {
        return $this->belongsToMany(Puse::class, 'product_uses');
    }
}
