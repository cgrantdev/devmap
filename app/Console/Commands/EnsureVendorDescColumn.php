<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * One-shot schema change so Colin doesn't need to run `php artisan
 * migrate` (which is currently blocked by an older encyclopedia
 * seeder duplicate-slug bug) just to unblock the vendor-description
 * backfill — the actual fix for Google's dedup problem.
 *
 * Idempotent: no-ops when the column already exists. Safe to re-run.
 */
class EnsureVendorDescColumn extends Command
{
    protected $signature = 'products:ensure-vendor-desc-column';
    protected $description = 'Add products.vendor_description + fetched_at (bypasses migrate chain).';

    public function handle(): int
    {
        $added = [];

        if (!Schema::hasColumn('products', 'vendor_description')) {
            Schema::table('products', function ($t) {
                $t->longText('vendor_description')->nullable()->after('description');
            });
            $added[] = 'vendor_description';
        }

        if (!Schema::hasColumn('products', 'vendor_description_fetched_at')) {
            Schema::table('products', function ($t) {
                $t->timestamp('vendor_description_fetched_at')->nullable();
            });
            $added[] = 'vendor_description_fetched_at';
        }

        if (empty($added)) {
            $this->info('Columns already present — nothing to do.');
            return self::SUCCESS;
        }

        $this->info('Added: ' . implode(', ', $added));
        return self::SUCCESS;
    }
}
