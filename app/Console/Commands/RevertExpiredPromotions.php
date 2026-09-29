<?php

namespace App\Console\Commands;

use App\Models\VendorPromotion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Flip is_active=false on VendorPromotion rows whose ends_at has
 * passed. The isLive() predicate already excludes ended rows from
 * frontend queries, but Julia's admin view showed "live" rows past
 * their end date and Instant Peptides' BOGO stayed listed as active
 * until she removed it manually. Colin/Julia PMAP Sep 30.
 *
 * Twin of RevertExpiredCouponBoosts — same 5-min cadence, same
 * safety guards. Runs from routes/console.php.
 */
class RevertExpiredPromotions extends Command
{
    protected $signature = 'promotions:revert-expired {--dry-run}';
    protected $description = 'Deactivate stackable promotions whose ends_at has passed.';

    public function handle(): int
    {
        $now = now();
        $expired = VendorPromotion::where('is_active', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', $now)
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired promotions.');
            return self::SUCCESS;
        }

        foreach ($expired as $p) {
            $this->line("  → deactivating #{$p->id} {$p->brand?->name}: \"{$p->title}\" (ended {$p->ends_at->diffForHumans()})");
            if ($this->option('dry-run')) continue;
            $p->forceFill(['is_active' => false])->save();
            Log::info('promotions:revert-expired deactivated', ['promo_id' => $p->id, 'brand_id' => $p->brand_id]);
        }

        $this->info("Deactivated {$expired->count()} expired promotion(s).");
        return self::SUCCESS;
    }
}
