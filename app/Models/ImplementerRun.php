<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImplementerRun extends Model
{
    protected $fillable = [
        'seo_recommendation_id', 'status',
        'started_at', 'finished_at', 'error',
        'branch', 'pr_url', 'pr_number',
        'input_tokens', 'output_tokens', 'cache_read_tokens', 'cache_write_tokens',
        'cost_usd', 'iterations', 'log',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'cost_usd' => 'decimal:4',
    ];

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(SeoRecommendation::class, 'seo_recommendation_id');
    }

    /**
     * Append a line to the log. Keeps size bounded so a runaway agent
     * can't blow the row up beyond MySQL's MEDIUMTEXT limit.
     */
    public function appendLog(string $line): void
    {
        $line = '[' . now()->toIso8601String() . '] ' . rtrim($line) . "\n";
        $current = $this->log ?? '';
        $combined = $current . $line;
        // Cap at 200KB — first 20KB + last 180KB when overflowing.
        if (strlen($combined) > 200_000) {
            $combined = substr($current, 0, 20_000)
                . "\n\n[... log truncated ...]\n\n"
                . substr($current . $line, -180_000);
        }
        $this->log = $combined;
        $this->save();
    }
}
