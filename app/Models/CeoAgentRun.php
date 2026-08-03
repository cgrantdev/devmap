<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeoAgentRun extends Model
{
    protected $fillable = [
        'agent_name', 'status', 'title', 'summary', 'next_steps', 'commit_hashes', 'links',
    ];

    protected $casts = [
        'commit_hashes' => 'array',
        'links' => 'array',
    ];
}
