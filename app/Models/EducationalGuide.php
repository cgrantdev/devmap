<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EducationalGuide extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'tag',
        'reading_time',
        'description',
        'peptides',
        'guide_url',
        'published_at',
        'is_featured',
        'status',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_featured' => 'boolean',
        'peptides' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($guide) {
            if (empty($guide->slug)) {
                $guide->slug = Str::slug($guide->title);
            }
        });
    }
}
