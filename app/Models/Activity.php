<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Activity extends Model
{
    protected $fillable = [
        'type',
        'description',
        'entity_name',
        'entity_type',
        'entity_id',
    ];

    /**
     * Log a new activity
     */
    public static function log(string $type, string $description, string $entityName, ?string $entityType = null, ?int $entityId = null): self
    {
        return self::create([
            'type' => $type,
            'description' => $description,
            'entity_name' => $entityName,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }

    /**
     * Get formatted time ago string
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
