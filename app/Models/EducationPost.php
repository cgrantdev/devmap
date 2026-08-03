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
        'show_in_encyclopedia',
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
        'show_in_encyclopedia' => 'boolean',
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

    /**
     * Array-cast fields — anything set here that gets assigned a JSON string
     * (from a seeder or import job that mistakenly wraps in json_encode()) is
     * unwrapped in saving() below so we never store the double-encoded shape
     * again. Front-end iterating a doubly-encoded array walks character by
     * character, breaking the encyclopedia editor UI.
     */
    private const JSON_ARRAY_FIELDS = [
        'key_effects', 'common_use_cases', 'possible_side_effects', 'contraindications',
        'stacking_recommendations', 'faqs', 'tags', 'key_points', 'areas_of_research',
        'mechanism_subsections', 'preclinical_subsections', 'human_use_subsections',
        'regulatory_subsections', 'potential_applications', 'references',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });

        // Guard: if any array-cast field is being saved as a JSON-encoded
        // string (rather than a real PHP array), decode it once so Laravel's
        // 'array' cast can encode it correctly on write.
        static::saving(function ($post) {
            foreach (self::JSON_ARRAY_FIELDS as $field) {
                $value = $post->getAttributes()[$field] ?? null;
                if (is_string($value) && $value !== '' && ($value[0] === '[' || $value[0] === '{')) {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        $post->{$field} = $decoded;
                    }
                }
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
