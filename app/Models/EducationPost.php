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
        'education_tag',
        'peptide_full_name',
        'half_life',
        'bioavailability',
        'storage',
        'image',
        'rating',
        'rating_count',
        'key_effects',
        'common_use_cases',
        'how_it_works',
        'typical_dosage',
        'frequency',
        'administration',
        'cycle_duration',
        'possible_side_effects',
        'contraindications',
        'stacking_recommendations',
        'faqs',
        'published_at',
        'status',
        'product_category_id',
    ];

    protected $casts = [
        'published_at' => 'date',
        'rating' => 'decimal:2',
        'key_effects' => 'array',
        'common_use_cases' => 'array',
        'possible_side_effects' => 'array',
        'contraindications' => 'array',
        'stacking_recommendations' => 'array',
        'faqs' => 'array',
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

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
