<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Quick status readout for the AI gap-filler pipeline — shows how
 * many products have vendor_description, generated_description,
 * neither, plus the pending queue depth.
 *
 * Lets Colin check "is it still working?" without SSH or SQL.
 */
class DescriptionProgress extends Command
{
    protected $signature = 'descriptions:progress';
    protected $description = 'Report AI description backfill progress + queue depth.';

    public function handle(): int
    {
        $total = Product::where('hidden', false)->where('status', 'active')->count();
        $withVendor = Product::where('hidden', false)->where('status', 'active')
            ->whereNotNull('vendor_description')->count();
        $withGenerated = Product::where('hidden', false)->where('status', 'active')
            ->whereNotNull('generated_description')->count();
        $withEither = Product::where('hidden', false)->where('status', 'active')
            ->where(fn ($q) => $q->whereNotNull('vendor_description')->orWhereNotNull('generated_description'))
            ->count();
        $boilerplateOnly = $total - $withEither;

        $queued = DB::table('jobs')
            ->where('payload', 'like', '%GenerateProductDescriptionJob%')
            ->count();
        $failed = DB::table('failed_jobs')
            ->where('payload', 'like', '%GenerateProductDescriptionJob%')
            ->count();

        $this->newLine();
        $this->info("Active products: {$total}");
        $this->line("  vendor_description:    {$withVendor}  (" . pct($withVendor, $total) . ")");
        $this->line("  generated_description: {$withGenerated}  (" . pct($withGenerated, $total) . ")");
        $this->line("  at least one unique:   {$withEither}  (" . pct($withEither, $total) . ")");
        $this->line("  still on boilerplate:  {$boilerplateOnly}  (" . pct($boilerplateOnly, $total) . ")");
        $this->newLine();
        $this->info("Queue:");
        $this->line("  pending jobs:          {$queued}");
        $this->line("  failed jobs:           {$failed}");

        return self::SUCCESS;
    }
}

if (!function_exists('pct')) {
    function pct(int $n, int $t): string {
        return $t > 0 ? round($n / $t * 100, 1) . '%' : '0%';
    }
}
