<?php

namespace App\Console\Commands;

use App\Helpers\ImageHelper;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Walks products whose image_url is hosted off-site (vendor CDN) and
 * mirrors each one into our own /storage/vendor-mirror directory as a
 * WebP. Populates products.mirrored_image_url + mirrored_image_at.
 *
 * Why: vendors (Bioedge, Certified-Pep) 403 cross-origin image requests
 * because of WordPress hotlink protection — cards render blank on
 * peptidemap.com even though the product data is fine. Server-side
 * fetches don't carry a browser Referer so they go through. Storing
 * the WebP on our side also means the catalog keeps working when a
 * vendor rewrites paths, migrates CDNs, or goes down.
 *
 * Idempotent: a product that already has a mirrored_image_url matching
 * the current image_url hash is skipped. If a vendor swaps the source
 * image for a new URL, the sha1 changes and we re-mirror.
 *
 * Scheduler hook: run hourly after ingestion so new scrapes get a
 * mirror within an hour of showing up.
 */
class SyncExternalProductImages extends Command
{
    protected $signature = 'products:sync-external-images {--limit=500} {--brand=} {--force} {--dry-run}';

    protected $description = 'Mirror vendor product images onto our own storage so hotlink-protected hosts do not break the catalog grid.';

    /** Days to wait before retrying a row whose last mirror attempt failed. */
    private const BACKOFF_DAYS = 3;

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $brandSlug = $this->option('brand');
        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry-run');

        $query = Product::query()
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->where(function ($q) {
                // Only external URLs — skip anything we already serve.
                $q->where('image_url', 'like', 'http%')
                  ->where('image_url', 'not like', '%peptidemap.com%')
                  ->where('image_url', 'not like', '/storage/%');
            });

        if (!$force) {
            $query->whereNull('mirrored_image_url');
        }

        if ($brandSlug) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $brandSlug));
        }

        // Backoff: skip rows that failed recently (geo/IP-blocked vendors)
        // and try never-attempted rows first, so blocked rows can't use up
        // the per-run limit. Guarded so the command still runs before the
        // migration that adds the column.
        $hasBackoff = Schema::hasColumn('products', 'mirror_failed_at');
        if ($hasBackoff) {
            if (!$force) {
                $query->where(function ($q) {
                    $q->whereNull('mirror_failed_at')
                      ->orWhere('mirror_failed_at', '<', now()->subDays(self::BACKOFF_DAYS));
                });
            }
            $query->orderByRaw('mirror_failed_at IS NOT NULL')->orderBy('mirror_failed_at');
        }

        $query->orderBy('id')->limit($limit);

        $columns = ['id', 'image_url', 'mirrored_image_url'];
        if ($hasBackoff) {
            $columns[] = 'mirror_failed_at';
        }
        $todo = $query->get($columns);

        $ok = 0;
        $fail = 0;
        $skipped = 0;
        $reasonCounts = [];
        $this->info('Candidates: '.$todo->count());

        foreach ($todo as $p) {
            $source = (string) $p->image_url;
            // Cheap skip: if the sha1 matches what's already stored,
            // there's nothing to do unless --force asked for it.
            if (!$force && $p->mirrored_image_url && str_contains($p->mirrored_image_url, sha1($source))) {
                $skipped++;
                continue;
            }

            $diag = null;
            $mirrored = ImageHelper::fetchAndStoreExternal($source, 'vendor-mirror', 82, 12, $diag);
            if (!$mirrored) {
                $fail++;
                $reason = $diag['reason'] ?? 'unknown';
                $status = $diag['status'] ? ' ('.$diag['status'].')' : '';
                $reasonCounts[$reason] = ($reasonCounts[$reason] ?? 0) + 1;
                $this->line('<fg=red>FAIL</> #'.$p->id.' ['.$reason.$status.'] '.$source);
                if ($hasBackoff && !$dry) {
                    $p->forceFill(['mirror_failed_at' => now()])->save();
                }
                continue;
            }

            if (!$dry) {
                $fill = [
                    'mirrored_image_url' => $mirrored,
                    'mirrored_image_at'  => now(),
                ];
                if ($hasBackoff) {
                    $fill['mirror_failed_at'] = null;
                }
                $p->forceFill($fill)->save();
            }
            $ok++;
            $this->line('<fg=green> OK </> #'.$p->id.' → '.$mirrored);
        }

        $this->newLine();
        $this->info("mirrored: {$ok}  skipped: {$skipped}  failed: {$fail}");
        if ($reasonCounts) {
            arsort($reasonCounts);
            $this->line('Failure breakdown:');
            foreach ($reasonCounts as $r => $n) {
                $this->line("  {$r}: {$n}");
            }
        }
        return self::SUCCESS;
    }
}
