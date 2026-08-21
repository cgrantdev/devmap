<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalReview extends Model
{
    protected $fillable = [
        'brand_id', 'source', 'source_review_id', 'author', 'author_location',
        'rating', 'title', 'body', 'source_url', 'published_at', 'imported_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'imported_at' => 'datetime',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
}
