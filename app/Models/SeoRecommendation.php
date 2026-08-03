<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRecommendation extends Model
{
    protected $fillable = [
        'title', 'category', 'impact', 'effort', 'status',
        'rationale', 'expected_impact', 'source',
        'shipped_by', 'shipped_at', 'commit_hashes',
        'position', 'pinned',
    ];

    protected $casts = [
        'commit_hashes' => 'array',
        'shipped_at' => 'datetime',
        'pinned' => 'boolean',
    ];
}
