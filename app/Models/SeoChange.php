<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoChange extends Model
{
    protected $fillable = [
        'happened_at',
        'title',
        'summary',
        'category',
        'actor',
        'commit_sha',
    ];

    protected $casts = [
        'happened_at' => 'date',
    ];

    public const CATEGORIES = [
        'meta', 'interlinks', 'content', 'technical',
        'redirects', 'speed', 'schema', 'agency', 'recovery',
    ];
}
