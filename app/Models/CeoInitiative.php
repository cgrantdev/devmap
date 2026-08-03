<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeoInitiative extends Model
{
    protected $fillable = [
        'category', 'title', 'status', 'owner', 'notes', 'position', 'pinned', 'completed_at',
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'completed_at' => 'datetime',
    ];
}
