<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Companion to EnsureVendorDescColumn. Adds products.generated_description
 * for the AI gap-filler (Colin 10/20) so products the scraper couldn't
 * yield from the vendor page get a truly-unique description via Claude.
 *
 * Idempotent.
 */
class EnsureGeneratedDescColumn extends Command
{
    protected $signature = 'products:ensure-generated-desc-column';
    protected $description = 'Add products.generated_description + generated_at (bypasses migrate chain).';

    public function handle(): int
    {
        $added = [];

        if (!Schema::hasColumn('products', 'generated_description')) {
            Schema::table('products', function ($t) {
                $t->longText('generated_description')->nullable()->after('vendor_description');
            });
            $added[] = 'generated_description';
        }

        if (!Schema::hasColumn('products', 'generated_description_at')) {
            Schema::table('products', function ($t) {
                $t->timestamp('generated_description_at')->nullable();
            });
            $added[] = 'generated_description_at';
        }

        if (empty($added)) {
            $this->info('Columns already present — nothing to do.');
            return self::SUCCESS;
        }

        $this->info('Added: ' . implode(', ', $added));
        return self::SUCCESS;
    }
}
