<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-preset SEO override for /calculator/{slug} pages. Preset list
 * is static (App\Support\CalculatorPresets); this model only holds
 * the title and meta description the agency has customised.
 */
class CalculatorPresetSeo extends Model
{
    protected $table = 'calculator_preset_seo';

    protected $fillable = ['slug', 'title', 'description'];

    /**
     * Keyed by slug for fast lookup during route render.
     *
     * @return array<string, array{title: ?string, description: ?string}>
     */
    public static function overrideMap(): array
    {
        return self::query()
            ->select('slug', 'title', 'description')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->slug => [
                'title' => $row->title,
                'description' => $row->description,
            ]])
            ->all();
    }
}
