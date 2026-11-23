<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
            $line = $label.': skipped slug collision ('.$stored.').';
            Log::warning($line);
            $command?->warn($line);
            self::emit('stderr', $line);

            return new self(null, false, true);
        }

        if ($rows->isEmpty()) {
            return new self(null, true, false);
        }

        return new self($rows->first(), false, false);
    }

    private static function emit(string $stream, string $line): void
    {
        $handle = match ($stream) {
            'stderr' => defined('STDERR') ? STDERR : null,
            default => defined('STDOUT') ? STDOUT : null,
        };
        if (is_resource($handle)) {
            @fwrite($handle, $line.PHP_EOL);
        }
    }
}
