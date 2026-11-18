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
        // Visible FAQ list, also the FAQPage JSON-LD. Null until the agency
        // saves the post from the editor (2026_11_19 migration).
        'faqs',
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
        'seo_schema',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_featured' => 'boolean',
        'key_points' => 'array',
        'tags' => 'array',
        'seo_schema' => 'array',
        'faqs' => 'array',
    ];

    /** blogs.faqs exists on this row (the 2026_11_19 migration has run). */
    public function hasFaqColumn(): bool
    {
        return array_key_exists('faqs', $this->getAttributes());
    }

    /**
     * FAQ pairs from a FAQPage block in seo_schema, the source catalog
     * posts used before blogs.faqs. The editor starts from these until
     * the post is saved with its own list.
     *
     * @return list<array{question: string, answer: string}>
     */
    public function schemaFaqs(): array
    {
        $blocks = $this->seo_schema;
        if (! is_array($blocks)) {
            return [];
        }
        if (isset($blocks['@type'])) {
            $blocks = [$blocks];
        }

        $pairs = [];
        foreach ($blocks as $block) {
            if (! is_array($block) || ($block['@type'] ?? null) !== 'FAQPage') {
                continue;
            }
            foreach ($block['mainEntity'] ?? [] as $entity) {
                $question = is_array($entity) ? ($entity['name'] ?? null) : null;
                $answer = is_array($entity) ? ($entity['acceptedAnswer']['text'] ?? null) : null;
                if (is_string($question) && $question !== '' && is_string($answer) && $answer !== '') {
                    $pairs[] = ['question' => $question, 'answer' => $answer];
                }
            }
        }

        return $pairs;
    }

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
