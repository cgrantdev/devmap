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
        'guide_type',
        'tag',
        'reading_time',
        'description',
        'outline',
        'introduction',
        'overview_benefits',
        'usage_guidelines_important_note',
        'dosage_recommendation',
        'administration_methods',
        'timing_frequency',
        'safety_first',
        'common_side_effects',
        'contraindications',
        'storage_handling',
        'dos',
        'donts',
        'conclusion',
        'peptides',
        'guide_url',
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
        'peptides' => 'array',
        'overview_benefits' => 'array',
        'dosage_recommendation' => 'array',
        'administration_methods' => 'array',
        'common_side_effects' => 'array',
        'contraindications' => 'array',
        'storage_handling' => 'array',
        'dos' => 'array',
        'donts' => 'array',
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
