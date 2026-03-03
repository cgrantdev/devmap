<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Research extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'peptide',
        'evidence_level',
        'evidence_type',
        'description',
        'source',
        'journal_type',
        'study_type',
        'study_summary',
        'background',
        'key_findings',
        'methodology',
        'clinical_implications',
        'limitations',
        'pubmed_url',
        'tags',
        'published_at',
        'status',
        'seo_page_title',
        'seo_description',
        'seo_og_title',
        'seo_og_description',
        'seo_og_image',
    ];

    protected $casts = [
        'published_at' => 'date',
        'tags' => 'array',
        'key_findings' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($research) {
            // Auto-generate any needed fields if necessary
        });
    }
}
