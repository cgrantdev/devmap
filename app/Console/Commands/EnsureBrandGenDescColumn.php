<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Companion schema command — adds vendor_settings.generated_description
 * + generated_description_at so the AI pipeline can give each vendor
 * a genuinely-unique "about" paragraph without touching their
 * manually-authored description.
 *
 * Same dedup logic as products: /brand/{slug} pages all share
 * structural shape and some pulled stats, so vendors with similar
 * templated bios risk the same near-duplicate pruning Google hit
 * product pages with. Giving each brand a distinct generated
 * paragraph differentiates them.
 *
 * Idempotent.
 */
class EnsureBrandGenDescColumn extends Command
{
    protected $signature = 'brands:ensure-generated-desc-column';
    protected $description = 'Add vendor_settings.generated_description columns (bypasses migrate chain).';

    public function handle(): int
    {
        $added = [];

        if (!Schema::hasColumn('vendor_settings', 'generated_description')) {
            Schema::table('vendor_settings', function ($t) {
                $t->longText('generated_description')->nullable()->after('description');
            });
            $added[] = 'generated_description';
        }

        if (!Schema::hasColumn('vendor_settings', 'generated_description_at')) {
            Schema::table('vendor_settings', function ($t) {
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
