<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Console\Command;

/**
 * Case-insensitive encyclopedia category lookup.
 *
 * Exactly one LOWER(slug) row is eligible. A missing category or more
 * than one row is skipped. This lookup never creates a category.
 */
final class EncyclopediaCategoryMatch
{
    public function __construct(
        public readonly ?ProductCategory $category,
        public readonly bool $missingCategory,
        public readonly bool $skippedSlugCollision,
    ) {}

    public static function find(string $slug, ?Command $command, string $label): self
    {
        $rows = ProductCategory::query()
            ->whereRaw('LOWER(slug) = ?', [mb_strtolower($slug)])
            ->orderBy('id')
            ->get();

        if ($rows->count() > 1) {
            $stored = $rows->map(fn (ProductCategory $category) => $category->slug)->implode(', ');
            $command?->warn($label.': skipped slug collision ('.$stored.').');

            return new self(null, false, true);
        }

        if ($rows->isEmpty()) {
            return new self(null, true, false);
        }

        return new self($rows->first(), false, false);
    }
}
