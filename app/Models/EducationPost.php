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
        'research_title',
        'research_outline',
        'research_url',
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
        // New encyclopedia entry fields
        'tags',
        'molecular_formula',
        'molecular_weight',
        'cas_registry_number',
        'amino_acid_sequence',
        'amino_acid_net_charge',
        'amino_acid_hydrophobic',
        'amino_acid_stability',
        'amino_acid_solubility',
        'key_points',
        'overview',
        'areas_of_research',
        'background',
        'mechanism_of_action_intro',
        'mechanism_subsections',
        'preclinical_intro',
        'preclinical_subsections',
        'preclinical_disclaimer',
        'human_use_intro',
        'human_use_subsections',
        'regulatory_subsections',
        'regulatory_important_note',
        'potential_applications_intro',
        'potential_applications',
        'potential_applications_important_context',
        'conclusion',
        'references',
        'areas_of_research_intro',
        // SEO fields
        'seo_page_title',
        'seo_description',
        'seo_og_title',
        'seo_og_description',
        'seo_og_image',
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
        // New encyclopedia entry casts
        'tags' => 'array',
        'key_points' => 'array',
        'areas_of_research' => 'array',
        'mechanism_subsections' => 'array',
        'preclinical_subsections' => 'array',
        'human_use_subsections' => 'array',
        'regulatory_subsections' => 'array',
        'potential_applications' => 'array',
        'references' => 'array',
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
