<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EducationPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'overview',
        'content',
        'image',
        'rating',
        'rating_count',
        'key_effects',
        'accordion_sections',
        'shop_url',
        'published_at',
        'status',
    ];

    protected $casts = [
        'published_at' => 'date',
        'rating' => 'decimal:2',
        'key_effects' => 'array',
        'accordion_sections' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }
}
