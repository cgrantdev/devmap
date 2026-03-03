<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'blog_type',
        'author_name',
        'author_job',
        'outline',
        'slug',
        'description',
        'introduction',
        'key_points',
        'detailed_analysis',
        'conclusion',
        'tags',
        'content',
        'image',
        'read_time',
        'published_at',
        'is_featured',
        'status',
        'seo_page_title',
        'seo_description',
        'seo_og_title',
        'seo_og_description',
        'seo_og_image',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_featured' => 'boolean',
        'key_points' => 'array',
        'tags' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->title);
            }
        });
    }
}
