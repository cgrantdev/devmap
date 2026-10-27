<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'from_path',
        'to_path',
        'status_code',
        'note',
        'created_by_user_id',
    ];

    protected $casts = [
        'status_code' => 'int',
        'hits' => 'int',
        'last_hit_at' => 'datetime',
    ];
}
