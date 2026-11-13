<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Vistara Labs has older hand-entered rows (no "VL-" external_id) that
 * duplicate the rows vistara:sync scrapes from their price library, often
 * with a per-spray dose as the size ("KLOW Nasal Spray 1.4mg" next to
 * "KLOW Nasal Spray 80mg"). The scraped rows carry vial sizes and daily
 * prices but were created without images.
 *
 * For every visible legacy row whose product URL path matches a scraped
 * row: copy its image (and category) onto scraped rows that lack one, then
 * hide the legacy row unless vistara:sync maintains it for its own size.
 * Nothing is deleted; rows are only hidden, and the output lists every id
 * so the change can be undone.
 */
class HideVistaraLegacyDuplicates extends Command
{
    protected $signature = 'vistara:hide-legacy-duplicates {--dry-run}';

    protected $description = 'Hide older Vistara rows duplicated by scraped price-library rows (copies images first).';

    private const BRAND_SLUG = 'vistara-labs';

    public function handle(): int
    {
        $brand = Brand::where('slug', self::BRAND_SLUG)->first();
        if (!$brand) {
            $this->error("Brand '" . self::BRAND_SLUG . "' not found.");
            return self::FAILURE;
        }
        $dry = (bool) $this->option('dry-run');

        $rows = Product::where('brand_id', $brand->id)->get([
            'id', 'name', 'size_mg', 'product_url', 'image_url', 'mirrored_image_url',
            'product_category_id', 'external_id', 'hidden',
        ]);
        $isScraped = fn (Product $p): bool => str_starts_with((string) $p->external_id, 'VL-');
        $scrapedByPath = $rows->filter($isScraped)->groupBy(fn (Product $p) => self::pathKey($p->product_url));
        $legacy = $rows->reject($isScraped)->filter(fn (Product $p) => !$p->hidden);

        $this->info('Legacy visible rows: ' . $legacy->count() . ($dry ? ' (dry run, nothing written)' : ''));

        $hiddenIds = [];
        $kept = 0;
        $imagesCopied = 0;
        foreach ($legacy as $old) {
            $key = self::pathKey($old->product_url);
            $matches = $key !== '' ? $scrapedByPath->get($key, collect()) : collect();
            if ($matches->isEmpty()) {
                $kept++;
                $this->line("KEEP  #{$old->id} {$old->name} [{$old->size_mg}] - no scraped row at this URL");
                continue;
            }

            // Copy images/categories onto scraped siblings first; that's
            // useful even when the legacy row itself stays visible.
            foreach ($matches as $new) {
                $copy = [];
                if (empty($new->image_url) && !empty($old->image_url)) {
                    $copy['image_url'] = $old->image_url;
                    if (!empty($old->mirrored_image_url)) {
                        $copy['mirrored_image_url'] = $old->mirrored_image_url;
                    }
                }
                if (empty($new->product_category_id) && !empty($old->product_category_id)) {
                    $copy['product_category_id'] = $old->product_category_id;
                }
                if ($copy === []) {
                    continue;
                }
                if (isset($copy['image_url'])) {
                    $imagesCopied++;
                }
                $this->line("      copy to #{$new->id} {$new->name} [{$new->size_mg}]: " . implode(', ', array_keys($copy)));
                if (!$dry) {
                    $new->forceFill($copy)->save();
                }
                // Keep the in-memory row current so a second legacy row for
                // the same URL doesn't copy over it again.
                foreach ($copy as $field => $value) {
                    $new->{$field} = $value;
                }
            }

            // A legacy row whose URL carries a variant query (?attribute_size=)
            // is matched by vistara:sync on that exact URL and kept current
            // for its own size, so no scraped row exists for that size. Only
            // hide it when a scraped row of the same size also exists.
            $sameSize = $matches->first(fn (Product $p) => self::sizeKey($p->size_mg) === self::sizeKey($old->size_mg));
            $hasVariantQuery = str_contains((string) parse_url((string) $old->product_url, PHP_URL_QUERY), 'attribute_');
            if ($hasVariantQuery && !$sameSize) {
                $kept++;
                $this->line("KEEP  #{$old->id} {$old->name} [{$old->size_mg}] - vistara:sync keeps this exact URL current");
                continue;
            }

            $note = $sameSize ? '' : ' (its size is not in the price library)';
            $this->line("HIDE  #{$old->id} {$old->name} [{$old->size_mg}] duplicates #" . $matches->pluck('id')->implode(', #') . $note);
            if (!$dry) {
                $old->forceFill(['hidden' => true])->save();
            }
            $hiddenIds[] = $old->id;
        }

        $this->newLine();
        $this->info('hidden: ' . count($hiddenIds) . "  kept: {$kept}  images copied: {$imagesCopied}");
        if ($hiddenIds !== []) {
            $this->line('Undo: set hidden = false on products ' . implode(', ', $hiddenIds));
        }

        return self::SUCCESS;
    }

    private static function sizeKey(?string $size): string
    {
        return strtolower((string) preg_replace('/\s+/', '', (string) $size));
    }

    /** host + path, lowercased, without query string or trailing slash. */
    private static function pathKey(?string $url): string
    {
        $parts = parse_url((string) $url);
        if (!$parts || empty($parts['host'])) {
            return '';
        }
        $host = strtolower((string) preg_replace('/^www\./i', '', $parts['host']));
        return $host . rtrim(strtolower($parts['path'] ?? '/'), '/');
    }
}
